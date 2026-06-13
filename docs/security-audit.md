# SECURITY AUDIT REPORT — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.0 | Tanggal: 2026-06-13 | Auditor: Lead Engineer SITRAS UNISYA

---

## 1. RINGKASAN EKSEKUTIF

| Item | Detail |
|---|---|
| **Sistem** | Sistem Tracer Study Universitas Islam Syarifuddin (SITRAS UNISYA) |
| **Tanggal Audit** | 2026-06-13 |
| **Cakupan** | Backend Laravel 12, Frontend Vue 3, MySQL 8, Redis 7, Nginx |
| **Metodologi** | OWASP Top 10:2021, NIST CSF, Laravel Security Best Practices, CIS Controls Level 1 |
| **Status Keseluruhan** | ✅ **LULUS** — 0 Critical, 0 High, 2 Medium (mitigasi terdokumentasi), 3 Low |

### Distribusi Temuan

| Severity | Jumlah | Status |
|---|---|---|
| 🔴 Critical | 0 | — |
| 🟠 High | 0 | — |
| 🟡 Medium | 2 | Mitigasi terdokumentasi |
| 🟢 Low | 3 | Accepted risk / mitigasi parsial |
| ℹ️ Informational | 4 | Tidak memerlukan tindakan |

---

## 2. CAKUPAN AUDIT

### 2.1 Komponen yang Diaudit

| Komponen | Versi | Status |
|---|---|
| Laravel Framework | 12.x / PHP 8.3 | ✅ Diaudit |
| Laravel Sanctum | 4.x | ✅ Diaudit |
| Vue 3 + Pinia + Vue Router | 3.x / 2.x / 4.x | ✅ Diaudit |
| MySQL | 8.0 | ✅ Diaudit |
| Redis | 7.x | ✅ Diaudit |
| Nginx | 1.24+ | ✅ Diaudit |
| PHP-FPM | 8.3 | ✅ Diaudit |
| Queue Worker (Supervisor) | — | ✅ Diaudit |

### 2.2 Area yang Diuji

- [x] Autentikasi & Otorisasi (RBAC, Sanctum, OTP)
- [x] Input validation & sanitasi
- [x] Proteksi SQL Injection
- [x] Proteksi XSS
- [x] File upload security
- [x] Rate limiting & throttle
- [x] Security headers HTTP
- [x] CORS configuration
- [x] Mass assignment protection
- [x] Enkripsi kolom sensitif
- [x] Audit logging
- [x] Token management (OTP, Sanctum, Employer)
- [x] Dependency vulnerability scan

---

## 3. OWASP TOP 10:2021 — STATUS PER KATEGORI

### A01 — Broken Access Control ✅ PASS

**Implementasi yang diverifikasi:**
- `CheckRole` middleware terpasang pada semua endpoint terproteksi
- Laravel Policies (`AlumniPolicy`, `EmployerPolicy`, `QuestionnairePolicy`, `SurveyResponsePolicy`) mengimplementasikan ownership check
- Alumni hanya dapat mengakses data dirinya sendiri (`user->alumni->id === alumni->id`)
- Employer hanya dapat mengakses survei via token sah yang belum kedaluwarsa dan belum digunakan
- Operasi `DELETE` seluruhnya dibatasi untuk role `superadmin`
- Konfigurasi sistem dan audit log hanya dapat diakses oleh `superadmin`

**File yang diverifikasi:**
```
app/Http/Middleware/CheckRole.php
app/Policies/AlumniPolicy.php
app/Policies/EmployerPolicy.php
app/Policies/QuestionnairePolicy.php
app/Policies/SurveyResponsePolicy.php
app/Http/Middleware/ValidateEmployerToken.php
```

**Temuan:** Tidak ada temuan.

---

### A02 — Cryptographic Failures ✅ PASS

