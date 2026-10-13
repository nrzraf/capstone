# Event Attendance Universitas Bakrie

**Project Capstone Kelompok 7 · Program Studi Informatika · Universitas Bakrie · 2026**

Aplikasi web **Full MVP** berbasis **PHP + MySQL (XAMPP)** untuk menggantikan proses
manual pengelolaan acara dan presensi organisasi mahasiswa. Presensi memakai **QR Code
asli (kamera)** dengan **fallback kode manual 6 digit**.

---

## ⚡ Cara Menjalankan (3 langkah)

1. **Nyalakan XAMPP** — klik Start pada **Apache** dan **MySQL** di XAMPP Control Panel.
2. **Buka installer** di browser:
   ```
   http://localhost:8080/presensi-ormawa/install.php
   ```
   Klik **"Jalankan Instalasi"** (membuat database + 9 tabel + data contoh).
3. **Buka aplikasi**:
   ```
   http://localhost:8080/presensi-ormawa/
   ```

> Aplikasi ini berjalan di **port 8080** karena port 80 sudah dipakai project lain
> (`adidaya1`). Project itu **tidak diubah** sama sekali.

### Akun Demo (semua password: `password123`)

| Peran | Email |
|---|---|
| **Admin** | `admin@ormawa.bakrie.ac.id` |
| **Panitia** | `panitia@student.bakrie.ac.id` |
| **Pembina** | `pembina@ormawa.bakrie.ac.id` |
| **Anggota** | `anggota@student.bakrie.ac.id` |

Halaman login punya tombol cepat untuk mengisi tiap akun.

---

## 🎯 Fitur (Full MVP — semua kebutuhan "Must")

| Modul | Keterangan | FR / UC |
|---|---|---|
| **Autentikasi & RBAC** | Login 4 peran, rate-limit 5 gagal → kunci 15 menit, auto-logout 30 menit | FR-01 · UC-01 |
| **Kelola Acara** | Buat/edit acara, ubah status, batalkan | FR-02 · UC-02 |
| **Proposal Digital** | Panitia ajukan proposal + berkas, pembina setujui/tolak/revisi | FR-03 · UC-03 |
| **RSVP / Pendaftaran** | Anggota daftar mandiri, anti-duplikasi, cek kuota | FR-04 · UC-04 |
| **Presensi QR** | Scan kamera (html5-qrcode), verifikasi token, tolak ganda | FR-05 · UC-05 |
| **Presensi Cadangan** | Input kode manual 6 digit bila QR/jaringan bermasalah | FR-06 · UC-05 |
| **Rekap & Laporan** | Rekap per acara, ringkasan hadir/tidak hadir/persentase | FR-07 · UC-06 |
| **Ekspor** | CSV, Excel (.xls), PDF (via cetak browser) | FR-07, FR-12 |
| **Dasbor** | KPI acara, rata-rata kehadiran, anggota teraktif | FR-08, FR-11 · UC-06 |
| **Kelola Anggota** | Admin tambah/ubah/nonaktifkan akun & hak akses | FR-09 · UC-07 |
| **Notifikasi & Email** | In-app + email (SMTP) acara baru, perubahan jadwal, pengingat H-1 | FR-10 · UC-08 |
| **Log Audit** | Jejak aktivitas: pelaku, waktu, jenis perubahan (append-only) | FR-14 |
| **Pencarian & Filter** | Daftar Acara & Kelola Anggota | FR-13 |

### Status Implementasi (audit & tes 77/77 lulus)

| Kebutuhan | Status |
|---|---|
| FR-01 s/d FR-14 | ✅ Terimplementasi & teruji |


## 🖥️ Alur Coba Cepat (demo)

1. Login sebagai **Panitia** → menu **Scan Presensi**.
2. Buka menu **Daftar Acara** di tab lain (login **Anggota**), klik **Daftar** pada sebuah acara → muncul **QR pribadi**.
3. Di layar **Scan Presensi** (Panitia), arahkan kamera ke QR tadi → nama peserta muncul = **Hadir**.
4. Coba juga **Mode Cadangan**: masukkan kode 6 digit yang tertera di halaman QR anggota.
5. Buka **Laporan** → lihat rekap & **Unduh CSV/Excel/PDF**.
6. Login **Admin** → **Kelola Anggota** & **Log Audit**.

---

## 🗂️ Struktur File

```
presensi-ormawa/
├── config.php          # Konfigurasi DB, aplikasi, sesi
├── db.php              # Koneksi PDO + helper query
├── helpers.php         # Auth, CSRF, flash, audit, format, QR
├── layout.php          # Layout UI (sidebar, header, flash)
├── index.php           # Router utama (?r=route)
├── install.php         # Installer: schema + data contoh
└── pages/
    ├── login.php, logout.php
    ├── dashboard.php
    ├── events.php, event_detail.php, event_form.php, event_save.php
    ├── rsvp.php, myqr.php
    ├── scan.php, scan_api.php        # presensi QR + fallback
    ├── report.php, export.php        # rekap + ekspor
    ├── members.php, member_form.php, member_save.php
    ├── audit.php, notifications.php
```

## 🗄️ Skema Database (9 tabel)

`role` · `member` · `event` · `registration` · `attendance` · `proposal` ·
`notification` · `audit_log` · `login_attempt`

Aturan integritas utama:
- `UNIQUE(event_id, member_id)` → cegah pendaftaran ganda.
- `UNIQUE(registration_id)` pada attendance → cegah presensi ganda.
- Presensi bersifat **immutable** (hanya insert, tidak diubah) + **audit trail**.

---

## 🔒 Keamanan

- Password **di-hash** (bcrypt via `password_hash`).
- **RBAC** 4 peran di setiap route.
- **CSRF token** pada semua form POST.
- **Rate-limit login** (5 gagal / 10 menit → kunci 15 menit).
- **Auto-logout** setelah 30 menit idle.
- Query **prepared statement** (anti SQL injection).

## ⚙️ Kebutuhan

- XAMPP (Apache + MySQL + PHP 8.x) — sudah tersedia di komputer ini.
- Koneksi internet (untuk: pustaka kamera `html5-qrcode` & gambar QR).

## 🛠️ Catatan Teknis

- **QR** dibuat lewat `api.qrserver.com` (tanpa dependency server). Bila ingin offline penuh,
  bisa diganti dengan pustaka `phpqrcode` (perlu Composer).
- **Ekspor Excel** memakai format HTML-table `.xls` (dibuka Excel/LibreOffice).
  Untuk `.xlsx` sejati, bisa ditambah pustaka PhpSpreadsheet.
- **PDF** memakai dialog cetak browser (Save as PDF) agar tanpa dependency berat.

---

*Diturunkan dari SRS Kelompok 7. Dokumen perancangan ada di folder `Downloads/capstone`.*
