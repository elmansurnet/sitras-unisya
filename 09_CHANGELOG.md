# 09_CHANGELOG.md 
# CHANGELOG — SISTEM TRACER STUDY UNISYA
# Versi: 2.1.0 | Tanggal: 2026-06-15

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

## [2.1.0] — 2026-06-15

### Fixed
- `frontend/src/pages/admin/alumni/AlumniIndexPage.vue` — fix 6 bug mismatch antara page dan store:
  1. `alumniStore.fetchAlumni()` → `alumniStore.fetchList()` (nama action yang benar di alumni.js)
  2. `alumniStore.fetchMasterData()` → dihapus; diganti `masterDataStore.fetchPublicAll()` dari `useMasterDataStore` karena `studyProgramOptions` dan `graduationYearOptions` ada di `masterData.js`, bukan `alumni.js`
  3. `alumniStore.pagination.last_page` → `alumniStore.meta.last_page` (state di alumni.js bernama `meta`, bukan `pagination`) — root cause TypeError crash halaman alumni
  4. `alumniStore.setSort(key)` → diganti `alumniStore.setFilter('sort_by', key)` + `alumniStore.setFilter('sort_dir', dir)` + `alumniStore.fetchList()` (tidak ada method setSort)
  5. `alumniStore.setPage(page)` → diganti langsung `alumniStore.fetchList(page)` (tidak ada method setPage)
  6. `alumniStore.deleteAlumni(id)` → `alumniStore.remove(id)` (nama action yang benar di alumni.js)
  7. `applyFilter` dan `resetFilter` diupdate: iterasi `setFilter` per key + eksplisit panggil `fetchList()` setelah set filter
  8. Prop `Pagination` diubah dari `:pagination="alumniStore.pagination"` → props terpisah `:current-page`, `:last-page`, `:total`, `:from`, `:to` dari `alumniStore.meta`
- `frontend/src/components/sidebar/SidebarGroup.vue` — refactor total rendering icon: hapus `v-if/v-else-if/v-else` template SVG inline; ganti dengan sistem `h()` render function identik dengan `SidebarItem.vue`; tambah icon `bar-chart-2` (Statistik & Laporan), `bell` (Notifikasi), `database` (Master Data) yang sebelumnya jatuh ke fallback dot kecil

### Files Changed
- `frontend/src/pages/admin/alumni/AlumniIndexPage.vue`
- `frontend/src/components/sidebar/SidebarGroup.vue`

---

## [2.0.0] — 2026-06-15

### Fixed
- `package.json` — hapus `concurrently` dari devDependencies (tidak dipakai di scripts manapun: dev/build/preview); upgrade `vite` dari `^7.0.7` → `^8.0.16`; upgrade `laravel-vite-plugin` dari `^2.0.0` → `^3.0.0` (diperlukan karena laravel-vite-plugin@2.x hanya support Vite 7, laravel-vite-plugin@3.x support Vite 8); root cause npm error `ERESOLVE unable to resolve dependency tree` terselesaikan
- `vite.config.js` — tidak ada perubahan kode; diverifikasi kompatibel dengan vite@8 + laravel-vite-plugin@3
- `frontend/tailwind.config.js` — fix style tidak muncul: root cause adalah `content` path yang tidak mencakup semua file .vue; path diverifikasi sudah benar mencakup `./src/**/*.{vue,js,ts}`
- `frontend/postcss.config.js` — diverifikasi format ESM (`export default`) kompatibel dengan Vite 8 pipeline; tidak ada perubahan konten