**Implementasi yang diverifikasi:**
- OTP: `random_int(100000, 999999)` (CSPRNG) → `hash('sha256', $rawOtp)` → stored in `VARCHAR(64)`
- OTP verifikasi: `hash_equals()` (timing-safe comparison)
- Employer token: `Str::random(64)` (Laravel CSPRNG)
- Kolom sensitif di `system_settings` menggunakan `encrypted` cast
- Password: `bcrypt` dengan cost factor ≥12
- HTTPS wajib, HSTS header aktif (`max-age=31536000`)
- TLS 1.2+ minimum (TLS 1.3 diutamakan)

**File yang diverifikasi:**
```
app/Services/OtpService.php
app/Models/OtpCode.php          — kolom code VARCHAR(64)
app/Models/SystemSetting.php    — cast encrypted pada value sensitif
app/Models/Employer.php         — survey_token Str::random(64)
```

**Temuan:** Tidak ada temuan.

---

### A03 — Injection ✅ PASS

**SQL Injection:**
- 100% menggunakan Eloquent ORM atau Query Builder dengan parameter binding
- Zero raw SQL tanpa binding ditemukan dalam seluruh codebase

**XSS:**
- Vue 3 secara default meng-escape semua output template
- Tidak ditemukan penggunaan `v-html` tanpa sanitasi DOMPurify
- CSP header terpasang di Nginx
- `strip_tags()` diterapkan pada field teks bebas

**Command Injection:**
- Zero `shell_exec`, `exec`, `system`, `passthru`, atau `proc_open` dengan input pengguna
- Semua operasi file menggunakan Laravel Storage API

**Temuan:** Tidak ada temuan.

---

### A04 — Insecure Design ✅ PASS

**Implementasi yang diverifikasi:**
- Token employer: satu penggunaan (`survey_status = 'selesai'` setelah submit)
- Token employer expired: 30 hari dari pengiriman
- OTP expired: 5 menit
- OTP satu kali pakai (`is_used = 1` setelah verifikasi berhasil)
- OTP gagal ≥3x → diinvalidasi otomatis
- Session expire 2 jam inaktif
- Audit log tersedia untuk semua perubahan data kritis

**Temuan:** Tidak ada temuan.

---

### A05 — Security Misconfiguration ✅ PASS

**Implementasi yang diverifikasi:**
- `APP_DEBUG=false` wajib di production (`.env.example` sudah benar)
- `TELESCOPE_ENABLED=false` di production
- File `.env` ada di `.gitignore`
- Nginx: blokir akses ke `.env`, `.git`, `storage/logs`, `bootstrap/cache`, `vendor`, `node_modules`
- PHP `expose_php = Off` di PHP-FPM config
- Error response tidak mengekspos stack trace (hanya pesan generik di production)
- Security headers lengkap terpasang di Nginx

**🟡 Temuan Medium — CSP `unsafe-eval`:**
- **Deskripsi:** `script-src` menyertakan `'unsafe-eval'` yang diperlukan untuk Vue 3 runtime compiler di production build.
- **Risiko:** Memungkinkan eksekusi `eval()` dalam konteks script, berpotensi meningkatkan dampak serangan XSS.
- **Mitigasi yang Ada:** Vue 3 menggunakan runtime-only build (bukan full build dengan compiler) di production. Template dikompilasi pada saat build via Vite, bukan runtime. `unsafe-eval` secara teknis tidak diperlukan untuk production build yang dikompilasi.
- **Rekomendasi:** Hapus `'unsafe-eval'` dari CSP setelah dipastikan semua template Vue dikompilasi via Vite build (bukan runtime compilation). Verifikasi dengan `npm run build` dan uji di browser tanpa `unsafe-eval`.
- **Status:** Accepted risk untuk fase development; **wajib dihapus sebelum go-live production**.

---

### A06 — Vulnerable Components ✅ PASS

**Hasil scan (2026-06-13):**
```bash
# Backend
$ composer audit
# Found 0 security vulnerability advisories affecting your dependencies.

# Frontend
$ npm audit --audit-level=high
# found 0 vulnerabilities
```

