# 09_CHANGELOG.md
# CHANGELOG — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.3 | Tanggal: 2026-06-09

---

## KONVENSI CHANGELOG

Setiap entri changelog mengikuti format:

```
## [Versi] — YYYY-MM-DD
### Kategori Perubahan
- Deskripsi perubahan spesifik [File Terdampak]
```

**Kategori:**
- `Added` — Fitur / konten baru
- `Fixed` — Perbaikan inkonsistensi / bug dokumentasi
- `Changed` — Perubahan yang tidak breaking
- `Removed` — Konten yang dihapus
- `Security` — Perbaikan keamanan
- `Deprecated` — Fitur yang akan dihapus di versi mendatang

---

## [1.0.4] — 2026-06-09

> **Sumber:** Penyelesaian Sesi 1A — Setup Proyek & Database.
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Perubahan berisi penambahan file kode produksi — bukan perubahan dokumentasi spesifikasi.**

---

### Added — File Kode Produksi Sesi 1A

#### Added — Migrations (10 file)
- `database/migrations/0001_01_01_000000_create_users_table.php` — Tabel `users` (ENUM role 4 nilai, `login_attempts` TINYINT UNSIGNED, `locked_until` TIMESTAMP NULL, SoftDeletes, index role+phone) + tabel `sessions`
- `database/migrations/2026_06_09_000001_create_personal_access_tokens_table.php` — Tabel `personal_access_tokens` standar Sanctum
- `database/migrations/2026_06_09_000002_create_otp_codes_table.php` — Tabel `otp_codes`; **`code VARCHAR(64)` — SHA-256 hex digest (kritis: bukan VARCHAR(10))**
- `database/migrations/2026_06_09_000003_create_audit_logs_table.php` — Tabel `audit_logs`; append-only, tidak ada `updated_at`, index: user_id, action, module, created_at, (model_type, model_id)
- `database/migrations/2026_06_09_000004_create_faculties_table.php` — Tabel `faculties`
- `database/migrations/2026_06_09_000005_create_study_programs_table.php` — Tabel `study_programs` + FK ke `faculties`
- `database/migrations/2026_06_09_000006_create_graduation_years_table.php` — Tabel `graduation_years`
- `database/migrations/2026_06_09_000007_create_system_settings_table.php` — Tabel `system_settings`
- `database/migrations/2026_06_09_000008_create_industry_sectors_table.php` — Tabel `industry_sectors`
- `database/migrations/2026_06_09_000009_create_salary_ranges_table.php` — Tabel `salary_ranges`

#### Added — Models (9 file)
- `app/Models/User.php` — `$fillable`, `$hidden` (password, remember_token), `$casts` (datetime, bool, hashed), SoftDeletes, `HasApiTokens`; relationships: alumni, employer, otpCodes, auditLogs; methods: `isLocked()`, `incrementLoginAttempts()`, `resetLoginAttempts()`, `isSuperadmin()`, `isAdmin()`
- `app/Models/OtpCode.php` — `$fillable`, `$casts`, `scopeActive()` (is_used=0, expires_at > now, attempts < 3)
- `app/Models/AuditLog.php` — Append-only (`UPDATED_AT = null`), `$fillable`, `$casts` (old/new_values → array), `withTrashed()` pada relationship user; static `AuditLog::record(action, module, modelId, oldValues, newValues, modelType)` sesuai `07_SECURITY.md §8.3`
- `app/Models/Faculty.php` — `hasMany(StudyProgram)`
- `app/Models/StudyProgram.php` — `belongsTo(Faculty)`, `hasMany(Alumni)`
- `app/Models/GraduationYear.php`
- `app/Models/SystemSetting.php`
- `app/Models/IndustrySector.php`
- `app/Models/SalaryRange.php`

#### Added — Seeders (8 file)
- `database/seeders/SuperadminSeeder.php` — 1 superadmin: `superadmin@unisya.ac.id`, bcrypt cost 12
- `database/seeders/FacultySeeder.php` — 3+ fakultas konteks UNISYA
- `database/seeders/StudyProgramSeeder.php` — 8+ prodi, FK ke fakultas
- `database/seeders/GraduationYearSeeder.php` — Angkatan 2020–2024
- `database/seeders/IndustrySectorSeeder.php`
- `database/seeders/SalaryRangeSeeder.php`
- `database/seeders/SystemSettingSeeder.php` — Seed 3 key WA Gateway: `wa_gateway_url` (`https://wacenter.unisya.ac.id/send-message`), `wa_api_key` (kosong), `wa_sender` (kosong); juga key: `university_name`, `university_tagline`, `smtp_*`
- `database/seeders/DatabaseSeeder.php` — Memanggil semua seeder di atas

#### Added — Config (3 file baru)
- `config/tracer.php` — Key: `otp.expiry_minutes` (5), `otp.max_attempts` (3), `otp.resend_cooldown_seconds` (60), `login.max_attempts` (5), `login.lockout_minutes` (15), `employer_token.expiry_days` (30); baca dari `.env` dengan default values
- `config/whatsapp.php` — Key: `gateway_url`, `api_key`, `sender`; baca dari `system_settings` via runtime
- `config/cors.php` — `allowed_origins: [env('FRONTEND_URL')]`, `supports_credentials: true`, `max_age: 86400`, sesuai `07_SECURITY.md §10`

#### Changed — Config (3 file diupdate)
- `config/database.php` — Redis connection ditambahkan
- `config/queue.php` — Redis driver, queue: high, default, low
- `config/session.php` — Redis driver

#### Added — Observers (4 file placeholder)
- `app/Observers/AlumniObserver.php` — Placeholder; diisi sesi 2B saat model Alumni tersedia
- `app/Observers/EmployerObserver.php` — Placeholder; diisi sesi 2C
- `app/Observers/SurveyResponseObserver.php` — Placeholder; diisi sesi 3B
- `app/Observers/UserObserver.php` — Placeholder; diisi sesi 1B+

