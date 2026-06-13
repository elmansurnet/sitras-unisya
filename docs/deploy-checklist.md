# DEPLOY CHECKLIST — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.0 | Tanggal: 2026-06-13

---

## PANDUAN PENGGUNAAN

Checklist ini wajib diselesaikan **100%** sebelum deployment ke production.
Beri tanda `[x]` pada setiap item yang sudah diverifikasi.
Item bertanda 🔴 adalah **BLOCKER** — deployment tidak boleh dilanjutkan jika belum selesai.

---

## FASE 1 — PRE-DEPLOY: KODE & DEPENDENCY

### 1.1 Security Audit
- [ ] 🔴 `composer audit` → **0 vulnerabilities**
- [ ] 🔴 `npm audit --audit-level=high` → **0 high/critical vulnerabilities**
- [ ] 🔴 CSP header tidak menyertakan `unsafe-eval` di production build
- [ ] Review `docs/security-audit.md` — semua item medium sudah dimitigasi atau diterima dengan justifikasi

### 1.2 Environment & Configuration
- [ ] 🔴 `.env` production sudah dibuat berdasarkan `.env.example`
- [ ] 🔴 `APP_ENV=production`
- [ ] 🔴 `APP_DEBUG=false`
- [ ] 🔴 `APP_KEY` sudah di-generate (`php artisan key:generate`)
- [ ] 🔴 `TELESCOPE_ENABLED=false`
- [ ] `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sudah diisi
- [ ] `REDIS_PASSWORD` sudah diisi (bukan default tanpa auth)
- [ ] `WHATSAPP_API_KEY` dan `WHATSAPP_SENDER` sudah diisi
- [ ] `SANCTUM_STATEFUL_DOMAINS` dan `FRONTEND_URL` sesuai domain production
- [ ] `MAIL_*` sudah dikonfigurasi dan ditest kirim email
- [ ] `EMPLOYER_TOKEN_EXPIRY_DAYS=30` dikonfirmasi
- [ ] File `.env` ada di `.gitignore` dan **tidak pernah di-commit**

### 1.3 Kode & Build
- [ ] 🔴 Semua test lulus: `php artisan test` → 0 failures, 0 errors
- [ ] 🔴 Frontend build production: `npm run build` di folder `frontend/`
- [ ] Tidak ada `dd()`, `dump()`, `var_dump()`, `console.log()` di kode production
- [ ] Tidak ada `// TODO` atau placeholder code yang tersisa
- [ ] `composer install --no-dev --optimize-autoloader` (tanpa dev dependencies)
- [ ] `composer.lock` dan `package-lock.json` di-commit ke Git

---

## FASE 2 — SERVER: INFRASTRUKTUR

### 2.1 OS & Software Versions
- [ ] Ubuntu 22.04 LTS (bukan 20.04 atau yang lebih lama)
- [ ] PHP 8.3 (bukan 8.1 atau 8.2)
- [ ] PHP-FPM 8.3 terinstall dan berjalan
- [ ] Nginx 1.24+ terinstall
- [ ] MySQL 8.0+ terinstall dan berjalan
- [ ] Redis 7.x terinstall dan berjalan
- [ ] Node.js 20.x terinstall (untuk build frontend)
- [ ] Supervisor terinstall

### 2.2 PHP-FPM Configuration
- [ ] Pool config `/etc/php/8.3/fpm/pool.d/sitras.conf` sudah dibuat sesuai `04_ARCHITECTURE.md §7`
- [ ] `expose_php = Off` dikonfirmasi
- [ ] `display_errors = off` dikonfirmasi
- [ ] `upload_max_filesize = 10M` dan `post_max_size = 12M`
- [ ] `memory_limit = 256M`
- [ ] PHP-FPM restart setelah konfigurasi: `systemctl restart php8.3-fpm`

### 2.3 MySQL Database
- [ ] 🔴 Database `sitras_unisya` sudah dibuat
- [ ] 🔴 User MySQL `sitras_user` sudah dibuat dengan password kuat
- [ ] 🔴 User MySQL hanya punya hak: `SELECT, INSERT, UPDATE, DELETE` (bukan `SUPER`, `FILE`, `PROCESS`)
- [ ] Verifikasi hak user: `SHOW GRANTS FOR 'sitras_user'@'localhost';`
- [ ] MySQL bind-address = `127.0.0.1` (tidak listen ke luar)
- [ ] MySQL root password sudah diubah dari default

### 2.4 Redis
- [ ] 🔴 Redis dilindungi dengan password (`requirepass` di `redis.conf`)
- [ ] Redis bind ke `127.0.0.1` (tidak listen ke luar)
- [ ] Verifikasi koneksi: `redis-cli -a {password} ping` → PONG

### 2.5 Firewall (UFW)
- [ ] 🔴 UFW aktif: `ufw status`
- [ ] 🔴 Hanya port 80 (HTTP), 443 (HTTPS), 22 (SSH) yang terbuka
- [ ] Port 3306 (MySQL) dan 6379 (Redis) **TIDAK** terbuka ke luar
- [ ] Port 8000 (Laravel dev server) **TIDAK** terbuka ke luar

