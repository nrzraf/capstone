# Capstone Kelompok 7 - Event Attendance Universitas Bakrie

Sistem Informasi Pengelolaan Acara & Presensi Digital (QR Code) untuk organisasi
mahasiswa Universitas Bakrie.

Diturunkan dari `SRS_TIF_Project Capstone Kelompok 7.pdf`.

## Isi Repositori

| Path | Isi |
|---|---|
| `index.html` | Halaman utama (daftar dokumen & prototype) |
| `docs/01_flowchart.html` | 6 diagram alur sistem |
| `docs/02_rencana_sistem.md` | Rencana sistem lengkap |
| `docs/03_arsitektur.html` | Diagram arsitektur 3-tier |
| `docs/04_prototype_ui.html` | Prototype UI 11 layar |
| `docs/DESIGN.md` | Design system & token |
| `docs/theme.css`, `docs/tokens.dtcg.json` | Ekspor token (Tailwind v4, W3C DTCG) |
| `app/` | Kode sumber aplikasi (PHP + MySQL) |

## Deploy di Vercel

Repositori ini adalah **situs statis** - langsung bisa di-deploy:

1. Buka [vercel.com/new](https://vercel.com/new)
2. Import repo `capstone`
3. Framework Preset: **Other**, Build Command kosong, Output Directory kosong
4. Deploy

## Menjalankan Aplikasi (PHP + MySQL)

Vercel tidak menjalankan PHP. Aplikasi di `app/` dijalankan di hosting PHP:

1. Salin isi `app/` ke `htdocs/presensi-ormawa` (XAMPP) atau hosting PHP.
2. Jalankan `install.php` untuk membuat database + data contoh.
3. Buka `index.php` dan login dengan akun demo.

### Akun Demo (password: `password123`)

| Peran | Email |
|---|---|
| Admin | `admin@ormawa.bakrie.ac.id` |
| Panitia | `panitia@student.bakrie.ac.id` |
| Pembina | `pembina@ormawa.bakrie.ac.id` |
| Anggota | `anggota@student.bakrie.ac.id` |

### Uji Otomatis

```bash
bash app/test_suite.sh   # 77 skenario, butuh XAMPP aktif
```

## Fitur

- Autentikasi & RBAC 4 peran, rate-limit login, auto-logout
- Kelola acara (draft, publikasi, berlangsung, pembatalan)
- Proposal digital: ajukan, review, setujui/tolak/revisi
- RSVP mandiri + QR unik per peserta per acara
- Presensi QR (kamera) + mode cadangan kode 6 digit
- Rekap, laporan, ekspor CSV / Excel / PDF
- Notifikasi in-app + email, pengingat H-1
- Log audit dan pencarian/filter

## Kebutuhan (FR)

14 Fungsional (FR-01 s/d FR-14) dan 8 Non-Fungsional (NFR-01 s/d NFR-08) -
seluruh FR terimplementasi dan teruji.