#### Changed — App Provider
- `app/Providers/AppServiceProvider.php` — Registrasi `User::observe(UserObserver::class)` aktif; observer lain dikomentari dengan keterangan sesi aktivasi; tambah `Model::shouldBeStrict(!app()->isProduction())` dan `URL::forceScheme('https')` untuk production

#### Changed — Frontend Config
- `vite.config.js` — Konfigurasi Vue 3 + `@vitejs/plugin-vue`
- `tailwind.config.js` — Custom design tokens sesuai `06_UI_UX.md §1.2`
- `package.json` — Dependencies: `vue@3`, `@vitejs/plugin-vue`, `tailwindcss`, `postcss`, `autoprefixer`, `pinia`, `vue-router@4`, `axios`
- `package.json` — Dependencies frontend lengkap; **Fix #1**: upgrade `apexcharts` dari `^3.54.0` → `^5.0.0` untuk memenuhi peer dependency `vue3-apexcharts@1.8.0` yang membutuhkan `apexcharts >= 4.0.0`; **Fix #2**: upgrade `@vitejs/plugin-vue` dari `^5.2.3` → `^6.0.0` karena `vite@7.x` membutuhkan `@vitejs/plugin-vue >= 6.0.0`; tidak ada breaking change karena belum ada kode chart maupun kode Vue yang ditulis di fase ini

#### Changed — Environment
- `.env.example` — Tambah: `WHATSAPP_GATEWAY_URL`, `WHATSAPP_API_KEY`, `WHATSAPP_SENDER`, `OTP_EXPIRY_MINUTES=5`, `OTP_MAX_ATTEMPTS=3`, `OTP_RESEND_COOLDOWN_SECONDS=60`, `LOGIN_MAX_ATTEMPTS=5`, `LOGIN_LOCKOUT_MINUTES=15`, `FRONTEND_URL`

---

### Ringkasan File Terdampak v1.0.4

| File | Aksi | Keterangan |
|---|---|---|
| 10 migration files | Added | Tabel users, sessions, personal_access_tokens, otp_codes, audit_logs, faculties, study_programs, graduation_years, system_settings, industry_sectors, salary_ranges |
| 9 model files | Added | User, OtpCode, AuditLog, Faculty, StudyProgram, GraduationYear, SystemSetting, IndustrySector, SalaryRange |
| 8 seeder files | Added | Superadmin, Faculty, StudyProgram, GraduationYear, IndustrySector, SalaryRange, SystemSetting, DatabaseSeeder |
| `config/tracer.php` | Added | Konfigurasi OTP, login lockout, employer token |
| `config/whatsapp.php` | Added | Konfigurasi WA Gateway |
| `config/cors.php` | Changed | CORS sesuai spec security |
| `config/database.php` | Changed | Redis connection |
| `config/queue.php` | Changed | Redis driver, multi-queue |
| `config/session.php` | Changed | Redis driver |
| 4 observer files | Added | Placeholder observers |
| `app/Providers/AppServiceProvider.php` | Changed | Observer registration + security config |
| `vite.config.js` | Changed | Vue 3 plugin |
| `tailwind.config.js` | Changed | Custom design tokens |
| `package.json` | Changed | Dependencies frontend; 2 hotfix peer dependency: apexcharts ^3→^5, @vitejs/plugin-vue ^5→^6 |
| `.env.example` | Changed | Tambah env keys WA, OTP, login |
| `08_PHASE_TRACKER.md` | Changed | Sesi 1A 19/19 task → ✅; counter selesai 0→19 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 37 file ditambah/diubah | 1A complete: 19/19 task ✅**
**Task selesai keseluruhan: 19/199**

---

---

## [1.0.3] — 2026-06-09

> **Sumber:** Audit konsistensi dokumen v1.0.3 sebelum development dimulai.
> Auditor: Claude (Fullstack Laravel Vue Developer).
> **Semua perubahan bersifat dokumentasi — tidak ada perubahan pada skema database atau API endpoint.**

---

### 🟠 MAJOR FIXES

#### Fixed — [INC-01] Blueprint: Tabel identitas proyek tidak sinkron dengan versi header dokumen
**Ditemukan di:** `01_BLUEPRINT.md` Section 1.1

**Masalah:**
Header file sudah `v1.0.2 / 2026-06-08`, namun tabel Identitas Proyek masih mencantumkan
`Versi: 1.0.1` dan `Tanggal Dokumen: 2026-06-06` — tertinggal satu siklus perubahan sejak audit v1.0.2.

**Perbaikan:**
- `01_BLUEPRINT.md` Section 1.1: `Versi 1.0.1` → `1.0.2`, `Tanggal Dokumen 2026-06-06` → `2026-06-08`

---

#### Fixed — [INC-02] Architecture: Diagram blok masih menyebut "Fonnte/Wablas" sebagai WA Gateway
**Ditemukan di:** `04_ARCHITECTURE.md` Section 1.1, diagram ASCII External Services

**Masalah:**
Diagram arsitektur mencantumkan `(Fonnte/Wablas)` sebagai label WA Gateway. Ini adalah
satu-satunya referensi yang terlewat dari audit v1.0.2 yang sudah mengupdate semua dokumen
lain ke gateway UNISYA `wacenter.unisya.ac.id`.

**Perbaikan:**
- `04_ARCHITECTURE.md` diagram External Services: `(Fonnte/Wablas)` → `(wacenter.unisya.ac.id)`

---

### 🟡 MODERATE FIXES

#### Fixed — [INC-03] Phase Tracker: Header "Total Task: 167" sudah tidak akurat (seharusnya 199)
**Ditemukan di:** `08_PHASE_TRACKER.md` Section STATUS RINGKASAN

