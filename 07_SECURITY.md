# 07_SECURITY.md
# SPESIFIKASI KEAMANAN — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.3 | Tanggal: 2026-06-09

---

## 1. KERANGKA KEAMANAN

Sistem ini mengimplementasikan keamanan berlapis (defense in depth) berdasarkan:
- **OWASP Top 10:2021**
- **NIST Cybersecurity Framework**
- **Laravel Security Best Practices**
- **CIS Controls Level 1**

---

## 2. OWASP TOP 10 MITIGASI

### A01 – Broken Access Control
**Mitigasi:**
- RBAC ketat: setiap endpoint dilindungi middleware `CheckRole`
- Laravel Policies untuk otorisasi resource-level
- Alumni hanya bisa akses data diri sendiri (`AlumniPolicy::view`)
- Employer hanya bisa akses via token sah yang belum kedaluwarsa
- Semua operasi **delete** memerlukan role `superadmin`
- Semua operasi **konfigurasi sistem dan audit log** memerlukan role `superadmin`
- Cek ownership di setiap request update/delete resource

```php
// Contoh Policy
class AlumniPolicy
{
    public function view(User $user, Alumni $alumni): bool
    {
        return match ($user->role) {
            'superadmin', 'admin' => true,
            'alumni' => $user->alumni?->id === $alumni->id,
            default => false,
        };
    }

    public function update(User $user, Alumni $alumni): bool
    {
        return match ($user->role) {
            'superadmin', 'admin' => true,
            'alumni' => $user->alumni?->id === $alumni->id,
            default => false,
        };
    }

    public function delete(User $user, Alumni $alumni): bool
    {
        return $user->role === 'superadmin';
    }
}
```

---

### A02 – Cryptographic Failures
**Mitigasi:**
- Password di-hash dengan `bcrypt` (cost factor 12 minimum)
- Kode OTP di-hash dengan `SHA-256` sebelum disimpan — **tidak pernah plaintext**
- Kolom `otp_codes.code` bertipe `VARCHAR(64)` untuk menampung SHA-256 hex digest
- Survey token employer di-generate dengan `Str::random(64)` (CSPRNG Laravel)
- `APP_KEY` Laravel 32-byte random (digunakan AES-256-CBC untuk enkripsi)
- Kolom sensitif (SMTP password, WA token) dienkripsi dengan `encrypted:` cast
- HTTPS wajib di semua endpoint (HSTS enabled)
- TLS 1.2+ minimum (TLS 1.3 diutamakan)

```php
// Enkripsi kolom sensitif di Model
protected $casts = [
    'wa_api_key'    => 'encrypted',
    'smtp_password' => 'encrypted',
];

// Hash OTP saat simpan — WAJIB VARCHAR(64) di kolom otp_codes.code
$otpCode->code = hash('sha256', $rawOtp);   // menghasilkan 64 char hex

// Verifikasi OTP — timing-safe comparison
$isValid = hash_equals(
    hash('sha256', $userInputOtp),
    $storedHash
);
```

---

### A03 – Injection (SQL, XSS, Command)
**Mitigasi SQL Injection:**
- 100% menggunakan Eloquent ORM atau Query Builder dengan parameter binding
- Zero raw SQL query tanpa binding
- Input divalidasi sebelum diproses

```php
// BENAR — Aman dari SQL Injection
Alumni::where('nim', $request->nim)->first();
DB::select('SELECT * FROM alumni WHERE nim = ?', [$nim]);

// SALAH — Dilarang keras
DB::statement("SELECT * FROM alumni WHERE nim = '{$nim}'");
```

**Mitigasi XSS:**
- Vue 3 secara default meng-escape output (hindari `v-html` kecuali konten sudah di-sanitize DOMPurify)
- Content-Security-Policy header di Nginx (lihat Section 9)
- `htmlspecialchars()` / `e()` saat render di Blade
- Input teks bebas disanitize dengan `strip_tags()` untuk field non-HTML

**Mitigasi Command Injection:**
- Zero `shell_exec`, `exec`, `system`, `passthru`, atau `proc_open` dengan input pengguna
- Semua operasi file menggunakan Laravel Storage API

---

