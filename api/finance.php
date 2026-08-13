<?php
/**
 * api/finance.php
 * API endpoint untuk Keuangan (Input Expense, Get Summary)
 * Ordio.io
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin'); // Hanya admin yang bisa akses keuangan

header('Content-Type: application/json; charset=utf-8');

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $method === 'GET'
    ? ($_GET['action']  ?? '')
    : ($_POST['action'] ?? '');

$userId = $_SESSION['user_id'];

function apiOk(array $data = []): never {
    echo json_encode(['ok' => true] + $data);
    exit;
}
function apiFail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

match ($action) {
    'summary'   => handleSummary($db),
    'list'      => handleListRecords($db),
    'add_expense' => handleAddExpense($db, $userId),
    default     => apiFail('Unknown action')
};

function handleSummary(PDO $db): never {
    $filter = $_GET['filter'] ?? 'today'; // today, week, month
    
    $where = "";
    if ($filter === 'today') {
        $where = "date(created_at) = date('now','localtime')";
    } elseif ($filter === 'week') {
        $where = "date(created_at) >= date('now','localtime', '-7 days')";
    } elseif ($filter === 'month') {
        $where = "strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now','localtime')";
    } else {
        $where = "1=1"; // all time
    }

    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
        FROM financial_records
        WHERE $where
    ");
    $stmt->execute();
    $row = $stmt->fetch();

    $income = (int)($row['total_income'] ?? 0);
    $expense = (int)($row['total_expense'] ?? 0);
    $profit = $income - $expense;

    apiOk([
        'income' => $income,
        'expense' => $expense,
        'profit' => $profit
    ]);
}

function handleListRecords(PDO $db): never {
    $filter = $_GET['filter'] ?? 'today';
    
    $where = "";
    if ($filter === 'today') {
        $where = "date(created_at) = date('now','localtime')";
    } elseif ($filter === 'week') {
        $where = "date(created_at) >= date('now','localtime', '-7 days')";
    } elseif ($filter === 'month') {
        $where = "strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now','localtime')";
    } else {
        $where = "1=1";
    }

    $stmt = $db->prepare("
        SELECT id, type, category, amount, description, created_at
        FROM financial_records
        WHERE $where
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $records = $stmt->fetchAll();

    apiOk(['records' => $records]);
}

function handleAddExpense(PDO $db, int $userId): never {
    $category = trim($_POST['category'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($category) || $amount <= 0) {
        apiFail('Kategori dan nominal (harus > 0) wajib diisi.');
    }

    $stmt = $db->prepare("
        INSERT INTO financial_records (type, category, amount, description, created_by)
        VALUES ('expense', ?, ?, ?, ?)
    ");
    $stmt->execute([$category, $amount, $description, $userId]);

    apiOk(['msg' => 'Pengeluaran berhasil dicatat.']);
}