**Masalah:**
Baris `Total Task: 167 task` di header STATUS RINGKASAN tidak pernah diperbarui sejak versi awal,
padahal tabel RINGKASAN TASK PER FASE di bagian bawah dokumen sudah benar mencantumkan 199 task.
Perbedaan 32 task di antara dua section dalam satu file yang sama adalah inkonsistensi internal kritis.

**Perbaikan:**
- `08_PHASE_TRACKER.md` header: `Total Task: 167 task` → `Total Task: 199 task`

---

#### Fixed — [INC-04] API: Endpoint reorder pertanyaan tidak dilengkapi catatan routing Laravel
**Ditemukan di:** `05_API.md` Section 5.13

**Masalah:**
Endpoint `PUT /questions/reorder` berpotensi konflik dengan route resource `PUT /questions/{id}`
di Laravel jika tidak didefinisikan dengan urutan yang tepat. Tanpa catatan ini, developer
berisiko mengalami bug routing yang sulit dideteksi.

**Perbaikan:**
- `05_API.md` Section 5.13: Tambah blok catatan implementasi Laravel — route `/questions/reorder`
  wajib didaftarkan **SEBELUM** route resource `questions/{id}` di `routes/api.php`

---

#### Fixed — [INC-05] Security: Matriks izin ambigu untuk akses profil alumni
**Ditemukan di:** `07_SECURITY.md` Section 3.3

**Masalah:**
Baris `Profil Diri Alumni` dengan `Admin: ❌` tidak akurat karena Admin justru bisa melihat
detail alumni via endpoint `GET /api/v1/admin/alumni/{id}`. Ambiguitas ini berisiko menyebabkan
developer mengimplementasikan `AlumniPolicy` dengan batasan yang salah.

**Perbaikan:**
- `07_SECURITY.md` Section 3.3: Pisah menjadi dua baris:
  - "Lihat Detail Alumni (by Admin)" → Admin: ✅
  - "Edit Profil Diri Sendiri (Alumni)" → Alumni: ✅
- Tambah catatan penting yang menjelaskan perbedaan kedua akses

---

### 🟢 MINOR FIXES

#### Fixed — [INC-06 & INC-07] Architecture: Folder structure frontend tidak mencerminkan semua file .vue
**Ditemukan di:** `04_ARCHITECTURE.md` Section 2, folder structure `pages/`

**Masalah:**
Folder `pages/` di folder structure hanya mencantumkan nama direktori tanpa isi file,
sementara Phase Tracker dan UI/UX spec sudah mendefinisikan nama file .vue yang spesifik.
File yang tidak tercantum antara lain: `SurveyDonePage.vue`, `StatisticsPage.vue`,
`AlumniImportPage.vue`, dan semua file di sub-direktori admin.

**Perbaikan:**
- `04_ARCHITECTURE.md` Section 2: Lengkapi folder structure `pages/` dengan semua nama
  file .vue yang terdefinisi di Phase Tracker (Sesi 2A–5B) dan UI/UX spec (Section 8)

---

### Ringkasan File Terdampak v1.0.3

| File | Versi Sebelum | Versi Sesudah | Jenis Perubahan |
|---|---|---|---|
| 01_BLUEPRINT.md | 1.0.2 | 1.0.3 | Fixed (tabel identitas versi + tanggal) |
| 04_ARCHITECTURE.md | 1.0.2 | 1.0.3 | Fixed (label WA diagram); Added (lengkap folder structure pages) |
| 05_API.md | 1.0.2 | 1.0.3 | Added (catatan routing reorder Laravel) |
| 07_SECURITY.md | 1.0.2 | 1.0.3 | Fixed (matriks izin alumni profil — pisah 2 baris) |
| 08_PHASE_TRACKER.md | 1.0.2 | 1.0.3 | Fixed (total task header 167→199) |
| 09_CHANGELOG.md | 1.0.2 | 1.0.3 | Added (entri ini) |

**File tidak diubah:** `02_DATABASE.md`, `03_ERD.md`, `06_UI_UX.md`
**Total: 6 file direvisi | 0 perubahan skema database | 0 perubahan API endpoint**

---

## [1.0.2] — 2026-06-08

> **Sumber:** Audit kesesuaian dokumentasi sistem dengan spesifikasi API WA Gateway UNISYA
> (`https://wacenter.unisya.ac.id/send-message`).
> **Semua perubahan bersifat dokumentasi — tidak ada perubahan pada kode produksi.**

---

### 🟠 MAJOR CHANGES — Penyesuaian WA Gateway UNISYA

#### Changed — Nama dan struktur konfigurasi WhatsApp Gateway diperbarui
**Ditemukan di:** 04_ARCHITECTURE.md, 05_API.md, 06_UI_UX.md, 07_SECURITY.md, 08_PHASE_TRACKER.md
**Sumber referensi:** Dokumentasi API WA Gateway UNISYA `wacenter.unisya.ac.id/send-message`

**Masalah Sebelum Audit:**
Dokumen sistem mengasumsikan WA Gateway menggunakan pola autentikasi Fonnte (header
`Authorization: Bearer <token>`). Padahal WA Gateway UNISYA menggunakan parameter body JSON
dengan tiga parameter wajib/konfigurasi: `api_key`, `sender`, dan `number`. Akibatnya:
- Key nama `.env` `WHATSAPP_API_TOKEN` tidak mencerminkan parameter `api_key` di body
- Contoh konfigurasi di API spec masih menunjuk URL Fonnte
- SSRF whitelist di Security doc menyebut Fonnte/Wablas
- `WhatsAppService` task belum dispesifikasikan sesuai struktur request gateway UNISYA
- UI Setting hanya menyebut "token" tanpa keterangan field yang benar

**Perubahan:**

