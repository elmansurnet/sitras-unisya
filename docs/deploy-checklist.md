# Deploy Checklist — SITRAS UNISYA Production

**Versi:** 1.0.0  
**Referensi:** `07_SECURITY.md` §15, `04_ARCHITECTURE.md` §6-§8  
**Update:** Perbarui checklist ini setiap kali ada perubahan arsitektur atau konfigurasi.

---

## Cara Penggunaan

Centang setiap item `[ ]` sebelum go-live. **JANGAN skip item berlabel 🔴 CRITICAL.**
Jika ada item yang tidak bisa dipenuhi, catat alasannya dan eskalasi ke Lead Engineer.

---

## PRE-DEPLOY: Persiapan Kode

### Dependency Audit
- [ ] 🔴 `composer audit` — output **0 vulnerability**
- [ ] 🔴 `npm audit --audit-level=high` — output **0 high/critical**
- [ ] `composer update --no-dev --optimize-autoloader` sudah dijalankan
- [ ] `npm run build` berhasil tanpa error
- [ ] `composer.lock` dan `package-lock.json` sudah di-commit

### Code Quality
- [ ] Semua Feature Tests pass: `php artisan test --testsuite=Feature`
- [ ] Semua Unit Tests pass: `php artisan test --testsuite=Unit`
- [ ] Tidak ada `dd()`, `dump()`, `var_dump()`, `console.log()` tersisa di kode
- [ ] Tidak ada `// TODO` atau `// FIXME` yang belum diselesaikan pada kode kritis
- [ ] `.env` production **TIDAK** berisi nilai dari `.env.example` (semua placeholder sudah diisi)

---

## SERVER: Konfigurasi Ubuntu 22.04

### System
- [ ] Ubuntu 22.04 LTS — semua security patch terbaru sudah diapply: `apt update && apt upgrade`
- [ ] Timezone server diset ke `Asia/Makassar` (WITA): `timedatectl set-timezone Asia/Makassar`
- [ ] Firewall UFW aktif, hanya port 80, 443, 22 yang terbuka:
  ```bash
  ufw allow 22/tcp
  ufw allow 80/tcp
  ufw allow 443/tcp
  ufw enable
  ```
- [ ] SSH key-based auth aktif, password auth dinonaktifkan (`PasswordAuthentication no` di `/etc/ssh/sshd_config`)
- [ ] Root login via SSH dinonaktifkan (`PermitRootLogin no`)

