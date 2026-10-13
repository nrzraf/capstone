<?php
/** FR-03 - Simpan pengajuan proposal (UC-03) */
require_role('panitia','admin');
require_once __DIR__ . '/../mailer.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php?r=events');
csrf_check();

$u = current_user();
$eventId = (int)($_POST['event_id'] ?? 0);
$ev = q_one("SELECT * FROM event WHERE id=?", [$eventId]);
if (!$ev) { flash('error', 'Acara tidak ditemukan.'); redirect('index.php?r=events'); }

$judul = trim($_POST['judul'] ?? '');
$ringkasan = trim($_POST['ringkasan'] ?? '');

// validasi: hanya boleh ajukan bila belum ada / berstatus revisi
$existing = q_one("SELECT * FROM proposal WHERE event_id=? ORDER BY id DESC LIMIT 1", [$eventId]);
if ($existing && !in_array($existing['status'], ['revisi'], true)) {
    flash('error', 'Proposal sudah diajukan.');
    redirect('index.php?r=event&id=' . $eventId);
}

// upload berkas (opsional)
$fileUrl = null;
if (!empty($_FILES['file']['name']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf','doc','docx'], true)) {
        flash('error', 'Format berkas harus PDF/DOC/DOCX.');
        redirect('index.php?r=proposal_form&event=' . $eventId);
    }
    if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
        flash('error', 'Ukuran berkas maksimal 10MB.');
        redirect('index.php?r=proposal_form&event=' . $eventId);
    }
    $dir = __DIR__ . '/../uploads/proposals';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $fname = 'prop_' . $eventId . '_' . time() . '.' . $ext;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $fname)) {
        $fileUrl = 'uploads/proposals/' . $fname;
    }
}

$catatan = $ringkasan !== '' ? $ringkasan : null;

if ($existing && $existing['status'] === 'revisi') {
    // kirim ulang setelah revisi
    q("UPDATE proposal SET judul=?, file_url=COALESCE(?, file_url), status='diajukan',
        catatan=?, diajukan_at=NOW(), diputus_at=NULL, reviewer_id=NULL
        WHERE id=?", [$judul, $fileUrl, $catatan, $existing['id']]);
    $propId = (int)$existing['id'];
    audit('proposal_resubmit', 'proposal', $propId, 'Ajukan ulang proposal: ' . $judul);
} else {
    q("INSERT INTO proposal (event_id, judul, file_url, status, catatan, diajukan_oleh) VALUES (?,?,?,'diajukan',?,?)",
      [$eventId, $judul, $fileUrl, $catatan, $u['id']]);
    $propId = (int) db()->lastInsertId();
    audit('proposal_submit', 'proposal', $propId, 'Ajukan proposal: ' . $judul);
}

// notifikasi ke pembina (FR-10)
$pembina = q_all("SELECT id, nama, email FROM member WHERE role_id=3 AND status='aktif'");
$body = email_template('Proposal Baru Perlu Persetujuan',
    'Proposal untuk acara <b>' . htmlspecialchars($ev['nama']) . '</b> telah diajukan dan menunggu persetujuan Anda.',
    'Tinjau Proposal', BASE_URL . '/index.php?r=proposals');
foreach ($pembina as $p) {
    q("INSERT INTO notification (member_id, event_id, tipe, isi) VALUES (?,?,'proposal_baru',?)",
      [$p['id'], $eventId, 'Proposal baru: ' . $ev['nama']]);
    send_mail($p['email'], '[Proposal] ' . $ev['nama'], $body, 'proposal_baru', $eventId);
}

flash('ok', 'Proposal berhasil diajukan ke pembina.');
redirect('index.php?r=event&id=' . $eventId);
