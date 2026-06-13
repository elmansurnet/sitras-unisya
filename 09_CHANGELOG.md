# 09_CHANGELOG.md 
# CHANGELOG — SISTEM TRACER STUDY UNISYA
# Versi: 1.7.0 | Tanggal: 2026-06-13

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

## [1.7.0] — 2026-06-13

### Added
- `app/Services/DashboardService.php` — getSummary() (total alumni/employer, periode aktif, response rate, employment stats, recent activities), getEmploymentStats() dengan filter periodId/graduationYearId/studyProgramId (employment rate, avg waiting months, relevance rate, by_industry, by_salary_range, by_graduation_year, by_study_program), getAlumniMap() (sebaran per kota/provinsi + koordinat lat/lng), emptyEmploymentStats() helper
- `app/Services/ReportService.php` — generateAlumniReport(), generateEmployerReport(), getReportList(), getReportDownloadUrl(); integrasi DomPDF + Laravel Excel; simpan ke storage/app/reports/
- `resources/views/reports/alumni-report.blade.php` — template PDF laporan alumni (data statistik, tabel alumni, ringkasan ketenagakerjaan)
- `resources/views/reports/employer-report.blade.php` — template PDF laporan employer (profil perusahaan, hasil survei)
- `app/Http/Controllers/Api/V1/Admin/DashboardController.php` — summary(), employmentStats() (validasi exists filter params), alumniMap() (validasi exists filter params); semua return JsonResponse sesuai 05_API.md §7
- `app/Http/Controllers/Api/V1/Admin/ReportController.php` — generatePdf(), generateExcel(), index(), download(); throttle:reports middleware; signed URL download
- `app/Console/Commands/GenerateMonthlyReport.php` — generate laporan bulanan otomatis, scheduled monthly
- `tests/Feature/Admin/DashboardTest.php` — 15 test: summary (struktur, null period, active period + kalkulasi response_rate, total_alumni count, audit log), employment-stats (struktur, filter valid, filter invalid 422×2), alumni-map (struktur + koordinat, null koordinat aman, filter valid), RBAC 401/403 semua endpoint

### Changed
- `routes/api.php` — tambah group /admin/dashboard (summary, employment-stats, alumni-map) dan /admin/reports (generate/pdf, generate/excel dengan throttle:reports, index, download)
- `app/Console/Kernel.php` — tambah jadwal GenerateMonthlyReport monthly pertama setiap bulan 01:00 WITA

### Fixed
- `app/Http/Controllers/Api/V1/Admin/DashboardController.php` — **hotfix** employmentStats() memanggil `getEmploymentStats($array)` → diganti named arguments 3 param terpisah sesuai signature DashboardService; alumniMap() memanggil `getAlumniMap($array)` → diganti 2 named arguments sesuai signature DashboardService. Mencegah TypeError di production saat filter digunakan.

### Document
- `05_API.md` diupdate ke versi **1.0.4** — dokumentasi endpoint dashboard §7 (summary, employment-stats, alumni-map) dan laporan §8 (generate/pdf, generate/excel, list, download) ditambahkan/diperbarui sesuai implementasi aktual Sesi 5A

### Files Changed
11 file (2 service, 2 blade PDF, 2 controller, 1 command, 1 kernel update, 1 routes update, 1 feature test, 1 hotfix controller)

---

## [1.6.0] — 2026-06-13

### Added
- `frontend/src/stores/survey.js` — Pinia store alumni/employer survey: questionnaire, sections, answers, completion percentage, status tracking, saveDraft & submit actions
- `frontend/src/stores/notification.js` — Pinia store admin notifications: templates list/current/CRUD actions, notification logs list dengan filter & pagination
- `frontend/src/stores/surveyAdmin.js` — Pinia store admin survey periods: list, current, pagination, filters, activate, close, sendInvitations (blast) actions; tidak terencana di task list awal namun diperlukan untuk memisahkan concern survey admin dari survey alumni/employer
- `frontend/src/components/survey/SurveyProgressBar.vue` — progress bar "X dari Y seksi" dengan persentase, animasi fill, ARIA progressbar role
- `frontend/src/components/survey/QuestionPreview.vue` — render satu pertanyaan dengan state jawaban, mendukung semua 10 tipe pertanyaan dari QuestionRenderer, mode read-only untuk preview
- `frontend/src/pages/alumni/SurveyPage.vue` — halaman survei alumni multi-step (satu seksi per halaman), navigasi prev/next, auto-save draft saat pindah seksi, modal konfirmasi submit, integrasi SurveyProgressBar & QuestionPreview, conditional logic visibility
- `frontend/src/pages/alumni/SurveyDonePage.vue` — halaman sukses alumni pasca-submit: animasi centang, tanggal submit, ringkasan, tombol kembali ke dashboard
- `frontend/src/pages/employer/SurveyPage.vue` — halaman survei employer via survey_token: layout minimal, semua pertanyaan single-page, saveDraft & submit, validasi required
- `frontend/src/pages/employer/DonePage.vue` — halaman konfirmasi employer selesai: pesan terima kasih, info perusahaan, tanpa navigasi (token sudah dipakai)
- `frontend/src/pages/admin/survey-periods/SurveyPeriodIndexPage.vue` — tabel daftar periode survei, kolom status (draft/active/closed), response rate progress bar, aksi aktivasi/tutup cepat
- `frontend/src/pages/admin/survey-periods/SurveyPeriodDetailPage.vue` — detail periode survei: header KPI (total undangan, submitted, response rate), progress bar per prodi, form kirim undangan massal (pilih channel WA/Email, pilih kuesioner, filter alumni sasaran)
- `frontend/src/pages/admin/notifications/NotificationTemplatePage.vue` — tabel template notifikasi, form buat/edit dengan preview variabel `{{variable}}`, validasi enum type/event/channel, RBAC superadmin
- `frontend/src/pages/admin/notifications/NotificationLogPage.vue` — tabel log notifikasi, filter type/status/recipient_type/date_from/date_to, modal detail error (provider_response JSON), pagination

### Files Changed
13 file baru (3 store, 2 komponen, 8 halaman)

### Notes
- `surveyAdmin.js` ditambahkan di luar task list resmi sebagai kebutuhan arsitektur: memisahkan state survey admin (period management) dari state survey alumni/employer agar tidak terjadi conflict state
- Fase 4 (Survei & Notifikasi) dinyatakan **selesai penuh**: 4A ✅ (28/28) + 4B ✅ (12/12) = 40 task selesai
- `04_ARCHITECTURE.md` diperbarui manual ke versi 1.0.4 (mencatat penambahan `surveyAdmin.js` di folder struktur)

---

## [1.5.0] — 2026-06-13

### Added
- `database/migrations/*_create_survey_periods_table.php` — tabel survey_periods
- `database/migrations/*_create_alumni_survey_period_table.php` — pivot alumni ↔ periode survei
- `database/migrations/*_create_survey_responses_table.php` — tabel survey_responses
- `database/migrations/*_create_survey_answers_table.php` — tabel survey_answers
- `database/migrations/*_create_notification_templates_table.php` — tabel notification_templates
- `database/migrations/*_create_notification_logs_table.php` — tabel notification_logs
- `app/Models/SurveyPeriod.php` — model dengan cast JSON target_graduation_years, relasi alumni/responses
- `app/Models/SurveyResponse.php` — model relasi ke questionnaire, alumni, employer, answers
- `app/Models/SurveyAnswer.php` — model dengan cast JSON answer_options
- `app/Models/NotificationTemplate.php` — model dengan cast JSON variables, relasi ke logs
- `app/Models/NotificationLog.php` — model dengan cast JSON provider_response
- `app/Observers/SurveyResponseObserver.php` — update alumni/employer survey_status + audit log saat submitted
- `app/Services/SurveyService.php` — getSurveyForAlumni, getSurveyForEmployer, saveDraft, submit, validateAnswers, calculateCompletion
- `app/Services/NotificationService.php` — renderTemplate, sendToAlumni, sendToEmployer, blastPeriod, logSend
- `app/Services/WhatsAppService.php` — sendMessage via WA Gateway UNISYA (wacenter.unisya.ac.id), baca config dari system_settings, retry logic, simpan provider_response
- `app/Http/Controllers/Api/V1/Admin/SurveyPeriodController.php` — index, show, store, update, activate, close, sendInvitations
- `app/Http/Controllers/Api/V1/Admin/NotificationController.php` — CRUD templates (index, show, store, update, destroy) + log listing dengan filter
- `app/Http/Controllers/Api/V1/Alumni/SurveyController.php` — show, saveDraft, submit
- `app/Http/Controllers/Api/V1/Employer/SurveyController.php` — show, saveDraft, submit (auth via survey_token)
- `app/Http/Requests/Survey/SaveDraftRequest.php` — validasi draft survey alumni & employer
- `app/Http/Requests/Survey/SubmitSurveyRequest.php` — validasi submit survey dengan cek required questions
- `app/Jobs/ProcessSurveyBlast.php` — job queue low, loop alumni sasaran, dispatch notifikasi per alumni
- `app/Console/Commands/SendSurveyReminders.php` — kirim reminder alumni belum submit, daily 08:00 WITA
- `app/Console/Commands/CloseExpiredSurveyPeriods.php` — auto-close periode melewati end_date, daily 00:05 WITA
- `app/Console/Commands/CleanupExpiredOtps.php` — hapus otp_codes expired, setiap 30 menit
- `app/Console/Kernel.php` — registrasi jadwal scheduler: survey:close-expired, survey:send-reminders, otp:cleanup
- `database/seeders/NotificationTemplateSeeder.php` — 8 template default (survey_invitation, otp_login, survey_reminder, employer_survey_invitation × WA & Email)
- `tests/Feature/Survey/AlumniSurveyTest.php` — 11 test: GET survey, saveDraft (idempotent, status update), submit (status update, double-submit 409, required validation)
- `tests/Feature/Survey/EmployerSurveyTest.php` — 9 test: token valid/invalid/expired, draft, submit, double-submit 409, required validation
- `tests/Feature/Survey/BlastTest.php` — 9 test: Queue::assertPushedOn('low'), validasi channel, RBAC, blast ke period non-active → 422
- `tests/Feature/Admin/NotificationTemplateTest.php` — 12 test: CRUD lengkap, unique type+event, enum validation, RBAC
- `tests/Feature/Admin/NotificationLogTest.php` — 13 test: filter type/status/recipient_type/date_from/date_to/kombinasi, paginasi

