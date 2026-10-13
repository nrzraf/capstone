# RENCANA SISTEM (System Plan)
## Sistem Informasi Pengelolaan Acara & Presensi Digital Berbasis QR Code
### Organisasi Mahasiswa Universitas Bakrie — Project Capstone Kelompok 7

> Dokumen ini menurunkan **SRS Kelompok 7** menjadi rencana teknis yang siap dieksekusi: arsitektur, tech stack, modul, model data, API, keamanan, dan roadmap rilis (MVP → Rilis 2).

---

## 1. Ringkasan & Tujuan

| Aspek | Isi |
|---|---|
| **Masalah** | Presensi manual (kertas) rentan "titip presensi", rekap manual lambat & rawan salah hitung, data tersebar, proposal manual lambat. |
| **Solusi** | Web app terintegrasi: manajemen acara + RSVP + presensi QR Code + rekap otomatis + dasbor + ekspor laporan. |
| **Sasaran utama** | Rekap pasca-acara < 10 menit (dari beberapa jam); akurasi kehadiran ↑; laporan dapat diverifikasi & diunduh kapan pun. |
| **Pengguna** | Anggota, Panitia/Pengurus Acara, Pembina Ormawa, Admin Sistem (4 aktor). |
| **Out of scope (fase lanjut)** | Pembayaran/iuran, SSO akademik, aplikasi mobile native. |

---

## 2. Arsitektur Sistem

Pola: **3-tier web** dengan pemisahan API dan frontend (SPA/SSR), memakai pola **layered + service-oriented** yang ringan (bukan microservice penuh) agar cocok untuk skala tim kecil dan ormawa.

```
┌──────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                           │
│  Web App responsif (Anggota / Panitia / Pembina / Admin)      │
│  • Halaman QR Scanner (kamera)   • Dasbor & Laporan          │
│  Akses: Android & iOS via browser, mulai layar 5" (NFR-07)    │
└───────────────┬──────────────────────────────────────────────┘
                │ HTTPS / REST JSON (JWT)
┌───────────────▼──────────────────────────────────────────────┐
│                      APPLICATION LAYER                        │
│  API Server + Service modules:                               │
│  Auth/RBAC · Event · Proposal · RSVP · Attendance(QR) ·      │
│  Report/Dashboard · Member · Notification · Audit            │
└───────────────┬──────────────────────────────────────────────┘
                │ ORM / SQL
┌───────────────▼──────────────────────────────────────────────┐
│                        DATA LAYER                             │
│  RDBMS (PostgreSQL)  ·  Object Storage (lampiran/proposal)   │
│  Cache/Queue (Redis) ·  Audit log (immutable)                │
└──────────────────────────────────────────────────────────────┘
```

### Komponen kunci
- **Frontend SPA** — antarmuka presensi & dasbor; memakai kamera perangkat untuk memindai QR.
- **API Server** — seluruh logika bisnis, validasi, otorisasi berbasis peran (RBAC).
- **Attendance Service** — inti sistem: verifikasi QR/kode cadangan, pencatatan stempel waktu immutable, anti-duplikasi.
- **QR Generator** — membangkitkan kode unik per peserta per acara saat RSVP.
- **Notification Service** — notifikasi in-app + email (acara baru, perubahan, pengingat H-1).
- **Report/Dashboard Service** — agregasi kehadiran & pembuatan berkas PDF/Excel/CSV.
- **Audit Trail** — mencatat pelaku, waktu, jenis perubahan (tidak dapat dihapus pengguna biasa).

---

## 3. Tech Stack yang Diusulkan

| Layer | Pilihan | Alasan singkat |
|---|---|---|
| Frontend | **Next.js (React + TypeScript)** + Tailwind CSS | SSR/SPA, ekosistem luas, mudah responsif (NFR-07). |
| QR Scan | **html5-qrcode / ZXing-js** (kamera browser) | Tanpa instalasi tambahan (NFR-07), check-in ≤ 5 detik. |
| Backend | **Node.js (NestJS) atau Laravel** | Struktur modular, cepat dikembangkan tim kecil. |
| Database | **PostgreSQL** | Data relasional (anggota–acara–presensi), transaksi andal. |
| Cache/Queue | **Redis** | Cache dasbor (< 5 menit refresh) & antrian notifikasi. |
| Object Storage | **S3-compatible (MinIO/Cloud)** | Simpan lampiran proposal & berkas ekspor. |
| Auth | **JWT + bcrypt/argon2** | Password ter-hash, sesi logout 30 menit (NFR-04). |
| Report | **PDFKit / Puppeteer**, **ExcelJS** | Ekspor PDF/Excel/CSV (FR-07, FR-12). |
| Deployment | Docker + PaaS (Railway/Render) | Reproducible, biaya rendah untuk ormawa. |
| Monitoring | Uptime check + error tracking | Target uptime 99% jam kampus (NFR-03). |


