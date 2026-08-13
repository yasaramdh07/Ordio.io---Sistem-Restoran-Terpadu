# PRD — Ordio.io
### QR Order & Internal Stock Management System (Prototype)

**Status:** Prototype untuk portfolio & GitHub
**Dibuat oleh:** SuraCode Studio
**Tanggal:** Agustus 2026

---

## 1. Ringkasan Proyek

Ordio.io adalah sistem pemesanan makanan berbasis QR code untuk restoran, yang juga merangkap sebagai sistem administrasi internal (stock bahan baku, pencatatan keuangan, dan pelaporan). Meskipun statusnya prototipe untuk portfolio, aplikasi ini **tidak boleh terasa seperti prototipe** di mata pengguna — UI harus terasa seperti produk jadi, bukan demo.

**Tagline:** Order in, serve fast

---

## 2. Tujuan

- Customer bisa scan QR di meja → pesan makanan tanpa perlu install app atau daftar akun
- Kasir bisa monitor & proses pesanan masuk secara real-time
- Admin bisa kelola menu, stock bahan baku, harga, staff, dan laporan keuangan — semua dari satu dashboard
- Semua laporan (penjualan, stock, keuangan) bisa diexport ke PDF per hari/minggu/bulan

---

## 3. Tech Stack

| Layer | Teknologi |
|---|---|
| Frontend | HTML, CSS, JavaScript (vanilla) |
| CSS Framework | Bootstrap 5 (base grid & components, akan di-override habis-habisan biar ga kelihatan default) |
| Icons | Bootstrap Icons |
| Backend | PHP (native, tanpa framework) |
| Database | SQLite (WAL mode) |
| Real-time update | AJAX polling (interval-based, konsisten dengan project sebelumnya) |
| PDF Export | DomPDF atau TCPDF (laporan keuangan/stock/penjualan) |
| QR Generator | PHP QR Code library (generate + downloadable PNG dari dashboard admin) |
| Font | Baloo 2 (heading/brand), Quicksand (body/UI text) |

**Warna:**
- Primary: `#f36638` (oranye — dari logo)
- Secondary/Dark: `#7a1e00` (coklat kemerahan tua — aksen teks/border)
- Netral: cream/off-white untuk background, charcoal untuk teks body

---

## 4. Prinsip Desain UI (penting)

> Tujuan: aplikasi harus terasa **niat dan custom-built**, bukan template AI generic.

- **Jangan** pakai default Bootstrap card/button/navbar tanpa override — semua komponen di-restyle pakai warna & border-radius khas brand
- **Jangan** glassmorphism, gradient berlebihan, drop shadow tebal, atau efek neon
- Gunakan flat design, whitespace yang cukup, dan tipografi rounded (Baloo 2 + Quicksand) sebagai identitas visual utama
- Ikon konsisten pakai Bootstrap Icons dengan style outline, ukuran seragam
- Elemen interaktif (tombol, status badge) punya micro-interaction sederhana (hover state, transisi halus) — bukan animasi berlebihan
- Tidak ada label "prototype", "demo", "beta", atau watermark apapun yang terlihat oleh user

**Strategi Responsive:**
| Role | Prioritas Utama | Catatan |
|---|---|---|
| Pelanggan | Mobile-first | 95% akses dari HP saat scan QR |
| Kasir | Desktop-first | Dipakai di workstation kasir, tapi tetap harus tidak rusak di tablet |
| Admin | Desktop-first, tapi full responsive | Perlu akses cepat dari HP untuk cek laporan/recap saat di luar resto |

---

## 5. User Roles