### Changed
- `app/Providers/AppServiceProvider.php` — register SurveyResponseObserver, binding SurveyService & NotificationService
- `routes/api.php` — tambah group: /admin/survey-periods, /admin/notifications, /alumni/survey, /employer/survey
- `database/seeders/DatabaseSeeder.php` — call NotificationTemplateSeeder

### Files Changed
39 file (6 migration, 5 model, 1 observer, 3 service, 4 controller, 2 request, 1 job, 3 command, 1 kernel, 1 seeder, 5 test, 3 update existing + app/Console/Kernel.php)

---

## [1.4.0] — 2026-06-12

> **Sumber:** Penyelesaian Sesi 3B — Kuesioner Dinamis (Frontend).
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **SHA commit:**
> - 3B.1: `eda9c01ed6088ae0e001096f3d3bbc53c26a2658` (stores/questionnaire.js)
> - 3B.2: `b7a0f19639b939099f1bdf61c61e1dcfbb04f6c2` (QuestionnaireIndexPage)
> - 3B.3 + 3B.8: `2ee60d1606d69dfee2176b95cc2f5f85eb10ab3d` (QuestionnaireBuilderPage)
> - 3B.4 + 3B.6: *(ConditionalLogicEditor + QuestionEditor)*
> - 3B.5 + 3B.7: `a6477fcf2a18efd3aa5a9d99656fc7cb128ab4a7` (QuestionRenderer)
> - 3B.9: `351f3430f5e7622aec6570f463cd9a250d022a05` (QuestionnairePreviewPage final)

---

### Added — Frontend Sesi 3B (9 task)

#### Added — Pinia Store (1 file)
- `frontend/src/stores/questionnaire.js` — State: `list`, `pagination`, `filters`, `current`, `sections`, `questions`, `loading*`, `error`; Getters: `totalPages`, `hasFilters`, `isDraft`, `isPublished`, `isArchived`, `questionsBySection`, `totalQuestions`; Actions lengkap: `fetchList`, `fetchById`, `create`, `update`, `destroy`, `publish`, `archive`, `addSection`, `updateSection`, `removeSection`, `fetchQuestions`, `addQuestion`, `updateQuestion`, `removeQuestion`, `reorderQuestions`, `setFilters`, `resetFilters`, `clearCurrent`, `clearBuilder`

#### Added — Pages Admin (3 file)
- `frontend/src/pages/admin/questionnaires/QuestionnaireIndexPage.vue` — Tabel daftar kuesioner; filter status (draft/aktif/arsip) + tipe (alumni/employer) + pencarian; pagination; aksi per baris (preview/builder/hapus); badge status berwarna; empty state; modal konfirmasi hapus
- `frontend/src/pages/admin/questionnaires/QuestionnaireBuilderPage.vue` — Layout split panel (seksi kiri + builder kanan); drag-drop reorder pertanyaan via `@vueuse/core useSortable` atau native HTML5 DnD; integrasi penuh `QuestionEditor` (modal edit) + `ConditionalLogicEditor` (panel inline); aksi tambah seksi, hapus seksi, publish/arsip dari header; auto-save optimistis ke store
- `frontend/src/pages/admin/questionnaires/QuestionnairePreviewPage.vue` — **Final (replace stub):** top bar sticky dengan toggle mode `Semua ↔ Per Seksi`; mode `all` tampilkan semua seksi sekaligus + dummy submit disabled; mode `step` tampilkan satu seksi per langkah dengan navigasi Prev/Next + dot navigator (desktop) + mini dot (mobile); answer counter (`X dari Y pertanyaan dijawab`, `Z wajib belum diisi`); completion percent dari answered/total (bukan step-based); `scrollToMain()` setiap navigasi step; conditional logic evaluator (`evaluateCondition` + `isQuestionVisible`) support operator: equals, not_equals, contains, not_contains, greater_than, less_than, is_empty, is_not_empty; ARIA: `aria-live="polite"` pada region seksi aktif, `aria-current="step"` pada dot navigator, `role="progressbar"` + `aria-valuenow` pada progress bar; reset jawaban; print/PDF; back navigation; link ke builder

#### Added — Form Components (3 file)
- `frontend/src/components/forms/QuestionEditor.vue` — Form edit/tambah pertanyaan: field `question_text`, `type` (10 tipe via dropdown), `is_required`, `help_text`; section dinamis per tipe: `options` (radio/checkbox/select — add/remove/reorder opsi), `settings` skala (min/max/label), `settings` placeholder (text/textarea/number/email), tanpa field tambahan (date/file); emit `save` + `cancel`; validasi client-side
- `frontend/src/components/forms/QuestionRenderer.vue` — Render pertanyaan di 3 mode (`builder` / `preview` / `fill`); **10 tipe:** text, textarea, radio, checkbox, select, scale (tombol numerik dengan label min/max), date, number, email, file (drop zone + file picker); builder toolbar: drag handle, badge tipe berwarna, badge kondisi (amber), tombol edit/logic/move-up/move-down/delete; v-model bridge via `localValue` + watcher dua arah; validasi error dari prop `error`; `isBuilderMode` computed untuk disable input di mode builder
- `frontend/src/components/forms/ConditionalLogicEditor.vue` — UI tambah/edit/hapus kondisi visibilitas pertanyaan; pilih pertanyaan sumber (dari flat list seksi yang sama), operator (8 pilihan sesuai tipe), nilai; toggle logika AND/OR antar kondisi; emit perubahan ke parent (QuestionnaireBuilderPage) untuk disimpan via `updateQuestion`

---

### Changed
- Tidak ada perubahan pada file yang sudah ada di luar penggantian stub QuestionnairePreviewPage

---

### Ringkasan File Terdampak v1.4.0

| File | Aksi | Keterangan |
|---|---|---|
| `frontend/src/stores/questionnaire.js` | Added | Pinia store lengkap (list+detail+builder+CRUD) |
| `frontend/src/pages/admin/questionnaires/QuestionnaireIndexPage.vue` | Added | Index + filter + pagination |
| `frontend/src/pages/admin/questionnaires/QuestionnaireBuilderPage.vue` | Added | Builder + drag-drop + integrasi editor |
| `frontend/src/pages/admin/questionnaires/QuestionnairePreviewPage.vue` | Changed | Replace stub → final (all+step, counter, ARIA) |
| `frontend/src/components/forms/QuestionEditor.vue` | Added | Form edit pertanyaan 10 tipe |
| `frontend/src/components/forms/QuestionRenderer.vue` | Added | Render 10 tipe + toolbar builder |
| `frontend/src/components/forms/ConditionalLogicEditor.vue` | Added | UI kondisi visibilitas |
| `08_PHASE_TRACKER.md` | Changed | Sesi 3B 9/9 ✅; counter 108→117 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 9 file produksi (6 Added + 1 Changed) | Sesi 3B complete: 9/9 task ✅**
**Task selesai keseluruhan: 128/219**

---

## [1.3.0] — 2026-06-12

> **Sumber:** Penyelesaian Sesi 3A — Kuesioner Dinamis Backend.
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Batch pelaksanaan:**
> - Batch 1 (3A.1–3A.5): Migrations + 4 Models (Questionnaire, QuestionnaireSection, Question, QuestionOption)
> - Batch 2 (3A.6–3A.7): QuestionnaireService + QuestionnairePolicy
> - Batch 3 (3A.8–3A.9): FormRequests + QuestionnaireController (13 actions)
> - Batch 4 (3A.10): Routes api.php update
> - Batch 5 (3A.11–3A.12): Unit Test + Feature Test

---

### Added — File Kode Produksi Sesi 3A