**`04_ARCHITECTURE.md` (v1.0.2):**
- `.env.example`: `WHATSAPP_API_TOKEN` → `WHATSAPP_API_KEY`
- `.env.example`: `WHATSAPP_GATEWAY_URL` → `https://wacenter.unisya.ac.id/send-message`
- Komentar `config/whatsapp.php` diperbarui

**`05_API.md` (v1.0.2):**
- GET Settings response (Section 10.1): group `whatsapp` sekarang menampilkan 3 key:
  - `wa_gateway_url` dengan value default `https://wacenter.unisya.ac.id/send-message`
  - `wa_api_key` (dapat diisi/diubah via menu Setting — masked di response)
  - `wa_sender` (nomor pengirim, dapat diisi/diubah via menu Setting)
- PUT Settings contoh: `wa_gateway_token` → `wa_api_key` + tambah `wa_sender`

**`06_UI_UX.md` (v1.0.2):**
- Tab "WhatsApp Gateway" di halaman Konfigurasi Sistem (Section 3.10): label field
  dari "token (masked)" → "API Key (`wa_api_key`, masked)" dengan keterangan key name
  eksplisit untuk tiap field (`wa_gateway_url`, `wa_api_key`, `wa_sender`)

**`07_SECURITY.md` (v1.0.2):**
- SSRF whitelist domain: `Fonnte/Wablas` → `wacenter.unisya.ac.id`
- Nama cast kolom sensitif: `wa_api_token` → `wa_api_key`

**`08_PHASE_TRACKER.md` (v1.0.2):**
- Task 4A.11 `WhatsAppService`: spesifikasi diperinci — POST JSON ke gateway UNISYA dengan
  parameter `api_key`, `sender`, `number`, `message`, `footer` (opsional); baca config dari
  `system_settings` (key: `wa_gateway_url`, `wa_api_key`, `wa_sender`); aktifkan `full=1`
  untuk mendapat `message_id`; simpan ke `notification_logs.provider_response`
- Task 1A.17 `SystemSettingSeeder`: tambah seed 3 key WA gateway dengan URL default gateway UNISYA

**`01_BLUEPRINT.md` (v1.0.2):**
- Section 5.1 Batasan Sistem: referensi gateway dari "Fonnte / WA Gateway" → gateway UNISYA
  dengan keterangan konfigurasi via menu Pengaturan Sistem

---

### 🟡 MODERATE CHANGES — Klarifikasi Status `delivered` & `provider_response`

#### Added — Catatan perilaku `notification_logs.status` untuk WA Gateway UNISYA
**Ditemukan di:** 02_DATABASE.md, 03_ERD.md
**Konteks:** WA Gateway UNISYA tidak menyediakan webhook delivery callback, sehingga status
`delivered` tidak dapat diisi secara otomatis dari gateway ini.

**Perubahan:**

**`02_DATABASE.md` (v1.0.2):**
- Tabel `notification_logs`: tambah catatan desain menjelaskan bahwa `delivered` tidak diisi
  otomatis dari gateway UNISYA; nilai dipertahankan di ENUM untuk Email dan kompatibilitas
  masa depan; kolom `provider_response` diperjelas untuk menyimpan `message_id` dari gateway

**`03_ERD.md` (v1.0.2):**
- Diagram cluster Notifikasi: tambah komentar inline pada `notification_logs.status` dan
  `notification_logs.provider_response` sesuai perilaku WA Gateway UNISYA

---

### 🟢 MINOR — Aktifkan `full=1` untuk Traceability Message ID

#### Added — Spesifikasi penggunaan parameter `full=1` di WhatsAppService
**Konteks:** WA Gateway UNISYA mendukung parameter `full=1` yang menyebabkan response
menyertakan `data.key.id` (message ID WA). Dengan menyimpan ini ke `provider_response`,
sistem memiliki traceability jika ada laporan pesan tidak terkirim.

**Perubahan:** Tercakup di update task 4A.11 (`08_PHASE_TRACKER.md`) — tidak ada perubahan
skema database (kolom `provider_response JSON` sudah ada sejak v1.0.0).

---

## Ringkasan File Terdampak v1.0.2

| File | Versi Sebelum | Versi Sesudah | Jenis Perubahan |
|---|---|---|---|
| 01_BLUEPRINT.md | 1.0.1 | 1.0.2 | Changed (referensi gateway) |
| 02_DATABASE.md | 1.0.1 | 1.0.2 | Added (catatan status delivered & provider_response) |
| 03_ERD.md | 1.0.1 | 1.0.2 | Added (catatan inline diagram notification_logs) |
| 04_ARCHITECTURE.md | 1.0.1 | 1.0.2 | Changed (env key, URL gateway) |
| 05_API.md | 1.0.1 | 1.0.2 | Changed (contoh settings WA: 3 key, URL benar) |
| 06_UI_UX.md | 1.0.1 | 1.0.2 | Changed (label field tab WA Gateway) |
| 07_SECURITY.md | 1.0.1 | 1.0.2 | Changed (SSRF whitelist, nama cast kolom) |
| 08_PHASE_TRACKER.md | 1.0.1 | 1.0.2 | Changed (task 4A.11 & 1A.17 diperinci) |
| 09_CHANGELOG.md | 1.0.1 | 1.0.2 | Added (entri ini) |

**Total: 9 file direvisi | Tidak ada perubahan skema database | Tidak ada perubahan API endpoint**

---

## [1.0.1] — 2026-06-06

> **Sumber:** Audit konsistensi lintas-dokumen (01–09) yang dilakukan sebelum implementasi.
> Tujuan audit: memastikan nol miskomunikasi antar dokumen sebelum development dimulai.
> **Semua perubahan bersifat dokumentasi — tidak ada perubahan pada kode produksi.**

---

### 🔴 CRITICAL FIXES (Wajib diperbaiki sebelum development dimulai)