### 5.1 Admin
- Login via username & password (akun dibuat manual/seed di database)
- Kelola menu (CRUD): nama, kategori, harga, foto, status aktif/nonaktif
- Kelola opsi kustomisasi menu (misal level pedas, less ice) — bisa diatur per menu
- Kelola bahan baku (ingredients): nama, satuan, stok saat ini, ambang batas stok rendah
- Mapping resep: hubungkan menu ke bahan baku + jumlah pemakaian per porsi
- Kelola akun kasir: create, edit, nonaktifkan akun kasir (username & password diatur admin)
- Kelola meja: tambah/hapus meja (untuk referensi internal, karena QR bersifat generic)
- Generate & download QR code (satu QR generic yang berlaku untuk semua meja)
- Input transaksi keuangan keluar (belanja bahan baku, operasional, dll) secara manual
- Lihat seluruh riwayat pesanan, status keuangan, dan level stock
- Download laporan PDF: penjualan (harian/mingguan/bulanan), stock, dan keuangan
- Dashboard ringkasan (total penjualan hari ini, stock menipis, pesanan aktif)

### 5.2 Kasir
- Login via username & password (dibuatkan oleh admin)
- Melihat pesanan masuk secara real-time (nama pemesan, nomor meja, item, catatan/opsi)
- Klik "Terima Pesanan" → status berubah jadi diproses, stock bahan baku otomatis terpotong
- Klik "Pesanan Selesai" → status berubah jadi selesai (sudah diantar ke meja)
- Bisa membatalkan pesanan (dengan alasan, opsional) jika diperlukan
- Melihat riwayat pesanan yang sudah ditangani hari itu

### 5.3 Pelanggan (tanpa login)
- Scan QR generic → masuk ke halaman pemesanan
- Isi nama pemesan + nomor meja secara manual
- Pilih menu dari katalog (dengan foto, harga, deskripsi)
- Untuk menu tertentu, isi opsi kustomisasi (misal level pedas, less ice) sesuai yang diatur admin
- Tambah catatan bebas per item (opsional)
- Review pesanan (cart) sebelum submit
- Submit pesanan → sistem otomatis mensimulasikan pembayaran cashless (dummy, tanpa gateway asli)
- Lihat status pesanan real-time: **Menunggu → Diproses → Selesai**

---

## 6. Alur Pemesanan (Order Flow)

```
Pelanggan scan QR generic
        ↓
Isi nama + nomor meja + pilih menu + opsi/catatan
        ↓
Submit → status "Menunggu" → pembayaran dummy otomatis sukses
        ↓
Kasir lihat pesanan masuk di dashboard (real-time via polling)
        ↓
Kasir klik "Terima" → status "Diproses" → stock bahan baku otomatis terpotong
        ↓
Kasir klik "Selesai" (setelah makanan diantar) → status "Selesai"
        ↓
Pelanggan melihat status update di halaman mereka secara real-time
```

**Catatan penting:** Karena QR bersifat generic (satu QR untuk semua meja), sistem **tidak bisa otomatis tahu meja mana yang mesan** — makanya nomor meja wajib diisi manual oleh pelanggan di form, dan ditampilkan jelas di dashboard kasir agar mudah dicocokkan.

---

## 7. Skema Database (SQLite)

```
users
- id, username, password_hash, role (admin/kasir), full_name, is_active, created_at

tables
- id, table_number, note (opsional, untuk referensi internal admin)

ingredients
- id, name, unit (gram/ml/pcs/dll), stock_qty, low_stock_threshold, updated_at

menu_categories
- id, name

menus
- id, name, category_id, price, description, image_path, is_active

menu_ingredients (mapping resep)
- id, menu_id, ingredient_id, qty_used

menu_options (misal: "Level Pedas", "Level Es")
- id, menu_id, option_name, is_required

menu_option_values (misal: "Level 1", "Less Ice")
- id, option_id, value_name, extra_price (default 0)

orders
- id, order_code, customer_name, table_number, status (menunggu/diproses/selesai/dibatalkan),
  total_price, payment_status (dummy: paid), created_at, updated_at

order_items
- id, order_id, menu_id, qty, price_at_order, note

order_item_options
- id, order_item_id, option_value_id

stock_logs
- id, ingredient_id, change_qty, type (in/out), reference_order_id (nullable), created_by, created_at

financial_records
- id, type (income/expense), category, amount, description,
  reference_order_id (nullable, hanya untuk income otomatis), created_by, created_at
```

