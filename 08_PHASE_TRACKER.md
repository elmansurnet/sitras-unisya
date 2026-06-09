# 08_PHASE_TRACKER.md
# PHASE TRACKER — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.5 | Tanggal: 2026-06-09

---

## STATUS RINGKASAN

| Fase | Nama | Sesi | Status |
|---|---|---|---|
| 0 | Dokumentasi & Desain | 0A | ✅ Selesai |
| 1 | Fondasi & Autentikasi | 1A, 1B | ✅ Selesai (1A ✅, 1B ✅) |
| 2 | Manajemen Data Inti | 2A, 2B, 2C | ⏳ Pending |
| 3 | Kuesioner Dinamis | 3A, 3B | ⏳ Pending |
| 4 | Survei & Notifikasi | 4A, 4B | ⏳ Pending |
| 5 | Analitik & Pelaporan | 5A, 5B | ⏳ Pending |
| 6 | Keamanan & Hardening | 6A | ⏳ Pending |
| 7 | Deployment & Optimasi | 7A | ⏳ Pending |

**Total Task: 199 task**
**Selesai: 47 task** *(1A.1–1A.19 selesai 2026-06-09, 1B.1–1B.28 selesai 2026-06-09)* *(Fase 0 dokumentasi tidak dihitung sebagai task development)*

---

## KONVENSI STATUS TASK

| Simbol | Makna |
|---|---|
| ⏳ | Belum dimulai |
| 🔄 | Sedang dikerjakan |
| ✅ | Selesai & diverifikasi |
| ❌ | Diblokir / ada kendala |
| ⏭️ | Di-skip (tidak relevan) |

---

## FASE 0 — DOKUMENTASI & DESAIN

### Sesi 0A — Dokumen Spesifikasi
**Status: ✅ Selesai**

| No | Task | Status |
|---|---|---|
| 0A.1 | 01_BLUEPRINT.md — Blueprint sistem lengkap | ✅ |
| 0A.2 | 02_DATABASE.md — Desain database 24 tabel | ✅ |
| 0A.3 | 03_ERD.md — Entity Relationship Diagram | ✅ |
| 0A.4 | 04_ARCHITECTURE.md — Arsitektur sistem & folder structure | ✅ |
| 0A.5 | 05_API.md — Spesifikasi REST API 73 endpoint | ✅ |
| 0A.6 | 06_UI_UX.md — Design system & layout halaman | ✅ |
| 0A.7 | 07_SECURITY.md — Keamanan, RBAC, OWASP mitigasi | ✅ |
| 0A.8 | 08_PHASE_TRACKER.md — Rencana fase & task tracker | ✅ |
| 0A.9 | 09_CHANGELOG.md — Riwayat perubahan dokumen | ✅ |
| 0A.10 | Audit konsistensi lintas-dokumen (v1.0.1) | ✅ |

---

## FASE 1 — FONDASI & AUTENTIKASI

### Sesi 1A — Setup Proyek & Database
**Status: ✅ Selesai | Tanggal Selesai: 2026-06-09**
**Dependensi:** Fase 0 selesai
**Estimasi:** 2–3 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 1A.1 | Install Laravel 12 + konfigurasi awal (`composer create-project`) | — | ✅ |
| 1A.2 | Install & konfigurasi Vue 3 + Vite + TailwindCSS + dependensi frontend | `package.json`, `vite.config.js`, `tailwind.config.js` | ✅ |
| 1A.3 | Konfigurasi `.env` production (DB, Redis, Mail, WA, Sanctum) | `.env.example` | ✅ |
| 1A.4 | Setup Redis (cache, session, queue driver) | `config/database.php`, `config/queue.php` | ✅ |
| 1A.5 | Konfigurasi file `config/tracer.php` (OTP expiry, lockout, employer token) | `config/tracer.php` | ✅ |
| 1A.6 | Migrasi: `users`, `personal_access_tokens` | Migration file | ✅ |
| 1A.7 | Migrasi: `otp_codes` (code VARCHAR(64) — SHA-256) | Migration file | ✅ |
| 1A.8 | Migrasi: `audit_logs` | Migration file | ✅ |
| 1A.9 | Migrasi: `faculties`, `study_programs`, `graduation_years` | Migration files | ✅ |
| 1A.10 | Migrasi: `system_settings`, `industry_sectors`, `salary_ranges` | Migration files | ✅ |
| 1A.11 | Model: `User` (fillable, hidden, casts, relationships, scope) | `app/Models/User.php` | ✅ |
| 1A.12 | Model: `OtpCode` (fillable, casts, scope `active`) | `app/Models/OtpCode.php` | ✅ |
| 1A.13 | Model: `AuditLog` (fillable, static `record()` helper, relationship) | `app/Models/AuditLog.php` | ✅ |
| 1A.14 | Model: `Faculty`, `StudyProgram`, `GraduationYear`, `SystemSetting`, `IndustrySector`, `SalaryRange` | Model files | ✅ |
| 1A.15 | Seeder: `SuperadminSeeder` (1 superadmin, bcrypt password) | `SuperadminSeeder.php` | ✅ |
| 1A.16 | Seeder: `FacultySeeder`, `StudyProgramSeeder`, `GraduationYearSeeder` | Seeder files | ✅ |
| 1A.17 | Seeder: `IndustrySectorSeeder`, `SalaryRangeSeeder`, `SystemSettingSeeder` — termasuk default key WA Gateway: `wa_gateway_url` (`https://wacenter.unisya.ac.id/send-message`), `wa_api_key` (kosong, diisi via Setting), `wa_sender` (kosong, diisi via Setting) | Seeder files | ✅ |
| 1A.18 | Konfigurasi `config/cors.php` sesuai 07_SECURITY.md Section 10 | `config/cors.php` | ✅ |
| 1A.19 | Registrasi `AlumniObserver`, `EmployerObserver`, `SurveyResponseObserver`, `UserObserver` di `AppServiceProvider` | `AppServiceProvider.php` | ✅ |