### A04 – Insecure Design
**Mitigasi:**
- Token employer satu sesi: setelah submit survei, token tidak bisa digunakan lagi (`survey_status = 'selesai'`)
- Token employer expired otomatis setelah 30 hari (`survey_token_expires_at`)
- OTP expire dalam 5 menit dari waktu generate
- OTP hanya bisa digunakan sekali (`is_used = 1` setelah verifikasi berhasil)
- Session expire otomatis setelah 2 jam inaktif
- Audit log untuk semua perubahan data kritis
- Prinsip least privilege pada setiap role (lihat Matriks Izin Section 3.3)

---

### A05 – Security Misconfiguration
**Mitigasi:**
```dotenv
APP_DEBUG=false            # Wajib false di production
APP_ENV=production
TELESCOPE_ENABLED=false    # Nonaktif di production
```
- Header keamanan HTTP lengkap di Nginx (lihat Section 9)
- `.env` tidak pernah di-commit ke Git, ada di `.gitignore`
- File `.env`, `.git`, `storage/logs` diblokir Nginx dengan `deny all`
- Error messages tidak menampilkan stack trace ke user
- PHP `expose_php = Off` di PHP-FPM config
- MySQL user hanya punya hak `SELECT, INSERT, UPDATE, DELETE` (bukan `SUPER/FILE/PROCESS`)
- Redis dilindungi password (bukan default tanpa auth)

---

### A06 – Vulnerable Components
**Mitigasi:**
- `composer audit` dijalankan setiap sebelum deploy
- `npm audit --audit-level=high` untuk dependencies frontend
- Review dependabot alerts / manual review berkala (bulanan)
- Versi PHP, MySQL, Nginx, Redis selalu up-to-date (minimal patch security terbaru)

---

### A07 – Authentication Failures
**Mitigasi:**
- Rate limiting pada endpoint auth (10 req/menit per IP)
- Rate limiting khusus OTP request (5 req/menit per IP)
- Rate limiting OTP verify (10 req/menit per IP)
- Lockout akun setelah 5 gagal login (terkunci 15 menit)
- OTP max 3 percobaan verifikasi; gagal semua → OTP diinvalidasi
- OTP expire 5 menit; OTP lama diinvalidasi saat request OTP baru
- Cooldown 60 detik sebelum boleh request OTP ulang
- Semua login event di-log ke `audit_logs` (IP, user agent, timestamp, hasil)
- Token Sanctum expire otomatis (configurable via `sanctum.expiration`)
- Logout menghapus token dari `personal_access_tokens`

```php
// AuthController — Lockout Logic
public function login(LoginRequest $request): JsonResponse
{
    $user = User::where('email', $request->email)->first();

    if ($user?->isLocked()) {
        return response()->json([
            'success' => false,
            'message' => "Akun terkunci hingga {$user->locked_until->format('H:i')}.",
            'data'    => ['locked_until' => $user->locked_until],
        ], 423);
    }

    if (!Auth::attempt($request->only('email', 'password'))) {
        $user?->incrementLoginAttempts();  // auto-lock jika >= 5
        AuditLog::record('login_failed', 'Auth', null, null, ['email' => $request->email]);
        return response()->json(['success' => false, 'message' => 'Kredensial tidak valid.'], 401);
    }

    $user = Auth::user();
    $user->resetLoginAttempts();
    $user->update(['last_login_at' => now()]);
    AuditLog::record('login', 'Auth', $user->id, null, null);

    return response()->json([
        'success' => true,
        'message' => 'Login berhasil',
        'data'    => ['token' => $user->createToken('web')->plainTextToken, ...],
    ]);
}
```

---

### A08 – Software and Data Integrity Failures
**Mitigasi:**
- Validasi tipe & ukuran file upload (whitelist MIME type, bukan hanya ekstensi)
- File di-rename ke UUID random sebelum disimpan
- Queue jobs di-serialize dengan aman (hindari unserialize data dari input user)
- `composer.lock` dan `package-lock.json` di-commit ke Git
- Verifikasi checksum file export (PDF/Excel) sebelum dikirim ke user

---