---

## 8. Modul Laporan (PDF Export)

Semua laporan bisa difilter per **harian / mingguan / bulanan**, dan didownload sebagai PDF:

1. **Laporan Penjualan** — total omzet, jumlah transaksi, menu terlaris
2. **Laporan Stock** — pemakaian bahan baku, stock masuk/keluar, item yang perlu direstock
3. **Laporan Keuangan** — ringkasan pemasukan (otomatis dari order) vs pengeluaran (manual admin), profit/loss sederhana

---

## 9. Asumsi & Batasan (Out of Scope)

- Pembayaran cashless bersifat **dummy/simulasi** — tidak terhubung payment gateway asli
- QR bersifat generic (1 QR untuk semua meja), bukan QR unik per meja
- Tidak ada sistem notifikasi push/WhatsApp — update status murni via polling di browser
- Tidak ada multi-cabang/multi-resto — sistem didesain untuk 1 resto saja
- Tidak ada sistem role granular tambahan (misal "chef" terpisah dari kasir) — sesuai brief awal hanya 3 role

---

## 10. Struktur Folder (Usulan)

```
ordio/
├── assets/
│   ├── img/          (logo, favicon — sudah tersedia)
│   ├── css/
│   └── js/
├── admin/
│   ├── dashboard.php
│   ├── menu.php
│   ├── ingredients.php
│   ├── staff.php
│   ├── reports.php
│   └── qr-generator.php
├── kasir/
│   ├── dashboard.php
│   └── order-history.php
├── order/
│   ├── index.php       (halaman pemesanan pelanggan)
│   └── status.php       (tracking status pesanan)
├── includes/
│   ├── db.php
│   ├── auth.php
│   └── functions.php
├── api/                 (endpoint untuk AJAX polling)
│   ├── get-orders.php
│   ├── update-order-status.php
│   └── ...
└── database/
    └── ordio.sqlite
```

---

## 11. Navigasi

### 11.1 Admin
| Device | Style |
|---|---|
| Desktop | Sidebar kiri, fixed, collapsible |
| Mobile | Bottom navbar (app-style modern), 4-5 ikon utama + menu "Lainnya" untuk sisanya |

Menu: Dashboard, Kelola Menu, Bahan Baku, Staff, Meja & QR, Keuangan, Laporan, Logout
(Di mobile, prioritaskan yang paling sering diakses saat di luar resto: **Dashboard, Laporan, Keuangan** — sisanya masuk ke menu "Lainnya")

### 11.2 Kasir
| Device | Style |
|---|---|
| Desktop | Top navbar simpel (cuma 2 halaman utama) |
| Mobile/Tablet | Bottom navbar juga, biar konsisten vibe-nya sama admin |

Menu: Pesanan Masuk, Riwayat Pesanan, Logout

### 11.3 Pelanggan
Tidak ada navbar formal — flow linear (scan → pesan → status). Cukup header minimal dengan logo Ordio + tombol "Lihat Status Pesanan" yang muncul setelah order disubmit.

**Prinsip bottom nav (biar kerasa app-modern, bukan web biasa):**
- Fixed di bawah, ikon + label singkat, ikon aktif diberi warna primary (`#f36638`) dan indikator kecil (dot atau background pill)
- Pakai Bootstrap Icons outline style, konsisten ukuran
- Transisi halus antar tab (fade/slide ringan, jangan berlebihan)
- Aman dari notch/safe-area di HP modern (`padding-bottom: env(safe-area-inset-bottom)`)

---

## 12. Next Steps

Setelah PRD ini disetujui, langkah selanjutnya:
1. Susun prompt build bertahap untuk Antigravity (per modul, seperti pola project sebelumnya)
2. Mulai dari: setup database + auth → menu & ingredient management → order flow → dashboard kasir → laporan & PDF export