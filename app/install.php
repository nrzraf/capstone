<?php
/**
 * Installer — membuat database, tabel, dan data contoh.
 * Buka: http://localhost/presensi-ormawa/install.php
 */
require_once __DIR__ . '/config.php';

$msg = [];
$err = null;

if (isset($_POST['do_install'])) {
    try {
        // 1) Koneksi tanpa dbname  buat database
        $root = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $root->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $root->exec('USE `' . DB_NAME . '`');

        // 2) Tabel
        $schema = [
            "DROP TABLE IF EXISTS audit_log",
            "DROP TABLE IF EXISTS notification",
            "DROP TABLE IF EXISTS attendance",
            "DROP TABLE IF EXISTS registration",
            "DROP TABLE IF EXISTS proposal",
            "DROP TABLE IF EXISTS event",
            "DROP TABLE IF EXISTS member",
            "DROP TABLE IF EXISTS role",
            "DROP TABLE IF EXISTS login_attempt",

            "CREATE TABLE role (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(30) NOT NULL UNIQUE,
                deskripsi VARCHAR(120)
            ) ENGINE=InnoDB",

            "CREATE TABLE member (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(120) NOT NULL,
                nim VARCHAR(30) NOT NULL UNIQUE,
                email VARCHAR(150) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                divisi VARCHAR(80),
                status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
                role_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_member_role FOREIGN KEY (role_id) REFERENCES role(id)
            ) ENGINE=InnoDB",

            "CREATE TABLE event (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(180) NOT NULL,
                jenis VARCHAR(60) NOT NULL,
                tanggal DATE NOT NULL,
                waktu TIME NOT NULL,
                lokasi VARCHAR(180) NOT NULL,
                kuota INT NOT NULL DEFAULT 0,
                deskripsi TEXT,
                status ENUM('draft','dipublikasikan','berlangsung','ditutup','dibatalkan') NOT NULL DEFAULT 'draft',
                alasan_batal VARCHAR(255) DEFAULT NULL,
                dibuat_oleh INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_event_member FOREIGN KEY (dibuat_oleh) REFERENCES member(id) ON DELETE SET NULL
            ) ENGINE=InnoDB",

            "CREATE TABLE registration (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id INT NOT NULL,
                member_id INT NOT NULL,
                kode_presensi CHAR(6) NOT NULL,
                qr_token CHAR(32) NOT NULL UNIQUE,
                status ENUM('terdaftar','hadir','tidak_hadir') NOT NULL DEFAULT 'terdaftar',
                registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_event_member (event_id, member_id),
                CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
                CONSTRAINT fk_reg_member FOREIGN KEY (member_id) REFERENCES member(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",

            "CREATE TABLE attendance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                registration_id INT NOT NULL,
                event_id INT NOT NULL,
                member_id INT NOT NULL,
                status ENUM('hadir','cadangan') NOT NULL DEFAULT 'hadir',
                metode ENUM('qr','manual') NOT NULL DEFAULT 'qr',
                scanned_by INT DEFAULT NULL,
                checkin_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_att_reg (registration_id),
                CONSTRAINT fk_att_reg FOREIGN KEY (registration_id) REFERENCES registration(id) ON DELETE CASCADE,
                CONSTRAINT fk_att_event FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
                CONSTRAINT fk_att_member FOREIGN KEY (member_id) REFERENCES member(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",

            "CREATE TABLE proposal (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id INT NOT NULL,
                status ENUM('diajukan','disetujui','ditolak','revisi') NOT NULL DEFAULT 'diajukan',
                catatan VARCHAR(255) DEFAULT NULL,
                diajukan_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                diputus_at DATETIME DEFAULT NULL,
                reviewer_id INT DEFAULT NULL,
                CONSTRAINT fk_prop_event FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",

            "CREATE TABLE notification (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT NOT NULL,
                event_id INT DEFAULT NULL,
                tipe VARCHAR(40) NOT NULL,
                isi VARCHAR(255) NOT NULL,
                dibaca TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_notif_member FOREIGN KEY (member_id) REFERENCES member(id) ON DELETE CASCADE
            ) ENGINE=InnoDB",

            "CREATE TABLE audit_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                actor_id INT DEFAULT NULL,
                aksi VARCHAR(60) NOT NULL,
                entitas VARCHAR(40) NOT NULL,
                entitas_id INT DEFAULT NULL,
                detail VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB",

            "CREATE TABLE login_attempt (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                berhasil TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_time (email, created_at)
            ) ENGINE=InnoDB",
        ];
        foreach ($schema as $sql) { $root->exec($sql); }
        $msg[] = 'Tabel berhasil dibuat (9 tabel).';

        // 3) Seed
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $roles = [
            ['anggota',  'Anggota / kader organisasi'],
            ['panitia',  'Panitia / pengurus acara'],
            ['pembina',  'Pembina ormawa (approver)'],
            ['admin',    'Admin sistem'],
        ];
        $rs = $root->prepare('INSERT INTO role (nama, deskripsi) VALUES (?, ?)');
        foreach ($roles as $r) { $rs->execute($r); }
        $msg[] = 'Peran (role) dibuat: anggota, panitia, pembina, admin.';

        $members = [
            ['Admin Sistem',       '1000000001', 'admin@ormawa.bakrie.ac.id',   'IT/PSDM',   'aktif', 4],
            ['Budi Santoso',       '1232001006', 'panitia@student.bakrie.ac.id', 'Acara',     'aktif', 2],
            ['Dr. Ratna Pembina',  '1000000002', 'pembina@ormawa.bakrie.ac.id', 'Kemahasiswaan','aktif', 3],
            ['Siti Aminah',        '1232001012', 'anggota@student.bakrie.ac.id', 'Humas',     'aktif', 1],
            ['Andi Pratama',       '1212001004', 'andi@student.bakrie.ac.id',    'Acara',     'aktif', 1],
            ['Dewi Lestari',       '1202721002', 'dewi@student.bakrie.ac.id',    'Dana Usaha','aktif', 1],
            ['Rafi Ibnusalis',     '1232001018', 'rafi@student.bakrie.ac.id',    'PSDM',      'aktif', 1],
        ];
        $ms = $root->prepare('INSERT INTO member (nama, nim, email, password_hash, divisi, status, role_id) VALUES (?,?,?,?,?,?,?)');
        foreach ($members as $m) { $ms->execute([$m[0],$m[1],$m[2],$hash,$m[3],$m[4],$m[5]]); }
        $msg[] = '7 akun contoh dibuat (password: password123).';

        // events
        $events = [
            ['Seminar Nasional Kepemimpinan', 'Seminar', '2026-05-12', '09:00:00', 'Aula Utama Universitas Bakrie', 150, 'Seminar kepemimpinan untuk seluruh pengurus dan anggota ormawa.', 'berlangsung', 2],
            ['Pelatihan Manajemen Organisasi', 'Pelatihan', '2026-05-18', '13:00:00', 'Ruang 302', 100, 'Pelatihan manajemen untuk pengurus baru.', 'dipublikasikan', 2],
            ['Rapat Kerja Tahunan', 'Rapat Kerja', '2026-05-20', '08:00:00', 'Ruang Rapat', 80, 'Rapat kerja penyusunan program kerja tahunan.', 'dipublikasikan', 2],
            ['Pengabdian Masyarakat Desa', 'Pengabdian Masyarakat', '2026-05-25', '07:00:00', 'Desa Sukamaju', 60, 'Kegiatan pengabdian masyarakat.', 'draft', 2],
        ];
        $es = $root->prepare('INSERT INTO event (nama, jenis, tanggal, waktu, lokasi, kuota, deskripsi, status, dibuat_oleh) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach ($events as $e) { $es->execute($e); }
        $msg[] = '4 acara contoh dibuat.';

        // registrations untuk event 1 (berlangsung) — untuk demo presensi
        $qr = fn() => strtoupper(bin2hex(random_bytes(16)));
        $regs = [
            [1, 4], [1, 5], [1, 6], [1, 7],  // Siti, Andi, Dewi, Rafi ke Seminar
            [2, 4], [2, 5],
            [3, 4],
        ];
        $reg = $root->prepare('INSERT INTO registration (event_id, member_id, kode_presensi, qr_token, status) VALUES (?,?,?,?,?)');
        $att = $root->prepare('INSERT INTO attendance (registration_id, event_id, member_id, status, metode, scanned_by, checkin_at) VALUES (?,?,?,?,?,?,?)');
        $n = 0;
        foreach ($regs as $r) {
            $kode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $reg->execute([$r[0], $r[1], $kode, $qr(), 'terdaftar']);
            $rid = (int)$root->lastInsertId();
            $n++;
            // presensi untuk event 1, kecuali satu orang (biar ada yang tidak hadir)
            if ($r[0] == 1 && $r[1] != 6) {
                $isManual = ($r[1] == 5);
                $att->execute([$rid, 1, $r[1], $isManual ? 'cadangan' : 'hadir', $isManual ? 'manual' : 'qr', 2, '2026-05-12 09:1' . random_int(0,9) . ':0' . random_int(0,9)]);
                $root->exec("UPDATE registration SET status='hadir' WHERE id=$rid");
            }
        }
        $msg[] = "$n pendaftaran contoh dibuat (sebagian sudah presensi).";

        $root->exec("INSERT INTO proposal (event_id, status, catatan, diputus_at, reviewer_id) VALUES (1,'disetujui','Disetujui untuk dilaksanakan', NOW(), 3)");
        $msg[] = 'Proposal contoh dibuat.';

        $ok = true;
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Inter,system-ui,sans-serif;background:#F8FAFC;color:#0F172A;display:grid;place-items:center;min-height:100vh;padding:24px}
.card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:32px;max-width:560px;width:100%;box-shadow:0 8px 24px rgba(15,23,42,.06)}
h1{font-size:22px;font-weight:800;letter-spacing:-.02em;margin-bottom:6px}
p.sub{color:#64748B;font-size:14px;margin-bottom:22px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;height:46px;border:none;border-radius:10px;background:#0F766E;color:#fff;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit}
.btn:hover{background:#0F172A}
ul{list-style:none;margin:18px 0}
li{padding:9px 0 9px 26px;position:relative;font-size:14px;border-bottom:1px solid #F1F5F9}
li:before{content:"";position:absolute;left:0;color:#047857;font-weight:800}
.msg{background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;padding:14px 16px;margin-bottom:18px}
.err{background:#FEF2F2;border-color:#FECACA;color:#BE123C}
table{width:100%;border-collapse:collapse;margin-top:8px;font-size:13.5px}
td{padding:8px;border-bottom:1px solid #F1F5F9}
td:first-child{color:#64748B;width:44%}
code{background:#F1F5F9;padding:2px 6px;border-radius:5px;font-size:13px}
a.go{display:inline-block;margin-top:16px;background:#0F172A;color:#fff;text-decoration:none;padding:12px 20px;border-radius:10px;font-weight:600}
</style>
</head>
<body>
<div class="card">
<?php if (!empty($ok)): ?>
  <div class="msg"><b>Instalasi berhasil!</b></div>
  <ul><?php foreach ($msg as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?></ul>
  <p style="font-size:14px;margin-top:6px;">Akun demo (semua password <code>password123</code>):</p>
  <table>
    <tr><td>Admin</td><td><code>admin@ormawa.bakrie.ac.id</code></td></tr>
    <tr><td>Panitia</td><td><code>panitia@student.bakrie.ac.id</code></td></tr>
    <tr><td>Pembina</td><td><code>pembina@ormawa.bakrie.ac.id</code></td></tr>
    <tr><td>Anggota</td><td><code>anggota@student.bakrie.ac.id</code></td></tr>
  </table>
  <a class="go" href="index.php"> Buka Aplikasi</a>
<?php else: ?>
  <h1>Instalasi Sistem Presensi Ormawa</h1>
  <p class="sub">Membuat database <code><?= DB_NAME ?></code>, 9 tabel, dan data contoh. Pastikan MySQL XAMPP aktif.</p>
  <?php if ($err): ?><div class="msg err"><b>Gagal:</b> <?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post">
    <button class="btn" name="do_install" value="1">Jalankan Instalasi</button>
  </form>
  <p class="sub" style="margin-top:16px;font-size:12.5px;"> Menjalankan ulang akan menghapus &amp; membuat ulang seluruh data.</p>
<?php endif; ?>
</div>
</body>
</html>