---

## 4. Modul & Pemetaan ke Kebutuhan

| Modul | FR terkait | UC | Prioritas rilis |
|---|---|---|---|
| Autentikasi & RBAC | FR-01 | UC-01 | MVP |
| Manajemen Acara | FR-02, FR-13 | UC-02 | MVP |
| Proposal & Persetujuan | FR-03 | UC-03 | Rilis 2 |
| RSVP / Pendaftaran | FR-04 | UC-04 | MVP |
| Presensi QR + Fallback | FR-05, FR-06, FR-14 | UC-05 | MVP |
| Rekap & Laporan | FR-07, FR-12 | UC-06 | MVP |
| Dasbor & Riwayat | FR-08, FR-11 | UC-06 | Rilis 2 |
| Manajemen Anggota | FR-09 | UC-07 | MVP |
| Notifikasi | FR-10 | UC-08 | Rilis 2 |
| Audit Trail | FR-14 | UC-02/05 | Rilis 2 |

---

## 5. Model Data (ERD Ringkas)

```
MEMBER(id, nama, nim, email, password_hash, divisi, status, role_id, created_at)
ROLE(id, nama, deskripsi)                     -- anggota, panitia, pembina, admin
EVENT(id, nama, jenis, tanggal, waktu, lokasi, kuota, deskripsi,
      status, dibuat_oleh, disetujui_oleh, alasan_batal, created_at)
PROPOSAL(id, event_id, file_url, status, catatan_pembina,
         diajukan_at, diputus_at, reviewer_id)
REGISTRATION(id, event_id, member_id, kode_presensi, qr_payload,
             status, registered_at)            -- UNIQUE(event_id, member_id)
ATTENDANCE(id, registration_id, event_id, member_id, status,
           metode[qr|manual], scanned_by, checkin_at)  -- immutable
NOTIFICATION(id, member_id, event_id, tipe, isi, terkirim_at, dibaca)
AUDIT_LOG(id, actor_id, aksi, entitas, entitas_id, detail, created_at)  -- append-only

Relasi:
ROLE 1─* MEMBER           MEMBER 1─* REGISTRATION
EVENT 1─* REGISTRATION    REGISTRATION 1─1 ATTENDANCE
EVENT 1─* PROPOSAL        EVENT 1─* NOTIFICATION
MEMBER 1─* AUDIT_LOG      EVENT 1─* AUDIT_LOG
```

Aturan integritas:
- `UNIQUE(event_id, member_id)` pada REGISTRATION → cegah pendaftaran ganda (A2, UC-04).
- `ATTENDANCE` bersifat **append-only** (immutable) setelah tersimpan → NFR-05.
- `EVENT` berpeserta tidak boleh di-*hard delete*; hanya berubah status "Dibatalkan" + alasan (A2, UC-02).

---

## 6. Rancangan API (REST — ringkas)

| Method | Endpoint | Fungsi | FR |
|---|---|---|---|
| POST | `/auth/login` | Login, keluarkan JWT | FR-01 |
| POST | `/auth/logout` | Akhiri sesi | FR-01 |
| GET/POST/PUT | `/events` `/events/{id}` | CRUD acara | FR-02 |
| POST | `/events/{id}/cancel` | Batalkan acara + alasan | FR-02 |
| POST | `/events/{id}/proposals` | Ajukan proposal | FR-03 |
| PATCH | `/proposals/{id}` | Setujui/tolak/revisi | FR-03 |
| POST | `/events/{id}/register` | RSVP → hasilkan QR | FR-04 |
| POST | `/attendance/scan` | Verifikasi QR/kode → catat hadir | FR-05, FR-06 |
| GET | `/events/{id}/attendance` | Rekap per acara | FR-07 |
| GET | `/reports/summary` | Ringkasan + dasbor | FR-08, FR-11 |
| GET | `/reports/export?format=pdf\|xlsx\|csv` | Unduh laporan | FR-07, FR-12 |
| GET/POST/PUT | `/members` | Kelola anggota | FR-09 |
| POST | `/notifications/broadcast` | Kirim pengumuman | FR-10 |
| GET | `/audit-logs` | Lihat jejak audit | FR-14 |