**Total Sesi 1A: 19 task — ✅ Selesai 19/19**

---

### Sesi 1B — Sistem Autentikasi Backend + Frontend
**Status: ✅ Selesai | Tanggal Selesai: 2026-06-09**
**Dependensi:** 1A selesai
**Estimasi:** 3–4 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 1B.1 | Middleware: `CheckRole` ... | `app/Http/Middleware/CheckRole.php` | ✅ |
| 1B.2 | Middleware: `EnsureAccountActive` ... | `app/Http/Middleware/EnsureAccountActive.php` | ✅ |
| 1B.3 | Middleware: `ValidateEmployerToken` ... | `app/Http/Middleware/ValidateEmployerToken.php` | ✅ |
| 1B.4 | Middleware: `LogActivity` ... | `app/Http/Middleware/LogActivity.php` | ✅ |
| 1B.5 | Service: `OtpService` ... | `app/Services/OtpService.php` | ✅ |
| 1B.6 | Service: `AuthService` ... | `app/Services/AuthService.php` | ✅ |
| 1B.7 | Controller: `Auth/OtpController` ... | `OtpController.php` | ✅ |
| 1B.8 | Controller: `Auth/AuthController` ... | `AuthController.php` | ✅ |
| 1B.9 | FormRequest: `LoginRequest`, `OtpRequestRequest`, `OtpVerifyRequest` | Request files | ✅ |
| 1B.10 | Job: `SendWhatsAppNotification`, `SendEmailNotification` ... | Job files | ✅ |
| 1B.11 | Registrasi RateLimiter di `AppServiceProvider` ... | `AppServiceProvider.php` | ✅ |
| 1B.12 | Routes: `/api/v1/auth/*` ... | `routes/api.php` | ✅ |
| 1B.13 | Routes: `/api/v1/public/*` ... | `routes/api.php` | ✅ |
| 1B.14 | Controller: `Public/PublicController` ... | `PublicController.php` | ✅ |
| 1B.15 | Frontend: Setup `services/api.js` ... | `frontend/src/services/api.js` | ✅ |
| 1B.16 | Frontend: Store `stores/auth.js` ... | `stores/auth.js` | ✅ |
| 1B.17 | Frontend: `layouts/AuthLayout.vue` ... | `AuthLayout.vue` | ✅ |
| 1B.18 | Frontend: `pages/auth/LoginPage.vue` ... | `LoginPage.vue` | ✅ |
| 1B.19 | Frontend: `pages/auth/OtpRequestPage.vue` ... | `OtpRequestPage.vue` | ✅ |
| 1B.20 | Frontend: `pages/auth/OtpVerifyPage.vue` ... | `OtpVerifyPage.vue` | ✅ |
| 1B.21 | Frontend: `pages/auth/EmployerTokenPage.vue` ... | `EmployerTokenPage.vue` | ✅ |
| 1B.22 | Frontend: Router guards ... | `router/index.js` | ✅ |
| 1B.23 | Frontend: `layouts/AdminLayout.vue` ... | `AdminLayout.vue` | ✅ |
| 1B.24 | Frontend: `layouts/AlumniLayout.vue` ... | `AlumniLayout.vue` | ✅ |
| 1B.25 | Frontend: `layouts/EmployerLayout.vue` ... | `EmployerLayout.vue` | ✅ |
| 1B.26 | Feature Test: login admin ... | `tests/Feature/Auth/` | ✅ |
| 1B.27 | Feature Test: OTP request + verify ... | `tests/Feature/Auth/` | ✅ |
| 1B.28 | Feature Test: employer token login ... | `tests/Feature/Auth/` | ✅ |

**Total Sesi 1B: 28 task — ✅ Selesai 28/28**

---

## FASE 2 — MANAJEMEN DATA INTI

