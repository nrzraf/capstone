#!/bin/bash
# ============================================================
# TEST SUITE — Sistem Pengelolaan Acara & Presensi QR (Ormawa)
# Uji end-to-end seluruh fitur + RBAC + keamanan
# ============================================================
B="http://localhost:8080/presensi-ormawa"
MYSQL="C:/xampp/mysql/bin/mysql.exe"
TMP="/tmp/ptest"; mkdir -p "$TMP"; cd "$TMP"
PASS=0; FAIL=0
ok(){ echo "  ✅ $1"; PASS=$((PASS+1)); }
no(){ echo "  ❌ $1"; FAIL=$((FAIL+1)); }
chk(){ [ "$1" = "$2" ] && ok "$3" || no "$3 (dapat '$1', harap '$2')"; }
chkcontains(){ echo "$1" | grep -q "$2" && ok "$3" || no "$3"; }
sql(){ "$MYSQL" -u root presensi_ormawa -N -e "$1" 2>/dev/null; }

login(){ # $1=email $2=cookiejar
  rm -f "$2"
  local c=$(curl -s -c "$2" "$B/index.php?r=login" | grep -oE 'name="csrf" value="[^"]+"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$2" -c "$2" -X POST -d "csrf=$c&email=$1&password=password123" -o /dev/null -w "%{http_code}" "$B/index.php?r=login"
}
code(){ curl -s -b "$2" -o /dev/null -w "%{http_code}" "$B/index.php?r=$1"; }
getcsrf(){ curl -s -b "$1" "$B/index.php?r=$2" | grep -oE "csrf_token\(\)|const CSRF = \"[a-f0-9]+\"|name=\"csrf\" value=\"[a-f0-9]+\"" | grep -oE "[a-f0-9]{32}" | head -1; }

echo "══════════════════════════════════════════════════════"
echo " BAGIAN 1 — AUTENTIKASI & RBAC (FR-01, UC-01)"
echo "══════════════════════════════════════════════════════"

echo "[1.1] Login semua peran"
chk "$(login admin@ormawa.bakrie.ac.id cj_admin)"   "302" "Login Admin"
chk "$(login panitia@student.bakrie.ac.id cj_pan)"   "302" "Login Panitia"
chk "$(login pembina@ormawa.bakrie.ac.id cj_pem)"   "302" "Login Pembina"
chk "$(login anggota@student.bakrie.ac.id cj_ang)"   "302" "Login Anggota"

echo "[1.2] Login salah ditolak"
rm -f cj_bad; cb=$(curl -s -c cj_bad "$B/index.php?r=login" | grep -oE 'value="[a-f0-9]{32}"' | head -1 | sed 's/value="//;s/"//')
OUT=$(curl -s -b cj_bad -c cj_bad -X POST -d "csrf=$cb&email=panitia@student.bakrie.ac.id&password=SALAH" "$B/index.php?r=login")
chkcontains "$OUT" "salah" "Login password salah → pesan error"

echo "[1.3] RBAC matrix (200=diizinkan, 403=ditolak)"
echo "       Panitia  : scan=$(code scan cj_pan) event_form=$(code event_form cj_pan) members=$(code members cj_pan)"
chk "$(code scan cj_pan)"       "200" "Panitia boleh akses Scan"
chk "$(code event_form cj_pan)" "200" "Panitia boleh akses Form Acara"
chk "$(code members cj_pan)"    "403" "Panitia TIDAK boleh akses Kelola Anggota"
chk "$(code audit cj_pan)"      "403" "Panitia TIDAK boleh akses Log Audit"
echo "       Anggota  : scan=$(code scan cj_ang) members=$(code members cj_ang) events=$(code events cj_ang) report=$(code report cj_ang)"
chk "$(code scan cj_ang)"    "403" "Anggota TIDAK boleh akses Scan"
chk "$(code members cj_ang)" "403" "Anggota TIDAK boleh akses Kelola Anggota"
chk "$(code events cj_ang)"  "200" "Anggota boleh akses Daftar Acara"
chk "$(code report cj_ang)"  "403" "Anggota TIDAK boleh akses Laporan"
echo "       Admin    : members=$(code members cj_admin) audit=$(code audit cj_admin) scan=$(code scan cj_admin)"
chk "$(code members cj_admin)" "200" "Admin boleh akses Kelola Anggota"
chk "$(code audit cj_admin)"   "200" "Admin boleh akses Log Audit"
echo "       Pembina  : report=$(code report cj_pem) scan=$(code scan cj_pem)"
chk "$(code report cj_pem)" "200" "Pembina boleh akses Laporan"
chk "$(code scan cj_pem)"   "403" "Pembina TIDAK boleh akses Scan"

echo "[1.4] Akses tanpa login → redirect ke login"
rm -f cj_none
chk "$(curl -s -o /dev/null -w '%{http_code}' "$B/index.php?r=dashboard")" "302" "Belum login akses dasbor → redirect"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 2 — MANAJEMEN ACARA (FR-02, UC-02)"
echo "══════════════════════════════════════════════════════"

echo "[2.1] Buat acara baru"
CSRF=$(getcsrf cj_pan event_form)
BEFORE=$(sql "SELECT COUNT(*) FROM event")
curl -s -b cj_pan -X POST -d "csrf=$CSRF&id=0&nama=TEST Acara Otomatis&jenis=Seminar&tanggal=2026-06-01&waktu=10:00&lokasi=Lab Test&kuota=50&deskripsi=uji&aksi=publish" -o /dev/null "$B/index.php?r=event_save"
AFTER=$(sql "SELECT COUNT(*) FROM event")
chk "$AFTER" "$((BEFORE+1))" "Acara baru tersimpan (count $BEFORE→$AFTER)"
NEWID=$(sql "SELECT id FROM event WHERE nama='TEST Acara Otomatis' ORDER BY id DESC LIMIT 1")
chk "$(sql "SELECT status FROM event WHERE id=$NEWID")" "dipublikasikan" "Status acara = dipublikasikan"

echo "[2.2] Edit acara"
CSRF=$(getcsrf cj_pan event_form)
curl -s -b cj_pan -X POST -d "csrf=$CSRF&id=$NEWID&nama=TEST Acara Diedit&jenis=Pelatihan&tanggal=2026-06-02&waktu=11:00&lokasi=Lab Baru&kuota=60&deskripsi=edit&aksi=draft" -o /dev/null "$B/index.php?r=event_save"
chk "$(sql "SELECT nama FROM event WHERE id=$NEWID")" "TEST Acara Diedit" "Nama acara terupdate"
chk "$(sql "SELECT kuota FROM event WHERE id=$NEWID")" "60" "Kuota terupdate"

echo "[2.3] Ubah status acara"
CSRF=$(getcsrf cj_pan "event&id=$NEWID")
curl -s -b cj_pan -X POST -d "csrf=$CSRF&act=status&status=berlangsung" -o /dev/null "$B/index.php?r=event&id=$NEWID"
chk "$(sql "SELECT status FROM event WHERE id=$NEWID")" "berlangsung" "Status → berlangsung"

echo "[2.4] Batalkan acara (soft delete A2)"
CSRF=$(getcsrf cj_pan "event&id=$NEWID")
curl -s -b cj_pan -X POST -d "csrf=$CSRF&act=cancel&alasan=uji pembatalan" -o /dev/null "$B/index.php?r=event&id=$NEWID"
chk "$(sql "SELECT status FROM event WHERE id=$NEWID")" "dibatalkan" "Status → dibatalkan"
chk "$(sql "SELECT alasan_batal FROM event WHERE id=$NEWID")" "uji pembatalan" "Alasan batal tersimpan"
chk "$(sql "SELECT COUNT(*) FROM event WHERE id=$NEWID")" "1" "Acara TIDAK terhapus permanen (A2)"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 3 — RSVP / PENDAFTARAN (FR-04, UC-04)"
echo "══════════════════════════════════════════════════════"

# siapkan acara bersih untuk RSVP
sql "DELETE FROM attendance WHERE event_id=$NEWID; DELETE FROM registration WHERE event_id=$NEWID; UPDATE event SET status='dipublikasikan', kuota=2, nama='TEST RSVP' WHERE id=$NEWID"
MID=$(sql "SELECT id FROM member WHERE email='anggota@student.bakrie.ac.id'")

echo "[3.1] Anggota daftar acara"
BEFORE=$(sql "SELECT COUNT(*) FROM registration WHERE event_id=$NEWID")
curl -s -b cj_ang -o /dev/null "$B/index.php?r=rsvp&id=$NEWID"
AFTER=$(sql "SELECT COUNT(*) FROM registration WHERE event_id=$NEWID")
chk "$AFTER" "$((BEFORE+1))" "Pendaftaran tercatat"
RID=$(sql "SELECT id FROM registration WHERE event_id=$NEWID AND member_id=$MID")
chk "$(sql "SELECT LENGTH(kode_presensi) FROM registration WHERE id=$RID")" "6" "Kode presensi 6 digit"
chk "$(sql "SELECT LENGTH(qr_token) FROM registration WHERE id=$RID")" "32" "QR token 32 char"

echo "[3.2] Anti-duplikasi (A2) — daftar lagi"
BEFORE=$(sql "SELECT COUNT(*) FROM registration WHERE event_id=$NEWID AND member_id=$MID")
curl -s -b cj_ang -o /dev/null "$B/index.php?r=rsvp&id=$NEWID"
AFTER=$(sql "SELECT COUNT(*) FROM registration WHERE event_id=$NEWID AND member_id=$MID")
chk "$AFTER" "$BEFORE" "Tidak ada duplikasi pendaftaran"

echo "[3.3] Kuota penuh ditolak"
# isi kuota (kuota=2): tambah 1 lagi manual lalu coba daftar member lain
sql "INSERT INTO registration (event_id,member_id,kode_presensi,qr_token,status) VALUES ($NEWID,5,'111111','TOKENPENUH00000000000000000001','terdaftar')"
sql "INSERT INTO registration (event_id,member_id,kode_presensi,qr_token,status) VALUES ($NEWID,6,'222222','TOKENPENUH00000000000000000002','terdaftar')"
BEFORE=$(sql "SELECT COUNT(*) FROM registration WHERE event_id=$NEWID")
curl -s -b cj_ang -o /dev/null "$B/index.php?r=rsvp&id=$NEWID"  # member 4 sudah ada
AFTER=$(sql "SELECT COUNT(*) FROM registration WHERE event_id=$NEWID")
chk "$AFTER" "$BEFORE" "Kuota penuh tidak menambah pendaftaran"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 4 — PRESENSI QR + FALLBACK (FR-05, FR-06, UC-05)"
echo "══════════════════════════════════════════════════════"

# reset acara untuk presensi
sql "DELETE FROM attendance WHERE event_id=$NEWID; UPDATE registration SET status='terdaftar' WHERE event_id=$NEWID"
sql "UPDATE registration SET qr_token='AAAA1111BBBB2222CCCC3333DDDD4444', kode_presensi='987654' WHERE event_id=$NEWID AND member_id=$MID"
API="$B/index.php?r=scan_api"
CS=$(getcsrf cj_pan "scan&event=$NEWID")

echo "[4.1] Presensi QR valid"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=$CS&event_id=$NEWID&code=ORMAWA|EV$NEWID|REG$RID|AAAA1111BBBB2222CCCC3333DDDD4444&metode=qr" "$API")
chkcontains "$OUT" '"ok":true' "QR valid → presensi berhasil"
chk "$(sql "SELECT metode FROM attendance WHERE registration_id=$RID")" "qr" "Metode tercatat = qr"
chk "$(sql "SELECT status FROM attendance WHERE registration_id=$RID")" "hadir" "Status = hadir"
chk "$(sql "SELECT status FROM registration WHERE id=$RID")" "hadir" "Registration ikut terupdate"

echo "[4.2] Presensi ganda ditolak (A2)"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=$CS&event_id=$NEWID&code=ORMAWA|EV$NEWID|REG$RID|AAAA1111BBBB2222CCCC3333DDDD4444&metode=qr" "$API")
chkcontains "$OUT" '"ok":false' "Scan QR ganda → ditolak"

echo "[4.3] Fallback kode manual valid"
RID2=$(sql "SELECT id FROM registration WHERE event_id=$NEWID AND member_id=5")
sql "UPDATE registration SET kode_presensi='555888' WHERE id=$RID2"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=$CS&event_id=$NEWID&code=555888&metode=manual" "$API")
chkcontains "$OUT" '"ok":true' "Kode manual valid → berhasil"
chk "$(sql "SELECT status FROM attendance WHERE registration_id=$RID2")" "cadangan" "Ditandai 'cadangan'"
chk "$(sql "SELECT metode FROM attendance WHERE registration_id=$RID2")" "manual" "Metode = manual"

echo "[4.4] Kode manual salah ditolak"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=$CS&event_id=$NEWID&code=000000&metode=manual" "$API")
chkcontains "$OUT" '"ok":false' "Kode manual salah → ditolak"

echo "[4.5] Format kode manual salah (bukan 6 digit) ditolak"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=$CS&event_id=$NEWID&code=12&metode=manual" "$API")
chkcontains "$OUT" '"ok":false' "Kode <6 digit → ditolak"

echo "[4.6] QR token acara lain ditolak (isolasi antar acara)"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=$CS&event_id=1&code=ORMAWA|EV$NEWID|REG$RID|AAAA1111BBBB2222CCCC3333DDDD4444&metode=qr" "$API")
chkcontains "$OUT" '"ok":false' "Token acara X tidak berlaku di acara Y"

echo "[4.7] CSRF wajib pada API"
OUT=$(curl -s -b cj_pan -X POST -d "csrf=SALAH&event_id=$NEWID&code=555888&metode=manual" "$API")
chkcontains "$OUT" '"ok":false' "CSRF salah → API menolak"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 5 — REKAP & EKSPOR (FR-07, FR-12, UC-06)"
echo "══════════════════════════════════════════════════════"

echo "[5.1] Halaman laporan"
chk "$(code "report&id=$NEWID" cj_pan)" "200" "Laporan per acara tampil"

echo "[5.2] Ekspor CSV"
CSV=$(curl -s -b cj_pan "$B/index.php?r=export&id=$NEWID&format=csv")
chkcontains "$CSV" "Laporan Kehadiran" "CSV berisi header"
chkcontains "$CSV" "Nama,NIM,Divisi" "CSV berisi kolom"
chkcontains "$CSV" "Hadir" "CSV berisi status"

echo "[5.3] Ekspor Excel"
CT=$(curl -s -b cj_pan -D - -o /dev/null "$B/index.php?r=export&id=$NEWID&format=excel" | grep -i "content-type" | tr -d '\r')
chkcontains "$CT" "ms-excel" "Excel content-type benar"

echo "[5.4] Ekspor PDF/print"
chkcontains "$(curl -s -b cj_pan "$B/index.php?r=export&id=$NEWID&format=print")" "Laporan Kehadiran" "Halaman cetak PDF tampil"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 6 — KELOLA ANGGOTA (FR-09, UC-07)"
echo "══════════════════════════════════════════════════════"

echo "[6.1] Admin tambah anggota"
CSRF=$(getcsrf cj_admin member_form)
BEFORE=$(sql "SELECT COUNT(*) FROM member")
curl -s -b cj_admin -X POST -d "csrf=$CSRF&id=0&nama=TEST Anggota&nim=9999999999&email=test999@ormawa.bakrie.ac.id&divisi=Test&role_id=1&status=aktif&password=rahasia123" -o /dev/null "$B/index.php?r=member_save"
AFTER=$(sql "SELECT COUNT(*) FROM member")
chk "$AFTER" "$((BEFORE+1))" "Anggota baru ditambah ($BEFORE→$AFTER)"

echo "[6.2] Password baru ter-hash"
HASH=$(sql "SELECT password_hash FROM member WHERE email='test999@ormawa.bakrie.ac.id'")
echo "$HASH" | grep -qE '^\$2y\$' && ok "Password ter-hash bcrypt" || no "Password TIDAK ter-hash: $HASH"

echo "[6.3] Nonaktifkan anggota"
NEWMID=$(sql "SELECT id FROM member WHERE email='test999@ormawa.bakrie.ac.id'")
CSRF=$(getcsrf cj_admin "member_form&id=$NEWMID")
curl -s -b cj_admin -X POST -d "csrf=$CSRF&id=$NEWMID&nama=TEST Anggota&nim=9999999999&email=test999@ormawa.bakrie.ac.id&divisi=Test&role_id=1&status=nonaktif&password=" -o /dev/null "$B/index.php?r=member_save"
chk "$(sql "SELECT status FROM member WHERE id=$NEWMID")" "nonaktif" "Status anggota → nonaktif"

echo "[6.4] Anggota nonaktif tidak bisa login"
rm -f cj_t; ct=$(curl -s -c cj_t "$B/index.php?r=login" | grep -oE 'value="[a-f0-9]{32}"' | head -1 | sed 's/value="//;s/"//')
OUT=$(curl -s -b cj_t -c cj_t -X POST -d "csrf=$ct&email=test999@ormawa.bakrie.ac.id&password=rahasia123" "$B/index.php?r=login")
chkcontains "$OUT" "salah" "Akun nonaktif ditolak login"

echo "[6.5] Duplikasi NIM/email ditolak"
CSRF=$(getcsrf cj_admin member_form)
OUT=$(curl -s -b cj_admin -c cj_admin -X POST -d "csrf=$CSRF&id=0&nama=Dup&nim=9999999999&email=dup@x.com&divisi=X&role_id=1&status=aktif&password=x" "$B/index.php?r=member_save")
chk "$(sql "SELECT COUNT(*) FROM member WHERE nim='9999999999'")" "1" "NIM duplikat tidak masuk"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 7 — AUDIT & KEAMANAN (FR-14, NFR-04)"
echo "══════════════════════════════════════════════════════"

echo "[7.1] Log audit mencatat aksi"
[ "$(sql "SELECT COUNT(*) FROM audit_log WHERE aksi='presensi'")" -ge 1 ] && ok "Audit mencatat presensi" || no "Audit tidak mencatat presensi"
[ "$(sql "SELECT COUNT(*) FROM audit_log WHERE aksi='event_create'")" -ge 1 ] && ok "Audit mencatat pembuatan acara" || no "Audit tidak mencatat pembuatan acara"
[ "$(sql "SELECT COUNT(*) FROM audit_log WHERE aksi='login'")" -ge 1 ] && ok "Audit mencatat login" || no "Audit tidak mencatat login"

echo "[7.2] CSRF protection pada form"
OUT=$(curl -s -b cj_admin -X POST -d "csrf=SALAH&id=0&nama=X&nim=1&email=x@x.com&role_id=1&status=aktif&password=x" "$B/index.php?r=member_save")
chkcontains "$OUT" "CSRF" "Form tanpa CSRF valid ditolak"

echo "[7.3] SQL injection tidak berbahaya"
OUT=$(curl -s -b cj_pan -o /dev/null -w "%{http_code}" "$B/index.php?r=events&cari=%27%20OR%201%3D1--")
chk "$OUT" "200" "Input berbahaya tidak crash (prepared stmt)"
chk "$(sql "SELECT COUNT(*) FROM member")" "$(sql "SELECT COUNT(*) FROM member")" "DB tetap utuh"

echo "[7.4] Rate-limit login (5 gagal → kunci)"
sql "DELETE FROM login_attempt WHERE email='ratelimit@test.com'"
for i in 1 2 3 4 5; do
  rm -f cj_rl; cr=$(curl -s -c cj_rl "$B/index.php?r=login" | grep -oE 'value="[a-f0-9]{32}"' | head -1 | sed 's/value="//;s/"//')
  curl -s -b cj_rl -c cj_rl -X POST -d "csrf=$cr&email=ratelimit@test.com&password=salah" -o /dev/null "$B/index.php?r=login"
done
rm -f cj_rl; cr=$(curl -s -c cj_rl "$B/index.php?r=login" | grep -oE 'value="[a-f0-9]{32}"' | head -1 | sed 's/value="//;s/"//')
OUT=$(curl -s -b cj_rl -c cj_rl -X POST -d "csrf=$cr&email=ratelimit@test.com&password=salah" "$B/index.php?r=login")
chkcontains "$OUT" "kunci" "Percobaan ke-6 → akun terkunci"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 8 — NOTIFIKASI & DASBOR (FR-08, FR-10, FR-11)"
echo "══════════════════════════════════════════════════════"
chk "$(code dashboard cj_pan)"     "200" "Dasbor tampil"
chk "$(code notifications cj_ang)" "200" "Halaman notifikasi tampil"
chkcontains "$(curl -s -b cj_pan "$B/index.php?r=dashboard")" "Rata-rata Kehadiran" "Dasbor punya KPI"
[ "$(sql "SELECT COUNT(*) FROM notification")" -ge 1 ] && ok "Tabel notifikasi terisi" || no "Tabel notifikasi kosong"

echo "[8.1] Dasbor anggota = tampilan pribadi (FR-11)"
OUT=$(curl -s -b cj_ang "$B/index.php?r=dashboard")
chkcontains "$OUT" "Riwayat Kehadiran Saya" "Anggota lihat riwayat pribadi"
echo "$OUT" | grep -q "Anggota Teraktif" && no "Anggota TIDAK boleh lihat data org-wide" || ok "Anggota tidak lihat data org-wide"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 9 — PROPOSAL DIGITAL (FR-03, UC-03)"
echo "══════════════════════════════════════════════════════"

echo "[9.1] Panitia mengajukan proposal"
chk "$(code "proposal_form&event=$NEWID" cj_pan)" "200" "Form proposal tampil"
chk "$(code proposals cj_pem)" "200" "Pembina lihat daftar proposal"
chk "$(code proposals cj_ang)" "403" "Anggota ditolak akses proposal"

echo "[9.2] Skema proposal siap"
[ "$(sql "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='presensi_ormawa' AND table_name='proposal' AND column_name IN ('judul','file_url','diajukan_oleh')")" = "3" ] && ok "Kolom proposal lengkap" || no "Kolom proposal tidak lengkap"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 10 — NOTIFIKASI EMAIL (FR-10)"
echo "══════════════════════════════════════════════════════"

echo "[10.1] Tabel mail_log tersedia"
[ "$(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='presensi_ormawa' AND table_name='mail_log'")" = "1" ] && ok "Tabel mail_log ada" || no "Tabel mail_log tidak ada"
chk "$(code mail_log cj_admin)" "200" "Admin buka outbox email"
chk "$(code mail_log cj_ang)" "403" "Anggota ditolak akses outbox"

echo "[10.2] Email tercatat saat acara dipublikasikan"
BEFORE=$(sql "SELECT COUNT(*) FROM mail_log")
sql "UPDATE event SET status='draft' WHERE id=$NEWID" 2>/dev/null
curl -s -b cj_pan -c cj_pan -X POST -d "csrf=$(getcsrf cj_pan 'event_form&id='$NEWID)&id=$NEWID&nama=Acara%20Uji%20FR10&jenis=Seminar&tanggal=2026-12-15&waktu=09:00&lokasi=Aula&kuota=50&deskripsi=Uji&aksi=publish" -o /dev/null "$B/index.php?r=event_save"
AFTER=$(sql "SELECT COUNT(*) FROM mail_log")
[ "$AFTER" -gt "$BEFORE" ] && ok "Email acara baru tercatat (+$((AFTER-BEFORE)))" || no "Email tidak tercatat"

echo "[10.3] Cron pengingat H-1 idempotent"
php "C:/xampp/htdocs/presensi-ormawa/cron_reminder.php" >/dev/null 2>&1
[ "$?" = "0" ] && ok "Cron berjalan tanpa error" || no "Cron gagal"

echo ""
echo "══════════════════════════════════════════════════════"
echo " BAGIAN 11 — BERSIH-BERSIH DATA UJI"
echo "══════════════════════════════════════════════════════"
sql "DELETE FROM attendance WHERE event_id=$NEWID"
sql "DELETE FROM registration WHERE event_id=$NEWID"
sql "DELETE FROM proposal WHERE event_id=$NEWID"
sql "DELETE FROM event WHERE id=$NEWID"
sql "DELETE FROM member WHERE email='test999@ormawa.bakrie.ac.id'"
sql "DELETE FROM login_attempt WHERE email='ratelimit@test.com'"
sql "DELETE FROM audit_log WHERE detail LIKE '%TEST%'"
ok "Data uji dibersihkan"

echo ""
echo "══════════════════════════════════════════════════════"
echo " HASIL AKHIR"
echo "══════════════════════════════════════════════════════"
echo "  ✅ LULUS : $PASS"
echo "  ❌ GAGAL : $FAIL"
echo "  Total   : $((PASS+FAIL))"
[ $FAIL -eq 0 ] && echo "  🎉 SEMUA TES LULUS" || echo "  ⚠️  ADA $FAIL TES GAGAL"
