<?php
/**
 * Konfigurasi aplikasi — Event Attendance Universitas Bakrie
 * Project Capstone Kelompok 7 — Universitas Bakrie
 */

// ---- Database (default XAMPP: user root, tanpa password) ----
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'presensi_ormawa');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Aplikasi ----
define('APP_NAME', 'Event Attendance Universitas Bakrie');
define('APP_ORG', 'Organisasi Mahasiswa · Universitas Bakrie');
define('BASE_URL', '/presensi-ormawa');   // ubah jika folder di htdocs berbeda
define('SESSION_TIMEOUT', 1800);           // 30 menit (NFR-04)
define('MAX_LOGIN_ATTEMPTS', 5);           // FR-01
define('LOCKOUT_SECONDS', 900);            // 15 menit

// ---- Notifikasi email / SMTP (FR-10) ----
// Kosongkan SMTP_HOST bila belum ada mail server  email otomatis dicatat ke mail_log + notifikasi in-app.
define('SMTP_HOST', '');            // mis. 'smtp.gmail.com'
define('SMTP_PORT', 587);           // 587 (tls) / 465 (ssl) / 25
define('SMTP_SECURE', 'tls');       // '', 'tls', atau 'ssl'
define('SMTP_USER', '');            // mis. 'ormawa@gmail.com'
define('SMTP_PASS', '');            // app password
define('SMTP_FROM', 'no-reply@ormawa.bakrie.ac.id');
define('SMTP_FROM_NAME', 'Event Attendance Universitas Bakrie');

// ---- Zona waktu ----
date_default_timezone_set('Asia/Jakarta');

// ---- Tampilkan error saat pengembangan (matikan di produksi) ----
error_reporting(E_ALL);
ini_set('display_errors', '1');