### Sesi 2A — Manajemen Alumni (Backend + Frontend)
**Dependensi:** Fase 1 selesai
**Estimasi:** 4–5 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 2A.1 | Migrasi: `alumni`, `alumni_work_histories` | Migration files | ⏳ |
| 2A.2 | Model: `Alumni` (fillable, casts, relationships ke user/studyProgram/graduationYear/workHistories) | `app/Models/Alumni.php` | ⏳ |
| 2A.3 | Model: `AlumniWorkHistory` (fillable, casts, relationship ke alumni/employer) | `app/Models/AlumniWorkHistory.php` | ⏳ |
| 2A.4 | Observer: `AlumniObserver` (created, updated, deleted → audit_logs) | `AlumniObserver.php` | ⏳ |
| 2A.5 | Repository: `AlumniRepository` (findByNim, findWithFilters, getMapCoordinates, getStats) | `AlumniRepository.php` | ⏳ |
| 2A.6 | Service: `AlumniService` (create, update, delete, import, export, sendInvitation) | `app/Services/AlumniService.php` | ⏳ |
| 2A.7 | Service: `ImportExportService` (parseExcel, validateRows, batchInsert, generateTemplate, exportExcel) | `ImportExportService.php` | ⏳ |
| 2A.8 | Policy: `AlumniPolicy` (view, create, update, delete — role-aware) | `AlumniPolicy.php` | ⏳ |
| 2A.9 | FormRequest: `StoreAlumniRequest`, `UpdateAlumniRequest`, `StoreWorkHistoryRequest` | Request files | ⏳ |
| 2A.10 | Controller: `Admin/AlumniController` (index, show, store, update, destroy, import, export, importTemplate, sendInvitation) | `AlumniController.php` | ⏳ |
| 2A.11 | Controller: `Alumni/ProfileController` (show, update, uploadPhoto) | `ProfileController.php` | ⏳ |
| 2A.12 | Controller: `Alumni/WorkHistoryController` (index, store, update, destroy) | `WorkHistoryController.php` | ⏳ |
| 2A.13 | Routes: `/api/v1/admin/alumni/*` + `/api/v1/alumni/*` | `routes/api.php` | ⏳ |
| 2A.14 | Job: `GenerateReportExport` (queue: default, generate Excel export besar) | `GenerateReportExport.php` | ⏳ |
| 2A.15 | Frontend: Store `stores/alumni.js` (Pinia: list, current, pagination, filters, CRUD actions) | `stores/alumni.js` | ⏳ |
| 2A.16 | Frontend: Komponen `common/DataTable.vue` (sortable, selectable, pagination, empty state) | `DataTable.vue` | ⏳ |
| 2A.17 | Frontend: Komponen `common/FilterBar.vue` (multi-filter, reset) | `FilterBar.vue` | ⏳ |
| 2A.18 | Frontend: Komponen `common/Pagination.vue` | `Pagination.vue` | ⏳ |
| 2A.19 | Frontend: Komponen `common/Badge.vue` (semua variant status) | `Badge.vue` | ⏳ |
| 2A.20 | Frontend: Komponen `common/ConfirmModal.vue` | `ConfirmModal.vue` | ⏳ |
| 2A.21 | Frontend: Komponen `common/Toast.vue` + composable `useToast.js` | Toast files | ⏳ |
| 2A.22 | Frontend: Komponen `common/FileUpload.vue` (drag-drop, preview, validasi client-side) | `FileUpload.vue` | ⏳ |
| 2A.23 | Frontend: `pages/admin/alumni/AlumniIndexPage.vue` (tabel, filter, aksi header) | `AlumniIndexPage.vue` | ⏳ |
| 2A.24 | Frontend: `pages/admin/alumni/AlumniDetailPage.vue` (info lengkap, riwayat kerja, respons survei) | `AlumniDetailPage.vue` | ⏳ |
| 2A.25 | Frontend: `pages/admin/alumni/AlumniFormPage.vue` (form tambah/edit — 5 tab) | `AlumniFormPage.vue` | ⏳ |
| 2A.26 | Frontend: `pages/admin/alumni/AlumniImportPage.vue` (upload, progress, hasil import) | `AlumniImportPage.vue` | ⏳ |
| 2A.27 | Frontend: `pages/alumni/ProfilePage.vue` + `ProfileEditPage.vue` | Profile pages | ⏳ |
| 2A.28 | Frontend: `pages/alumni/WorkHistoryPage.vue` (daftar + form tambah/edit riwayat kerja) | `WorkHistoryPage.vue` | ⏳ |
| 2A.29 | Frontend: `pages/alumni/DashboardPage.vue` (banner, kartu status survei adaptif, kelengkapan profil) | `AlumniDashboardPage.vue` | ⏳ |
| 2A.30 | Feature Test: CRUD alumni (create, read, update, delete — per role) | `tests/Feature/Admin/AlumniTest.php` | ⏳ |
| 2A.31 | Feature Test: Import alumni (berhasil, validasi error, duplikat NIM) | `tests/Feature/Admin/AlumniImportTest.php` | ⏳ |

**Total Sesi 2A: 31 task**

---

### Sesi 2B — Manajemen Employer (Backend + Frontend)
**Dependensi:** 2A selesai (model Alumni tersedia untuk relasi)
**Estimasi:** 3–4 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 2B.1 | Migrasi: `employers`, `alumni_employer` (pivot) | Migration files | ⏳ |
| 2B.2 | Model: `Employer` (fillable, casts, relationships ke user/alumni/workHistories/surveyResponses) | `app/Models/Employer.php` | ⏳ |
| 2B.3 | Observer: `EmployerObserver` (created, updated, deleted → audit_logs) | `EmployerObserver.php` | ⏳ |
| 2B.4 | Repository: `EmployerRepository` (findWithFilters, getStats) | `EmployerRepository.php` | ⏳ |
| 2B.5 | Service: `EmployerService` (create, update, delete, generateToken, sendSurveyToken, regenerateToken) | `EmployerService.php` | ⏳ |
| 2B.6 | Policy: `EmployerPolicy` (view, create, update, delete — role-aware) | `EmployerPolicy.php` | ⏳ |
| 2B.7 | FormRequest: `StoreEmployerRequest`, `UpdateEmployerRequest` | Request files | ⏳ |
| 2B.8 | Controller: `Admin/EmployerController` (index, show, store, update, destroy, sendSurveyToken, regenerateToken) | `EmployerController.php` | ⏳ |
| 2B.9 | Controller: `Employer/ProfileController` (show, update) | `Employer/ProfileController.php` | ⏳ |
| 2B.10 | Routes: `/api/v1/admin/employers/*` + `/api/v1/employer/profile` | `routes/api.php` | ⏳ |
| 2B.11 | Frontend: Store `stores/employer.js` (Pinia: list, current, pagination, filters, CRUD actions) | `stores/employer.js` | ⏳ |
| 2B.12 | Frontend: `pages/admin/employers/EmployerIndexPage.vue` (tabel, filter, aksi) | `EmployerIndexPage.vue` | ⏳ |
| 2B.13 | Frontend: `pages/admin/employers/EmployerDetailPage.vue` (profil, alumni terkait, status survei, kirim token) | `EmployerDetailPage.vue` | ⏳ |
| 2B.14 | Frontend: `pages/admin/employers/EmployerFormPage.vue` (form tambah/edit employer) | `EmployerFormPage.vue` | ⏳ |
| 2B.15 | Feature Test: CRUD employer (create, read, update, delete — per role) | `tests/Feature/Admin/EmployerTest.php` | ⏳ |
| 2B.16 | Feature Test: generate token, send token, regenerate token | `tests/Feature/Admin/EmployerTokenTest.php` | ⏳ |