#### Added — Migrations (4 file)
- `database/migrations/2026_06_12_000012_create_questionnaires_table.php` — Tabel `questionnaires` (title, description, type ENUM: tracer_study/kepuasan/umum, status ENUM: draft/published/archived, `created_by` FK ke users, `published_at` TIMESTAMP NULL, SoftDeletes, index status+type)
- `database/migrations/2026_06_12_000013_create_questionnaire_sections_table.php` — Tabel `questionnaire_sections` (FK ke questionnaires, title, description, `order_position` TINYINT UNSIGNED, index questionnaire_id+order_position)
- `database/migrations/2026_06_12_000014_create_questions_table.php` — Tabel `questions` (FK ke questionnaire_sections+questionnaires, question_text, `question_type` ENUM: text/textarea/radio/checkbox/select/likert/rating/date/file/number, `is_required` BOOLEAN, `order_position` SMALLINT UNSIGNED, `validation_rules` JSON NULL, `conditional_logic` JSON NULL, SoftDeletes)
- `database/migrations/2026_06_12_000015_create_question_options_table.php` — Tabel `question_options` (FK ke questions, option_text, option_value, `order_position` TINYINT UNSIGNED; digunakan untuk radio/checkbox/select/likert)

#### Added — Models (4 file)
- `app/Models/Questionnaire.php` — `$fillable`, `$casts` (`published_at`→datetime), SoftDeletes; relasi: `sections()` (HasMany, ordered by order_position), `questions()` (HasManyThrough via sections), `responses()` (HasMany SurveyResponse); scope `published()`, `draft()`, `archived()`; helper `isPublished()`, `isDraft()`, `canBeEdited()` (hanya draft yang bisa diedit)
- `app/Models/QuestionnaireSection.php` — `$fillable`; relasi: `questionnaire()` (BelongsTo), `questions()` (HasMany ordered by order_position); method `reorderQuestions(array $orderedIds)`
- `app/Models/Question.php` — `$fillable`, `$casts` (`validation_rules`→array, `conditional_logic`→array, `is_required`→boolean); relasi: `section()` (BelongsTo), `questionnaire()` (BelongsTo), `options()` (HasMany QuestionOption ordered by order_position), `answers()` (HasMany SurveyAnswer); helper `hasOptions()` (true untuk radio/checkbox/select/likert), `requiresOptions()`, `isConditional()` (cek conditional_logic tidak null)
- `app/Models/QuestionOption.php` — `$fillable`; relasi: `question()` (BelongsTo)

#### Added — Service (1 file)
- `app/Services/QuestionnaireService.php` — Method lengkap dalam DB transaction:
  - `create(array $data, User $createdBy)` → Questionnaire berstatus draft + AuditLog
  - `update(Questionnaire $q, array $data)` → guard: tolak update jika status bukan draft; AuditLog dengan $oldValues
  - `publish(Questionnaire $q)` → validasi min 1 seksi + min 1 pertanyaan per seksi; set `published_at`; AuditLog level info
  - `archive(Questionnaire $q)` → guard: hanya published yang bisa diarsipkan; AuditLog level warning (tidak bisa di-unarchive)
  - `addSection(Questionnaire $q, array $data)` → auto-set `order_position` = max+1; AuditLog
  - `updateSection(QuestionnaireSection $s, array $data)` → AuditLog
  - `deleteSection(QuestionnaireSection $s)` → guard: questionnaire harus draft; hapus semua question+option dalam seksi (cascade manual karena SoftDeletes); re-order seksi tersisa; AuditLog
  - `addQuestion(QuestionnaireSection $s, array $data)` → auto-set order_position; sync options jika tipe memerlukan options; AuditLog
  - `updateQuestion(Question $q, array $data)` → sync options; AuditLog dengan $oldValues
  - `deleteQuestion(Question $q)` → guard: questionnaire harus draft; hapus options; re-order pertanyaan tersisa; AuditLog
  - `reorderSections(Questionnaire $q, array $orderedIds)` → update order_position batch; AuditLog
  - `reorderQuestions(QuestionnaireSection $s, array $orderedIds)` → update order_position batch; AuditLog

#### Added — Policy (1 file)
- `app/Policies/QuestionnairePolicy.php` — `viewAny`/`view` (superadmin+admin), `create` (superadmin+admin), `update`/`addSection`/`addQuestion` (superadmin+admin; guard: hanya status draft), `publish` (superadmin+admin; guard: hanya dari draft), `archive` (superadmin only), `delete` (superadmin only; hanya draft)

#### Added — Form Requests (3 file)
- `app/Http/Requests/Questionnaire/StoreQuestionnaireRequest.php` — `title` required max:255, `description` nullable, `type` required in:tracer_study,kepuasan,umum; `authorize()` cek role admin/superadmin
- `app/Http/Requests/Questionnaire/StoreSectionRequest.php` — `title` required max:255, `description` nullable; `authorize()` cek role + questionnaire status draft via route model binding
- `app/Http/Requests/Questionnaire/StoreQuestionRequest.php` — `question_text` required, `question_type` required in:10 tipe valid, `is_required` boolean, `order_position` nullable integer, `validation_rules` nullable array, `conditional_logic` nullable array (struktur: `{show_if: {question_id, operator, value}}`), `options` array required_if:question_type,in:radio,checkbox,select,likert (setiap item: `option_text` required, `option_value` required, `order_position` integer)

#### Added — Controller (1 file)
- `app/Http/Controllers/Api/V1/Admin/QuestionnaireController.php` — 13 action methods, semua dengan `Gate::authorize()`:
  - `index` — list paginate (filter: type, status, search); load count sections+questions
  - `show` — load relasi penuh: sections → questions → options (nested eager loading)
  - `store` → `StoreQuestionnaireRequest` + `QuestionnaireService::create()`
  - `update` → `StoreQuestionnaireRequest` + `QuestionnaireService::update()` (guard draft)
  - `publish` → `QuestionnaireService::publish()` (validasi min section+question)
  - `archive` → `QuestionnaireService::archive()` (superadmin only, AuditLog warning)
  - `addSection` → `StoreSectionRequest` + `QuestionnaireService::addSection()`
  - `updateSection` → `StoreSectionRequest` + `QuestionnaireService::updateSection()`
  - `deleteSection` → `QuestionnaireService::deleteSection()` (cascade + reorder)
  - `addQuestion` → `StoreQuestionRequest` + `QuestionnaireService::addQuestion()`
  - `updateQuestion` → `StoreQuestionRequest` + `QuestionnaireService::updateQuestion()`
  - `deleteQuestion` → `QuestionnaireService::deleteQuestion()` (cascade options + reorder)
  - `reorder` → body `{sections: [...ids]}` atau `{section_id, questions: [...ids]}`; `QuestionnaireService::reorderSections/reorderQuestions()`; route didaftarkan SEBELUM `{questionnaire}` sesuai INC-04

#### Changed — Routes & Provider (2 file)
- `routes/api.php` — Tambah routes admin questionnaire: static routes (`reorder`, `stats`) didaftarkan SEBELUM `apiResource` sesuai INC-04; nested routes untuk sections (`{questionnaire}/sections`, `{questionnaire}/sections/{section}/questions`); route `publish` dan `archive` sebagai POST method
- `app/Providers/AppServiceProvider.php` — Registrasi `QuestionnairePolicy` untuk `Questionnaire::class`

#### Added — Tests (2 file)
- `tests/Unit/QuestionnaireServiceTest.php` — 18 unit test cases: `publish()` berhasil (ada seksi+pertanyaan), gagal tanpa seksi (QuestionnairePublishException), gagal tanpa pertanyaan, gagal dari status bukan draft; `archive()` berhasil dari published, gagal dari draft (tidak bisa arsip draft); `reorderSections()` urutan baru tersimpan; `addQuestion()` dengan options sync (radio 3 pilihan → 3 QuestionOption tersimpan); `deleteSection()` cascade hapus questions+options; `conditional_logic` tersimpan sebagai array; `update()` tolak jika status bukan draft
- `tests/Feature/Admin/QuestionnaireTest.php` — 24 feature test cases: `index` (filter type/status, search, pagination), `show` (nested eager load sections→questions→options), `store` (berhasil, validasi type invalid, forbidden alumni), `update` (berhasil draft, forbidden jika published), `publish` (berhasil, gagal tanpa seksi, gagal tanpa pertanyaan, forbidden employer), `archive` (superadmin berhasil, admin forbidden), `addSection`, `updateSection`, `deleteSection` (cascade check), `addQuestion` (dengan options), `updateQuestion` (sync options), `deleteQuestion`, `reorder` (sections + questions)

---

### Security
- `QuestionnairePolicy::archive()` dan `delete()` hanya superadmin sesuai `07_SECURITY.md §3.3`
- `update()` dan semua mutasi konten hanya diizinkan saat status `draft` — mencegah modifikasi kuesioner yang sudah disebarkan ke alumni
- `publish()` memvalidasi kelengkapan struktur sebelum diizinkan — tidak ada kuesioner kosong yang bisa dipublish
- Route `reorder` didaftarkan SEBELUM `{questionnaire}` di `routes/api.php` sesuai `05_API.md §INC-04` untuk mencegah konflik routing Laravel
- Semua query via Eloquent dengan parameter binding; `conditional_logic` disimpan sebagai JSON terenkode (bukan eval/exec)
- Gate::authorize() di seluruh 13 action QuestionnaireController — tidak ada endpoint yang lolos tanpa otorisasi

---

### Ringkasan File Terdampak v1.3.0

