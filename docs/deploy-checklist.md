# Deploy Checklist — SITRAS UNISYA Production

**Versi:** 1.0.0  
**Tanggal:** 2026-06-13  
**Referensi:** 07_SECURITY.md §15, 04_ARCHITECTURE.md  

> Checklist ini WAJIB diselesaikan 100% sebelum aplikasi go-live ke production.
> Setiap item harus diverifikasi dan ditandai oleh engineer yang bertanggung jawab.

---

## A. PRE-DEPLOY — CODE & DEPENDENCY

- [ ] `composer audit` → 0 vulnerability
- [ ] `npm audit --audit-level=high` (di `frontend/`) → 0 high/critical
- [ ] `php artisan test` → semua test hijau (Unit + Feature)
- [ ] Tidak ada `dd()`, `dump()`, `var_dump()`, `echo` debug di kode production
- [ ] Tidak ada TODO atau placeholder code di file yang akan di-deploy
- [ ] `composer.lock` dan `package-lock.json` di-commit ke Git
- [ ] `.env` ada di `.gitignore` dan tidak pernah di-commit
- [ ] Review `composer.json` — tidak ada package `dev` yang ter-include di production
  ```bash
  composer install --no-dev --optimize-autoloader
  ```
- [ ] Frontend Vue 3 build production:
  ```bash
  cd frontend && npm run build
  ```
  Output di `public/build/` — verifikasi tidak ada source map yang terekspos

---

## B. SERVER SETUP — Ubuntu 22.04

### B.1 Software Versions
- [ ] PHP 8.3 + PHP-FPM 8.3 terinstall
- [ ] Nginx 1.24+ terinstall
- [ ] MySQL 8.0+ terinstall
- [ ] Redis 7.x terinstall
- [ ] Node.js 20.x terinstall (untuk build frontend)
- [ ] Supervisor terinstall (untuk queue worker)

### B.2 PHP Hardening
- [ ] `expose_php = Off` di `/etc/php/8.3/fpm/php.ini`
- [ ] `display_errors = Off`
- [ ] `log_errors = On`
- [ ] `error_log = /var/log/php/error.log`
- [ ] `upload_max_filesize = 12M`
- [ ] `post_max_size = 12M`
- [ ] `max_execution_time = 60`

### B.3 Nginx Hardening
```nginx
# Tambahkan di nginx.conf atau server block
server_tokens off;
autoindex off;
```
- [ ] `server_tokens off;` aktif
- [ ] `autoindex off;` aktif
- [ ] Akses ke `.env`, `.git`, `storage/logs` diblokir:
  ```nginx
  location ~ /\.env { deny all; }
  location ~ /\.git { deny all; }
  location ~ /storage/logs { deny all; }
  ```
- [ ] Rate limiting zone dikonfigurasi:
  ```nginx
  limit_req_zone $binary_remote_addr zone=otp:10m  rate=5r/m;
  limit_req_zone $binary_remote_addr zone=auth:10m rate=10r/m;
  limit_req_zone $binary_remote_addr zone=api:10m  rate=60r/m;
  ```
- [ ] Rate limiting aktif di location block API
- [ ] Security headers terpasang (referensi: 07_SECURITY.md §9):
  - [ ] `X-Frame-Options: SAMEORIGIN`
  - [ ] `X-Content-Type-Options: nosniff`
  - [ ] `X-XSS-Protection: 1; mode=block`
  - [ ] `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
  - [ ] `Content-Security-Policy` (lihat 07_SECURITY.md §9)
  - [ ] `Referrer-Policy: strict-origin-when-cross-origin`
  - [ ] `Permissions-Policy: camera=(), microphone=(), geolocation=()`

### B.4 MySQL Hardening
- [ ] Database user `sitras_user` dibuat dengan hak minimal:
  ```sql
  GRANT SELECT, INSERT, UPDATE, DELETE ON sitras_db.* TO 'sitras_user'@'localhost';
  -- Tidak ada: DROP, CREATE, ALTER, SUPER, FILE, PROCESS
  ```
- [ ] Password MySQL user kuat (min 20 karakter, random)
- [ ] Root MySQL tidak bisa login dari luar localhost
- [ ] Backup otomatis dikonfigurasi (harian, dienkripsi GPG)

### B.5 Redis Hardening
- [ ] Redis `requirepass` dikonfigurasi dengan password kuat
- [ ] Redis hanya listen di `127.0.0.1` (tidak di 0.0.0.0)
- [ ] Redis tidak bisa diakses dari luar server

### B.6 Firewall (UFW)
- [ ] Hanya port berikut yang ALLOW:
  ```bash
  ufw allow 22/tcp    # SSH
  ufw allow 80/tcp    # HTTP (redirect ke HTTPS)
  ufw allow 443/tcp   # HTTPS
  ufw default deny incoming
  ufw enable
  ```
- [ ] Port 3306 (MySQL), 6379 (Redis) TIDAK exposed ke publik

### B.7 SSH Hardening
- [ ] SSH key-based authentication aktif
- [ ] Password authentication dinonaktifkan:
  ```
  # /etc/ssh/sshd_config
  PasswordAuthentication no
  PermitRootLogin no
  ```
- [ ] SSH port menggunakan port non-default (opsional tapi direkomendasikan)

---

## C. KONFIGURASI APLIKASI (.env)

```dotenv
# Wajib
APP_NAME="SITRAS UNISYA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tracer.unisya.ac.id
FRONTEND_URL=https://tracer.unisya.ac.id

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sitras_db
DB_USERNAME=sitras_user
DB_PASSWORD={strong_random_password}

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD={strong_random_password}
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Cache
CACHE_STORE=redis