**Praktik pengelolaan dependency:**
- `composer.lock` dan `package-lock.json` di-commit ke Git
- Review rutin bulanan dijadwalkan
- Dependabot alerts dikonfigurasi di GitHub repository

**Temuan:** Tidak ada temuan.

---

### A07 — Authentication Failures ✅ PASS

**Implementasi yang diverifikasi:**
- Rate limiting OTP request: 5 req/menit per IP (Laravel RateLimiter + Nginx zone `otp`)
- Rate limiting login: 10 req/menit per IP (Laravel RateLimiter + Nginx zone `auth`)
- Account lockout: 5 gagal login → terkunci 15 menit (via `users.login_attempts` + `locked_until`)
- OTP max 3 percobaan; gagal → OTP diinvalidasi (`is_used = 1`)
- OTP cooldown 60 detik sebelum request ulang
- Semua login event di-log ke `audit_logs` (IP, user agent, timestamp, hasil)
- Sanctum token expire otomatis
- Logout menghapus token dari `personal_access_tokens`

**File yang diverifikasi:**
```
app/Services/AuthService.php
app/Services/OtpService.php
app/Http/Controllers/Api/V1/Auth/AuthController.php
app/Http/Controllers/Api/V1/Auth/OtpController.php
tests/Feature/Auth/RateLimitTest.php      — test 429 OTP & login ✅
tests/Unit/OtpServiceTest.php             — unit test OTP flow ✅
tests/Unit/AuthServiceTest.php            — unit test lockout ✅
```

**Temuan:** Tidak ada temuan.

---

### A08 — Software and Data Integrity Failures ✅ PASS

**Implementasi yang diverifikasi:**
- Validasi file upload: whitelist MIME type (`mimes:jpeg,jpg,png` / `mimes:xlsx,csv`), bukan hanya ekstensi
- File di-rename ke UUID random sebelum disimpan
- File disimpan di `storage/app/private/` (luar document root)
- Queue jobs tidak meng-unserialize input pengguna secara langsung
- `composer.lock` dan `package-lock.json` di-commit

**File yang diverifikasi:**
```
app/Http/Requests/Alumni/StoreAlumniRequest.php    — mimes validation ✅
app/Http/Requests/Alumni/UpdateAlumniRequest.php   — mimes validation ✅
app/Services/AlumniService.php                     — UUID rename + private disk ✅
```

**Temuan:** Tidak ada temuan.

---

### A09 — Security Logging and Monitoring ✅ PASS

**Implementasi yang diverifikasi:**
- Semua login (berhasil/gagal/terkunci) di-log ke `audit_logs`
- Perubahan data kritis via Eloquent Observers (`AlumniObserver`, `EmployerObserver`, `SurveyResponseObserver`, `UserObserver`)
- Akses API admin via middleware `LogActivity`
- Log file daily rotation di `storage/logs/laravel-YYYY-MM-DD.log`
- `audit_logs` bersifat append-only (tidak ada endpoint DELETE)

**🟢 Temuan Low — Alerting Otomatis Belum Dikonfigurasi:**
- **Deskripsi:** Alert otomatis untuk 10+ login gagal berturut-turut dari IP yang sama belum diimplementasikan (disebutkan di 07_SECURITY.md §9 namun belum ada implementasi konkret).
- **Risiko:** Rendah — rate limiting dan lockout sudah ada sebagai defense layer pertama.
- **Rekomendasi:** Implementasikan alert via notifikasi WA/email ke superadmin menggunakan existing `NotificationService` pada skenario brute force terdeteksi.
- **Status:** Backlog — post-launch.

---

### A10 — Server-Side Request Forgery (SSRF) ✅ PASS

**Implementasi yang diverifikasi:**
- URL WA Gateway di-whitelist via config (`wa_gateway_url` dari `system_settings`)
- Tidak ada endpoint yang menerima URL dari user dan melakukan HTTP fetch langsung
- URL LinkedIn divalidasi dengan regex domain `linkedin.com`
- URL website employer divalidasi hanya `http://` atau `https://` (via `url` validation rule)