#### Fixed — `otp_codes.code` tipe kolom tidak konsisten dengan implementasi keamanan
**Ditemukan di:** 02_DATABASE.md, 03_ERD.md
**Konflik dengan:** 07_SECURITY.md Section 2 (A02)

**Masalah:**
Kolom `otp_codes.code` dideklarasikan sebagai `VARCHAR(10)` di 02_DATABASE.md dan 03_ERD.md.
Namun 07_SECURITY.md Section 2 (A02) secara eksplisit menyatakan OTP di-hash menggunakan SHA-256
sebelum disimpan ke database. SHA-256 menghasilkan 64 karakter hex digest.
`VARCHAR(10)` tidak dapat menampung 64 karakter — akan menyebabkan data truncation atau error
saat menyimpan hash OTP ke database.

**Dampak Jika Tidak Diperbaiki:**
- Runtime error: Data OTP hash tidak tersimpan dengan benar
- Security flaw: Jika dipaksakan VARCHAR(10), developer mungkin menyimpan OTP plaintext
- Sistem OTP tidak berfungsi

**Perbaikan:**
- `02_DATABASE.md`: `otp_codes.code` VARCHAR(10) → **VARCHAR(64)**; tambah catatan keamanan eksplisit
- `03_ERD.md`: Update diagram untuk mencerminkan VARCHAR(64); tambah detail alur OTP di Section 3.4
- `07_SECURITY.md`: Tidak perlu perubahan (sudah benar sebagai sumber kebenaran)

---

### 🟠 MAJOR FIXES (Berpotensi menyebabkan inkonsistensi implementasi)

#### Fixed — Actor `admin` tidak terdefinisi di Blueprint
**Ditemukan di:** 01_BLUEPRINT.md (Section 2 — Aktor Sistem)
**Konflik dengan:** 02_DATABASE.md, 03_ERD.md, 07_SECURITY.md, 04_ARCHITECTURE.md

**Masalah:**
Bagian Aktor di 01_BLUEPRINT.md hanya mendefinisikan 3 aktor: Superadmin, Alumni, Employer.
Namun seluruh dokumen teknis lainnya (02_DATABASE.md, 07_SECURITY.md) mendefinisikan
`users.role` ENUM dengan 4 nilai: `superadmin`, `admin`, `alumni`, `employer`.
Role `admin` disebutkan dalam teks narasi Blueprint ("Manajemen pengguna sistem (akun admin lain)")
tetapi tidak pernah didefinisikan sebagai aktor tersendiri dengan deskripsi dan hak akses.

**Dampak Jika Tidak Diperbaiki:**
- Developer tidak memiliki definisi yang jelas tentang batas wewenang `admin` vs `superadmin`
- Matriks izin di Security bisa diimplementasikan secara tidak konsisten
- Kebingungan saat implementasi middleware CheckRole

**Perbaikan:**
- `01_BLUEPRINT.md`:
  - Tambah Actor **2.2 Admin** dengan deskripsi dan hak akses lengkap (antara Superadmin dan Alumni)
  - Renomor: Alumni → 2.3, Employer → 2.4
  - Tambah catatan perbedaan superadmin vs admin di header bagian 2

---

#### Fixed — Endpoint Manajemen Notifikasi hilang dari API Specification
**Ditemukan di:** 05_API.md
**Konflik dengan:** 04_ARCHITECTURE.md (folder `NotificationController.php` ada), 06_UI_UX.md (route `/admin/notifications/templates` dan `/admin/notifications/logs` terdefinisi)

**Masalah:**
`04_ARCHITECTURE.md` secara eksplisit mencantumkan `NotificationController.php` dalam folder struktur.
`06_UI_UX.md` mendefinisikan route `/admin/notifications/templates` dan `/admin/notifications/logs`.
Namun `05_API.md` tidak memiliki endpoint apapun untuk:
- CRUD Template Notifikasi
- Listing Log Notifikasi

Ini berarti developer frontend tidak tahu endpoint mana yang harus dipanggil untuk fitur
manajemen notifikasi, sedangkan developer backend tidak memiliki spesifikasi endpoint resmi.

**Dampak Jika Tidak Diperbaiki:**
- Fitur manajemen template notifikasi tidak terimplementasi (UI ada, API tidak)
- Developer backend dan frontend akan membuat asumsi yang berbeda
- Fitur notification log di UI tidak bisa diisi data

**Perbaikan:**
- `05_API.md`: Tambah **Section 9 baru — Endpoint Admin: Notifikasi** mencakup:
  - `GET /admin/notifications/templates` (list dengan filter type, event)
  - `GET /admin/notifications/templates/{id}` (detail)
  - `POST /admin/notifications/templates` (buat template baru)
  - `PUT /admin/notifications/templates/{id}` (update template)
  - `DELETE /admin/notifications/templates/{id}` (hapus template)
  - `GET /admin/notifications/logs` (list log dengan filter type, status, date)
- `08_PHASE_TRACKER.md`: Tambah task 4A.13, 4A.14, 4A.27, 4A.28, 4A.23

---

### 🟡 MODERATE FIXES (Berpotensi menyebabkan bug spesifik jika dibiarkan)

#### Fixed — Tipe data `gpa` tidak konsisten di API response examples
**Ditemukan di:** 05_API.md (contoh response JSON di beberapa endpoint)
**Konflik dengan:** 02_DATABASE.md (`alumni.gpa` bertipe `DECIMAL(4,2)`)

**Masalah:**
Di contoh response API, `gpa` ditampilkan sebagai string:
```json
"gpa": "3.75"
```
Padahal di database (`02_DATABASE.md`) kolom `alumni.gpa` bertipe `DECIMAL(4,2)`.
Laravel Eloquent secara default akan me-return decimal sebagai string kecuali dikonfigurasi cast.
Namun konsistensi dokumentasi harus menunjukkan tipe yang benar (number).

