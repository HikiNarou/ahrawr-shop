# AhRawr Shop - E-Commerce Application

Aplikasi e-commerce sederhana dan lengkap dengan sistem autentikasi (login & register).

## Fitur

- 🔐 **Sistem Autentikasi**: Login dan register dengan keamanan lengkap
- 🛒 **Katalog Produk**: Tampilan produk dengan detail dan gambar
- 🛍️ **Keranjang Belanja**: Sistem keranjang dengan manajemen quantity
- 👤 **Profil Pengguna**: Manajemen profil dan informasi akun
- 🔒 **Keamanan**: CSRF protection, password hashing, session management
- 📱 **Responsive Design**: Desain yang responsif untuk semua perangkat

## Instalasi

1. **Setup Database**
   ```bash
   # Import database.sql ke MySQL
   mysql -u root -p < database.sql
   ```

2. **Konfigurasi Database**
   - Edit file `koneksi.php` sesuai dengan konfigurasi database Anda
   - Default: localhost, username: root, password: kosong, database: ecommerce

3. **Setup Web Server**
   - Pastikan PHP 7.4+ dan MySQL telah terinstall
   - Arahkan web server ke folder aplikasi
   - Buat folder `uploads` dengan permission 755

## Struktur File

- `index.php` - Halaman utama (redirect ke katalog atau profil)
- `login.php` - Halaman login
- `register.php` - Halaman registrasi
- `view_product.php` - Katalog produk
- `product_detail.php` - Detail produk
- `cart.php` - Keranjang belanja
- `profile.php` - Profil pengguna
- `auth.php` - Fungsi autentikasi
- `account_service.php` - Service untuk manajemen akun
- `koneksi.php` - Konfigurasi database

## Penggunaan

1. Buka aplikasi di browser
2. Daftar akun baru atau login dengan akun yang sudah ada
3. Jelajahi katalog produk
4. Tambahkan produk ke keranjang
5. Kelola profil dan informasi akun

## Keamanan

- Password di-hash menggunakan PHP password_hash()
- CSRF protection pada semua form
- Session management yang aman
- SQL injection protection dengan prepared statements
- Input validation dan sanitization