| File | Aksi | Keterangan |
|---|---|---|
| `database/migrations/2026_06_12_000012_create_questionnaires_table.php` | Added | Tabel questionnaires (type ENUM, status ENUM, SoftDeletes) |
| `database/migrations/2026_06_12_000013_create_questionnaire_sections_table.php` | Added | Tabel sections (order_position, FK questionnaires) |
| `database/migrations/2026_06_12_000014_create_questions_table.php` | Added | Tabel questions (10 type ENUM, validation_rules+conditional_logic JSON) |
| `database/migrations/2026_06_12_000015_create_question_options_table.php` | Added | Tabel options untuk radio/checkbox/select/likert |
| `app/Models/Questionnaire.php` | Added | Model + scopes published/draft/archived + helper canBeEdited() |
| `app/Models/QuestionnaireSection.php` | Added | Model + relasi + reorderQuestions() |
| `app/Models/Question.php` | Added | Model + casts JSON + helpers hasOptions()/isConditional() |
| `app/Models/QuestionOption.php` | Added | Model dasar options |
| `app/Services/QuestionnaireService.php` | Added | 12 methods (CRUD + publish + archive + reorder + cascade) |
| `app/Policies/QuestionnairePolicy.php` | Added | Role-aware + guard status draft |
| `app/Http/Requests/Questionnaire/StoreQuestionnaireRequest.php` | Added | Validasi store questionnaire |
| `app/Http/Requests/Questionnaire/StoreSectionRequest.php` | Added | Validasi store/update section |
| `app/Http/Requests/Questionnaire/StoreQuestionRequest.php` | Added | Validasi store/update question + options array |
| `app/Http/Controllers/Api/V1/Admin/QuestionnaireController.php` | Added | 13 action methods (CRUD + publish + archive + reorder) |
| `routes/api.php` | Changed | Routes admin questionnaire (static sebelum param, nested sections/questions) |
| `app/Providers/AppServiceProvider.php` | Changed | QuestionnairePolicy registration |
| `tests/Unit/QuestionnaireServiceTest.php` | Added | 18 unit test cases (publish/archive/reorder/conditional logic) |
| `tests/Feature/Admin/QuestionnaireTest.php` | Added | 24 feature test cases (CRUD + publish + archive + reorder per role) |
| `08_PHASE_TRACKER.md` | Changed | Sesi 3A 12/12 ✅; Fase 3: 3A ✅, 3B ⏳; counter 121→133 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 20 file ditambah/diubah | Sesi 3A complete: 12/12 task ✅**
**Task selesai keseluruhan: 119/219**

---

## [1.2.0] — 2026-06-12

> **Sumber:** Penyelesaian Sesi 2C — Konfigurasi Akademik & Sistem (Backend + Frontend).
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Batch pelaksanaan:**
> - Batch 1 (2C.1–2C.3): FacultyController, StudyProgramController, GraduationYearController + FormRequests
> - Batch 2 (2C.4): UserController + UserObserver + FormRequests + AppServiceProvider update
> - Batch 3 (2C.5–2C.6): SettingController + AuditLogController + FormRequests
> - Batch 4 (2C.7): Routes api.php update
> - Batch 5 (2C.8–2C.10): FacultyPage.vue, StudyProgramPage.vue, GraduationYearPage.vue
> - Batch 6 (2C.11–2C.13): UserManagementPage.vue, SystemSettingPage.vue, AuditLogPage.vue

---

### Added — File Kode Produksi Sesi 2C

#### Added — Controllers (6 file)
- `app/Http/Controllers/Api/V1/Admin/FacultyController.php` — CRUD fakultas (index, show, store, update, destroy); middleware `auth:sanctum` + role admin/superadmin; AuditLog::record() di store/update/destroy; response sesuai `05_API.md §5.1`
- `app/Http/Controllers/Api/V1/Admin/StudyProgramController.php` — CRUD program studi dengan filter `faculty_id`; validasi FK ke tabel faculties; load relasi faculty di show/index; AuditLog setiap mutasi
- `app/Http/Controllers/Api/V1/Admin/GraduationYearController.php` — CRUD tahun kelulusan (year INTEGER unik); guard duplikat tahun di StoreRequest; AuditLog di setiap mutasi
- `app/Http/Controllers/Api/V1/Admin/UserController.php` — CRUD user admin + action `toggleActive` (superadmin only via Gate::authorize); tidak bisa hapus/nonaktif diri sendiri; load role di setiap response; AuditLog level warning untuk toggleActive
- `app/Http/Controllers/Api/V1/Admin/SettingController.php` — `index` (semua setting dikelompokkan per group), `update` (bulk update array settings, superadmin only); cache invalidation setelah update; AuditLog dengan diff nilai lama→baru
- `app/Http/Controllers/Api/V1/Admin/AuditLogController.php` — `index` read-only (superadmin only); filter: `module`, `action`, `user_id`, `date_from`, `date_to`, `level`; paginate 50/page; response ringkas (tanpa payload besar)

#### Added — Form Requests (8 file)
- `app/Http/Requests/Faculty/StoreFacultyRequest.php` — `name` required unique, `code` required unique 2–10 char uppercase
- `app/Http/Requests/Faculty/UpdateFacultyRequest.php` — sama, unique ignore current id
- `app/Http/Requests/StudyProgram/StoreStudyProgramRequest.php` — `name`, `code`, `faculty_id` (exists:faculties,id), `degree_level` ENUM
- `app/Http/Requests/StudyProgram/UpdateStudyProgramRequest.php` — unique code ignore current, `faculty_id` nullable (tidak ganti kalau tidak diisi)
- `app/Http/Requests/GraduationYear/StoreGraduationYearRequest.php` — `year` integer antara 1990–2050, unique graduation_years
- `app/Http/Requests/GraduationYear/UpdateGraduationYearRequest.php` — unique year ignore current
- `app/Http/Requests/User/StoreUserRequest.php` — `name`, `email` unique users, `password` min 8, `role` in:admin/superadmin, `is_active` boolean
- `app/Http/Requests/User/UpdateUserRequest.php` — semua optional; `email` unique ignore current; `password` nullable min 8
- `app/Http/Requests/Setting/UpdateSettingRequest.php` — `settings` array required, setiap item: `key` string exists di system_settings, `value` nullable string

#### Added — Observer (1 file)
- `app/Observers/UserObserver.php` — Event `created`, `updated`, `deleted` → `AuditLog::record()`; `updated`: capture `$oldValues` sebelum save; `deleted`: log `deleted_by` + level `warning` jika yang dihapus adalah admin aktif

#### Changed — Provider & Routes (2 file)
- `app/Providers/AppServiceProvider.php` — Registrasi `UserObserver` untuk `User::class`; pastikan tidak duplikasi dengan observer sebelumnya
- `routes/api.php` — Tambah route group `admin/faculties` (apiResource), `admin/study-programs` (apiResource + filter), `admin/graduation-years` (apiResource), `admin/users` (apiResource + POST `{user}/toggle-active`), `admin/settings` (GET index + PUT update), `admin/audit-logs` (GET index only, superadmin middleware)

#### Added — Frontend Pages (6 file)
- `frontend/src/pages/admin/settings/FacultyPage.vue` — Tabel inline-edit fakultas; modal tambah/edit; konfirmasi hapus dengan cek apakah fakultas punya program studi aktif; search by nama/kode; 14 KB
- `frontend/src/pages/admin/settings/StudyProgramPage.vue` — Tabel program studi; filter dropdown per fakultas; badge degree_level; modal form (select fakultas via dropdown); 19 KB
- `frontend/src/pages/admin/settings/GraduationYearPage.vue` — Tabel tahun kelulusan dengan badge jumlah alumni; CRUD modal sederhana; sort descending default; 14 KB
- `frontend/src/pages/admin/settings/UserManagementPage.vue` — Tabel user admin; toggle aktif/nonaktif dengan konfirmasi (hanya superadmin, dikunci untuk diri sendiri); badge role; modal tambah/edit user + reset password; 22 KB
- `frontend/src/pages/admin/settings/SystemSettingPage.vue` — Tab SMTP, WhatsApp Gateway, Umum, Keamanan, Notifikasi; tombol "Test Koneksi" per channel; auto-save per group; masked field untuk api_key/password; 16 KB
- `frontend/src/pages/admin/settings/AuditLogPage.vue` — Tabel audit log read-only; filter module/action/user/level/date range; expandable row untuk lihat detail payload; export CSV; 19 KB

---

### Security
- `UserController::toggleActive()` dan `destroy()` hanya bisa dieksekusi superadmin (Gate::authorize) sesuai `07_SECURITY.md §3.3`
- `AuditLogController` hanya GET, tidak ada mutasi — sesuai prinsip audit trail immutable
- `SettingController::update()` superadmin only; nilai setting tidak di-expose ke role lebih rendah
- Semua controller menggunakan Form Request (bukan manual `$request->validate()`); mass assignment via `$fillable` di semua model terkait
- UserObserver mencatat level `warning` saat admin aktif dihapus untuk deteksi anomali

---

### Ringkasan File Terdampak v1.2.0

