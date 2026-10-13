<?php
/**
 * Migrasi — menambah fitur FR-03 (proposal) & FR-10 (notifikasi email).
 * Aman dijalankan berulang (idempotent). Buka:
 *   http://localhost:8080/presensi-ormawa/migrate.php
 */
require_once __DIR__ . '/config.php';

$log = [];
$err = null;

function col_exists(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=? AND table_name=? AND column_name=?");
    $st->execute([DB_NAME, $table, $col]);
    return (int)$st->fetchColumn() > 0;
}
function table_exists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_name=?");
    $st->execute([DB_NAME, $table]);
    return (int)$st->fetchColumn() > 0;
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // ---- FR-03: kolom tambahan pada proposal ----
    if (!col_exists($pdo, 'proposal', 'file_url')) {
        $pdo->exec("ALTER TABLE proposal ADD COLUMN file_url VARCHAR(255) DEFAULT NULL AFTER event_id");
        $log[] = "proposal.file_url ditambahkan";
    }
    if (!col_exists($pdo, 'proposal', 'diajukan_oleh')) {
        $pdo->exec("ALTER TABLE proposal ADD COLUMN diajukan_oleh INT DEFAULT NULL AFTER file_url");
        $log[] = "proposal.diajukan_oleh ditambahkan";
    }
    if (!col_exists($pdo, 'proposal', 'judul')) {
        $pdo->exec("ALTER TABLE proposal ADD COLUMN judul VARCHAR(180) DEFAULT NULL AFTER event_id");
        $log[] = "proposal.judul ditambahkan";
    }

    // ---- FR-10: tabel mail_log (outbox email) ----
    if (!table_exists($pdo, 'mail_log')) {
        $pdo->exec("CREATE TABLE mail_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            penerima VARCHAR(150) NOT NULL,
            subjek VARCHAR(200) NOT NULL,
            isi TEXT,
            tipe VARCHAR(40) DEFAULT NULL,
            event_id INT DEFAULT NULL,
            status ENUM('terkirim','gagal','log') NOT NULL DEFAULT 'log',
            error VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tipe (tipe)
        ) ENGINE=InnoDB");
        $log[] = "Tabel mail_log dibuat";
    }

    // ---- FR-10: tanda pengingat H-1 sudah terkirim (idempotent) ----
    if (!col_exists($pdo, 'event', 'h1_sent_at')) {
        $pdo->exec("ALTER TABLE event ADD COLUMN h1_sent_at DATETIME DEFAULT NULL");
        $log[] = "event.h1_sent_at ditambahkan";
    }
    if (!col_exists($pdo, 'event', 'perubahan_dikirim_at')) {
        $pdo->exec("ALTER TABLE event ADD COLUMN perubahan_dikirim_at DATETIME DEFAULT NULL");
        $log[] = "event.perubahan_dikirim_at ditambahkan";
    }

    // folder upload
    $up = __DIR__ . '/uploads/proposals';
    if (!is_dir($up)) { @mkdir($up, 0777, true); }
    if (is_dir($up)) $log[] = "Folder uploads/proposals siap";

    if (!$log) $log[] = "Tidak ada perubahan (sudah ter-migrasi).";
} catch (Throwable $e) {
    $err = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Migrasi — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Inter,system-ui,sans-serif;background:#F8FAFC;color:#0F172A;display:grid;place-items:center;min-height:100vh;padding:24px}
.card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:32px;max-width:560px;width:100%;box-shadow:0 8px 24px rgba(15,23,42,.06)}
h1{font-size:22px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
p.sub{color:#64748B;font-size:14px;margin-bottom:20px}
ul{list-style:none;margin:16px 0}
li{padding:9px 0 9px 26px;position:relative;font-size:14px;border-bottom:1px solid #F1F5F9}
li:before{content:"";position:absolute;left:0;color:#047857;font-weight:800}
.msg{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:14px 16px;margin-bottom:16px}
.err{background:#FEF2F2;border-color:#FECACA;color:#BE123C}
a.go{display:inline-block;margin-top:16px;background:#0F766E;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:600}
</style></head><body>
<div class="card">
<?php if ($err): ?>
  <h1>Migrasi Gagal</h1>
  <div class="msg err"><?= htmlspecialchars($err) ?></div>
  <p class="sub">Pastikan database sudah diinstall (<a href="install.php">install.php</a>) dan MySQL aktif.</p>
<?php else: ?>
  <h1>Migrasi Berhasil</h1>
  <p class="sub">Fitur FR-03 (proposal digital) &amp; FR-10 (notifikasi email) ditambahkan.</p>
  <div class="msg"><b>Perubahan diterapkan:</b></div>
  <ul><?php foreach ($log as $l): ?><li><?= htmlspecialchars($l) ?></li><?php endforeach; ?></ul>
  <a class="go" href="index.php?r=dashboard"> Buka Aplikasi</a>
<?php endif; ?>
</div></body></html>