**Total Sesi 2B: 16 task**

---

### Sesi 2C — Konfigurasi Akademik & Sistem (Backend + Frontend)
**Dependensi:** 2A selesai
**Estimasi:** 2–3 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 2C.1 | Controller: `Admin/FacultyController` (CRUD) | `FacultyController.php` | ⏳ |
| 2C.2 | Controller: `Admin/StudyProgramController` (CRUD) | `StudyProgramController.php` | ⏳ |
| 2C.3 | Controller: `Admin/GraduationYearController` (CRUD) | `GraduationYearController.php` | ⏳ |
| 2C.4 | Controller: `Admin/UserController` (CRUD admin, toggleActive — superadmin only) | `UserController.php` | ⏳ |
| 2C.5 | Controller: `Admin/SettingController` (index, update — superadmin only) | `SettingController.php` | ⏳ |
| 2C.6 | Controller: `Admin/AuditLogController` (index dengan filter — superadmin only) | `AuditLogController.php` | ⏳ |
| 2C.7 | Routes: `/api/v1/admin/faculties`, `/study-programs`, `/graduation-years`, `/users`, `/settings`, `/audit-logs` | `routes/api.php` | ⏳ |
| 2C.8 | Frontend: `pages/admin/settings/FacultyPage.vue` (CRUD inline) | `FacultyPage.vue` | ⏳ |
| 2C.9 | Frontend: `pages/admin/settings/StudyProgramPage.vue` (CRUD, filter per fakultas) | `StudyProgramPage.vue` | ⏳ |
| 2C.10 | Frontend: `pages/admin/settings/GraduationYearPage.vue` (CRUD) | `GraduationYearPage.vue` | ⏳ |
| 2C.11 | Frontend: `pages/admin/settings/UserManagementPage.vue` (CRUD admin, toggle aktif — superadmin) | `UserManagementPage.vue` | ⏳ |
| 2C.12 | Frontend: `pages/admin/settings/SystemSettingPage.vue` (tab: umum, smtp, wa, keamanan, notif) | `SystemSettingPage.vue` | ⏳ |
| 2C.13 | Frontend: `pages/admin/settings/AuditLogPage.vue` (tabel, filter, detail — superadmin) | `AuditLogPage.vue` | ⏳ |

**Total Sesi 2C: 13 task**

---

## FASE 3 — KUESIONER DINAMIS

### Sesi 3A — Kuesioner Backend
**Dependensi:** Fase 1 selesai
**Estimasi:** 3–4 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 3A.1 | Migrasi: `questionnaires`, `questionnaire_sections`, `questions`, `question_options` | Migration files | ⏳ |
| 3A.2 | Model: `Questionnaire` (fillable, casts, relationships ke sections/questions/responses) | `Questionnaire.php` | ⏳ |
| 3A.3 | Model: `QuestionnaireSection` (fillable, relationships ke questionnaire/questions) | `QuestionnaireSection.php` | ⏳ |
| 3A.4 | Model: `Question` (fillable, casts JSON: validation_rules/conditional_logic, relationships) | `Question.php` | ⏳ |
| 3A.5 | Model: `QuestionOption` (fillable, relationships) | `QuestionOption.php` | ⏳ |
| 3A.6 | Service: `QuestionnaireService` (create, update, publish, archive, addSection, addQuestion, reorder) | `QuestionnaireService.php` | ⏳ |
| 3A.7 | Policy: `QuestionnairePolicy` (view, create, update, publish, delete) | `QuestionnairePolicy.php` | ⏳ |
| 3A.8 | FormRequest: `StoreQuestionnaireRequest`, `StoreSectionRequest`, `StoreQuestionRequest` | Request files | ⏳ |
| 3A.9 | Controller: `Admin/QuestionnaireController` (index, show, store, update, publish, archive, addSection, updateSection, deleteSection, addQuestion, updateQuestion, deleteQuestion, reorder) | `QuestionnaireController.php` | ⏳ |
| 3A.10 | Routes: `/api/v1/admin/questionnaires/*` (semua endpoint kuesioner) | `routes/api.php` | ⏳ |
| 3A.11 | Unit Test: QuestionnaireService (publish, archive, reorder, conditional logic) | `tests/Unit/QuestionnaireServiceTest.php` | ⏳ |
| 3A.12 | Feature Test: CRUD kuesioner, seksi, pertanyaan | `tests/Feature/Admin/QuestionnaireTest.php` | ⏳ |

**Total Sesi 3A: 12 task**

---