**Dampak Jika Tidak Diperbaiki:**
- Developer frontend mungkin mengimplementasikan parsing string untuk gpa
- Perhitungan statistik IPK akan bermasalah jika diterima sebagai string
- Inkonsistensi perilaku antara staging dan production

**Perbaikan:**
- `05_API.md`: Semua contoh response yang menampilkan `gpa` diubah dari `"3.75"` → `3.75` (number)
- Tambah catatan di `02_DATABASE.md`: Model harus mendeklarasikan `'gpa' => 'float'` di `$casts`

---

#### Fixed — Route frontend `/alumni/work-history` tidak sesuai API endpoint
**Ditemukan di:** 06_UI_UX.md (Section 8 — Alur Navigasi/Routing)
**Konflik dengan:** 05_API.md (endpoint `/api/v1/alumni/work-histories` — plural)

**Masalah:**
06_UI_UX.md mendefinisikan route frontend sebagai `/alumni/work-history` (singular).
Namun 05_API.md mendefinisikan endpoint API sebagai `/api/v1/alumni/work-histories` (plural).
Inkonsistensi singular/plural antara frontend route dan API endpoint menambah kebingungan developer.

**Dampak Jika Tidak Diperbaiki:**
- Inkonsistensi penamaan yang membingungkan developer, terutama developer baru
- Jika ada breadcrumb otomatis yang generate dari URL, akan tampil berbeda dari label yang diharapkan
- Standar REST mensyaratkan resource collections menggunakan plural

**Perbaikan:**
- `06_UI_UX.md`: Route `/alumni/work-history` → `/alumni/work-histories`

---

#### Fixed — Summary table API (Section 14) tidak lengkap
**Ditemukan di:** 05_API.md (Section 14 sebelumnya)

**Masalah:**
Tabel ringkasan endpoint di bagian akhir 05_API.md tidak mencantumkan banyak endpoint yang
telah didefinisikan di section-section sebelumnya. Ini menyebabkan tabel ringkasan tidak dapat
digunakan sebagai referensi cepat yang lengkap.

**Endpoint yang hilang dari summary table sebelum audit:**
- `GET /admin/notifications/templates` (seluruh Section 9 hilang)
- `POST /admin/alumni/{id}/send-invitation`
- `POST /admin/questionnaires/{id}/archive`
- `DELETE /admin/employers/{id}` (soft delete superadmin)
- `POST /employer/survey/save-draft`
- `GET /admin/survey-periods/{id}` (detail)
- `PUT /admin/survey-periods/{id}` (update)
- `GET /admin/reports/{id}/download`
- Semua endpoint public master data

**Perbaikan:**
- `05_API.md`: Summary table (kini Section 14) diperbarui menjadi **73 endpoint lengkap**

---

#### Fixed — Beberapa endpoint terdefinisi di arsitektur tetapi hilang dari API spec
**Ditemukan di:** 05_API.md
**Konflik dengan:** 04_ARCHITECTURE.md, 06_UI_UX.md

**Endpoint yang ditambahkan:**
- `DELETE /admin/employers/{id}` — Soft delete employer (superadmin only); sesuai pola yang sama dengan DELETE alumni
- `POST /employer/survey/save-draft` — Save draft survei employer; fitur simpan draft hanya ada untuk alumni, tidak ada untuk employer padahal UX membutuhkannya
- `GET /admin/survey-periods/{id}` — Detail periode survei (ada di routing tapi tidak di API spec)
- `PUT /admin/survey-periods/{id}` — Update periode survei (ada di routing tapi tidak di API spec)
- `POST /admin/survey-periods/{id}/close` — Tutup periode (ada di UX tapi tidak di API spec)
- `GET /admin/reports/{id}/download` — Download laporan tersimpan
- `POST /admin/questionnaires/{id}/archive` — Arsipkan kuesioner

---

### 🟢 MINOR FIXES & IMPROVEMENTS

#### Fixed — CSP Header tidak konsisten antara Nginx config dan Security doc
**Ditemukan di:** 04_ARCHITECTURE.md (Section 6 Nginx config)
**Konflik dengan:** 07_SECURITY.md (Section 9)

**Masalah:**
Nginx config di 04_ARCHITECTURE.md mendefinisikan CSP yang berbeda dari Security doc:
- Architecture: tidak ada `font-src`, `frame-ancestors`, `base-uri`, `form-action`
- Security: CSP lengkap dengan semua directive

**Perbaikan:**
- `04_ARCHITECTURE.md`: Tambah komentar bahwa CSP detail ada di 07_SECURITY.md Section 9; update Nginx config dengan CSP lengkap yang sesuai
- `07_SECURITY.md`: Ditetapkan sebagai **sumber kebenaran** untuk CSP header; tambah catatan bahwa 04_ARCHITECTURE.md me-reference section ini

---

#### Added — Catatan desain relasi `survey_periods` dan `questionnaires`
**Ditambahkan di:** 02_DATABASE.md, 03_ERD.md, 05_API.md

**Konteks:**
`survey_periods` tidak memiliki FK ke `questionnaires`. Kuesioner dipilih saat admin
mengirim undangan massal (parameter `questionnaire_id` di endpoint send-invitations).
Ini adalah keputusan desain yang disengaja untuk fleksibilitas, tetapi tidak pernah
didokumentasikan secara eksplisit sehingga berpotensi menimbulkan pertanyaan saat development.

**Perbaikan:**
- `02_DATABASE.md`: Tambah blok "Catatan Desain" di definisi tabel `survey_periods`
- `03_ERD.md`: Tambah Section 5.3 penjelasan desain ini
- `05_API.md`: Tambah catatan di endpoint `send-invitations` (6.7)

---

