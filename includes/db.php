<?php
/**
 * includes/db.php
 * Database connection — SQLite dengan WAL mode
 * Ordio.io — QR Order & Stock Management
 */

define('DB_PATH', __DIR__ . '/../database/ordio.db');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);

            // Pengaturan dasar PDO
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Aktifkan WAL mode untuk concurrency yang lebih baik
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA synchronous = NORMAL');
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA cache_size = -64000'); // 64MB cache
            $pdo->exec('PRAGMA temp_store = MEMORY');

            // Inisialisasi schema jika belum ada
            initSchema($pdo);

        } catch (PDOException $e) {
            // Jangan expose detail error ke user di production
            error_log('[Ordio DB Error] ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }

    return $pdo;
}

function initSchema(PDO $pdo): void {
    // ─── Core users table ───────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            password_hash TEXT    NOT NULL,
            role          TEXT    NOT NULL CHECK(role IN ('admin', 'kasir')),
            full_name     TEXT    NOT NULL DEFAULT '',
            is_active     INTEGER NOT NULL DEFAULT 1,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
        );
        INSERT OR IGNORE INTO users (username, password_hash, role, full_name)
        VALUES (
            'admin',
            '$2y$12$2fdgtuR0H7M3ugKOqYZq9eKCvCoc1Fq3Z.5g/hagLkJ5LSDyrgj52',
            'admin',
            'Administrator'
        );
    ");
    // password hash di atas adalah untuk: Ordio@2026

    // ─── Menu categories ────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_categories (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT    NOT NULL UNIQUE COLLATE NOCASE
        );
    ");

    // ─── Menus ──────────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menus (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER REFERENCES menu_categories(id) ON DELETE SET NULL,
            name        TEXT    NOT NULL,
            price       INTEGER NOT NULL DEFAULT 0,
            description TEXT    NOT NULL DEFAULT '',
            image_path  TEXT    NOT NULL DEFAULT '',
            is_active   INTEGER NOT NULL DEFAULT 1,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
        );
    ");

    // ─── Ingredients (bahan baku) ───────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingredients (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            name                TEXT    NOT NULL,
            unit                TEXT    NOT NULL DEFAULT 'pcs',
            stock_qty           REAL    NOT NULL DEFAULT 0,
            low_stock_threshold REAL    NOT NULL DEFAULT 0,
            updated_at          TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
        );
    ");

    // ─── Menu ↔ Ingredient mapping (resep) ─────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_ingredients (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            menu_id       INTEGER NOT NULL REFERENCES menus(id) ON DELETE CASCADE,
            ingredient_id INTEGER NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
            qty_used      REAL    NOT NULL DEFAULT 0,
            UNIQUE(menu_id, ingredient_id)
        );
    ");

    // ─── Menu options (misal Level Pedas) ───────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_options (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            menu_id     INTEGER NOT NULL REFERENCES menus(id) ON DELETE CASCADE,
            option_name TEXT    NOT NULL,
            is_required INTEGER NOT NULL DEFAULT 0
        );
    ");

    // ─── Option values (misal Level 1, Level 2) ─────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_option_values (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            option_id   INTEGER NOT NULL REFERENCES menu_options(id) ON DELETE CASCADE,
            value_name  TEXT    NOT NULL,
            extra_price INTEGER NOT NULL DEFAULT 0
        );
    ");

    // ─── Tables (meja restoran) ──────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tables (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            table_number TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            note         TEXT    NOT NULL DEFAULT ''
        );
    ");

    // ─── Orders (pesanan pelanggan) ──────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            order_code     TEXT    NOT NULL UNIQUE,
            customer_name  TEXT    NOT NULL,
            table_number   TEXT    NOT NULL,
            status         TEXT    NOT NULL DEFAULT 'menunggu', -- menunggu, diproses, selesai, dibatalkan
            total_price    INTEGER NOT NULL DEFAULT 0,
            payment_status TEXT    NOT NULL DEFAULT 'paid', -- Pembayaran default lunas
            created_at     TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
            updated_at     TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
        );
    ");

    // ─── Order Items ─────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id       INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            menu_id        INTEGER NOT NULL REFERENCES menus(id) ON DELETE RESTRICT,
            qty            INTEGER NOT NULL DEFAULT 1,
            price_at_order INTEGER NOT NULL,
            note           TEXT    NOT NULL DEFAULT ''
        );
    ");

    // ─── Order Item Options (varian/opsi yang dipilih) ───────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_item_options (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            order_item_id   INTEGER NOT NULL REFERENCES order_items(id) ON DELETE CASCADE,
            option_value_id INTEGER NOT NULL REFERENCES menu_option_values(id) ON DELETE RESTRICT
        );
    ");

    // ─── Stock Logs (log pemakaian bahan baku) ───────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock_logs (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            ingredient_id      INTEGER NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
            change_qty         INTEGER NOT NULL,
            type               TEXT    NOT NULL, -- 'in', 'out'
            reference_order_id INTEGER, -- bisa null kalau manual admin
            created_by         INTEGER NOT NULL REFERENCES users(id),
            created_at         TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
        );
    ");

    // ─── Financial Records (Pendapatan & Pengeluaran) ─────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS financial_records (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            type               TEXT    NOT NULL, -- 'income', 'expense'
            category           TEXT    NOT NULL, 
            amount             INTEGER NOT NULL,
            description        TEXT    NOT NULL DEFAULT '',
            reference_order_id INTEGER, -- null jika manual
            created_by         INTEGER NOT NULL REFERENCES users(id),
            created_at         TEXT    NOT NULL DEFAULT (datetime('now','localtime'))
        );
    ");
}