### Sesi 3B — Kuesioner Builder Frontend
**Dependensi:** 3A selesai
**Estimasi:** 4–5 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 3B.1 | Frontend: Store `stores/questionnaire.js` (Pinia: kuesioner, seksi, pertanyaan, state builder) | `stores/questionnaire.js` | ⏳ |
| 3B.2 | Frontend: `pages/admin/questionnaires/QuestionnaireIndexPage.vue` (daftar, filter type/status) | `QuestionnaireIndexPage.vue` | ⏳ |
| 3B.3 | Frontend: `pages/admin/questionnaires/QuestionnaireBuilderPage.vue` (split layout: list pertanyaan + preview) | `QuestionnaireBuilderPage.vue` | ⏳ |
| 3B.4 | Frontend: Komponen `forms/QuestionEditor.vue` (form edit pertanyaan — semua tipe dinamis) | `QuestionEditor.vue` | ⏳ |
| 3B.5 | Frontend: Komponen `forms/QuestionRenderer.vue` (render pertanyaan berdasarkan question_type) | `QuestionRenderer.vue` | ⏳ |
| 3B.6 | Frontend: Komponen `forms/ConditionalLogicEditor.vue` (UI pengaturan show_if per pertanyaan) | `ConditionalLogicEditor.vue` | ⏳ |
| 3B.7 | Frontend: Toolbar tipe pertanyaan (10 tipe: text, textarea, radio, checkbox, select, likert, rating, date, file, number) | Bagian dari QuestionnaireBuilderPage | ⏳ |
| 3B.8 | Frontend: Drag-and-drop reorder pertanyaan (gunakan Vue-draggable atau implementasi Sortable.js) | Bagian dari builder | ⏳ |
| 3B.9 | Frontend: `pages/admin/questionnaires/QuestionnairePreviewPage.vue` (tampilan identik seperti alumni melihat) | `QuestionnairePreviewPage.vue` | ⏳ |

**Total Sesi 3B: 9 task**

---

## FASE 4 — SURVEI & NOTIFIKASI

### Sesi 4A — Survei & Notifikasi Backend
**Dependensi:** Fase 2 + Fase 3 selesai
**Estimasi:** 4–5 hari

> **Catatan v1.0.1:** Task 4A.10 dan 4A.11 ditambahkan dari hasil audit konsistensi.
> Endpoint CRUD notification templates dan GET notification logs sebelumnya tercantum
> di 04_ARCHITECTURE.md dan 06_UI_UX.md namun belum ada di 05_API.md.
> Sudah diperbaiki di 05_API.md Section 9. Task tracker diperbarui.

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 4A.1 | Migrasi: `survey_periods`, `alumni_survey_period` (pivot), `survey_responses`, `survey_answers` | Migration files | ⏳ |
| 4A.2 | Migrasi: `notification_templates`, `notification_logs` | Migration files | ⏳ |
| 4A.3 | Model: `SurveyPeriod` (fillable, casts JSON: target_graduation_years, relationships ke alumni/responses) | `SurveyPeriod.php` | ⏳ |
| 4A.4 | Model: `SurveyResponse` (fillable, relationships ke questionnaire/alumni/employer/answers) | `SurveyResponse.php` | ⏳ |
| 4A.5 | Model: `SurveyAnswer` (fillable, casts JSON: answer_options, relationship ke response/question) | `SurveyAnswer.php` | ⏳ |
| 4A.6 | Model: `NotificationTemplate` (fillable, casts JSON: variables, relationship ke logs) | `NotificationTemplate.php` | ⏳ |
| 4A.7 | Model: `NotificationLog` (fillable, casts JSON: provider_response) | `NotificationLog.php` | ⏳ |
| 4A.8 | Observer: `SurveyResponseObserver` (submitted → update alumni/employer survey_status → audit_log) | `SurveyResponseObserver.php` | ⏳ |
| 4A.9 | Service: `SurveyService` (getSurveyForAlumni, getSurveyForEmployer, saveDraft, submit, validateAnswers, calculateCompletion) | `SurveyService.php` | ⏳ |
| 4A.10 | Service: `NotificationService` (renderTemplate, sendToAlumni, sendToEmployer, blastPeriod, logSend) | `NotificationService.php` | ⏳ |
| 4A.11 | Service: `WhatsAppService` (sendMessage via WA Gateway UNISYA `wacenter.unisya.ac.id` — POST JSON dengan parameter `api_key`, `sender`, `number`, `message`, `footer` opsional; baca config dari `system_settings` key: `wa_gateway_url`, `wa_api_key`, `wa_sender`; aktifkan `full=1` untuk dapat `message_id`; simpan ke `notification_logs.provider_response`; handle response `status: true/false`; retry logic) | `WhatsAppService.php` | ⏳ |
| 4A.12 | Controller: `Admin/SurveyPeriodController` (index, show, store, update, activate, close, sendInvitations) | `SurveyPeriodController.php` | ⏳ |
| 4A.13 | Controller: `Admin/NotificationController` — CRUD templates: index, show, store, update, destroy | `NotificationController.php` | ⏳ |
| 4A.14 | Controller: `Admin/NotificationController` — Log listing: index dengan filter (type, status, recipient_type, date_from, date_to) | `NotificationController.php` | ⏳ |
| 4A.15 | Controller: `Alumni/SurveyController` (show, saveDraft, submit) | `Alumni/SurveyController.php` | ⏳ |
| 4A.16 | Controller: `Employer/SurveyController` (show, saveDraft, submit) | `Employer/SurveyController.php` | ⏳ |
| 4A.17 | FormRequest: `SaveDraftRequest`, `SubmitSurveyRequest` | Request files | ⏳ |
| 4A.18 | Job: `ProcessSurveyBlast` (queue: low — loop alumni sasaran, dispatch SendWhatsApp/EmailNotification per alumni) | `ProcessSurveyBlast.php` | ⏳ |
| 4A.19 | Routes: `/api/v1/admin/survey-periods/*`, `/api/v1/admin/notifications/*`, `/api/v1/alumni/survey/*`, `/api/v1/employer/survey/*` | `routes/api.php` | ⏳ |
| 4A.20 | Scheduler Command: `SendSurveyReminders` (kirim reminder ke alumni belum submit, daily 08:00) | `SendSurveyReminders.php` | ⏳ |
| 4A.21 | Scheduler Command: `CloseExpiredSurveyPeriods` (auto-close periode lewat end_date, daily 00:00) | `CloseExpiredSurveyPeriods.php` | ⏳ |
| 4A.22 | Scheduler Command: `CleanupExpiredOtps` (hapus otp_codes expired, hourly) | `CleanupExpiredOtps.php` | ⏳ |
| 4A.23 | Seeder: `NotificationTemplateSeeder` (template default: survey_invitation, otp_login, survey_reminder, employer_survey_invitation — untuk WA dan Email) | `NotificationTemplateSeeder.php` | ⏳ |
| 4A.24 | Feature Test: survey flow alumni (start, saveDraft, submit — status update) | `tests/Feature/Survey/AlumniSurveyTest.php` | ⏳ |
| 4A.25 | Feature Test: survey flow employer (start, saveDraft, submit) | `tests/Feature/Survey/EmployerSurveyTest.php` | ⏳ |
| 4A.26 | Feature Test: blast invitations (queue dispatched, alumni status updated) | `tests/Feature/Survey/BlastTest.php` | ⏳ |
| 4A.27 | Feature Test: notification template CRUD | `tests/Feature/Admin/NotificationTemplateTest.php` | ⏳ |
| 4A.28 | Feature Test: notification log listing (filter type, status, date) | `tests/Feature/Admin/NotificationLogTest.php` | ⏳ |