### A09 – Security Logging and Monitoring Failures
**Mitigasi:**
- Semua login (berhasil/gagal/terkunci) di-log ke `audit_logs`
- Semua perubahan data kritis di-log via Eloquent Observers
- Semua akses API admin di-log via middleware `LogActivity`
- Log file disimpan di `storage/logs/laravel-YYYY-MM-DD.log` (daily rotation)
- Alert otomatis jika terjadi 10+ login gagal berturut-turut dari IP yang sama
- `audit_logs` bersifat append-only — tidak ada endpoint delete untuk audit log

---

### A10 – Server-Side Request Forgery (SSRF)
**Mitigasi:**
- Whitelist URL untuk WhatsApp Gateway (hanya domain `wacenter.unisya.ac.id` yang dikonfigurasi)
- Tidak ada endpoint yang menerima URL dari user dan langsung melakukan HTTP fetch
- Validasi URL LinkedIn: wajib domain `linkedin.com` (regex validation)
- Validasi URL website employer: hanya skema `http://` atau `https://`

---

## 3. IMPLEMENTASI RBAC

### 3.1 Definisi Role
| Role | Deskripsi |
|---|---|
| `superadmin` | Akses penuh termasuk hapus data permanen, kelola admin, konfigurasi sistem, audit log |
| `admin` | Kelola alumni, employer, survei, kuesioner, laporan, notifikasi. Tidak bisa hapus permanen atau konfigurasi sistem |
| `alumni` | Akses profil & survei diri sendiri saja |
| `employer` | Akses survei via token (tidak punya akun permanen dengan password) |

> **Catatan:** Sistem memiliki **4 role** (`superadmin`, `admin`, `alumni`, `employer`).
> Superadmin dan Admin sama-sama merupakan staf institusi, namun dengan cakupan izin berbeda.
> Lihat 01_BLUEPRINT.md Section 2 untuk deskripsi lengkap tiap aktor.

### 3.2 Middleware `CheckRole`
```php
// app/Http/Middleware/CheckRole.php
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user() || !in_array($request->user()->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}

// Penggunaan di routes/api.php
Route::middleware(['auth:sanctum', 'role:superadmin,admin'])->group(function () {
    Route::apiResource('alumni', AlumniController::class)->except(['destroy']);
    Route::delete('alumni/{id}', [AlumniController::class, 'destroy'])
         ->middleware('role:superadmin');
});

Route::middleware(['auth:sanctum', 'role:superadmin'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::apiResource('users', UserController::class);
    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
});
```

### 3.3 Matriks Izin Lengkap
| Fitur | Superadmin | Admin | Alumni | Employer |
|---|---|---|---|---|
| Dashboard Admin | ✅ | ✅ | ❌ | ❌ |
| CRUD Alumni (tambah/ubah/import) | ✅ | ✅ | ❌ | ❌ |
| Hapus Alumni (soft delete permanen) | ✅ | ❌ | ❌ | ❌ |
| Export Alumni | ✅ | ✅ | ❌ | ❌ |
| Lihat Detail Alumni (by Admin) | ✅ | ✅ | ❌ | ❌ |
| CRUD Employer | ✅ | ✅ | ❌ | ❌ |
| Hapus Employer (soft delete) | ✅ | ❌ | ❌ | ❌ |
| Kirim Token Survei Employer | ✅ | ✅ | ❌ | ❌ |
| Kelola Kuesioner | ✅ | ✅ | ❌ | ❌ |
| Kelola Periode Survei | ✅ | ✅ | ❌ | ❌ |
| Kirim Undangan Massal | ✅ | ✅ | ❌ | ❌ |
| Kelola Template Notifikasi | ✅ | ✅ | ❌ | ❌ |
| Lihat Log Notifikasi | ✅ | ✅ | ❌ | ❌ |
| Laporan & Ekspor | ✅ | ✅ | ❌ | ❌ |
| Konfigurasi Sistem | ✅ | ❌ | ❌ | ❌ |
| Kelola Admin (tambah/nonaktifkan) | ✅ | ❌ | ❌ | ❌ |
| Audit Log | ✅ | ❌ | ❌ | ❌ |
| Edit Profil Diri Sendiri (Alumni) | ❌ | ❌ | ✅ | ❌ |
| Isi & Submit Survei Alumni | ❌ | ❌ | ✅ | ❌ |
| Riwayat Pekerjaan (kelola sendiri) | ❌ | ❌ | ✅ | ❌ |
| Profil Employer (via token) | ❌ | ❌ | ❌ | ✅ |
| Isi & Submit Survei Employer | ❌ | ❌ | ❌ | ✅ |