```bash
# Verifikasi:
ufw status verbose
# Expected:
# 22/tcp    ALLOW
# 80/tcp    ALLOW
# 443       ALLOW
```

### 2.6 SSH Security
- [ ] SSH menggunakan key-based authentication
- [ ] `PasswordAuthentication no` di `/etc/ssh/sshd_config`
- [ ] Root login dinonaktifkan (`PermitRootLogin no`)
- [ ] SSH port default 22 (atau ubah ke port non-standar untuk keamanan tambahan)

---

## FASE 3 — APLIKASI: LARAVEL

### 3.1 Direktori & Permissions
- [ ] 🔴 Aplikasi di `/var/www/sitras-unisya/`
- [ ] 🔴 `storage/` writable: `chown -R www-data:www-data storage/ bootstrap/cache/`
- [ ] 🔴 `chmod -R 755 storage/ bootstrap/cache/`
- [ ] `storage/app/private/` sudah ada (bukan di public)
- [ ] `storage/app/reports/` sudah ada
- [ ] Document root Nginx mengarah ke `/var/www/sitras-unisya/public`

### 3.2 Artisan Commands
```bash
# Jalankan secara berurutan:
- [ ] php artisan config:cache
- [ ] php artisan route:cache
- [ ] php artisan view:cache
- [ ] php artisan event:cache
- [ ] php artisan migrate --force
- [ ] php artisan db:seed --force       # Seeder awal saja (superadmin, master data)
- [ ] php artisan storage:link          # Jika ada symlink public storage (hanya untuk file non-private)
```

### 3.3 Queue Worker (Supervisor)
- [ ] 🔴 Config Supervisor sudah dibuat sesuai `04_ARCHITECTURE.md §5.3`
- [ ] 🔴 Worker `sitras-worker-default` berjalan (2 processes, queue: high,default)
- [ ] 🔴 Worker `sitras-worker-low` berjalan (1 process, queue: low)
- [ ] Verifikasi: `supervisorctl status`
- [ ] Supervisor autostart: `systemctl enable supervisor`

### 3.4 Scheduler (Cron)
- [ ] 🔴 Crontab untuk `www-data` sudah dikonfigurasi:
```bash
* * * * * cd /var/www/sitras-unisya && php artisan schedule:run >> /dev/null 2>&1
```
- [ ] Verifikasi: `crontab -u www-data -l`
- [ ] Test scheduler: `php artisan schedule:list` menampilkan semua jadwal

### 3.5 Log Rotation
- [ ] Config logrotate sudah dibuat di `/etc/logrotate.d/sitras`:
```
/var/www/sitras-unisya/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 640 www-data www-data
}
```

---

## FASE 4 — NGINX & SSL

### 4.1 Nginx Configuration
- [ ] 🔴 Config Nginx sudah dibuat sesuai `04_ARCHITECTURE.md §6`
- [ ] 🔴 Rate limiting zones aktif (otp, auth, api)
- [ ] 🔴 Security headers terpasang (X-Frame-Options, X-XSS-Protection, X-Content-Type-Options, HSTS, CSP, Referrer-Policy, Permissions-Policy)
- [ ] 🔴 CSP tidak menyertakan `unsafe-eval` di production
- [ ] Blokir akses ke `.env`, `.git`, `storage/logs`, `bootstrap/cache`, `vendor`
- [ ] `autoindex off` aktif
- [ ] `fastcgi_hide_header X-Powered-By` aktif
- [ ] Gzip compression aktif
- [ ] SPA fallback (`try_files $uri $uri/ /index.php?$query_string`) aktif
- [ ] Config test: `nginx -t` → `syntax is ok, test is successful`
- [ ] Reload: `systemctl reload nginx`