### Nginx
- [ ] 🔴 Nginx config aktif sesuai `04_ARCHITECTURE.md` §6
- [ ] 🔴 SSL certificate Let's Encrypt aktif dan valid: `certbot certificates`
- [ ] 🔴 HTTPS redirect (HTTP → HTTPS 301) aktif
- [ ] TLS 1.2 minimum, TLS 1.3 aktif
- [ ] Security headers lengkap terpasang (verifikasi via `https://securityheaders.com`):
  - [ ] `X-Frame-Options: SAMEORIGIN`
  - [ ] `X-XSS-Protection: 1; mode=block`
  - [ ] `X-Content-Type-Options: nosniff`
  - [ ] `Referrer-Policy: strict-origin-when-cross-origin`
  - [ ] `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
  - [ ] `Content-Security-Policy` sesuai `07_SECURITY.md` §9
- [ ] Rate limiting zones aktif: `otp` (5 req/m), `auth` (10 req/m), `api` (60 req/m)
- [ ] `autoindex off` di semua location
- [ ] Blokir akses `.env`, `.git`, `storage/logs`, `bootstrap/cache`, `vendor`
- [ ] `fastcgi_hide_header X-Powered-By` aktif
- [ ] Gzip compression aktif untuk `text/plain`, `text/css`, `application/json`, `application/javascript`

### PHP-FPM 8.3
- [ ] Pool config sesuai `04_ARCHITECTURE.md` §7
- [ ] `expose_php = Off`
- [ ] `display_errors = off`
- [ ] `upload_max_filesize = 10M`
- [ ] `post_max_size = 12M`
- [ ] `memory_limit = 256M`
- [ ] `max_execution_time = 60`

---

## DATABASE: MySQL 8

- [ ] 🔴 Database user `sitrasuser` hanya punya hak: `SELECT, INSERT, UPDATE, DELETE` — TIDAK ada `SUPER`, `FILE`, `PROCESS`, `GRANT`
- [ ] 🔴 Password MySQL kuat (min 20 karakter, random)
- [ ] MySQL hanya listen di `127.0.0.1` (tidak expose ke network eksternal): `bind-address = 127.0.0.1`
- [ ] Backup otomatis dikonfigurasi (crontab, harian, enkripsi GPG):
  ```bash
  0 2 * * * mysqldump -u sitrasuser -p sitras_unisya | gpg --symmetric > /backup/sitras-$(date +\%Y\%m\%d).sql.gpg
  ```
- [ ] Retention backup: minimal 7 hari
- [ ] Restore backup pernah diuji (test restore sekali sebelum go-live)
- [ ] `php artisan migrate --force` berhasil tanpa error
- [ ] `php artisan db:seed --force` berhasil (seeder wajib: SuperadminSeeder, FacultySeeder, StudyProgramSeeder, GraduationYearSeeder, IndustrySectorSeeder, SalaryRangeSeeder, SystemSettingSeeder, NotificationTemplateSeeder)

---

## REDIS 7

- [ ] 🔴 Redis dilindungi password (`requirepass` di `redis.conf`): password kuat min 32 karakter
- [ ] Redis hanya listen di `127.0.0.1`: `bind 127.0.0.1`
- [ ] Redis tidak expose ke network eksternal
- [ ] `REDIS_PASSWORD` di `.env` sudah diisi dengan password yang sama

---

## LARAVEL: Environment & Konfigurasi

### Wajib
- [ ] 🔴 `APP_DEBUG=false`
- [ ] 🔴 `APP_ENV=production`
- [ ] 🔴 `APP_KEY` sudah di-generate: `php artisan key:generate`
- [ ] 🔴 `TELESCOPE_ENABLED=false` (atau Telescope tidak diinstall di production)
- [ ] `APP_URL=https://tracer.unisya.ac.id`
- [ ] `APP_TIMEZONE=Asia/Jakarta`
- [ ] `LOG_CHANNEL=daily`
- [ ] `LOG_LEVEL=error`

### Database & Cache
- [ ] `DB_CONNECTION=mysql`
- [ ] `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sudah diisi
- [ ] `CACHE_DRIVER=redis`
- [ ] `SESSION_DRIVER=redis`
- [ ] `QUEUE_CONNECTION=redis`

### Autentikasi & Keamanan
- [ ] `OTP_EXPIRY_MINUTES=5`
- [ ] `OTP_MAX_ATTEMPTS=3`
- [ ] `OTP_RESEND_COOLDOWN_SECONDS=60`
- [ ] `LOGIN_MAX_ATTEMPTS=5`
- [ ] `LOGIN_LOCKOUT_MINUTES=15`
- [ ] `EMPLOYER_TOKEN_EXPIRY_DAYS=30`
- [ ] `SANCTUM_STATEFUL_DOMAINS=tracer.unisya.ac.id`
- [ ] `SESSION_DOMAIN=.unisya.ac.id`
- [ ] `FRONTEND_URL=https://tracer.unisya.ac.id`

### Notifikasi
- [ ] `WHATSAPP_GATEWAY_URL=https://wacenter.unisya.ac.id/send-message`
- [ ] `WHATSAPP_API_KEY` sudah diisi dengan API key valid
- [ ] `WHATSAPP_SENDER` sudah diisi dengan nomor pengirim aktif
- [ ] `MAIL_*` sudah dikonfigurasi dan ditest (kirim email percobaan)

### Storage
- [ ] `php artisan storage:link` sudah dijalankan
- [ ] Direktori `storage/app/private/` sudah ada dengan permission 750
- [ ] Direktori `storage/app/reports/` sudah ada dengan permission 750
- [ ] `storage/` owner `www-data`: `chown -R www-data:www-data storage/ bootstrap/cache/`