> **Catatan Penting — Akses Profil Alumni:**
> - **Admin/Superadmin** dapat melihat data detail semua alumni via `GET /api/v1/admin/alumni/{id}` (baris "Lihat Detail Alumni by Admin").
> - **Alumni** hanya dapat melihat dan mengedit data dirinya sendiri via `GET/PUT /api/v1/alumni/profile` (baris "Edit Profil Diri Sendiri").
> - Kedua akses dikendalikan oleh `AlumniPolicy` — method `view()` mengecek role dan ownership.

---

## 4. SISTEM OTP

### 4.1 Spesifikasi OTP
| Parameter | Nilai |
|---|---|
| Panjang kode | 6 digit numerik |
| Masa berlaku | 5 menit |
| Maksimal percobaan verifikasi | 3 kali |
| Cooldown request ulang | 60 detik |
| Metode generate | `random_int(100000, 999999)` (CSPRNG) |
| Metode penyimpanan | Hash SHA-256 di database, kolom `VARCHAR(64)` |
| Channel pengiriman | WhatsApp / Email |
| Rate limit request | 5 req/menit per IP |

### 4.2 Flow Keamanan OTP
```
1. User request OTP (identifier: NIM / email / phone)
2. Sistem validasi: apakah ada OTP aktif yang belum kedaluwarsa?
   → Jika ada DAN created_at < 60 detik: kembalikan error cooldown
   → Jika ada DAN created_at >= 60 detik: invalidasi OTP lama, buat baru
3. Generate OTP: $rawOtp = random_int(100000, 999999)
4. Hash: $hashedOtp = hash('sha256', (string) $rawOtp)
5. Simpan ke otp_codes: {code: $hashedOtp, expires_at: now()+5menit, attempts: 0}
6. Kirim $rawOtp (plaintext) ke user via WA/Email (via Queue job)
7. User input OTP → hash → bandingkan dengan stored hash (hash_equals)
8. VALID → otp_codes.is_used = 1 → create Sanctum token → return
9. GAGAL → otp_codes.attempts++ → jika attempts >= 3: set is_used = 1 (block)
10. OTP kadaluwarsa/blocked → user harus request OTP baru
11. Cleanup: scheduler harian hapus semua otp_codes yang expired
```

---

## 5. KEAMANAN TOKEN EMPLOYER

### 5.1 Spesifikasi Token
| Parameter | Nilai |
|---|---|
| Panjang token | 64 karakter (alfanumerik, CSPRNG) |
| Masa berlaku | 30 hari dari tanggal pengiriman |
| Penggunaan | Satu survei (tidak bisa akses lagi setelah `survey_status = 'selesai'`) |
| Metode generate | `Str::random(64)` (Laravel, CSPRNG) |
| Penyimpanan | Plaintext di `employers.survey_token` (bukan hash — karena digunakan untuk URL) |
| Transmisi | HTTPS only (embedded dalam URL link survei) |

### 5.2 Middleware Validasi Token
```php
// app/Http/Middleware/ValidateEmployerToken.php
class ValidateEmployerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        $employer = Employer::where('survey_token', $token)
            ->where('survey_token_expires_at', '>', now())
            ->where('survey_status', '!=', 'selesai')  // tidak bisa akses jika sudah selesai
            ->first();

        if (!$employer) {
            return response()->json([
                'success'    => false,
                'message'    => 'Link survei tidak valid atau sudah kedaluwarsa.',
                'error_code' => 'INVALID_EMPLOYER_TOKEN',
            ], 401);
        }

        // Catat waktu pertama akses (jika belum pernah)
        if (!$employer->survey_token_used_at) {
            $employer->update(['survey_token_used_at' => now()]);
        }

        $request->merge(['employer' => $employer]);
        return $next($request);
    }
}
```

---

## 6. PROTEKSI UPLOAD FILE