Contoh payload check-in:
```json
POST /attendance/scan
{ "event_id": "evt_102", "code": "QR:8f3a-...", "metode": "qr" }
→ 201 { "status": "Hadir", "member": "Budi S.", "checkin_at": "2026-05-12T09:14:07+07:00" }
→ 409 { "error": "Kode sudah digunakan" }   // tolak ganda A2
```

---

## 7. Keamanan & Kepatuhan (NFR-04, NFR-05)

- Password **di-hash** (bcrypt/argon2); tidak pernah disimpan plaintext.
- **RBAC** 4 peran; hak override khusus admin.
- Auto-logout setelah **30 menit** idle.
- Presensi bersifat **immutable** + **audit trail** (pelaku, waktu, jenis perubahan).
- QR unik per peserta per acara → mencegah titip presensi & pemindaian ganda.
- Fallback kode manual 6 digit dicatat dengan penanda "mode cadangan" agar dapat diaudit.

## 8. Non-Fungsional — Target Terukur

| NFR | Target |
|---|---|
| Performance | ≥ 300 peserta/sesi; respons ≤ 2 s; ≥ 50 check-in bersamaan (NFR-01) |
| Usability | Task success ≥ 90%; SUS ≥ 70 (NFR-02) |
| Availability | Uptime ≥ 99% (07.00–21.00) + mode cadangan (NFR-03) |
| Security | Hash password, RBAC, auto-logout 30 menit (NFR-04) |
| Data Integrity | Presensi immutable + timestamp (NFR-05) |
| Maintainability | Fitur kecil selesai ≤ 3 hari kerja (NFR-06) |
| Portability | Browser Android/iOS, layar ≥ 5" (NFR-07) |
| Scalability | 5.000 anggota & 200 acara/tahun, degradasi ≤ 20% (NFR-08) |

---

## 9. Roadmap Rilis (mengacu Product Backlog & jadwal 8 minggu)

**MVP (Rilis 1)** — Must:
PB-01 Login · PB-02 Kelola Acara · PB-04 RSVP · PB-05 Presensi QR · PB-06 Fallback · PB-07 Rekap · PB-09 Kelola Anggota · PB-12 Ekspor.

**Rilis 2** — Should:
PB-03 Proposal digital · PB-08 Dasbor · PB-10 Notifikasi · PB-11 Riwayat kehadiran pribadi.

**Rencana sprint (8 minggu)** — selaras jadwal SRS:
1. M1 — Studi pendahuluan & pemetaan masalah
2. M1–M3 — Elisitasi (wawancara, observasi, dokumen) + kuesioner
3. M3–M4 — Draf SRS & product backlog
4. M4–M5 — Pemodelan use case & validasi stakeholder
5. M5 — FGD konfirmasi kebutuhan final
6. M6–M7 — Perancangan (wireframe/purwarupa) → mulai MVP
7. M7–M8 — UAT awal & evaluasi
8. M8 — Finalisasi laporan pendahuluan

---

## 10. Peran Tim

| Nama | Peran |
|---|---|
| Muhammad Biko Waffie | Project Lead / Scrum Master & DevOps / Cloud / QA / Security |
| Bintang Rabbani | Product Analyst |
| Nicholas Fernando Putra | UI/UX Designer & Software/Systems Engineer (Frontend) |
| Muhammad Rafi Ibnusalis | Software/Systems Engineer (Backend) & Data/AI Engineer |

---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Jaringan kampus buruk saat acara | Presensi gagal | Mode cadangan kode manual 6 digit (FR-06) |
| Titip presensi / QR dibagikan | Akurasi turun | QR unik per peserta + anti-ganda + verifikasi acara aktif |
| Beban check-in bersamaan | Timeout | Optimasi indeks DB + antrian + target NFR-01 |
| Adopsi rendah dari panitia | Data tidak konsisten | UI tanpa pelatihan (NFR-02) + UAT awal |
| Kehilangan data | Kehilangan bukti kehadiran | Backup DB terjadwal + audit trail immutable |

---

*Sumber: SRS_TIF_Project Capstone Kelompok 7, Universitas Bakrie 2026.*
