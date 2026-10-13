---
version: alpha
name: Bakrie Event & Presensi
description: Sistem operasional kampus yang tenang dan tepercaya — presensi QR yang cepat, angka yang jujur, tanpa dekorasi berlebih.
colors:
  primary: "#0F172A"
  secondary: "#64748B"
  tertiary: "#0F766E"
  neutral: "#F8FAFC"
  surface: "#FFFFFF"
  border: "#E2E8F0"
  success: "#047857"
  warning: "#B45309"
  danger: "#BE123C"
  info: "#0369A1"
typography:
  h1:
    fontFamily: Inter
    fontSize: 2rem
    fontWeight: 700
    lineHeight: 1.15
    letterSpacing: "-0.02em"
  h2:
    fontFamily: Inter
    fontSize: 1.375rem
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.015em"
  h3:
    fontFamily: Inter
    fontSize: 1.0625rem
    fontWeight: 600
    lineHeight: 1.3
  body-md:
    fontFamily: Inter
    fontSize: 0.9375rem
    fontWeight: 400
    lineHeight: 1.55
  body-sm:
    fontFamily: Inter
    fontSize: 0.8125rem
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "0.02em"
  mono:
    fontFamily: "JetBrains Mono"
    fontSize: 0.875rem
    fontWeight: 500
    lineHeight: 1.4
rounded:
  sm: 6px
  md: 10px
  lg: 16px
  pill: 999px
spacing:
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 40px
components:
  button-primary:
    backgroundColor: "{colors.tertiary}"
    textColor: "#FFFFFF"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: 12px
    height: 44px
  button-primary-hover:
    backgroundColor: "{colors.primary}"
    textColor: "#FFFFFF"
    rounded: "{rounded.md}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    padding: 12px
    height: 44px
  input-field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    typography: "{typography.body-md}"
    rounded: "{rounded.sm}"
    padding: 12px
    height: 44px
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    rounded: "{rounded.lg}"
    padding: 24px
  page-shell:
    backgroundColor: "{colors.neutral}"
    textColor: "{colors.primary}"
    rounded: "{rounded.lg}"
    padding: 24px
  table-row:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.primary}"
    typography: "{typography.body-sm}"
    padding: 12px
  badge-present:
    backgroundColor: "{colors.success}"
    textColor: "#FFFFFF"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: 6px
  badge-absent:
    backgroundColor: "{colors.danger}"
    textColor: "#FFFFFF"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: 6px
  badge-fallback:
    backgroundColor: "{colors.warning}"
    textColor: "#FFFFFF"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: 6px
  badge-info:
    backgroundColor: "{colors.info}"
    textColor: "#FFFFFF"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: 6px
  divider:
    backgroundColor: "{colors.border}"
    rounded: "{rounded.sm}"
    height: 1px
---

## Overview

Bakrie Event & Presensi adalah alat kerja, bukan halaman pemasaran. Pengguna
membukanya di sela-sela acara organisasi — sering di ponsel, sering terburu-buru,
kadang dengan sinyal buruk. Karena itu identitas visualnya **tenang, padat, dan
jujur**: kontras tinggi untuk keterbacaan cepat, satu warna aksi saja, dan
angka yang ditampilkan tanpa hiasan.

Tone: institusional-modern. Bukan startup riang, bukan enterprise dingin.
Warna aksi **teal** dipilih karena terbaca jelas di layar ponsel di bawah
cahaya ruangan maupun luar ruangan, dan berbeda dari biru akademik generik
sehingga tombol utama tidak pernah ambigu.

## Colors