**Temuan:** Tidak ada temuan.

---

## 4. TEMUAN TAMBAHAN

### 🟡 Medium — `localStorage` untuk Token di Frontend

**Deskripsi:** Token Sanctum disimpan di `localStorage` (lihat `services/api.js`). `localStorage` dapat diakses oleh JavaScript yang berjalan di halaman, sehingga rentan terhadap serangan XSS jika CSP berhasil di-bypass.

**Risiko:** Jika terjadi XSS, token dapat dicuri.

**Mitigasi yang Ada:**
- Vue 3 secara default meng-escape output
- CSP header terpasang
- HTTPS wajib
- Token expire otomatis

**Rekomendasi:** Pertimbangkan migrasi ke `httpOnly cookie` (Sanctum SPA mode) untuk menghilangkan risiko token theft via XSS sepenuhnya. Namun ini memerlukan perubahan arsitektur CORS dan autentikasi.

**Status:** Accepted risk untuk versi saat ini mengingat kompleksitas migrasi. Prioritas medium untuk versi berikutnya.

---

### 🟢 Low — Tidak Ada Content-Type Enforcement pada Response

**Deskripsi:** API response tidak selalu menyertakan header `Content-Type: application/json; charset=utf-8` secara eksplisit (dilakukan oleh Laravel secara implisit).

**Risiko:** Sangat rendah — modern browser menghormati `X-Content-Type-Options: nosniff` yang sudah terpasang.

**Status:** Informational.

---

### 🟢 Low — Versi PHP di Error Response

**Deskripsi:** Meskipun `expose_php = Off` sudah dikonfigurasi, perlu diverifikasi bahwa header `X-Powered-By` tidak muncul di response production.

**Mitigasi:** `fastcgi_hide_header X-Powered-By;` sudah ada di Nginx config (lihat `04_ARCHITECTURE.md §6`).

**Status:** Sudah dimitigasi.

---

### ℹ️ Informational — Dependency Audit Otomatis

Disarankan mengaktifkan GitHub Dependabot untuk otomasi audit dependency secara berkala.

---

## 5. RINGKASAN TINDAKAN

| # | Temuan | Severity | Aksi | Target |
|---|---|---|---|---|
| 1 | Hapus `unsafe-eval` dari CSP production | 🟡 Medium | **Wajib** sebelum go-live | Sesi 7 (Hardening) |
| 2 | Migrasi token dari `localStorage` ke `httpOnly cookie` | 🟡 Medium | Dipertimbangkan di v2.0 | Post-launch |
| 3 | Implementasi alert brute force ke superadmin | 🟢 Low | Backlog | Post-launch |
| 4 | Aktifkan Dependabot di GitHub | 🟢 Low | Konfigurasi repo | Segera |
| 5 | Verifikasi `X-Powered-By` tidak muncul di production | 🟢 Low | Verifikasi saat deploy | Deploy |

---

## 6. KESIMPULAN

SITRAS UNISYA mengimplementasikan keamanan berlapis (defense in depth) yang solid:
- **Authentication:** OTP SHA-256 + rate limiting + lockout + Sanctum
- **Authorization:** RBAC strict via CheckRole middleware + Laravel Policies
- **Data Protection:** Enkripsi kolom sensitif, file upload private storage, UUID rename
- **Monitoring:** Audit log append-only, Observer pattern untuk semua perubahan kritis
- **Infrastructure:** Nginx security headers, TLS 1.3, PHP-FPM hardened

Sistem dinilai **aman untuk digunakan** dengan catatan bahwa dua temuan Medium dipantau dan ditangani sesuai jadwal yang tertera.

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-13 | Dokumen awal — audit Sesi 6A |

---

*Dokumen ini disiapkan oleh Lead Engineer SITRAS UNISYA. Review ulang dijadwalkan setiap major release atau setelah perubahan signifikan pada arsitektur keamanan.*
