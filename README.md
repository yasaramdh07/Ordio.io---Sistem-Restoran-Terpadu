# Ordio.io - Sistem Restoran Terpadu

Ordio.io adalah aplikasi manajemen restoran modern dan terpadu (Integrated Restaurant Management System) yang dirancang untuk mempermudah operasional restoran dari ujung ke ujung. Aplikasi ini mencakup pemesanan pelanggan mandiri melalui pemindaian QR Code, dashboard kasir *real-time*, manajemen stok bahan baku, hingga pencatatan keuangan dan laporan.

## ✨ Fitur Utama

1. 📱 **Mobile-First Customer Order**
   Pelanggan dapat memindai satu QR Code generik di meja mana saja, membuka katalog menu yang modern di *smartphone* mereka, memilih opsi kustomisasi (misal: "Level Pedas"), dan langsung men-submit pesanan ke dapur/kasir.
2. 💻 **Dashboard Kasir Real-time**
   Tampilan *Kanban Board* untuk kasir yang memantau pesanan masuk secara langsung (*live polling*). Kasir dapat menerima pesanan, memproses (otomatis memotong stok bahan baku), dan menyelesaikan pesanan.
3. 🍳 **Manajemen Stok & Resep Cerdas**
   Setiap menu dapat diikat dengan komposisi bahan baku (resep). Ketika kasir memproses pesanan, sistem otomatis memotong stok bahan baku sesuai takaran resep. Termasuk notifikasi bahan baku yang menipis (Low Stock Warning).
4. 📊 **Keuangan & Laporan Otomatis**
   Setiap pesanan yang selesai otomatis tercatat sebagai "Income". Admin juga dapat mencatat pengeluaran ("Expense") secara manual. Ringkasan Laba/Rugi dihitung seketika. Tersedia juga fitur *generate* laporan Penjualan, Keuangan, dan Stok dalam format PDF.
5. 👥 **Multi-role (Admin & Kasir)**
   Akses terpisah antara Admin (pengelola seluruh aspek restoran) dan Kasir (fokus pada operasional pesanan dan antrean).
6. 🎨 **Modern UI/UX & Safe-Area Ready**
   Antarmuka pengguna yang terpoles sempurna dengan font *Baloo 2* & *Quicksand*, dukungan *bottom navbar*, modal interaktif, animasi spinner untuk mencegah klik-ganda, dan optimalisasi *notch* pada ponsel masa kini.

## 🛠️ Tech Stack

Ordio.io dirancang seringan mungkin agar bisa di-*deploy* di *shared hosting* termurah sekalipun tanpa kerumitan instalasi paket:

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5 (via CDN), Chart.js, html2pdf.js, SweetAlert2 / Toast.
- **Backend**: PHP 7.4+ (Native / Procedural). Tidak menggunakan framework berat maupun Composer.
- **Database**: SQLite3 (Otomatis). Tidak perlu mengonfigurasi MySQL/MariaDB! Skema database di- *generate* secara mandiri oleh kode.

## 🚀 Cara Instalasi & Menjalankan (Local Development)

Karena Ordio.io menggunakan SQLite, proses instalasinya sangat mudah:

1. **Clone / Download Repository ini**
   Letakkan folder proyek di dalam direktori server lokal Anda (misal: `C:\laragon\www\ordio` atau `htdocs\ordio`).
2. **Nyalakan Local Server**
   Jalankan server Apache/Nginx (contoh: melalui XAMPP, Laragon, atau MAMP). Pastikan ekstensi `pdo_sqlite` aktif di `php.ini` (biasanya sudah aktif bawaan).
3. **Akses via Browser**
   Buka `http://localhost/ordio/` di browser Anda.
4. **Login Default**
   Secara otomatis file database `ordio.db` akan dibuat di dalam folder `database/` saat pertama kali dijalankan. Gunakan akses berikut untuk masuk:
   - **Username:** `admin`
   - **Password:** `Ordio@2026`

> **Note:** Karena menggunakan arsitektur SQLite *Write-Ahead Logging* (WAL), pastikan folder `database/` memiliki izin baca-tulis (Read/Write permissions) dari *web server* Anda di mode produksi.

## 📁 Struktur Folder

- `admin/` - Halaman manajemen utama untuk Admin (Dashboard, Menu, Bahan Baku, Meja, Keuangan, Laporan).
- `kasir/` - Halaman *Point of Sales* / antrean pesanan untuk kasir.
- `order/` - Antarmuka *front-end* khusus pelanggan untuk memesan makanan via HP.
- `api/` - Seluruh *endpoint* backend untuk *AJAX request*.
- `assets/` - Penyimpanan *file* CSS custom, ikon, dan direktori *upload* gambar menu.
- `database/` - Lokasi penyimpanan file `ordio.db` (SQLite).
- `includes/` - Berkas pendukung seperti koneksi database (`db.php`), layout header, dan layout footer.

## 🔒 Catatan Keamanan
Jangan lupa untuk segera mengganti password bawaan administrator dan mengamankan file `database/ordio.db` dari akses publik (bisa dilakukan menggunakan `.htaccess` di dalam folder database).

---
*Dibangun dengan dedikasi untuk mendukung kemudahan operasional F&B lokal.*