- **Primary (#0F172A):** tinta utama — judul, teks inti, dan state hover tombol.
  Hampir-hitam agar kontras AA di atas surface putih.
- **Tertiary (#0F766E, "Campus Teal"):** satu-satunya warna aksi. Dipakai untuk
  tombol primer, tab aktif, tautan, dan fokus ring. Tidak untuk dekorasi.
- **Secondary (#64748B):** teks sekunder, label, ikon non-aktif, placeholder.
- **Neutral (#F8FAFC) / Surface (#FFFFFF):** latar aplikasi dan kartu.
- **Border (#E2E8F0):** pemisah tipis — struktur dibuat dengan garis dan spasi,
  bukan dengan kotak berwarna.
- **Status:** success `#047857` (Hadir), warning `#B45309` (mode cadangan),
  danger `#BE123C` (Tidak hadir / Dibatalkan), info `#0369A1` (Diajukan).

Semua pasangan teks-di-atas-latar utama lolos WCAG AA (≥ 4.5:1). Warna status
selalu dipasangkan ikon/label, tidak pernah warna saja (buta warna).

## Typography

Satu keluarga untuk semuanya: **Inter**, karena angka dan teks antarmuka harus
tajam pada ukuran kecil. Hierarchy dibangun dari **skala dan berat**, bukan dari
banyak warna atau kotak.

- `h1` hanya untuk judul halaman. `h2` untuk judul section. `h3` untuk judul kartu.
- `body-md` untuk isi; `body-sm` untuk teks bantu dan metadata.
- `label` (uppercase, tracking kecil) untuk header tabel, badge, dan label form.
- `mono` **hanya** untuk data yang harus presisi: kode presensi 6 digit, ID acara,
  timestamp check-in. Tidak untuk teks biasa.

## Layout & Spacing

Grid 12 kolom, lebar konten maksimum 1200px, gutter 24px. Skala spasi 4/8/16/24/40.
Kartu memakai radius `lg` (16px); kontrol memakai `sm`/`md`. Kartu punya batas
tipis 1px, **bukan** bayangan berat — aplikasi terasa ringan, bukan "mengambang".

Susunan utama:
- **Monitor/Dasbor** (pengurus): kartu KPI padat di atas, tabel di bawah.
- **Operate** (panitia scan): satu aksi dominan — area kamera besar, sisa UI turun.
- **Configure** (form acara): satu kolom, label di atas field, error di bawah field.

Target sentuh minimum **44×44px** untuk semua kontrol (dipakai saat berdiri di
depan pintu acara).

## Elevation & Depth

Minimal dan fungsional. Elevasi dipakai hanya untuk hal yang benar-benar
mengapung: dropdown, modal, toast.

- `elevation-0`: kartu & permukaan dasar — hanya border 1px.
- `elevation-1`: dropdown/menu — `0 1px 3px rgba(15,23,42,.10)`.
- `elevation-2`: modal/toast — `0 8px 24px rgba(15,23,42,.16)`.

Tidak ada glassmorphism, tidak ada gradient dekoratif.

## Shapes

Sudut membulat konsisten: `sm` 6px (input, tombol kecil), `md` 10px (tombol
primer), `lg` 16px (kartu), `pill` untuk badge status & avatar. Bentuk tidak
dipakai untuk membedakan makna — warna dan label yang melakukannya.

## Components

- **button-primary** — aksi utama tiap layar, maksimum satu per area.
  Contoh: "Simpan Acara", "Daftar Acara", "Unduh Laporan".
- **button-secondary** — aksi pendamping dengan border, teks primary.
- **input-field** — label di atas, bantuan opsional, pesan error di bawah
  (warna danger + ikon).
- **card** — pembungkus konten dengan border tipis; judul pakai `h3`.
- **badge-present / absent / fallback** — status presensi. `fallback` (mode
  cadangan) wajib tampil agar data manual tetap teraudit dan jelas berbeda.
- **Tabel rekap** — header `label` uppercase, baris zebra tipis, kolom angka
  rata kanan, kolom persentase memakai `mono`.

## Do's and Don'ts

**Do**
- Gunakan teal hanya untuk satu aksi utama per layar.
- Tampilkan timestamp dan status secara eksplisit (kepercayaan = transparansi).
- Gunakan `mono` untuk kode presensi & timestamp agar tidak salah baca.
- Pertahankan target sentuh ≥ 44px, termasuk tombol besar saat acara.

**Don't**
- Jangan pakai gradient, glassmorphism, atau bayangan berat.
- Jangan gunakan warna saja untuk menandai status — selalu tambahkan ikon/label.
- Jangan tambah kartu statistik hiasan tanpa data nyata.
- Jangan pakai lebih dari satu warna aksi; hindari indigo/violet generik.
- Jangan sembunyikan mode cadangan — data manual harus terlihat berbeda.