| File | Aksi | Keterangan |
|---|---|---|
| `app/Http/Controllers/Api/V1/Admin/FacultyController.php` | Added | CRUD fakultas + AuditLog |
| `app/Http/Controllers/Api/V1/Admin/StudyProgramController.php` | Added | CRUD prodi + filter faculty_id |
| `app/Http/Controllers/Api/V1/Admin/GraduationYearController.php` | Added | CRUD tahun kelulusan |
| `app/Http/Controllers/Api/V1/Admin/UserController.php` | Added | CRUD user admin + toggleActive (superadmin) |
| `app/Http/Controllers/Api/V1/Admin/SettingController.php` | Added | index + bulk update (superadmin) |
| `app/Http/Controllers/Api/V1/Admin/AuditLogController.php` | Added | index read-only + filter (superadmin) |
| `app/Http/Requests/Faculty/StoreFacultyRequest.php` | Added | Validasi store fakultas |
| `app/Http/Requests/Faculty/UpdateFacultyRequest.php` | Added | Validasi update fakultas |
| `app/Http/Requests/StudyProgram/StoreStudyProgramRequest.php` | Added | Validasi store prodi |
| `app/Http/Requests/StudyProgram/UpdateStudyProgramRequest.php` | Added | Validasi update prodi |
| `app/Http/Requests/GraduationYear/StoreGraduationYearRequest.php` | Added | Validasi store tahun kelulusan |
| `app/Http/Requests/GraduationYear/UpdateGraduationYearRequest.php` | Added | Validasi update tahun kelulusan |
| `app/Http/Requests/User/StoreUserRequest.php` | Added | Validasi store user admin |
| `app/Http/Requests/User/UpdateUserRequest.php` | Added | Validasi update user admin |
| `app/Http/Requests/Setting/UpdateSettingRequest.php` | Added | Validasi bulk update settings |
| `app/Observers/UserObserver.php` | Added | created/updated/deleted → AuditLog |
| `app/Providers/AppServiceProvider.php` | Changed | Registrasi UserObserver |
| `routes/api.php` | Changed | 6 route group baru (faculties, study-programs, graduation-years, users, settings, audit-logs) |
| `frontend/src/pages/admin/settings/FacultyPage.vue` | Added | CRUD inline fakultas (14 KB) |
| `frontend/src/pages/admin/settings/StudyProgramPage.vue` | Added | CRUD prodi + filter (19 KB) |
| `frontend/src/pages/admin/settings/GraduationYearPage.vue` | Added | CRUD tahun kelulusan (14 KB) |
| `frontend/src/pages/admin/settings/UserManagementPage.vue` | Added | CRUD user + toggleActive (22 KB) |
| `frontend/src/pages/admin/settings/SystemSettingPage.vue` | Added | Setting sistem multi-tab (16 KB) |
| `frontend/src/pages/admin/settings/AuditLogPage.vue` | Added | Audit log read-only + filter (19 KB) |
| `08_PHASE_TRACKER.md` | Changed | Sesi 2C 13/13 ✅; Fase 2 ✅ selesai penuh; counter 108→121 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 26 file ditambah/diubah | Sesi 2C complete: 13/13 task ✅ | Fase 2 complete: 60/60 task ✅**
**Task selesai keseluruhan: 107/219**

---

## [1.1.0] — 2026-06-12

> **Sumber:** Penyelesaian Sesi 2B — Manajemen Employer (Backend + Frontend).
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **SHA commit utama:**
> - 2B.1–2B.3: `c873c441a55d64a8a9d8c767f190b0c204dea937`
> - 2B.4–2B.6: `0d91950c1b047bde940f68820a5f30dbf0839caa`
> - 2B.7–2B.9: `755fd9b85dc406a0b9d069bf17dc13f5c32dbdbb`
> - 2B.10: `dd3a77846624b0a8b3d7427e814a9876df533b31`
> - 2B.11–2B.16: `e5d56fe6a31da1c82e051a5979babcd0d947053b`

---

### Added — File Kode Produksi Sesi 2B

#### Added — Migrations (2 file)
- `database/migrations/2026_06_12_000010_create_employers_table.php` — Tabel `employers` (FK ke `users`, `industry_sector_id`, `survey_token` VARCHAR(64), `token_expires_at`, `survey_status` ENUM: belum_disurvei/terkirim/selesai, SoftDeletes, index lengkap)
- `database/migrations/2026_06_12_000011_create_alumni_employer_table.php` — Tabel pivot `alumni_employer` (FK ke `alumni.id` + `employers.id`, `is_current` BOOLEAN)

#### Added — Model (1 file)
- `app/Models/Employer.php` — `$fillable`, `$casts` (dates, boolean, JSON), SoftDeletes; relasi: `user()`, `alumni()` (BelongsToMany via pivot alumni_employer), `workHistories()` (HasManyThrough), `surveyResponses()`; accessor `surveyTokenIsExpired()`

#### Added — Observer (1 file)
- `app/Observers/EmployerObserver.php` — Event handler `created`, `updated`, `deleted` → `AuditLog::record()` dengan module `employer`, capture `$oldValues` sebelum update, log `deleted_by` pada soft delete

#### Added — Repository (2 file)
- `app/Repositories/Contracts/EmployerRepositoryInterface.php` — Interface: `findWithFilters()`, `findWithRelations()`, `getStats()`, `findByToken()`
- `app/Repositories/EmployerRepository.php` — Implementasi: `paginate()` (search by nama/email/industri, filter status/sector), `findWithRelations()` (load alumni pivot + surveyResponses), `getStats()` (count per survey_status), `findByToken()` (validate token aktif)

#### Added — Service (1 file)
- `app/Services/EmployerService.php` — `create()` (DB transaction + generateToken awal + AuditLog), `update()` (capture oldValues + AuditLog), `delete()` (soft delete + AuditLog), `generateToken()` (`Str::random(64)`, expiry 30 hari dari config), `sendSurveyToken()` (dispatch SendWhatsAppNotification/SendEmailNotification ke queue high, update survey_status→terkirim), `regenerateToken()` (invalidasi token lama + generate baru + AuditLog level warning)

#### Added — Policy (1 file)
- `app/Policies/EmployerPolicy.php` — `viewAny`/`view` (superadmin+admin), `create`/`update` (superadmin+admin), `delete` (superadmin only), `sendSurveyToken` (superadmin+admin)

#### Added — Form Requests (2 file)
- `app/Http/Requests/Employer/StoreEmployerRequest.php` — Validasi: `name` required, `email` unique employers, `phone` format, `industry_sector_id` exists, `address` nullable; `authorize()` cek role admin/superadmin
- `app/Http/Requests/Employer/UpdateEmployerRequest.php` — Validasi: `email` unique ignore current, semua field optional kecuali `name`; `authorize()` cek role + policy

#### Added — Controllers (2 file)
- `app/Http/Controllers/Api/V1/Admin/EmployerController.php` — `index` (paginate+filter+stats), `show` (load alumni terkait), `store`, `update`, `destroy`, `sendSurveyToken`, `regenerateToken`; Gate::authorize() di setiap action; response sesuai `05_API.md §4.1–4.9`
- `app/Http/Controllers/Api/V1/Employer/ProfileController.php` — `show` (profil employer dari token), `update` (field terbatas: nama/alamat/deskripsi); akses via middleware `ValidateEmployerToken`

#### Changed — Routes & Provider (2 file)
- `routes/api.php` — Tambah routes admin employer (`/api/v1/admin/employers/*` dengan static routes `sendSurveyToken`, `regenerateToken`, `stats` didaftarkan SEBELUM `{employer}`) dan route employer self-service (`/api/v1/employer/profile`)
- `app/Providers/AppServiceProvider.php` — Registrasi `EmployerPolicy` untuk `Employer::class`; bind `EmployerRepositoryInterface` → `EmployerRepository`

#### Added — Frontend (4 file)
- `frontend/src/stores/employer.js` — Pinia store: `employers` list, `currentEmployer`, `pagination`, `filters` (status/sector/search), actions: `fetchEmployers()`, `fetchEmployer()`, `createEmployer()`, `updateEmployer()`, `deleteEmployer()`, `sendSurveyToken()`, `regenerateToken()`; loading & error flags
- `frontend/src/pages/admin/employers/EmployerIndexPage.vue` — Tabel employer dengan filter status/sektor/pencarian, pagination, aksi cepat (detail/edit/hapus/kirim token), badge survey_status, empty state
- `frontend/src/pages/admin/employers/EmployerDetailPage.vue` — Tab profil employer, daftar alumni terkait (pivot), status survei, tombol kirim token (pilih channel WA/email) + regenerasi token dengan konfirmasi modal
- `frontend/src/pages/admin/employers/EmployerFormPage.vue` — Form tambah/edit employer: field nama/email/phone/industri/alamat/deskripsi; mode create vs edit otomatis berdasarkan route param; validasi client-side + server error handling

#### Added — Tests (2 file)
- `tests/Feature/Admin/EmployerTest.php` — 26 test cases: `index` (filter status, filter sektor, search, pagination), `show` (load relasi alumni), `store` (berhasil, validasi duplikat email, forbidden alumni/employer), `update` (berhasil, ignore own email, forbidden), `destroy` (superadmin berhasil, admin forbidden, check soft delete), `stats` (count per survey_status)
- `tests/Feature/Admin/EmployerTokenTest.php` — 14 test cases: `sendSurveyToken` channel WA (Queue::fake() → assertDispatched SendWhatsAppNotification), channel email (assertDispatched SendEmailNotification), channel invalid (422), `regenerateToken` (token baru ≠ token lama, expiry diperbarui, AuditLog level=warning), employer profile access via ValidateEmployerToken middleware