**Total Sesi 4A: 28 task**

---

### Sesi 4B — Survei & Notifikasi Frontend
**Dependensi:** 4A selesai, 3B selesai
**Estimasi:** 4–5 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 4B.1 | Frontend: Store `stores/survey.js` (Pinia: questionnaire, response, answers, completion, status) | `stores/survey.js` | ⏳ |
| 4B.2 | Frontend: Store `stores/notification.js` (Pinia: templates, logs, pagination) | `stores/notification.js` | ⏳ |
| 4B.3 | Frontend: Komponen `survey/SurveyProgressBar.vue` (X dari Y seksi + persentase) | `SurveyProgressBar.vue` | ⏳ |
| 4B.4 | Frontend: Komponen `survey/QuestionPreview.vue` (preview satu pertanyaan dengan state jawaban) | `QuestionPreview.vue` | ⏳ |
| 4B.5 | Frontend: `pages/alumni/SurveyPage.vue` (multi-step: satu seksi per halaman, navigasi prev/next, simpan draft, submit + konfirmasi modal) | `SurveyPage.vue` | ⏳ |
| 4B.6 | Frontend: `pages/alumni/SurveyDonePage.vue` (animasi sukses, tanggal submit, tombol kembali) | `SurveyDonePage.vue` | ⏳ |
| 4B.7 | Frontend: `pages/employer/SurveyPage.vue` (form survei employer — minimal layout) | `Employer/SurveyPage.vue` | ⏳ |
| 4B.8 | Frontend: `pages/employer/DonePage.vue` (halaman konfirmasi employer selesai) | `Employer/DonePage.vue` | ⏳ |
| 4B.9 | Frontend: `pages/admin/survey-periods/SurveyPeriodIndexPage.vue` (tabel, status, response rate) | `SurveyPeriodIndexPage.vue` | ⏳ |
| 4B.10 | Frontend: `pages/admin/survey-periods/SurveyPeriodDetailPage.vue` (progress bar, form kirim undangan massal: channel + kuesioner + filter) | `SurveyPeriodDetailPage.vue` | ⏳ |
| 4B.11 | Frontend: `pages/admin/notifications/NotificationTemplatePage.vue` (tabel template, form buat/edit dengan preview variabel) | `NotificationTemplatePage.vue` | ⏳ |
| 4B.12 | Frontend: `pages/admin/notifications/NotificationLogPage.vue` (tabel log, filter, modal detail error) | `NotificationLogPage.vue` | ⏳ |

**Total Sesi 4B: 12 task**

---

## FASE 5 — ANALITIK & PELAPORAN

### Sesi 5A — Analitik & Pelaporan Backend
**Dependensi:** Fase 4 selesai (data survei tersedia)
**Estimasi:** 3–4 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 5A.1 | Service: `DashboardService` (getSummary, getEmploymentStats, getAlumniMapData, trendData) | `DashboardService.php` | ⏳ |
| 5A.2 | Service: `ReportService` (generateAlumniReport, generateByProdi, generateByAngkatan — PDF + Excel) | `ReportService.php` | ⏳ |
| 5A.3 | Install: `barryvdh/laravel-dompdf` (PDF generation) | `composer.json` | ⏳ |
| 5A.4 | Install: `maatwebsite/excel` (Laravel Excel untuk import/export) | `composer.json` | ⏳ |
| 5A.5 | View Blade: `resources/views/reports/alumni-report.blade.php` (template PDF laporan alumni) | Blade file | ⏳ |
| 5A.6 | View Blade: `resources/views/reports/employer-report.blade.php` (template PDF laporan employer) | Blade file | ⏳ |
| 5A.7 | Controller: `Admin/DashboardController` (summary, employmentStats, alumniMap) | `DashboardController.php` | ⏳ |
| 5A.8 | Controller: `Admin/ReportController` (generatePdf, generateExcel, index, download) | `ReportController.php` | ⏳ |
| 5A.9 | Routes: `/api/v1/admin/dashboard/*`, `/api/v1/admin/reports/*` | `routes/api.php` | ⏳ |
| 5A.10 | Scheduler Command: `GenerateMonthlyReport` (auto-generate laporan bulanan, tanggal 1 jam 07:00) | `GenerateMonthlyReport.php` | ⏳ |
| 5A.11 | Feature Test: dashboard summary + stats (data sesuai filter periode/prodi/angkatan) | `tests/Feature/Admin/DashboardTest.php` | ⏳ |