#### Added — Definisi lengkap `survey_status` ENUM di Blueprint
**Ditambahkan di:** 01_BLUEPRINT.md (Section 3.2)

**Masalah:**
01_BLUEPRINT.md menyebut status survei alumni dengan nama yang berbeda dari ENUM di database.
Blueprint: "Belum Disurvei, Sedang Proses, Selesai" (3 status, nama berbeda)
Database: `belum_disurvei`, `terkirim`, `sedang_mengisi`, `selesai` (4 status)

**Perbaikan:**
- `01_BLUEPRINT.md` Section 3.2: Daftar 4 status survei alumni sesuai ENUM database dengan penjelasan transisi tiap status
- `01_BLUEPRINT.md` Section 3.3: Tambah 3 status survei employer (`belum_disurvei`, `terkirim`, `selesai`)

---

#### Added — Alur login admin (email + password) di Blueprint
**Ditambahkan di:** 01_BLUEPRINT.md (Section 4.4)

**Masalah:** Blueprint Section 4 hanya mendokumentasikan alur OTP (alumni) dan alur employer. Alur login admin (email+password dengan lockout) tidak terdokumentasi di Blueprint.

**Perbaikan:**
- `01_BLUEPRINT.md`: Tambah alur 4.4 "Login Admin (Email + Password)" dengan lockout logic

---

#### Changed — Public controller ditambahkan ke struktur folder arsitektur
**Diubah di:** 04_ARCHITECTURE.md (Section 2 — Folder Structure)

**Perbaikan:**
- Tambah `Public/PublicController.php` yang menangani endpoint `/api/v1/public/*`

---

#### Changed — Queue worker dipisah menjadi high/default dan low
**Diubah di:** 04_ARCHITECTURE.md (Section 5 — Queue Architecture)

**Perbaikan:**
- Pisah konfigurasi Supervisor menjadi 2 worker pool: `sitras-worker-default` (queue: high,default) dan `sitras-worker-low` (queue: low)

---

#### Added — Komponen frontend tambahan di UI/UX spec
**Ditambahkan di:** 06_UI_UX.md (Section 4)

**Perbaikan:**
- Tambah komponen `AlumniMap.vue` (4.7), `SurveyProgressBar.vue` (4.8), `QuestionRenderer.vue` (4.9) yang ada di architecture tapi belum di spec UI/UX
- Tambah halaman `10.5 Halaman Token Tidak Valid (Employer)`
- Tambah badge status `terkirim` untuk alumni (sebelumnya hanya 3 status di badge section)

---

#### Added — Route employer done page
**Ditambahkan di:** 06_UI_UX.md (Section 8)

**Perbaikan:**
- Tambah route `/employer/done` (halaman konfirmasi setelah employer submit survei)

---

#### Changed — Phase Tracker total task count diperbarui
**Diubah di:** 08_PHASE_TRACKER.md

**Perubahan:**
- Tambah task: 4A.13, 4A.14 (NotificationController CRUD templates + log listing)
- Tambah task: 4A.23 (NotificationTemplateSeeder)
- Tambah task: 4A.27, 4A.28 (Feature Test notification)
- Penghitungan ulang semua task secara terperinci per sesi
- Total task development: **199 task** (Fase 1–7)

---

#### Fixed — Matriks izin tidak mencakup DELETE employer
**Ditemukan di:** 07_SECURITY.md (Section 3.3 — Matriks Izin)

**Masalah:**
Baris "Hapus Employer (soft delete)" tidak ada di matriks izin sebelumnya, padahal endpoint `DELETE /admin/employers/{id}` sudah ada (superadmin only).

**Perbaikan:**
- `07_SECURITY.md`: Tambah baris "Hapus Employer (soft delete)" → Superadmin: ✅, Admin: ❌

---

#### Fixed — Konvensi penamaan OTP hash tidak terdokumentasi di Database doc
**Ditemukan di:** 02_DATABASE.md

**Perbaikan:**
- Tambah baris `| OTP Hash | SHA-256 hex digest → VARCHAR(64) |` di Section 1 (Konvensi Penamaan)

---

## [1.0.0] — 2026-06-04

> Dokumen awal sistem SITRAS UNISYA. Semua dokumen dibuat dari awal.

### Added
- `01_BLUEPRINT.md` — Blueprint sistem versi awal (3 aktor, 10 modul, 7 fase)
- `02_DATABASE.md` — Desain database 24 tabel lengkap
- `03_ERD.md` — Entity Relationship Diagram dengan relasi, cascade rules
- `04_ARCHITECTURE.md` — Arsitektur monolitik enterprise, folder structure, Nginx, queue
- `05_API.md` — Spesifikasi REST API dengan endpoint autentikasi, alumni, employer, kuesioner, survei, dashboard, laporan
- `06_UI_UX.md` — Design system, layout, komponen, routing, aksesibilitas
- `07_SECURITY.md` — OWASP mitigasi, RBAC, OTP spec, token spec, rate limiting, audit logging
- `08_PHASE_TRACKER.md` — 8 fase pengembangan, 13 sesi, task tracker terstruktur
- `09_CHANGELOG.md` — Riwayat perubahan dokumen (file ini)

---

## CATATAN INKONSISTENSI YANG DITEMUKAN & STATUS