# Sanctum
SANCTUM_STATEFUL_DOMAINS=tracer.unisya.ac.id
SANCTUM_TOKEN_EXPIRATION=1440

# OTP
OTP_EXPIRY_MINUTES=5
OTP_MAX_ATTEMPTS=3
OTP_RESEND_COOLDOWN_SECONDS=60

# Login Lockout
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15

# WA Gateway UNISYA
WA_GATEWAY_URL=https://wacenter.unisya.ac.id/send-message
WA_API_KEY={dari_system_settings_terenkripsi}
WA_SENDER={dari_system_settings_terenkripsi}

# SMTP
MAIL_MAILER=smtp
MAIL_HOST={smtp_host}
MAIL_PORT=587
MAIL_USERNAME={smtp_user}
MAIL_PASSWORD={smtp_password}
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tracer@unisya.ac.id
MAIL_FROM_NAME="SITRAS UNISYA"

# Telescope (nonaktif di production)
TELESCOPE_ENABLED=false
```

- [ ] `APP_DEBUG=false` ✅
- [ ] `APP_ENV=production` ✅
- [ ] `TELESCOPE_ENABLED=false` ✅
- [ ] `APP_KEY` sudah di-generate: `php artisan key:generate`
- [ ] `SANCTUM_TOKEN_EXPIRATION=1440` (24 jam)
- [ ] Semua password/key menggunakan nilai random yang kuat
- [ ] `.env` file permission: `chmod 600 .env`

---

## D. DEPLOYMENT STEPS

```bash
# 1. Clone / pull repository
git clone https://github.com/elmansurnet/sitras-unisya.git /var/www/sitras
cd /var/www/sitras

# 2. Install dependencies (tanpa dev)
composer install --no-dev --optimize-autoloader

# 3. Copy dan konfigurasi .env
cp .env.example .env
# Edit .env dengan nilai production
php artisan key:generate

# 4. Optimisasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Migrasi database
php artisan migrate --force

# 6. Seeder data awal
php artisan db:seed --force

# 7. Storage symlink (opsional jika diperlukan untuk signed URL)
php artisan storage:link

# 8. Build frontend
cd frontend && npm ci && npm run build && cd ..

# 9. Permission
chown -R www-data:www-data /var/www/sitras/storage
chmod -R 775 /var/www/sitras/storage
chown -R www-data:www-data /var/www/sitras/bootstrap/cache
chmod -R 775 /var/www/sitras/bootstrap/cache
```

- [ ] Semua langkah di atas dijalankan tanpa error

---

## E. QUEUE WORKER (Supervisor)

```ini
; /etc/supervisor/conf.d/sitras-worker.conf

[program:sitras-worker-default]
command=php /var/www/sitras/artisan queue:work redis --queue=default --sleep=3 --tries=3 --max-time=3600
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
stdout_logfile=/var/log/supervisor/sitras-worker-default.log
stderr_logfile=/var/log/supervisor/sitras-worker-default-error.log

[program:sitras-worker-high]
command=php /var/www/sitras/artisan queue:work redis --queue=high --sleep=3 --tries=3 --max-time=3600
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
stdout_logfile=/var/log/supervisor/sitras-worker-high.log
stderr_logfile=/var/log/supervisor/sitras-worker-high-error.log

