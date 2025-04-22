# BookArchive - Sistem Manajemen Buku dan Catatan

## Deskripsi Aplikasi
BookArchive adalah aplikasi web untuk mengelola koleksi buku dan catatan pribadi. Aplikasi ini memungkinkan pengguna untuk:
- Mendaftar dan login dengan sistem API key
- Mengelola koleksi buku pribadi
- Membuat dan mengelola catatan terkait buku
- Mengakses data melalui API yang aman

## Teknologi Pemrograman Backend
- **Bahasa Pemrograman**: PHP Native
- **Database**: MySQL
- **API Authentication**: API Key
- **Format Data**: JSON
- **Server**: Apache (XAMPP)

## Teknologi Frontend
- **Platform**: Web
- **Bahasa**: HTML, CSS, JavaScript
- **Framework**: Native (Vanilla JS)
- **UI Components**: Remix Icon
- **Font**: Inter (Google Fonts)

## Link Aplikasi
- **URL Aplikasi**: http://localhost/book-archive
- **URL API**: http://localhost/book-archive/api/

## Panduan Pengujian API dengan Postman

### Persiapan
1. Download dan install [Postman](https://www.postman.com/downloads/)
2. Import file collection `postman/BookArchive.postman_collection.json`
3. Buat environment baru dengan variabel:
   - `base_url`: http://localhost/book-archive
   - `api_key`: (akan diisi setelah registrasi/login)

### Langkah-langkah Pengujian

#### 1. Registrasi Pengguna
1. Buka request "Register User"
2. Isi body dengan data:
```json
{
    "username": "testuser",
    "password": "testpassword"
}
```
3. Klik "Send"
4. Simpan API key yang diterima untuk digunakan di request berikutnya

#### 2. Login Pengguna
1. Buka request "Login User"
2. Isi body dengan data yang sama:
```json
{
    "username": "testuser",
    "password": "testpassword"
}
```
3. Klik "Send"
4. Simpan API key yang diterima

#### 3. Mengelola Buku
1. Set API key di environment variable
2. Test endpoint buku:
   - GET `/api/books.php` - Lihat semua buku
   - POST `/api/books.php` - Tambah buku baru
   - PUT `/api/books.php` - Update buku
   - DELETE `/api/books.php?id=1` - Hapus buku

#### 4. Mengelola Catatan
1. Test endpoint catatan:
   - GET `/api/notes.php` - Lihat semua catatan
   - POST `/api/notes.php` - Buat catatan baru
   - PUT `/api/notes.php` - Update catatan
   - DELETE `/api/notes.php?id=1` - Hapus catatan

### Contoh Response

#### Registrasi Berhasil
```json
{
    "success": true,
    "message": "Registration successful",
    "api_key": "your-api-key-here"
}
```

#### Login Berhasil
```json
{
    "success": true,
    "message": "Login successful",
    "api_key": "your-api-key-here"
}
```

#### Daftar Buku
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "The Great Gatsby",
            "author": "F. Scott Fitzgerald",
            "published_date": "1925-04-10",
            "isbn": "9780743273565"
        }
    ]
}
```

### Tips Pengujian
1. Pastikan server XAMPP berjalan
2. Database sudah terbuat dan tabel sudah dibuat
3. Gunakan API key yang valid untuk setiap request
4. Perhatikan format JSON yang benar
5. Cek response status code untuk memastikan request berhasil

## Troubleshooting
1. **Error 500**: Periksa koneksi database dan konfigurasi
2. **Error 401**: Pastikan API key valid dan terdaftar
3. **Error 404**: Periksa URL endpoint
4. **Error 400**: Periksa format data yang dikirim

## Kontak
Untuk bantuan lebih lanjut, silakan hubungi administrator sistem. 