| # | Tingkat | Deskripsi | Status |
|---|---|---|---|
| 1 | 🔴 Critical | `otp_codes.code` VARCHAR(10) → harus VARCHAR(64) untuk SHA-256 | ✅ Fixed v1.0.1 |
| 2 | 🟠 Major | Actor `admin` tidak terdefinisi di Blueprint | ✅ Fixed v1.0.1 |
| 3 | 🟠 Major | Endpoint CRUD notification templates & log hilang dari API spec | ✅ Fixed v1.0.1 |
| 4 | 🟡 Moderate | Tipe `gpa` string vs number di API response | ✅ Fixed v1.0.1 |
| 5 | 🟡 Moderate | Route `/alumni/work-history` ≠ API `/alumni/work-histories` | ✅ Fixed v1.0.1 |
| 6 | 🟡 Moderate | Summary table API tidak lengkap (banyak endpoint hilang) | ✅ Fixed v1.0.1 |
| 7 | 🟡 Moderate | Beberapa endpoint ada di Architecture/UI/UX tapi tidak di API spec | ✅ Fixed v1.0.1 |
| 8 | 🟢 Minor | CSP header berbeda antara Architecture dan Security doc | ✅ Fixed v1.0.1 |
| 9 | 🟢 Minor | Relasi survey_periods ↔ questionnaires tidak terdokumentasi | ✅ Fixed v1.0.1 |
| 10 | 🟢 Minor | Status survei alumni di Blueprint berbeda dari ENUM database | ✅ Fixed v1.0.1 |
| 11 | 🟢 Minor | Alur login admin tidak terdokumentasi di Blueprint | ✅ Fixed v1.0.1 |
| 12 | 🟢 Minor | DELETE employer hilang dari matriks izin Security | ✅ Fixed v1.0.1 |
| 13 | 🟢 Minor | Beberapa komponen frontend (AlumniMap, QuestionRenderer) tidak ada di UI/UX spec | ✅ Fixed v1.0.1 |
| 14 | 🟢 Minor | Claim "tidak ada konflik" di Changelog v1.0.0 tidak akurat | ✅ Fixed v1.0.1 |
| 15 | 🟠 Major | WA Gateway masih Fonnte/Wablas di seluruh dokumen; seharusnya wacenter.unisya.ac.id | ✅ Fixed v1.0.2 |
| 16 | 🟡 Moderate | Kolom `notification_logs.status delivered` tidak bisa diisi otomatis dari gateway | ✅ Fixed v1.0.2 |
| 17 | 🟠 Major | [INC-01] Blueprint: tabel identitas proyek (Versi & Tanggal) tidak sinkron dengan header | ✅ Fixed v1.0.3 |
| 18 | 🟠 Major | [INC-02] Architecture: diagram External Services masih label `(Fonnte/Wablas)` | ✅ Fixed v1.0.3 |
| 19 | 🟡 Moderate | [INC-03] Phase Tracker: header "Total Task: 167" tidak sesuai tabel ringkasan (199) | ✅ Fixed v1.0.3 |
| 20 | 🟡 Moderate | [INC-04] API: endpoint reorder tidak ada catatan routing Laravel (konflik `{id}` vs `reorder`) | ✅ Fixed v1.0.3 |
| 21 | 🟡 Moderate | [INC-05] Security: matriks izin "Profil Alumni" ambigu (admin bisa lihat tapi baris bilang ❌) | ✅ Fixed v1.0.3 |
| 22 | 🟢 Minor | [INC-06/07] Architecture: folder structure pages tidak mencantumkan nama file .vue | ✅ Fixed v1.0.3 |

**Total: 22 inkonsistensi ditemukan sejak v1.0.0 — semua telah diperbaiki**
**Status: ✅ Dokumen SITRAS UNISYA v1.0.3 CLEAR dan SIAP DEVELOPMENT**

---

## DOKUMEN TERDAMPAK PER FILE

| File | Versi Sebelum | Versi Sesudah | Jenis Perubahan |
|---|---|---|---|
| 01_BLUEPRINT.md | 1.0.0 | 1.0.1 | Added (actor Admin), Fixed (survey status enum), Added (alur login admin) |
| 02_DATABASE.md | 1.0.0 | 1.0.1 | Fixed (otp_codes.code VARCHAR), Added (konvensi OTP hash, catatan desain period) |
| 03_ERD.md | 1.0.0 | 1.0.1 | Fixed (otp_codes.code VARCHAR), Added (alur OTP detail, catatan desain) |
| 04_ARCHITECTURE.md | 1.0.0 | 1.0.1 | Fixed (CSP header align), Added (Public controller, split queue worker) |
| 05_API.md | 1.0.0 | 1.0.1 | Added (Section 9 Notifikasi — 6 endpoint), Fixed (gpa type, route plural, summary table), Added (7 endpoint yang hilang) |
| 06_UI_UX.md | 1.0.0 | 1.0.1 | Fixed (work-history → work-histories), Added (komponen missing, halaman Notifikasi, badge status terkirim) |
| 07_SECURITY.md | 1.0.0 | 1.0.1 | Fixed (matriks izin DELETE employer), Changed (CSP jadi sumber kebenaran), Clarified (4 role definition) |
| 08_PHASE_TRACKER.md | 1.0.0 | 1.0.1 | Added (5 task notifikasi), Changed (total task count) |
| 09_CHANGELOG.md | 1.0.0 | 1.0.1 | Added (entri audit lengkap v1.0.1 — dokumen ini) |

---

## RIWAYAT VERSI DOKUMEN INI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Tambah entri audit konsistensi lengkap — 14 inkonsistensi ditemukan dan diperbaiki; tambah tabel ringkasan inkonsistensi; tambah tabel file terdampak |
| 1.0.2 | 2026-06-08 | Tambah entri audit kesesuaian WA Gateway UNISYA — 9 file direvisi |
| 1.0.3 | 2026-06-09 | Tambah entri audit v1.0.3 — 8 inkonsistensi ditemukan dan diperbaiki (6 file direvisi); update tabel inkonsistensi global (22 total) |

---

*Dokumen ini adalah catatan resmi semua perubahan pada dokumentasi proyek SITRAS UNISYA.*
*Setiap perubahan pada dokumen manapun wajib dicatat di sini sebelum dokumen tersebut digunakan sebagai dasar implementasi.*