---

### Security
- `EmployerPolicy::delete()` hanya superadmin sesuai `07_SECURITY.md §3.3`
- `survey_token` disimpan plaintext di DB (bukan hash) — berbeda dari OTP; sesuai desain karena token diverifikasi exact-match via `findByToken()`
- `regenerateToken()` di-log ke audit_logs dengan `level=warning` untuk traceability
- Gate::authorize() di setiap EmployerController action; tidak ada endpoint yang lolos tanpa otorisasi
- Semua query via Eloquent dengan parameter binding; tidak ada raw SQL

---

### Ringkasan File Terdampak v1.1.0

| File | Aksi | Keterangan |
|---|---|---|
| `database/migrations/2026_06_12_000010_create_employers_table.php` | Added | Tabel employers (survey_token, status ENUM, SoftDeletes) |
| `database/migrations/2026_06_12_000011_create_alumni_employer_table.php` | Added | Pivot alumni_employer |
| `app/Models/Employer.php` | Added | Model Employer (relasi, casts, accessor token expired) |
| `app/Observers/EmployerObserver.php` | Added | Observer created/updated/deleted → AuditLog |
| `app/Repositories/Contracts/EmployerRepositoryInterface.php` | Added | Interface repository |
| `app/Repositories/EmployerRepository.php` | Added | Implementasi findWithFilters, getStats, findByToken |
| `app/Services/EmployerService.php` | Added | CRUD + generateToken + sendSurveyToken + regenerateToken |
| `app/Policies/EmployerPolicy.php` | Added | Role-aware: delete=superadmin only |
| `app/Http/Requests/Employer/StoreEmployerRequest.php` | Added | Validasi store employer |
| `app/Http/Requests/Employer/UpdateEmployerRequest.php` | Added | Validasi update employer (unique ignore) |
| `app/Http/Controllers/Api/V1/Admin/EmployerController.php` | Added | 7 actions (CRUD + sendToken + regenerateToken) |
| `app/Http/Controllers/Api/V1/Employer/ProfileController.php` | Added | show + update (employer self-service) |
| `routes/api.php` | Changed | Routes admin employer + employer profile |
| `app/Providers/AppServiceProvider.php` | Changed | EmployerPolicy registration + Repository binding |
| `frontend/src/stores/employer.js` | Added | Pinia store employer (CRUD + token actions) |
| `frontend/src/pages/admin/employers/EmployerIndexPage.vue` | Added | Tabel employer + filter + aksi |
| `frontend/src/pages/admin/employers/EmployerDetailPage.vue` | Added | Detail profil + alumni terkait + kirim token |
| `frontend/src/pages/admin/employers/EmployerFormPage.vue` | Added | Form tambah/edit employer |
| `tests/Feature/Admin/EmployerTest.php` | Added | 26 test cases CRUD per role |
| `tests/Feature/Admin/EmployerTokenTest.php` | Added | 14 test cases token lifecycle |
| `08_PHASE_TRACKER.md` | Changed | Sesi 2B 16/16 ✅; counter 92→108; status Fase 2 update |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 22 file ditambah/diubah | Sesi 2B complete: 16/16 task ✅**
**Task selesai keseluruhan: 94/219**

---

## [1.0.9] — 2026-06-12

### Changed
- `08_PHASE_TRACKER.md` — Sesi 2A dinyatakan ✅ Selesai penuh (31/31 task diverifikasi ada di repository): semua backend task (2A.1–2A.14), frontend stores & components (2A.15–2A.22), frontend pages admin alumni (2A.23–2A.26), frontend pages alumni (2A.27–2A.29), dan feature tests (2A.30–2A.31) telah dikonfirmasi keberadaannya; task 2A.4 (AlumniObserver) diupdate ✅; counter task selesai 61→92; status Fase 2 diupdate menjadi "2A ✅, 2B ⏳, 2C ⏳"; versi dokumen 1.0.8→1.0.9

### Added
- Entri RIWAYAT VERSI 1.0.9 di `08_PHASE_TRACKER.md`

### Ringkasan File Terdampak v1.0.9

| File | Aksi | Keterangan |
|---|---|---|
| `08_PHASE_TRACKER.md` | Changed | Sesi 2A 31/31 ✅; counter 61→92; status Fase 2 update; versi 1.0.8→1.0.9 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 2 file diubah | Sesi 2A complete: 31/31 task ✅**
**Task selesai keseluruhan: 78/219**

---

## [1.0.8] — 2026-06-12

### Changed
- `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php` — refactor: hapus inline `validate()` dan private helper `rules()` / `authorizeSelf()` / `authorizeOwnership()`; inject `StoreWorkHistoryRequest` di `store()` dan `UpdateWorkHistoryRequest` di `update()`; perbaiki `$oldValues` agar capture field relevan sebelum update
- `08_PHASE_TRACKER.md` — update status Sesi 2A: 14/31 backend selesai, tambah catatan audit 2026-06-12, perjelas task 2A.9 (6 Form Request), koreksi catatan 2A.4 (placeholder, diimplementasi bersama 2B)

### Added
- `app/Http/Requests/Alumni/UpdateWorkHistoryRequest.php` — Form Request baru untuk `WorkHistoryController@update`: aturan validasi ownership (`authorize()` cek `alumni_id`), rules `company_name`, `position`, `industry_sector_id`, `start_date`, `end_date`, `is_current`, `description`

### Fixed
- Konsistensi Form Request di seluruh controller Sesi 2A: semua method kini menggunakan Form Request yang di-inject (bukan inline `$request->validate()`), sesuai aturan implementasi di System Instructions
- Tidak ada file duplikat di `app/Http/Controllers/Api/V1/Admin/alumni/` — direktori tersebut tidak pernah ter-push ke main branch (audit bersih)

---

## [1.0.7] — 2026-06-11

> **Sumber:** Patch Sesi 2A — Refactor `WorkHistoryController` + tambah `UpdateWorkHistoryRequest`.
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Perubahan berisi perbaikan konsistensi kode produksi — tidak ada perubahan skema database atau API endpoint.**

***

### Changed — WorkHistoryController (Refactor Form Request)

#### Changed — Controllers (1 file)
- `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php` — Refactor method `store()` dan `update()`:
  - `store()`: inject `StoreWorkHistoryRequest` (sebelumnya: `Illuminate\Http\Request` dengan inline `$request->validate()`)
  - `update()`: inject `UpdateWorkHistoryRequest` baru (sebelumnya: `Illuminate\Http\Request` dengan inline `$request->validate()`)
  - Hapus private method `rules()` yang duplikat logika validasi
  - Hapus private helper `authorizeSelf()` & `authorizeOwnership()` (sudah ditangani di method `authorize()` Form Request)
  - Perbaiki `$oldValues` di `update()`: capture field `position`, `company_name`, `employment_type`, `is_current`, `start_date`, `end_date` sebelum update (sebelumnya hanya capture seluruh `toArray()` tanpa seleksi field relevan)

#### Added — Form Requests (1 file)
- `app/Http/Requests/Alumni/UpdateWorkHistoryRequest.php` — Baru:
  - `authorize()`: cek ownership `alumni_id` milik authenticated user (role alumni) atau superadmin/admin
  - Rules: `position`, `company_name` required; `employment_type` ENUM; `start_date` date; `end_date` nullable setelah `start_date`; `is_current` boolean; semua field konsisten dengan `02_DATABASE.md §2.2 alumni_work_histories`
  - Route model binding: inject `AlumniWorkHistory $workHistory` untuk otorisasi di `authorize()`

### Ringkasan File Terdampak v1.0.7

| File | Aksi | Keterangan |
|---|---|---|
| `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php` | Changed | Inject Form Request (store + update), hapus inline validate & helper private, perbaiki $oldValues capture |
| `app/Http/Requests/Alumni/UpdateWorkHistoryRequest.php` | Added | Form Request baru untuk update riwayat kerja |
| `08_PHASE_TRACKER.md` | Changed | 2A.9 tambah `UpdateWorkHistoryRequest`; 2A.12 keterangan refactor; counter 2A backend 14→15, total selesai 61→62 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 4 file ditambah/diubah | Patch 2A.12 refactor WorkHistoryController ✅**
**Task selesai keseluruhan: 62/219**

---

## [1.0.6] — 2026-06-09

> **Sumber:** Penyelesaian Backend Sesi 2A — Manajemen Alumni (task 2A.1–2A.14).
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Perubahan berisi penambahan file kode produksi — tidak ada perubahan dokumentasi spesifikasi.**

---

### Added — File Kode Produksi Sesi 2A (Backend)

#### Added — Migrations (3 file)
- `database/migrations/*_create_alumni_table.php` — Tabel `alumni` (30+ kolom, ENUM `survey_status`: belum_disurvei/terkirim/sedang_mengisi/selesai, FK ke users/study_programs/graduation_years, index lengkap, SoftDeletes)
- `database/migrations/*_create_alumni_work_histories_table.php` — Tabel `alumni_work_histories` (ENUM `employment_type`, `job_relevance`, FK ke alumni/salary_ranges/industry_sectors)
- `database/migrations/*_create_survey_responses_table.php` — Skeleton tabel `survey_responses` untuk Sesi 4A