**Total Sesi 5A: 11 task**

---

### Sesi 5B — Analitik & Pelaporan Frontend
**Dependensi:** 5A selesai
**Estimasi:** 3–4 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 5B.1 | Install: `apexcharts` + `vue3-apexcharts` | `package.json` | ⏳ |
| 5B.2 | Install: `leaflet` | `package.json` | ⏳ |
| 5B.3 | Frontend: Store `stores/dashboard.js` (Pinia: summary, stats, mapData) | `stores/dashboard.js` | ⏳ |
| 5B.4 | Frontend: Komponen `charts/BarChart.vue` (wrapper ApexCharts — top industri) | `BarChart.vue` | ⏳ |
| 5B.5 | Frontend: Komponen `charts/DonutChart.vue` (wrapper ApexCharts — distribusi status pekerjaan) | `DonutChart.vue` | ⏳ |
| 5B.6 | Frontend: Komponen `charts/LineChart.vue` (wrapper ApexCharts — tren respons bulanan) | `LineChart.vue` | ⏳ |
| 5B.7 | Frontend: Komponen `charts/AlumniMap.vue` (Leaflet.js — peta Indonesia dengan marker per kota/provinsi) | `AlumniMap.vue` | ⏳ |
| 5B.8 | Frontend: `pages/admin/DashboardPage.vue` (4 KPI card, 3 chart, tabel aktivitas, kartu periode aktif) | `DashboardPage.vue` | ⏳ |
| 5B.9 | Frontend: `pages/admin/dashboard/StatisticsPage.vue` (statistik ketenagakerjaan detail — semua chart + peta) | `StatisticsPage.vue` | ⏳ |
| 5B.10 | Frontend: `pages/admin/reports/ReportPage.vue` (form generate, loading progress, tabel laporan tersimpan) | `ReportPage.vue` | ⏳ |

**Total Sesi 5B: 10 task**

---

## FASE 6 — KEAMANAN & HARDENING

### Sesi 6A — Security Implementation & Testing
**Dependensi:** Fase 5 selesai (sistem lengkap)
**Estimasi:** 3–4 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 6A.1 | Audit keamanan menyeluruh: OWASP Top 10 checklist per endpoint | Dokumen audit | ⏳ |
| 6A.2 | Implementasi & verifikasi seluruh middleware stack (urutan sesuai 04_ARCHITECTURE.md) | `bootstrap/app.php` | ⏳ |
| 6A.3 | Verifikasi proteksi mass assignment semua model (`$fillable` tidak ada yang terlewat) | Semua model | ⏳ |
| 6A.4 | Verifikasi enkripsi kolom sensitif (`system_settings.value` dengan is_encrypted=1) | `SystemSetting.php` | ⏳ |
| 6A.5 | Implementasi validasi MIME type yang benar (tidak hanya ekstensi) untuk semua upload | Request files | ⏳ |
| 6A.6 | Verifikasi semua file upload disimpan di `storage/app/private/` dan diakses via signed URL | `AlumniService.php`, config | ⏳ |
| 6A.7 | Review & test rate limiting (otp, auth, api, export) — simulasi exceed limit | Unit/Feature test | ⏳ |
| 6A.8 | Review semua endpoint — pastikan tidak ada endpoint yang terlewat middleware Auth/CheckRole | `routes/api.php` | ⏳ |
| 6A.9 | Penetration test sederhana: SQLi, XSS, CSRF, IDOR attempt | Dokumen hasil | ⏳ |
| 6A.10 | Jalankan `composer audit` dan `npm audit --audit-level=high` — resolve semua issue | — | ⏳ |
| 6A.11 | Feature Test lengkap: coverage minimal 80% untuk semua controller | `tests/Feature/` | ⏳ |
| 6A.12 | Unit Test: OtpService (hash, expiry, cooldown, max attempts) | `tests/Unit/OtpServiceTest.php` | ⏳ |
| 6A.13 | Unit Test: AuthService (lockout increment, reset, lockout time) | `tests/Unit/AuthServiceTest.php` | ⏳ |
| 6A.14 | Eksekusi checklist deploy keamanan (15 poin dari 07_SECURITY.md Section 15) | Checklist dokumen | ⏳ |

**Total Sesi 6A: 14 task**

---

## FASE 7 — DEPLOYMENT & OPTIMASI

### Sesi 7A — Server Setup, Deploy, & Optimasi
**Dependensi:** Fase 6 selesai (semua test pass)
**Estimasi:** 2–3 hari