---

## QUEUE WORKER: Supervisor

- [ ] Supervisor terinstall: `apt install supervisor`
- [ ] Config worker `sitras-worker-default` sesuai `04_ARCHITECTURE.md` §5.3 aktif
- [ ] Config worker `sitras-worker-low` aktif
- [ ] Supervisor reload: `supervisorctl reread && supervisorctl update`
- [ ] Kedua worker berjalan: `supervisorctl status`
- [ ] `php artisan queue:work` bisa dijalankan manual tanpa error

---

## SCHEDULER: Crontab

- [ ] Crontab untuk `www-data` user sudah dikonfigurasi:
  ```bash
  * * * * * cd /var/www/sitras-unisya && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] `php artisan schedule:list` menampilkan semua 4 command terjadwal:
  - [ ] `survey:send-reminders` — daily 08:00 WITA
  - [ ] `survey:close-expired` — daily 00:05 WITA
  - [ ] `otp:cleanup` — every 30 minutes
  - [ ] `queue:prune-failed` — daily

---

## POST-DEPLOY: Verifikasi

### Fungsional
- [ ] 🔴 Homepage SPA Vue 3 bisa dibuka di browser: `https://tracer.unisya.ac.id`
- [ ] 🔴 Login superadmin berhasil
- [ ] 🔴 Request OTP berhasil (WA atau email terkirim)
- [ ] 🔴 Login alumni via OTP berhasil
- [ ] API health check: `GET https://tracer.unisya.ac.id/api/v1/public/health` → `200 OK`

### Keamanan
- [ ] 🔴 `https://securityheaders.com` — skor minimal **A**
- [ ] 🔴 `https://www.ssllabs.com/ssltest/` — skor minimal **A**
- [ ] Akses langsung ke `https://tracer.unisya.ac.id/.env` → `404`
- [ ] Akses langsung ke `https://tracer.unisya.ac.id/storage/logs/laravel.log` → `404`
- [ ] Akses `http://tracer.unisya.ac.id` redirect ke `https://` (301)
- [ ] Rate limit OTP aktif: 6 request berturut-turut → request ke-6 dapat `429`

### Monitoring
- [ ] Log Laravel bisa diakses di server: `tail -f storage/logs/laravel-$(date +%Y-%m-%d).log`
- [ ] Supervisor log berjalan: `tail -f /var/log/sitras/worker-default.log`
- [ ] Tidak ada ERROR di Laravel log dalam 10 menit pertama setelah deploy

---

## ROLLBACK PLAN

Jika terjadi masalah kritis setelah deploy:

```bash
# 1. Kembalikan ke commit sebelumnya
git log --oneline -5
git checkout <previous_commit_sha>

# 2. Jalankan ulang migrate rollback jika ada migration baru
php artisan migrate:rollback --step=1

# 3. Restart services
php artisan cache:clear
php artisan config:clear
supervisorctl restart sitras-worker-default sitras-worker-low
nginx -s reload

# 4. Verifikasi rollback berhasil
curl -I https://tracer.unisya.ac.id/api/v1/public/health
```

---

## PASCA GO-LIVE: Monitoring Minggu Pertama

- [ ] Review Laravel error log setiap hari selama 7 hari pertama
- [ ] Pantau queue worker — tidak ada job stuck di `failed_jobs`
- [ ] Pantau audit_logs untuk aktivitas mencurigakan (>10 login gagal dari IP sama)
- [ ] Verifikasi backup harian berjalan dan file backup ada
- [ ] Konfirmasi notifikasi OTP terkirim ke user nyata (WA & email)
- [ ] Jalankan `composer audit` dan `npm audit` di hari ke-7

---

*Dokumen ini adalah checklist operasional. Setiap item harus dicentang oleh engineer yang bertanggung jawab.*
*Simpan salinan checklist yang sudah diisi sebagai bukti audit untuk keperluan akreditasi.*