#### Added — Models (2 file)
- `app/Models/Alumni.php` — `$fillable`, `$casts` (gpa→decimal:2, dates, boolean), SoftDeletes, relasi lengkap (user, studyProgram, graduationYear, workHistories, surveyResponses), method `isProfileComplete()`
- `app/Models/AlumniWorkHistory.php` — `$fillable`, `$casts` (start_date, end_date→datetime), relasi ke Alumni, SalaryRange, IndustrySector

#### Added — Repository (1 file)
- `app/Repositories/AlumniRepository.php` — `paginate()` (search/filter/sort), `findWithRelations()`, `findByUserId()`, `all()` (untuk export), `stats()` (ringkasan per survey_status)

#### Added — Services (2 file)
- `app/Services/AlumniService.php` — `create()`, `update()`, `delete()` (DB transaction + AuditLog), `uploadPhoto()` (storage/app/private), `import()` (batch via ImportExportService), `export()` (dispatch GenerateReportExport), `sendInvitation()` (dispatch SendBulkInvitationJob)
- `app/Services/ImportExportService.php` — `parseExcel()`, `validateRows()`, `batchInsert()`, `generateTemplate()`, `exportExcel()` via maatwebsite/excel

#### Added — Policy (1 file)
- `app/Policies/AlumniPolicy.php` — `viewAny`/`view`/`create`/`update` (superadmin+admin; alumni self-only), `delete` (superadmin only sesuai 07_SECURITY.md §3.3), `import`/`export` (superadmin+admin)

#### Added — Form Requests (4 file)
- `app/Http/Requests/Alumni/StoreAlumniRequest.php` — Validasi lengkap nim/nik/gpa/email unique, study_program/graduation_year exists
- `app/Http/Requests/Alumni/UpdateAlumniRequest.php` — Validasi dengan `Rule::unique()->ignore()` untuk update
- `app/Http/Requests/Alumni/ImportAlumniRequest.php` — file `mimes:xlsx,csv`, max 10MB
- `app/Http/Requests/Alumni/SendInvitationRequest.php` — channel (whatsapp/email/both), questionnaire_id exists

#### Added — Controllers (3 file)
- `app/Http/Controllers/Api/V1/Admin/AlumniController.php` — `index` (paginate+filter), `show`, `store`, `update`, `destroy`, `import`, `export`, `importTemplate`, `stats`, `sendInvitation`; response format sesuai 05_API.md §3.1–3.9; Gate::authorize() di setiap action
- `app/Http/Controllers/Api/V1/Alumni/ProfileController.php` — `show`, `update` (field terbatas alumni self), `uploadPhoto`; akses foto via temporary signed URL
- `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php` — `index`/`store`/`update`/`destroy` (self), `indexForAdmin`; `is_current` reset logic (hanya 1 pekerjaan aktif)

#### Added — Jobs & Exports (3 file)
- `app/Jobs/SendBulkInvitationJob.php` — Kirim undangan via WA Gateway UNISYA (POST ke `wacenter.unisya.ac.id`), update `survey_status`→`terkirim`, `AuditLog::record()`, retry 3x, queue: `high`
- `app/Jobs/GenerateReportExport.php` — Generate Excel via maatwebsite/excel, simpan ke `storage/private/exports/`, queue: `default`
- `app/Exports/AlumniExport.php` — Maatwebsite Excel export class: heading row, auto-size kolom, bold header

#### Changed — Routes
- `routes/api.php` — Tambah routes admin alumni (`/api/v1/admin/alumni/*`) dan alumni self-service (`/api/v1/alumni/*`); static routes (`/import`, `/export`, `/template`, `/stats`) didaftarkan SEBELUM `{alumni}` sesuai 05_API.md §INC-04 note

#### Changed — App Provider
- `app/Providers/AuthServiceProvider.php` — Register `AlumniPolicy` untuk `Alumni::class`

---

### Security
- `AlumniPolicy::delete()` hanya superadmin sesuai 07_SECURITY.md §3.3
- File upload disimpan ke `storage/app/private/`, akses via `temporaryUrl()` (signed URL)
- `Gate::authorize()` digunakan di setiap controller action (bukan hanya middleware role)
- `self-authorization` check di WorkHistoryController dan ProfileController (alumni hanya bisa akses milik sendiri)
- Tidak ada raw SQL — semua query via Eloquent dengan parameter binding

---

### Ringkasan File Terdampak v1.0.6

| File | Aksi | Keterangan |
|---|---|---|
| 3 migration files | Added | alumni, alumni_work_histories, survey_responses (skeleton) |
| 2 model files | Added | Alumni (gpa decimal:2, SoftDeletes), AlumniWorkHistory |
| `app/Repositories/AlumniRepository.php` | Added | paginate/filter/sort/stats |
| `app/Services/AlumniService.php` | Added | CRUD + upload + import + export + invite |
| `app/Services/ImportExportService.php` | Added | Excel parse/validate/batch/template |
| `app/Policies/AlumniPolicy.php` | Added | Role-aware: delete=superadmin only |
| 4 form request files | Added | Store, Update, Import, SendInvitation |
| `Admin/AlumniController.php` | Added | 10 actions (CRUD + import + export + stats + invite) |
| `Alumni/ProfileController.php` | Added | show + update + uploadPhoto |
| `Alumni/WorkHistoryController.php` | Added | CRUD self + indexForAdmin |
| `app/Jobs/SendBulkInvitationJob.php` | Added | WA blast via gateway UNISYA, queue: high |
| `app/Jobs/GenerateReportExport.php` | Added | Excel export, queue: default |
| `app/Exports/AlumniExport.php` | Added | Maatwebsite export class |
| `routes/api.php` | Changed | Routes admin/alumni alumni dengan static-before-param ordering |
| `app/Providers/AuthServiceProvider.php` | Changed | AlumniPolicy registration |
| `08_PHASE_TRACKER.md` | Changed | Sesi 2A backend 14/31 task → ✅; counter selesai 47→61 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: 17 file ditambah/diubah | 2A backend complete: 14/31 task ✅**
**Task selesai keseluruhan: 61/219**

---

## [1.0.5] — 2026-06-09

> **Sumber:** Penyelesaian Sesi 1B — Sistem Autentikasi Backend + Frontend.
> Engineer: Claude (Lead Engineer SITRAS UNISYA).
> **Perubahan berisi penambahan file kode produksi — bukan perubahan dokumentasi spesifikasi.**

---

### Added — File Kode Produksi Sesi 1B

#### Added — Middleware (4 file)
- `app/Http/Middleware/CheckRole.php` — Validasi role via parameter; support multi-role (`CheckRole:admin,superadmin`)
- `app/Http/Middleware/EnsureAccountActive.php` — Cek `users.is_active = 1`; return 403 jika nonaktif
- `app/Http/Middleware/ValidateEmployerToken.php` — Cek token exist, belum expired, belum used; set employer context ke request
- `app/Http/Middleware/LogActivity.php` — Tulis ke `audit_logs` setiap request dari middleware grup admin

#### Added — Services (2 file)
- `app/Services/OtpService.php` — Generate `random_int(100000,999999)`, hash SHA-256, kirim via queue, verify dengan `hash_equals`, cooldown & max attempts check
- `app/Services/AuthService.php` — Login admin (email+password), lockout logic (`incrementLoginAttempts`, `resetLoginAttempts`), employer token login

#### Added — Controllers (3 file)
- `app/Http/Controllers/Api/V1/Auth/OtpController.php` — `request()`, `verify()`; rate limit `otp-request` (3/menit per identifier)
- `app/Http/Controllers/Api/V1/Auth/AuthController.php` — `loginAdmin()`, `loginEmployer()`, `logout()`, `me()`
- `app/Http/Controllers/Api/V1/Public/PublicController.php` — `validateToken()`, master data (faculties, study programs, graduation years, sectors, salary ranges)

#### Added — Form Requests (3 file)
- `app/Http/Requests/Auth/LoginRequest.php` — Validasi email+password login admin
- `app/Http/Requests/Auth/OtpRequestRequest.php` — Validasi identifier + channel (wa/email)
- `app/Http/Requests/Auth/OtpVerifyRequest.php` — Validasi identifier + code (6 digit)

#### Added — Jobs (2 file)
- `app/Jobs/SendWhatsAppNotification.php` — Queue: high; kirim via WA Gateway UNISYA `wacenter.unisya.ac.id`; log ke `notification_logs`
- `app/Jobs/SendEmailNotification.php` — Queue: high; kirim via Laravel Mail; log ke `notification_logs`

#### Added — Routes
- `routes/api.php` — Semua route `/api/v1/auth/*` dengan rate limiting tepat; route `/api/v1/public/*`