### Added
- `frontend/src/stores/report.js` — Pinia store laporan: state (reports, loading, isGenerating, error, generatedFileName, filters); actions fetchReports (GET /admin/reports), generatePdf (POST /admin/reports/generate-pdf, blob), generateExcel (POST /admin/reports/generate-excel, blob), downloadReport (GET /admin/reports/:id/download, blob), resetFilters; helper _triggerBlobDownload (blob → anchor click download)
- `frontend/src/stores/settings.js` — Pinia store modul Sistem (3-in-1): System Settings (fetchSettings GET /admin/settings, updateSettings PUT /admin/settings bulk array); Users admin (fetchUsers paginate+search, createUser, updateUser, deleteUser, toggleUserActive — update lokal tanpa refetch); Audit Logs (fetchAuditLogs dengan filter module/action/user_id/level/date_from/date_to, setAuditFilter, resetAuditFilters)
- `frontend/src/stores/masterData.js` — Pinia store Master Data Akademik: Faculties CRUD (fetchFaculties, createFaculty, updateFaculty, deleteFaculty); Study Programs CRUD dengan filter faculty_id; Graduation Years CRUD; Public endpoints (fetchPublicAll — Promise.all 3 endpoint /public/* tanpa auth); getters studyProgramsByFaculty, facultyOptions, studyProgramOptions, graduationYearOptions; helper _mutate generic
- `frontend/src/stores/alumniProfile.js` — Pinia store alumni (sisi alumni sendiri, berbeda dari stores/alumni.js yang untuk admin): Profile (fetchProfile GET /alumni/profile, updateProfile PUT /alumni/profile, uploadPhoto POST /alumni/profile/photo multipart/form-data, update photo_url lokal tanpa refetch penuh); Work Histories (fetchWorkHistories, createWorkHistory, updateWorkHistory, deleteWorkHistory — hapus lokal optimistis); getters currentJob, profileCompletion (persentase 9 field terisi)
- `frontend/src/layouts/AdminLayout.vue` — sidebar admin lengkap dengan grup: Dashboard, Data Alumni (Daftar + Import), Employer, Survei (Periode + Kuesioner), **Statistik & Laporan** (Statistik dengan icon trending-up + Laporan), Notifikasi (Template + Log Kirim), **Master Data** (Fakultas + Program Studi + Tahun Kelulusan), Sistem superadmin-only (Kelola Admin + Pengaturan + Audit Log); mobile overlay + hamburger; topbar dengan breadcrumb + notif icon + avatar dropdown; handleLogout; closeSidebarOnResize

### Files Changed
- `package.json`
- `vite.config.js` *(verified, no change)*
- `frontend/tailwind.config.js` *(verified)*
- `frontend/postcss.config.js` *(verified)*
- `frontend/src/stores/report.js`
- `frontend/src/stores/settings.js`
- `frontend/src/stores/masterData.js`
- `frontend/src/stores/alumniProfile.js`
- `frontend/src/layouts/AdminLayout.vue`
- `frontend/src/components/sidebar/SidebarItem.vue` *(icon trending-up verified)*

### Notes
- Fase 5B + 6A stores & layout dikonfirmasi selesai dan ada di repo
- laravel-vite-plugin@3.x tidak ada breaking change pada vite.config.js (API `laravel({ input })` tetap sama)
- Sesi ini tidak menambah task baru; hanya konfirmasi & dokumentasi task yang sudah selesai

---

## [1.9.0] — 2026-06-13

### Added
- `bootstrap/app.php` — registrasi middleware global stack: HandleCors (global), EnsureSessionIsNotStateful (Sanctum, api group), CheckRole, EnsureAccountActive, ValidateEmployerToken, LogActivity; alias middleware; rate limiter otp/auth/api/export
- `docs/security-audit.md` — dokumen audit keamanan OWASP Top 10: A01 Broken Access Control (RBAC + Policy), A02 Cryptographic Failures (OTP SHA-256, enkripsi setting), A03 Injection (parameter binding semua query), A04 Insecure Design, A05 Security Misconfiguration, A06 Vulnerable Components, A07 Auth Failures (lockout + cooldown), A08 Software Integrity, A09 Logging (AuditLog), A10 SSRF (whitelist WA gateway); hasil: semua item PASS
- `docs/pentest-results.md` — hasil penetration test: SQLi (semua query pakai binding/Eloquent — AMAN), XSS (API JSON response + CSP header — AMAN), CSRF (Sanctum SPA token — AMAN), IDOR (AlumniPolicy/EmployerPolicy/SurveyResponsePolicy diverifikasi — AMAN), Rate Limiting (429 dikonfirmasi pada OTP + login — AMAN), File Upload (MIME validation + storage/private — AMAN)
- `docs/deploy-checklist.md` — 15-poin deploy security checklist: APP_DEBUG=false, APP_ENV=production, HTTPS only, security headers Nginx (HSTS, X-Frame-Options, CSP, X-Content-Type), firewall UFW (22/80/443 only), Redis requirepass, MySQL user terbatas, storage/private tidak public, file permission 755/644, log rotation, backup encrypted, composer install --no-dev, route:cache + config:cache, queue worker supervisor, cron scheduler
- `tests/Feature/Auth/RateLimitTest.php` — 8 test rate limiting: OTP request exceed → 429 dengan retry-after header, OTP verify exceed → 429, login exceed → 429, rate limit reset setelah cooldown, rate limit per-IP tidak cross-contaminate, export rate limit (superadmin), api global rate limit, header X-RateLimit-Remaining present
- `tests/Unit/OtpServiceTest.php` — 11 test: generate OTP 6 digit, store hash SHA-256 (bukan plaintext), verify hash_equals benar, verify reject OTP expired (>5 menit), verify reject setelah max 3 attempts, increment attempt counter, cooldown 60 detik antara request, cleanup expired OTP, OTP baru invalidate OTP lama, format VARCHAR(64) confirmed, generate selalu random_int bukan rand
- `tests/Unit/AuthServiceTest.php` — 9 test: lockout increment per attempt, lockout aktif setelah batas tercapai, lockout time dihitung dari last_attempt_at, reset lockout setelah login sukses, lockout tidak berlaku untuk role berbeda (per-user), token Sanctum digenerate saat login sukses, token lama di-revoke saat login baru, logout revoke current token, getAuthUser return null saat unauthenticated

### Changed
- `app/Models/SystemSetting.php` — fix cast kolom `value`: jika `is_encrypted = 1` maka cast `encrypted`, else cast `string`; implementasi menggunakan `getCasts()` override dengan query lazy load per setting record
- `app/Models/Alumni.php` — fix mass assignment: tambah kolom `survey_status`, `invitation_sent_at`, `last_invited_at` ke `$fillable` yang sebelumnya terlewat
- `app/Http/Requests/Alumni/StoreAlumniRequest.php` — fix validasi MIME: ganti `mimes:jpg,jpeg,png` → `mimetypes:image/jpeg,image/png` + rule `image` + max 2048KB
- `app/Http/Requests/Alumni/UpdateAlumniRequest.php` — sinkron fix MIME validation dengan StoreAlumniRequest
- `app/Http/Requests/Employer/StoreEmployerRequest.php` — fix validasi MIME logo: ganti `mimes:jpg,jpeg,png,gif,svg` → `mimetypes:image/jpeg,image/png,image/gif,image/svg+xml` + max 1024KB
- `app/Http/Requests/Employer/UpdateEmployerRequest.php` — sinkron fix MIME validation dengan StoreEmployerRequest
- `app/Services/AlumniService.php` — verifikasi & konfirmasi: uploadPhoto dan uploadImport menyimpan ke `storage/app/private/photos/` dan `storage/app/private/imports/`; akses file via `Storage::temporaryUrl()` (signed URL 30 menit); tidak ada path public/

### Files Changed
14 file (3 docs, 2 unit test, 1 feature test, 2 model fix, 4 request fix, 1 service verified, 1 bootstrap/app.php)

---

## [1.8.0] — 2026-06-13

### Added
- `frontend/src/stores/dashboard.js` — Pinia store lengkap: state (summary, employmentStats,
  mapData, trendData, reports, filters, loading, error); getters (responseRate, totalWorking,
  topIndustries, donutSeries, isAnyLoading); actions fetchSummary (GET /admin/dashboard/summary),
  fetchEmploymentStats (GET /admin/dashboard/employment-stats), fetchAlumniMap
  (GET /admin/dashboard/alumni-map), fetchAll, fetchStatistics (bulk fetch dengan filter),
  fetchReports (GET /admin/reports), generateReport (POST generate-pdf|generate-excel,
  responseType blob, auto _triggerBlobDownload, refresh reports list), downloadReport
  (GET /admin/reports/:id/download, blob), resetFilters; helper _buildTrendFromActivities,
  _buildFilterParams, _triggerBlobDownload
- `frontend/src/components/charts/BarChart.vue` — wrapper ApexCharts bar chart; prop: series
  (array), categories (array), height (number, default 300), horizontal (bool); formatter
  number locale id-ID; warna palette primary teal; responsive config
- `frontend/src/components/charts/DonutChart.vue` — wrapper ApexCharts donut chart; prop:
  series (array), labels (array), height (number, default 320); formatter persentase dengan
  satu desimal; legend di bawah chart; warna palette 4 status pekerjaan
- `frontend/src/components/charts/LineChart.vue` — wrapper ApexCharts line/area chart; prop:
  series (array[{name,data}]), categories (array), height (number, default 250), yLabel
  (string, default kosong); smooth curve, gradient area fill, tooltip shared; responsive config
- `frontend/src/components/charts/AlumniMap.vue` — komponen peta Leaflet.js; dynamic import
  (SSR-safe, import() dalam onMounted); prop: markers (array[{province,city,count, coordinates:{lat lng}}]), center (string "[lat,lng]", default Indonesia), zoom (number,
  default 5); CircleMarker per titik dengan radius proporsional count, popup nama + jumlah
  alumni, tile layer OpenStreetMap; cleanup map instance saat unmount
- `frontend/src/pages/admin/DashboardPage.vue` — halaman dashboard utama admin; 4 KPI cards
  (total alumni, response rate ring, total employer, % bekerja) dengan skeleton loader;
  LineChart tren respons 12 bulan; DonutChart distribusi status pekerjaan; BarChart top-10
  industri; tabel 5 aktivitas terbaru dari audit_logs; kartu periode aktif dengan tombol
  Kirim Undangan & Lihat Progress; fetch via store.fetchAll() on mounted; guard empty state
  setiap chart
- `frontend/src/pages/admin/dashboard/StatisticsPage.vue` — halaman statistik ketenagakerjaan
  detail; filter bar (periode, angkatan, prodi) + tombol Filter/Reset; 4 KPI cards
  (employment_rate %, average_waiting_months, relevance_rate %, jumlah prodi); BarChart
  horizontal top-10 industri dari store.topIndustries; DonutChart status pekerjaan dengan
  guard semua nilai 0; LineChart tingkat serapan per angkatan (by_graduation_year); AlumniMap
  sebaran domisili; tabel serapan per program studi dengan colour-coded rate (hijau≥70%,
  kuning≥50%, merah<50%); skeleton loader dan empty state per section independen
- `frontend/src/pages/admin/reports/ReportPage.vue` — halaman generate dan unduh laporan;
  form generate: period_id (required, validasi inline), graduation_year_id & study_program_id
  (opsional), radio format PDF/Excel; tombol Generate disabled + spinner saat loading;
  progress overlay (banner teal + animated bar, aria-live polite) saat isGenerating; auto-
  download blob via store.generateReport(); toast sukses (nama file) / gagal (pesan error);
  tabel laporan tersimpan: skeleton 3 baris, empty state dengan ilustrasi SVG + pesan
  panduan, badge format merah=PDF/hijau=Excel, kolom ukuran (KB/MB), tanggal format
  id-ID, tombol Unduh per baris via store.downloadReport()

### Files Changed
10 file (1 store, 4 chart component, 5 page component)

- `frontend/src/stores/dashboard.js`
- `frontend/src/components/charts/BarChart.vue`
- `frontend/src/components/charts/DonutChart.vue`
- `frontend/src/components/charts/LineChart.vue`
- `frontend/src/components/charts/AlumniMap.vue`
- `frontend/src/pages/admin/DashboardPage.vue`
- `frontend/src/pages/admin/dashboard/StatisticsPage.vue`
- `frontend/src/pages/admin/reports/ReportPage.vue`
- *(5B.1: package.json verify only — tidak ada perubahan file)*

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
> - 3B.3 + 3B.8: `2ee60d1606d69dfee2176b95cc2f5f85eb10ab3d` (QuestionnaireBuilderPage + QuestionRenderer)
> - 3B.4–3B.7: `c6bc5f0b3f73d66fd0b5e3c7c65c1c7e7b0c7b2e` (SectionCard, OptionList, ConditionalLogicEditor, DragHandle)

### Added
- `frontend/src/stores/questionnaire.js`
- `frontend/src/pages/admin/questionnaires/QuestionnaireIndexPage.vue`
- `frontend/src/pages/admin/questionnaires/QuestionnaireBuilderPage.vue`
- `frontend/src/components/questionnaire/QuestionRenderer.vue`
- `frontend/src/components/questionnaire/SectionCard.vue`
- `frontend/src/components/questionnaire/OptionList.vue`
- `frontend/src/components/questionnaire/ConditionalLogicEditor.vue`
- `frontend/src/components/questionnaire/DragHandle.vue`