### 6.1 Validasi File Upload
```php
// Form Request validation rules
'photo' => [
    'required',
    'file',
    'mimes:jpeg,jpg,png',            // Whitelist MIME types (bukan hanya ekstensi)
    'max:2048',                       // Max 2MB
    Rule::dimensions()->maxWidth(2000)->maxHeight(2000),
],
'import_file' => [
    'required',
    'file',
    'mimes:xlsx,csv',
    'max:10240',                      // Max 10MB
],
'logo' => [
    'nullable',
    'file',
    'mimes:jpeg,jpg,png,svg',
    'max:1024',                       // Max 1MB
],
```

### 6.2 Penyimpanan File Aman
- File disimpan di **luar document root**: `storage/app/private/` (bukan di `public/`)
- Akses file hanya via Laravel **signed URL** (bukan direct URL statis)
- Nama file di-rename ke UUID random (bukan nama asli dari user)
- Direktori upload: `autoindex off` di Nginx

```php
// File rename dengan UUID sebelum simpan
$filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
$path = $file->storeAs('photos', $filename, 'private');

// Generate signed URL untuk akses sementara (1 jam)
$url = Storage::disk('private')->temporaryUrl($path, now()->addHour());
```

---

## 7. RATE LIMITING

### 7.1 Konfigurasi Rate Limiter (AppServiceProvider)
```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

RateLimiter::for('otp-request', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip())->response(function () {
        return response()->json([
            'success' => false,
            'message' => 'Terlalu banyak permintaan OTP. Coba lagi dalam 1 menit.',
        ], 429);
    });
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip())->response(function () {
        return response()->json([
            'success' => false,
            'message' => 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.',
        ], 429);
    });
});

RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(20)->by($request->ip());
});

RateLimiter::for('export', function (Request $request) {
    return Limit::perMinutes(5, 5)->by($request->user()->id ?? $request->ip())
        ->response(function () {
            return response()->json([
                'success' => false,
                'message' => 'Batas export tercapai. Coba lagi dalam 5 menit.',
            ], 429);
        });
});
```

### 7.2 Nginx Rate Limiting (Layer Tambahan)
```nginx
limit_req_zone $binary_remote_addr zone=otp:10m  rate=5r/m;
limit_req_zone $binary_remote_addr zone=auth:10m rate=10r/m;
limit_req_zone $binary_remote_addr zone=api:10m  rate=60r/m;
```

---

## 8. AUDIT LOGGING

### 8.1 Event yang Dicatat
| Event | Level | Keterangan |
|---|---|---|
| Login berhasil | INFO | user_id, IP, user_agent |
| Login gagal | WARNING | identifier, IP, alasan |
| Login terkunci | WARNING | identifier, IP, locked_until |
| Logout | INFO | user_id |
| Request OTP | INFO | identifier, channel |
| OTP gagal verifikasi | WARNING | identifier, IP |
| Buat alumni | INFO | user_id, data baru |
| Update alumni | INFO | user_id, old vs new values |
| Hapus alumni (soft) | WARNING | user_id, data yang dihapus |
| Import alumni | INFO | user_id, total_rows, imported, failed |
| Buat employer | INFO | user_id, data baru |
| Publikasi kuesioner | INFO | user_id, questionnaire_id |
| Kirim undangan massal | INFO | user_id, period_id, queued |
| Submit survei alumni | INFO | alumni_id, response_id |
| Submit survei employer | INFO | employer_id, response_id |
| Ubah konfigurasi sistem | WARNING | user_id, key yang diubah (bukan value) |
| Tambah/hapus/nonaktifkan admin | WARNING | user_id, target_user_id |
| Akses audit log | INFO | user_id |
| Generate laporan | INFO | user_id, parameter laporan |
| Regenerate token employer | WARNING | user_id, employer_id |