#### Added — Frontend (11 file)
- `resources/js/services/api.js` — Axios instance + request interceptor (Bearer token) + response interceptor (401 redirect)
- `resources/js/stores/auth.js` — Pinia store: `user`, `token`, `login()`, `logout()`, `fetchMe()`, `isAuthenticated` computed
- `resources/js/layouts/AuthLayout.vue` — Split panel kiri (branding) + kanan (slot form), responsif mobile stack
- `resources/js/pages/auth/LoginPage.vue` — Form email+password admin; error handling lockout & nonaktif
- `resources/js/pages/auth/OtpRequestPage.vue` — Form identifier + channel (WA/Email) untuk alumni
- `resources/js/pages/auth/OtpVerifyPage.vue` — Form 6-digit OTP, countdown timer, tombol resend (cooldown 60 detik)
- `resources/js/pages/auth/EmployerTokenPage.vue` — Validasi token employer, redirect ke survei jika valid
- `resources/js/router/index.js` — Vue Router 4; router guards: `requiresAuth`, role check, redirect logic
- `resources/js/layouts/AdminLayout.vue` — Topbar, sidebar dengan sub-menu collapsible, breadcrumb
- `resources/js/layouts/AlumniLayout.vue` — Topbar navigasi alumni, avatar, responsif
- `resources/js/layouts/EmployerLayout.vue` — Header minimal, nama perusahaan, logo UNISYA

#### Added — Tests (3 file)
- `tests/Feature/Auth/AdminLoginTest.php` — Berhasil, gagal, lockout, akun nonaktif
- `tests/Feature/Auth/OtpTest.php` — Request + verify: berhasil, kedaluwarsa, max attempts, cooldown
- `tests/Feature/Auth/EmployerTokenTest.php` — Valid, kedaluwarsa, sudah digunakan

#### Changed — App Provider
- `app/Providers/AppServiceProvider.php` — Tambah registrasi `RateLimiter` untuk `otp-request`, `auth`, `api`, `export`; daftarkan middleware alias `CheckRole`, `EnsureAccountActive`, `ValidateEmployerToken`, `LogActivity`

#### Changed — Bootstrap
- `bootstrap/app.php` — Daftarkan middleware alias baru ke kernel

---

### Ringkasan File Terdampak v1.0.5

| File | Aksi | Keterangan |
|---|---|---|
| 4 middleware files | Added | CheckRole, EnsureAccountActive, ValidateEmployerToken, LogActivity |
| 2 service files | Added | OtpService, AuthService |
| 3 controller files | Added | OtpController, AuthController, PublicController |
| 3 form request files | Added | LoginRequest, OtpRequestRequest, OtpVerifyRequest |
| 2 job files | Added | SendWhatsAppNotification, SendEmailNotification |
| `routes/api.php` | Changed | Route auth + public + rate limiting |
| 11 frontend files | Added | api.js, auth.js store, 3 layouts, 5 halaman auth, router/index.js |
| 3 test files | Added | AdminLoginTest, OtpTest, EmployerTokenTest |
| `app/Providers/AppServiceProvider.php` | Changed | RateLimiter + middleware alias registration |
| `bootstrap/app.php` | Changed | Middleware alias registration |
| `08_PHASE_TRACKER.md` | Changed | Sesi 1B 28/28 task → ✅; counter selesai 19→47 |
| `09_CHANGELOG.md` | Added | Entri ini |

**Total: ~35 file ditambah/diubah | 1B complete: 28/28 task ✅**
**Task selesai keseluruhan: 47/219**

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
**Task selesai keseluruhan: 19/219**

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

#### Fixed — [INC-03] Phase Tracker: Header "Total Task: 167" sudah tidak akurat (seharusnya 219)
**Ditemukan di:** `08_PHASE_TRACKER.md` Section STATUS RINGKASAN

**Masalah:**
Baris `Total Task: 167 task` di header STATUS RINGKASAN tidak pernah diperbarui sejak versi awal,
padahal tabel RINGKASAN TASK PER FASE di bagian bawah dokumen sudah benar mencantumkan 219 task.
Perbedaan 32 task di antara dua section dalam satu file yang sama adalah inkonsistensi internal kritis.

**Perbaikan:**
- `08_PHASE_TRACKER.md` header: `Total Task: 167 task` → `Total Task: 219 task`

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
| 08_PHASE_TRACKER.md | 1.0.2 | 1.0.3 | Fixed (total task header 167→219) |
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
- Total task development: **219 task** (Fase 1–7)

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
| 19 | 🟡 Moderate | [INC-03] Phase Tracker: header "Total Task: 167" tidak sesuai tabel ringkasan (219) | ✅ Fixed v1.0.3 |
| 20 | 🟡 Moderate | [INC-04] API: endpoint reorder tidak ada catatan routing Laravel (konflik `{id}` vs `reorder`) | ✅ Fixed v1.0.3 |
| 21 | 🟡 Moderate | [INC-05] Security: matriks izin "Profil Alumni" ambigu (admin bisa lihat tapi baris bilang ❌) | ✅ Fixed v1.0.3 |
| 22 | 🟢 Minor | [INC-06/07] Architecture: folder structure pages tidak mencantumkan nama file .vue | ✅ Fixed v1.0.3 |

**Total: 22 inkonsistensi ditemukan sejak v1.0.0 — semua telah diperbaiki**
**Status: ✅ Dokumen SITRAS UNISYA v1.0.5 CLEAR | Development Progress: 47/219 task (Sesi 1A, 1B ✅)**

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
| 1.0.4 | 2026-06-09 | Tambah entri penyelesaian Sesi 1A — 37 file produksi ditambah/diubah; 19/219 task development selesai |
| 1.0.5 | 2026-06-09 | Tambah entri penyelesaian Sesi 1B — ~35 file produksi (middleware, service, controller, job, frontend Vue); 28/28 task ✅ |
| 1.0.6 | 2026-06-09 | Tambah entri penyelesaian Sesi 2A backend — 17 file produksi (migration, model, repository, service, policy, request, controller, job, export, routes); 14/31 task ✅ |
| 1.0.7 | 2026-06-11 | Tambah entri patch WorkHistoryController refactor — inject Form Request, hapus inline validate, tambah UpdateWorkHistoryRequest; 1 task diperbarui |
| 1.0.8 | 2026-06-12 | Changed `app/Http/Controllers/Api/V1/Alumni/WorkHistoryController.php`, fixed Konsistensi Form Request di seluruh controller Sesi 2A, dan added `app/Http/Requests/Alumni/UpdateWorkHistoryRequest.php` — Form Request baru |
| 1.0.9 | 2026-06-12 | Sesi 2A dinyatakan ✅ Selesai penuh (31/31 task diverifikasi ada di repository) |
| 1.1.0 | 2026-06-12 | Tambah entri penyelesaian Sesi 2B — 20 file produksi (migrations, model, observer, repository, service, policy, 2 form request, 2 controller, routes, app provider, 4 frontend Vue, 2 feature tests); 16/16 task ✅; counter 78→94 |
| 1.2.0 | 2026-06-12 | Tambah entri penyelesaian Sesi 2C — 26 file produksi (6 controller, 9 form request, 1 observer, routes+provider update, 6 halaman frontend settings); 13/13 task ✅; Fase 2 selesai penuh (2A+2B+2C); counter 94→107 |
| 1.3.0 | 2026-06-12 | Tambah entri penyelesaian Sesi 3A — 18 file produksi (4 migrations, 4 models, QuestionnaireService 12 methods, QuestionnairePolicy, 3 FormRequest, QuestionnaireController 13 actions, routes+provider, unit test 18 cases + feature test 24 cases); 12/12 task ✅; counter 107→119; Fase 3: 3A ✅, 3B ⏳ |
| 1.4.0 | 2026-06-12 | Tambah entri penyelesaian Sesi 3B — 7 file produksi frontend (store, 3 page, 3 component); QuestionnairePreviewPage replace stub → final; 9/9 task ✅; counter 119→128 |
| 1.5.0 | 2026-06-13 | Sesi 4A dinyatakan Selesai penuh 28/28 task diverifikasi ada di repository. Counter task selesai 119→147. Status Fase 4 diupdate 4A ✅ |
| 1.6.0 | 2026-06-13 | `surveyAdmin.js` ditambahkan di luar task list resmi sebagai kebutuhan arsitektur: memisahkan state survey admin (period management) dari state survey alumni/employer agar tidak terjadi conflict state, Fase 4 (Survei & Notifikasi) dinyatakan **selesai penuh**: 4A ✅ (28/28) + 4B ✅ (12/12) = 40 task selesai, Counter task selesai 128→168. `04_ARCHITECTURE.md` diperbarui manual ke versi 1.0.4 (mencatat penambahan `surveyAdmin.js` di folder struktur) |
| 1.7.0 | 2026-06-13 | **hotfix** employmentStats() memanggil `getEmploymentStats($array)` → diganti named arguments 3 param terpisah sesuai signature DashboardService; alumniMap() memanggil `getAlumniMap($array)` → diganti 2 named arguments sesuai signature DashboardService. Mencegah TypeError di production saat filter digunakan, `05_API.md` diupdate ke versi **1.0.4** — dokumentasi endpoint dashboard §7 (summary, employment-stats, alumni-map) dan laporan §8 (generate/pdf, generate/excel, list, download) ditambahkan/diperbarui sesuai implementasi aktual Sesi 5A. Counter task selesai 168→179. Status Fase 5 diupdate 5A ✅ |


---

*Dokumen ini adalah catatan resmi semua perubahan pada dokumentasi proyek SITRAS UNISYA.*
*Setiap perubahan pada dokumen manapun wajib dicatat di sini sebelum dokumen tersebut digunakan sebagai dasar implementasi.*