### 4.2 SSL / TLS
- [ ] 🔴 Sertifikat SSL valid (Let's Encrypt atau institutional cert)
- [ ] 🔴 HTTPS aktif dan redirect HTTP → HTTPS berfungsi
- [ ] 🔴 HSTS header aktif (`max-age=31536000; includeSubDomains; preload`)
- [ ] TLS 1.2 minimum dikonfigurasi
- [ ] TLS 1.3 diutamakan
- [ ] Verifikasi SSL: `curl -I https://tracer.unisya.ac.id` → 200 OK, HSTS header ada
- [ ] Test SSL grade: https://www.ssllabs.com/ssltest/ → minimal grade A

### 4.3 Security Headers Verification
- [ ] Test via securityheaders.com → minimal grade A
- [ ] Verifikasi CSP tidak ada `unsafe-eval`:
```bash
curl -I https://tracer.unisya.ac.id | grep -i content-security-policy
```
- [ ] Verifikasi tidak ada `X-Powered-By` header:
```bash
curl -I https://tracer.unisya.ac.id | grep -i x-powered-by
# Expected: (kosong — tidak ada header tersebut)
```

---

## FASE 5 — FUNGSIONAL: SMOKE TEST

### 5.1 API Health Check
```bash
# Public endpoint (tidak butuh auth)
curl https://tracer.unisya.ac.id/api/v1/public/info
# Expected: 200 OK dengan info sistem

# Endpoint tanpa token (harus 401)
curl https://tracer.unisya.ac.id/api/v1/admin/alumni
# Expected: 401 Unauthenticated
```

### 5.2 Authentication Flow
- [ ] Login via email/password berhasil → token diterima
- [ ] Request OTP berhasil → OTP terkirim via WA/Email
- [ ] Verifikasi OTP berhasil → token diterima
- [ ] Token tidak valid → 401
- [ ] Login dengan kredensial salah 5x → 423 (akun terkunci)

### 5.3 RBAC Smoke Test
- [ ] Alumni tidak bisa akses endpoint admin → 403
- [ ] Admin tidak bisa hapus permanen → 403
- [ ] Superadmin bisa akses audit log → 200
- [ ] Employer token valid → akses survei berhasil
- [ ] Employer token expired → 401

### 5.4 File Upload Test
- [ ] Upload foto alumni (JPEG ≤2MB) → berhasil, signed URL bisa diakses
- [ ] Upload foto dengan tipe tidak diizinkan (PDF) → 422
- [ ] Upload melebihi batas ukuran → 422

### 5.5 Notification Test
- [ ] Kirim WA test ke nomor development → pesan terkirim
- [ ] Kirim Email test → email diterima
- [ ] Log notifikasi muncul di `notification_logs`

### 5.6 Queue Test
- [ ] Dispatch test job: `php artisan tinker → ProcessSurveyBlast::dispatch(...)`
- [ ] Verifikasi job diproses: `php artisan queue:monitor`
- [ ] Tidak ada failed jobs: `php artisan queue:failed`

---

## FASE 6 — BACKUP & MONITORING

### 6.1 Database Backup
- [ ] 🔴 Backup otomatis dikonfigurasi (minimal harian)
- [ ] Backup dienkripsi dengan GPG sebelum disimpan
- [ ] Test restore backup berhasil di environment terpisah
- [ ] Backup disimpan di lokasi berbeda dari server production (offsite/S3)

```bash
# Script backup MySQL yang direkomendasikan:
mysqldump -u sitras_user -p sitras_unisya | gzip | gpg --encrypt -r backup@unisya.ac.id > backup_$(date +%Y%m%d).sql.gz.gpg
```

### 6.2 Monitoring
- [ ] Uptime monitoring dikonfigurasi (Uptime Robot atau sejenisnya)
- [ ] Disk space alert dikonfigurasi (alert jika >80%)
- [ ] Memory alert dikonfigurasi
- [ ] Cron health check: supervisor cron monitor (opsional)

---

## FASE 7 — SIGN-OFF

### Verifikasi Akhir

```bash
# Jalankan seluruh test suite terakhir kali
php artisan test
# Expected: All tests passed

# Verifikasi tidak ada diagnostic output
curl -s https://tracer.unisya.ac.id/api/v1/public/info | python3 -m json.tool
```

### Checklist Ringkasan Final

| # | Item | Status |
|---|---|---|
| 1 | 0 vulnerability dari `composer audit` + `npm audit` | ☐ |
| 2 | `APP_DEBUG=false`, `APP_ENV=production` | ☐ |
| 3 | Semua test PHPUnit lulus | ☐ |
| 4 | HTTPS aktif + HSTS + grade SSL A | ☐ |
| 5 | Security headers verified (securityheaders.com ≥ A) | ☐ |
| 6 | CSP tanpa `unsafe-eval` di production | ☐ |
| 7 | Queue worker berjalan (Supervisor) | ☐ |
| 8 | Cron scheduler aktif | ☐ |
| 9 | Backup harian dikonfigurasi | ☐ |
| 10 | Firewall: hanya port 22, 80, 443 | ☐ |
| 11 | MySQL user hanya SELECT/INSERT/UPDATE/DELETE | ☐ |
| 12 | Redis dilindungi password | ☐ |
| 13 | Smoke test semua fitur utama ✅ | ☐ |

### Sign-Off

| Peran | Nama | Tanggal | Tanda Tangan |
|---|---|---|---|
| Lead Engineer | | | |
| System Administrator | | | |
| Project Manager | | | |

---

## ROLLBACK PLAN

Jika terjadi masalah kritis setelah deploy:

```bash
# 1. Rollback kode ke commit sebelumnya
git revert HEAD --no-edit
git push origin main

# 2. Rollback database (jika ada migration baru)
php artisan migrate:rollback --step=1

# 3. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Restart services
systemctl restart php8.3-fpm
systemctl reload nginx
supervisorctl restart all
```

**Estimasi waktu rollback:** < 5 menit jika tidak ada perubahan schema database.

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-13 | Dokumen awal — dibuat pada Sesi 6A |

---

*Dokumen ini wajib diperbarui setiap kali ada perubahan konfigurasi infrastruktur atau prosedur deployment.*