[program:sitras-worker-low]
command=php /var/www/sitras/artisan queue:work redis --queue=low --sleep=10 --tries=3 --max-time=3600
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
stdout_logfile=/var/log/supervisor/sitras-worker-low.log
stderr_logfile=/var/log/supervisor/sitras-worker-low-error.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start sitras-worker-default sitras-worker-high sitras-worker-low
```

- [ ] Supervisor queue worker `sitras-worker-default` berjalan
- [ ] Supervisor queue worker `sitras-worker-high` berjalan  
- [ ] Supervisor queue worker `sitras-worker-low` berjalan
- [ ] `supervisorctl status` menampilkan `RUNNING` untuk semua worker

---

## F. SCHEDULER (Cron)

```bash
# Tambahkan ke crontab www-data
crontab -u www-data -e

# Tambahkan baris:
* * * * * cd /var/www/sitras && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Cron scheduler aktif: `crontab -u www-data -l` menampilkan entry
- [ ] Verifikasi jadwal: `php artisan schedule:list`:
  - [ ] `survey:close-expired` → dailyAt('00:05') timezone Asia/Makassar
  - [ ] `survey:send-reminders` → dailyAt('08:00') timezone Asia/Makassar
  - [ ] `otp:cleanup` → everyThirtyMinutes

---

## G. HTTPS & SSL/TLS

- [ ] Sertifikat SSL Let's Encrypt aktif:
  ```bash
  certbot --nginx -d tracer.unisya.ac.id
  ```
- [ ] Auto-renewal dikonfigurasi (`certbot renew --dry-run` berhasil)
- [ ] HTTP → HTTPS redirect aktif di Nginx
- [ ] TLS 1.2 minimum (TLS 1.3 diutamakan):
  ```nginx
  ssl_protocols TLSv1.2 TLSv1.3;
  ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:...
  ```
- [ ] HSTS header aktif: `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`

---

## H. POST-DEPLOY VERIFICATION

### H.1 Fungsional
- [ ] Login admin berhasil via browser
- [ ] OTP request + verify berhasil (test dengan nomor WA aktif)
- [ ] Upload foto alumni berhasil, akses via signed URL (bukan direct URL)
- [ ] Queue job terkirim: `php artisan queue:monitor`
- [ ] Scheduler berjalan: cek log `storage/logs/scheduler-*.log`

### H.2 Security Verification
- [ ] [securityheaders.com](https://securityheaders.com) → Grade A minimal (B acceptable)
- [ ] `APP_DEBUG=false` diverifikasi: akses URL tidak ada yang menampilkan stack trace
- [ ] `.env` tidak bisa diakses: `curl https://tracer.unisya.ac.id/.env` → 404
- [ ] `storage/logs` tidak bisa diakses via URL
- [ ] Rate limiting bekerja: 6 request OTP dalam 1 menit → HTTP 429
- [ ] File foto alumni tidak bisa diakses tanpa signed URL

### H.3 Performance
- [ ] Response time endpoint utama < 500ms (tanpa cache cold)
- [ ] Redis cache berfungsi (config cache, route cache)
- [ ] `php artisan config:clear && php artisan config:cache` tanpa error

---

## I. LOG ROTATION

```bash
# /etc/logrotate.d/sitras
/var/www/sitras/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        php /var/www/sitras/artisan queue:restart
    endscript
}
```

- [ ] Logrotate dikonfigurasi untuk `storage/logs/*.log`
- [ ] Rotasi 30 hari dengan kompresi

---

## J. ROLLBACK PLAN

Jika terjadi masalah kritis setelah deploy:

```bash
# 1. Identifikasi commit sebelumnya
git log --oneline -5

# 2. Rollback kode
git reset --hard {previous_commit_sha}
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache

# 3. Rollback migrasi (jika ada migrasi baru)
php artisan migrate:rollback

# 4. Restart queue workers
supervisorctl restart sitras-worker-default sitras-worker-high sitras-worker-low

# 5. Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

- [ ] Rollback plan disosialisasikan ke seluruh tim
- [ ] Backup database terbaru tersedia sebelum deploy

---

## TANDA TANGAN VERIFIKASI

| Peran | Nama | Tanggal | Tanda Tangan |
|---|---|---|---|
| Lead Engineer | | | |
| System Administrator | | | |
| Project Manager | | | |

---

*Checklist ini dihasilkan dari 07_SECURITY.md v1.0.3 dan pengalaman Sesi 6A. Update dokumen ini setiap kali ada perubahan infrastruktur.*