### 8.2 Implementasi Observer
```php
// app/Observers/AlumniObserver.php
class AlumniObserver
{
    public function created(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'create',
            module: 'Alumni',
            modelId: $alumni->id,
            oldValues: null,
            newValues: $alumni->only(['nim', 'full_name', 'study_program_id', 'graduation_year_id'])
        );
    }

    public function updated(Alumni $alumni): void
    {
        if ($alumni->isDirty()) {
            AuditLog::record(
                action: 'update',
                module: 'Alumni',
                modelId: $alumni->id,
                oldValues: $alumni->getOriginal(),
                newValues: $alumni->getDirty()
            );
        }
    }

    public function deleted(Alumni $alumni): void
    {
        AuditLog::record(
            action: 'delete',
            module: 'Alumni',
            modelId: $alumni->id,
            oldValues: $alumni->only(['nim', 'full_name']),
            newValues: null
        );
    }
}
```

### 8.3 AuditLog Model Helper
```php
// app/Models/AuditLog.php
class AuditLog extends Model
{
    public static function record(
        string $action,
        string $module,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $modelType = null
    ): self {
        return self::create([
            'user_id'    => auth()->id(),
            'user_role'  => auth()->user()?->role,
            'action'     => $action,
            'module'     => $module,
            'model_type' => $modelType,
            'model_id'   => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

## 9. KEAMANAN HEADERS HTTP

> Header ini adalah **sumber kebenaran** untuk konfigurasi CSP. File 04_ARCHITECTURE.md
> me-reference section ini untuk konfigurasi Nginx.

```nginx
# Wajib dipasang di Nginx (semua dengan directive `always`)
add_header X-Frame-Options            "SAMEORIGIN" always;
add_header X-XSS-Protection           "1; mode=block" always;
add_header X-Content-Type-Options     "nosniff" always;
add_header Referrer-Policy            "strict-origin-when-cross-origin" always;
add_header Permissions-Policy         "camera=(), microphone=(), geolocation=()" always;
add_header Strict-Transport-Security  "max-age=31536000; includeSubDomains; preload" always;

# Content Security Policy
# - script-src: 'unsafe-eval' diperlukan untuk Vue 3 dev; di production pertimbangkan hash-based CSP
# - style-src:  Google Fonts diizinkan untuk font Plus Jakarta Sans & Inter
# - font-src:   Google Fonts diizinkan
# - img-src:    data: untuk base64 inline image, blob: untuk object URL
# - connect-src: 'self' saja (API call ke domain yang sama)
add_header Content-Security-Policy "
    default-src  'self';
    script-src   'self' 'unsafe-inline' 'unsafe-eval';
    style-src    'self' 'unsafe-inline' https://fonts.googleapis.com;
    font-src     'self' https://fonts.gstatic.com;
    img-src      'self' data: blob:;
    connect-src  'self';
    frame-ancestors 'none';
    base-uri     'self';
    form-action  'self';
" always;
```

---

## 10. KONFIGURASI CORS

```php
// config/cors.php
return [
    'paths'                  => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'        => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins'        => [env('FRONTEND_URL', 'https://tracer.unisya.ac.id')],
    'allowed_origins_patterns' => [],
    'allowed_headers'        => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-CSRF-TOKEN',
    ],
    'exposed_headers'        => ['X-API-Version'],
    'max_age'                => 86400,       // 24 jam preflight cache
    'supports_credentials'   => true,
];
```

---

## 11. PROTEKSI MASS ASSIGNMENT

```php
// Semua Model menggunakan $fillable (whitelist), BUKAN $guarded = []
class Alumni extends Model
{
    protected $fillable = [
        'user_id', 'nim', 'nik', 'full_name', 'gender',
        'birth_place', 'birth_date', 'study_program_id',
        'graduation_year_id', 'thesis_title', 'gpa',
        'graduation_predicate', 'address_street', 'address_village',
        'address_district', 'address_city', 'address_province',
        'address_postal_code', 'address_latitude', 'address_longitude',
        'phone', 'email', 'linkedin_url', 'photo',
        'survey_status', 'import_batch',
    ];

    // Field yang tidak pernah diekspos ke API response
    protected $hidden = ['deleted_at'];