| No | Task | File/Artefak | Status |
|---|---|---|---|
| 7A.1 | Setup server Ubuntu 22.04 LTS (update, upgrade, hardening dasar) | Server | ⏳ |
| 7A.2 | Install PHP 8.3-FPM + ekstensi wajib (mbstring, xml, curl, gd, zip, redis, pdo_mysql) | Server | ⏳ |
| 7A.3 | Install & konfigurasi MySQL 8.0 (user terbatas, SSL jika perlu, backup cron) | Server | ⏳ |
| 7A.4 | Install & konfigurasi Redis 7.x (requirepass, bind 127.0.0.1) | Server | ⏳ |
| 7A.5 | Install & konfigurasi Nginx dengan config dari 04_ARCHITECTURE.md (SSL, rate limiting, security headers) | `/etc/nginx/sites-available/sitras-unisya` | ⏳ |
| 7A.6 | Setup SSL Let's Encrypt (certbot auto-renew) | Server | ⏳ |
| 7A.7 | Konfigurasi PHP-FPM pool `sitras` sesuai 04_ARCHITECTURE.md Section 7 | `/etc/php/8.3/fpm/pool.d/sitras.conf` | ⏳ |
| 7A.8 | Deploy aplikasi: clone repo, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build` | Server | ⏳ |
| 7A.9 | Konfigurasi Supervisor untuk queue workers (sitras-worker-default + sitras-worker-low) | `/etc/supervisor/conf.d/sitras.conf` | ⏳ |
| 7A.10 | Konfigurasi cron untuk Laravel Scheduler (`* * * * * php artisan schedule:run`) | Crontab | ⏳ |
| 7A.11 | Jalankan migrasi + seeder production (`php artisan migrate --force && db:seed --class=ProductionSeeder`) | — | ⏳ |
| 7A.12 | Optimasi Laravel: `config:cache`, `route:cache`, `view:cache`, `event:cache` | — | ⏳ |
| 7A.13 | Setup logrotate untuk `/storage/logs/` dan `/var/log/sitras/` | `logrotate.conf` | ⏳ |
| 7A.14 | Setup backup otomatis: script mysqldump + gzip + GPG encrypt + cron harian | Backup script | ⏳ |
| 7A.15 | Final acceptance testing: smoke test semua fitur utama di environment production | Checklist | ⏳ |
| 7A.16 | Konfigurasi firewall (UFW): hanya port 22, 80, 443 yang terbuka | Server | ⏳ |

**Total Sesi 7A: 16 task**

---

## RINGKASAN TASK PER FASE

| Fase | Sesi | Task | Status |
|---|---|---|---|
| 0 | 0A | 10 (dokumentasi — tidak dihitung development) | ✅ Selesai |
| 1 | 1A | 19 | ✅ Selesai (2026-06-09) |
| 1 | 1B | 28 | ✅ Selesai (2026-06-09) |
| 2 | 2A | 31 | ⏳ |
| 2 | 2B | 16 | ⏳ |
| 2 | 2C | 13 | ⏳ |
| 3 | 3A | 12 | ⏳ |
| 3 | 3B | 9 | ⏳ |
| 4 | 4A | 28 | ⏳ |
| 4 | 4B | 12 | ⏳ |
| 5 | 5A | 11 | ⏳ |
| 5 | 5B | 10 | ⏳ |
| 6 | 6A | 14 | ⏳ |
| 7 | 7A | 16 | ⏳ |
| **TOTAL** | **13 sesi** | **199 task** | — |

> **Catatan Penghitungan:** Total 199 task development (Fase 1–7). Fase 0 berisi 10 task dokumentasi yang sudah selesai dan tidak termasuk dalam hitungan development.

---

## DEPENDENSI ANTAR FASE

```
Fase 0 (Dokumentasi)
    ↓
Fase 1A (Setup + DB) → Fase 1B (Auth)
                              ↓
              ┌───────────────┼────────────────┐
              ↓               ↓                ↓
          Fase 2A         Fase 2B          Fase 2C
         (Alumni)        (Employer)       (Konfigurasi)
              │               │
              └───────┬───────┘
                      ↓
                  Fase 3A (Kuesioner Backend)
                      ↓
                  Fase 3B (Kuesioner Frontend)
                      ↓
          ┌───────────┼──────────────┐
          ↓           ↓              ↓
       Fase 4A    Fase 2A,3A     Fase 2B
    (Survei+Notif Backend)
          ↓
       Fase 4B (Survei+Notif Frontend)
          ↓
       Fase 5A (Analitik Backend)
          ↓
       Fase 5B (Analitik Frontend)
          ↓
       Fase 6A (Security & Testing)
          ↓
       Fase 7A (Deployment)
```

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Tambah task 4A.13 (NotificationController — CRUD templates) dan 4A.14 (NotificationController — log listing) hasil audit konsistensi; tambah task 4A.27 dan 4A.28 (Feature Test notification template & log); tambah task 4A.23 (NotificationTemplateSeeder); update total dari 165 → 199 (setelah penghitungan ulang lengkap semua task); tambah task 0A.10 (audit konsistensi v1.0.1) |
| 1.0.2 | 2026-06-08 | Update task 4A.11: spesifikasi WhatsAppService diperinci sesuai API WA Gateway UNISYA (parameter body `api_key`/`sender`/`number`/`message`/`footer`, baca config dari `system_settings`, aktifkan `full=1`); update task 1A.17: SystemSettingSeeder harus seed 3 key WA gateway |
| 1.0.3 | 2026-06-09 | Fix header STATUS RINGKASAN: `Total Task: 167` → `199` yang tertinggal sejak v1.0.1 (INC-03) |
| 1.0.4 | 2026-06-09 | Update progress Sesi 1A selesai (19/19 task ✅); update STATUS RINGKASAN dan RINGKASAN TASK PER FASE |
| 1.0.5 | 2026-06-09 | Update progress Sesi 1B selesai (28/28 task ✅); update STATUS RINGKASAN dan RINGKASAN TASK PER FASE; counter task selesai 19→47 |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
