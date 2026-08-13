<?php
/**
 * api/menus.php
 * JSON API untuk CRUD menu, kategori, options & ingredients mapping
 * Ordio.io
 *
 * GET  ?action=list[&category_id=X]     — daftar menu (+ filter kategori)
 * GET  ?action=get&id=X                 — satu menu + ingredients + options
 * GET  ?action=categories               — daftar kategori
 * GET  ?action=ingredients_list         — daftar bahan baku (untuk dropdown)
 * POST action=save                      — buat/update menu (multipart/form-data)
 * POST action=delete&id=X              — hapus menu
 * POST action=toggle&id=X              — toggle is_active
 * POST action=save_category            — tambah kategori
 * POST action=delete_category&id=X    — hapus kategori
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET'
    ? ($_GET['action']  ?? 'list')
    : ($_POST['action'] ?? '');

function apiOk(array $data = []): never {
    echo json_encode(['ok' => true] + $data);
    exit;
}
function apiFail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// ─── Router ─────────────────────────────────────────────────
match ($action) {
    'list'            => handleList($db),
    'get'             => handleGet($db),
    'categories'      => handleCategories($db),
    'ingredients_list'=> handleIngredientsList($db),
    'save'            => handleSave($db),
    'delete'          => handleDelete($db),
    'toggle'          => handleToggle($db),
    'save_category'   => handleSaveCategory($db),
    'delete_category' => handleDeleteCategory($db),
    default           => apiFail('Unknown action')
};

// ─── Handlers ────────────────────────────────────────────────

function handleList(PDO $db): never {
    $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $search     = trim($_GET['search'] ?? '');

    $sql = "SELECT m.*, mc.name AS category_name
            FROM menus m
            LEFT JOIN menu_categories mc ON mc.id = m.category_id
            WHERE 1=1";
    $params = [];

    if ($categoryId) {
        $sql .= " AND m.category_id = ?";
        $params[] = $categoryId;
    }
    if ($search !== '') {
        $sql .= " AND (m.name LIKE ? OR m.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY mc.name ASC, m.name ASC";

    $menus = $db->prepare($sql);
    $menus->execute($params);
    apiOk(['menus' => $menus->fetchAll()]);
}

function handleGet(PDO $db): never {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');

    $menu = $db->prepare("
        SELECT m.*, mc.name AS category_name
        FROM menus m
        LEFT JOIN menu_categories mc ON mc.id = m.category_id
        WHERE m.id = ?
    ");
    $menu->execute([$id]);
    $menuData = $menu->fetch();
    if (!$menuData) apiFail('Menu tidak ditemukan.', 404);

    // Ingredients
    $ings = $db->prepare("
        SELECT mi.id, mi.ingredient_id, mi.qty_used, i.name AS ing_name, i.unit
        FROM menu_ingredients mi
        JOIN ingredients i ON i.id = mi.ingredient_id
        WHERE mi.menu_id = ?
        ORDER BY i.name
    ");
    $ings->execute([$id]);

    // Options + values
    $opts = $db->prepare("
        SELECT mo.id AS option_id, mo.option_name, mo.is_required,
               mov.id AS value_id, mov.value_name, mov.extra_price
        FROM menu_options mo
        LEFT JOIN menu_option_values mov ON mov.option_id = mo.id
        WHERE mo.menu_id = ?
        ORDER BY mo.id, mov.id
    ");
    $opts->execute([$id]);

    // Group options
    $optionsMap = [];
    foreach ($opts->fetchAll() as $row) {
        $oid = $row['option_id'];
        if (!isset($optionsMap[$oid])) {
            $optionsMap[$oid] = [
                'option_id'   => $oid,
                'option_name' => $row['option_name'],
                'is_required' => $row['is_required'],
                'values'      => [],
            ];
        }
        if ($row['value_id']) {
            $optionsMap[$oid]['values'][] = [
                'value_id'   => $row['value_id'],
                'value_name' => $row['value_name'],
                'extra_price'=> $row['extra_price'],
            ];
        }
    }

    apiOk([
        'menu'        => $menuData,
        'ingredients' => $ings->fetchAll(),
        'options'     => array_values($optionsMap),
    ]);
}

function handleCategories(PDO $db): never {
    $cats = $db->query("SELECT * FROM menu_categories ORDER BY name ASC")->fetchAll();
    apiOk(['categories' => $cats]);
}

function handleIngredientsList(PDO $db): never {
    $ings = $db->query("SELECT id, name, unit FROM ingredients ORDER BY name ASC")->fetchAll();
    apiOk(['ingredients' => $ings]);
}

function handleSave(PDO $db): never {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name']        ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0) ?: null;
    $price       = max(0, (int)($_POST['price'] ?? 0));
    $description = trim($_POST['description']  ?? '');
    $isActive    = (int)($_POST['is_active']   ?? 1);
    $ingJson     = $_POST['ingredients_json']  ?? '[]';
    $optJson     = $_POST['options_json']      ?? '[]';

    if ($name === '') apiFail('Nama menu wajib diisi.');

    $ingredients = json_decode($ingJson, true) ?? [];
    $options     = json_decode($optJson, true) ?? [];

    // ── Image upload ──────────────────────────────────────────
    $imagePath = trim($_POST['existing_image'] ?? '');

    if (!empty($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed, true)) {
            apiFail('Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.');
        }
        if ($_FILES['image']['size'] > 3 * 1024 * 1024) {
            apiFail('Ukuran gambar maksimal 3MB.');
        }

        $uploadDir = __DIR__ . '/../assets/img/menus/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename  = 'menu_' . uniqid('', true) . '.' . $ext;
        $filepath  = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            apiFail('Gagal mengupload gambar.');
        }

        // Hapus gambar lama
        if ($imagePath) {
            $oldFile = __DIR__ . '/../' . $imagePath;
            if (file_exists($oldFile)) @unlink($oldFile);
        }

        $imagePath = 'assets/img/menus/' . $filename;
    }

    // ── DB transaction ────────────────────────────────────────
    $db->beginTransaction();
    try {
        if ($id) {
            $db->prepare("
                UPDATE menus
                SET category_id=?, name=?, price=?, description=?, image_path=?, is_active=?
                WHERE id=?
            ")->execute([$categoryId, $name, $price, $description, $imagePath, $isActive, $id]);
        } else {
            $db->prepare("
                INSERT INTO menus (category_id, name, price, description, image_path, is_active)
                VALUES (?,?,?,?,?,?)
            ")->execute([$categoryId, $name, $price, $description, $imagePath, $isActive]);
            $id = (int)$db->lastInsertId();
        }

        // Sync ingredients (delete + re-insert)
        $db->prepare("DELETE FROM menu_ingredients WHERE menu_id=?")->execute([$id]);
        $stmtIng = $db->prepare("
            INSERT INTO menu_ingredients (menu_id, ingredient_id, qty_used) VALUES (?,?,?)
        ");
        foreach ($ingredients as $ing) {
            $ingId = (int)($ing['ingredient_id'] ?? 0);
            $qty   = (float)($ing['qty'] ?? 0);
            if ($ingId > 0 && $qty > 0) {
                $stmtIng->execute([$id, $ingId, $qty]);
            }
        }

        // Sync options (cascade delete handles values)
        $db->prepare("DELETE FROM menu_options WHERE menu_id=?")->execute([$id]);
        $stmtOpt = $db->prepare("
            INSERT INTO menu_options (menu_id, option_name, is_required) VALUES (?,?,?)
        ");
        $stmtVal = $db->prepare("
            INSERT INTO menu_option_values (option_id, value_name, extra_price) VALUES (?,?,?)
        ");
        foreach ($options as $opt) {
            $optName    = trim($opt['option_name'] ?? '');
            $isRequired = (int)($opt['is_required'] ?? 0);
            if ($optName === '') continue;

            $stmtOpt->execute([$id, $optName, $isRequired]);
            $optionId = (int)$db->lastInsertId();

            foreach ($opt['values'] ?? [] as $val) {
                $valName    = trim($val['value_name'] ?? '');
                $extraPrice = max(0, (int)($val['extra_price'] ?? 0));
                if ($valName === '') continue;
                $stmtVal->execute([$optionId, $valName, $extraPrice]);
            }
        }

        $db->commit();
        apiOk(['id' => $id]);
    } catch (Throwable $e) {
        $db->rollBack();
        error_log('[Ordio API/menus save] ' . $e->getMessage());
        apiFail('Gagal menyimpan: ' . $e->getMessage());
    }
}

function handleDelete(PDO $db): never {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');

    // Ambil image_path untuk dihapus setelah delete
    $stmt = $db->prepare("SELECT image_path FROM menus WHERE id=?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    $db->prepare("DELETE FROM menus WHERE id=?")->execute([$id]);

    if ($data && $data['image_path']) {
        $oldFile = __DIR__ . '/../' . $data['image_path'];
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    apiOk();
}

function handleToggle(PDO $db): never {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');

    $db->prepare("
        UPDATE menus SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?
    ")->execute([$id]);

    $row = $db->prepare("SELECT is_active FROM menus WHERE id=?")->execute([$id]);
    $stmt = $db->prepare("SELECT is_active FROM menus WHERE id=?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    apiOk(['is_active' => (int)($data['is_active'] ?? 0)]);
}

function handleSaveCategory(PDO $db): never {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') apiFail('Nama kategori wajib diisi.');

    try {
        $db->prepare("INSERT INTO menu_categories (name) VALUES (?)")->execute([$name]);
        apiOk(['id' => (int)$db->lastInsertId(), 'name' => $name]);
    } catch (Throwable $e) {
        apiFail('Kategori sudah ada atau gagal disimpan.');
    }
}

function handleDeleteCategory(PDO $db): never {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) apiFail('ID tidak valid.');
    $db->prepare("DELETE FROM menu_categories WHERE id=?")->execute([$id]);
    apiOk();
}