    // Field yang tidak boleh di-set via mass assignment:
    // id, user_id (set manual), created_at, updated_at, deleted_at
}
```

---

## 12. ENKRIPSI DATABASE

- Kolom sensitif menggunakan `encrypted` cast Laravel:
  - `system_settings.value` jika `is_encrypted = 1`
  - Password SMTP, token WA Gateway di `system_settings`
- Backup database dienkripsi dengan GPG sebelum disimpan ke disk
- MySQL SSL connection direkomendasikan jika MySQL di server terpisah
- Tidak ada kolom password yang disimpan dalam plaintext di database apapun

---

## 13. PENGELOLAAN DEPENDENCY

```bash
# Jalankan setiap sebelum deploy ke production
composer audit
npm audit --audit-level=high

# Update rutin (bulanan)
composer update --no-dev --optimize-autoloader
npm update

# Lock file wajib di-commit ke Git
# composer.lock
# package-lock.json
```

---

## 14. INCIDENT RESPONSE

| Skenario | Tindakan Langsung | Tindakan Lanjutan |
|---|---|---|
| Brute force login | Auto-lock 15 menit, catat di audit_log | Alert ke superadmin, block IP via Nginx |
| Token employer bocor | Admin regenerate token via dashboard | Notifikasi employer dengan token baru |
| Data breach | Nonaktifkan semua sesi (hapus personal_access_tokens), alert superadmin | Audit log review, notifikasi pengguna terdampak |
| OTP abuse / rate limit bypass | Block IP via Nginx fail2ban | Review log, perketat rate limit |
| Malicious file upload | Hapus file dari storage, log event | Block user, review filter MIME |
| Token employer digunakan setelah expired | Token tidak bisa diakses (middleware reject) | Superadmin/admin generate token baru |

---

## 15. CHECKLIST KEAMANAN DEPLOY

- [ ] `APP_DEBUG=false` di .env production
- [ ] `APP_ENV=production`
- [ ] `TELESCOPE_ENABLED=false`
- [ ] HTTPS aktif dengan sertifikat valid (Let's Encrypt)
- [ ] HSTS header aktif (`max-age=31536000`)
- [ ] Rate limiting Nginx aktif (otp, auth, api zones)
- [ ] `composer audit` menghasilkan 0 vulnerability
- [ ] `npm audit --audit-level=high` bersih
- [ ] File `.env` tidak bisa diakses publik (Nginx `deny all`)
- [ ] Directory listing dinonaktifkan (`autoindex off`)
- [ ] PHP `expose_php = Off`
- [ ] MySQL user hanya hak `SELECT, INSERT, UPDATE, DELETE`
- [ ] Redis dilindungi password
- [ ] Backup database dikonfigurasi (harian, dienkripsi GPG)
- [ ] Supervisor queue worker berjalan (`sitras-worker-default` + `sitras-worker-low`)
- [ ] Cron scheduler aktif (`php artisan schedule:run`)
- [ ] Log rotation dikonfigurasi (logrotate)
- [ ] Firewall: hanya port 80, 443, 22 yang terbuka
- [ ] SSH menggunakan key-based auth (password auth dinonaktifkan)
- [ ] Storage directory di luar document root (akses via signed URL)
- [ ] CSP header terpasang dan diverifikasi via securityheaders.com

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Perjelas 4 role (superadmin, admin, alumni, employer) di Section 3.1; update matriks izin untuk DELETE employer (superadmin only); tambah `delete` method di AlumniPolicy contoh; tambah catatan otp_codes.code wajib VARCHAR(64); perjelas cooldown OTP 60 detik di spesifikasi Section 4; perjelas token employer satu survei (bukan satu request); CSP header dikonsolidasi sebagai sumber kebenaran di Section 9; tambah `EMPLOYER_TOKEN_EXPIRY_DAYS` ke checklist deploy; tambah CORS `max_age` dan `exposed_headers` |
| 1.0.2 | 2026-06-08 | Update SSRF whitelist domain dari Fonnte/Wablas → `wacenter.unisya.ac.id`; update nama cast kolom dari `wa_api_token` → `wa_api_key` sesuai perubahan konfigurasi gateway |
| 1.0.3 | 2026-06-09 | Fix matriks izin Section 3.3: pisah baris "Profil Diri Alumni" menjadi "Lihat Detail Alumni (by Admin)" dan "Edit Profil Diri Sendiri (Alumni)" untuk menghilangkan ambiguitas implementasi AlumniPolicy; tambah catatan penting pemisahan dua akses tersebut (INC-05) |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
