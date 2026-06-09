# 04_ARCHITECTURE.md
# ARSITEKTUR SISTEM — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.3 | Tanggal: 2026-06-09

---

## 1. GAMBARAN ARSITEKTUR

### 1.1 Pola Arsitektur
Sistem menggunakan pola **Monolitik Enterprise** dengan pemisahan yang tegas antara:
- **Frontend SPA** (Vue 3 + Vite) — berjalan di browser
- **Backend API** (Laravel 12) — berjalan di server
- **Database Layer** (MySQL 8+) — persistence
- **Queue & Scheduler** (Laravel Queue + Redis) — async processing
- **Storage Layer** — file uploads

```
┌────────────────────────────────────────────────────────────────────────┐
│                          CLIENT LAYER                                  │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  Browser (Chrome / Firefox / Edge)                               │ │
│  │  Vue 3 SPA │ Pinia Store │ Vue Router │ ApexCharts │ Leaflet.js  │ │
│  │  TailwindCSS │ Axios                                             │ │
│  └──────────────────────────┬───────────────────────────────────────┘ │
└─────────────────────────────┼──────────────────────────────────────────┘
                              │ HTTPS (REST API / JSON)
                              │ Authorization: Bearer {sanctum_token}
                              ▼
┌────────────────────────────────────────────────────────────────────────┐
│                         SERVER LAYER (Ubuntu 22.04 LTS)               │
│                                                                        │
│  ┌──────────────────────────────────────────────────────────────────┐ │
│  │  NGINX 1.24+                                                     │ │
│  │  - Reverse proxy ke PHP-FPM                                      │ │
│  │  - SSL Termination (Let's Encrypt / TLS 1.2+)                    │ │
│  │  - Static file serving (/public/assets)                          │ │
│  │  - Gzip compression                                              │ │
│  │  - Rate limiting (limit_req_zone: otp, auth, api)               │ │
│  │  - Security headers (lihat 07_SECURITY.md untuk detail CSP)      │ │
│  └──────────────────────────┬───────────────────────────────────────┘ │
│                             │                                          │
│  ┌──────────────────────────▼───────────────────────────────────────┐ │
│  │  PHP-FPM 8.3 (FastCGI)                                           │ │
│  │  Laravel 12 Application                                          │ │
│  │                                                                  │ │
│  │  ┌──────────────┐ ┌───────────────┐ ┌────────────────────────┐ │ │
│  │  │  HTTP Layer  │ │  API Routes   │ │  Middleware Stack       │ │ │
│  │  │  Sanctum Auth│ │  /api/v1/*    │ │  Auth, CORS, Throttle  │ │ │
│  │  └──────────────┘ └───────────────┘ └────────────────────────┘ │ │
│  │                                                                  │ │
│  │  ┌──────────────────────────────────────────────────────────┐   │ │
│  │  │              APPLICATION CORE                            │   │ │
│  │  │  Controllers → Services → Repositories → Models         │   │ │
│  │  │  Policies │ Observers │ Events │ Listeners │ Jobs        │   │ │
│  │  └──────────────────────────────────────────────────────────┘   │ │
│  │                                                                  │ │
│  │  ┌──────────────┐ ┌───────────────┐ ┌────────────────────────┐ │ │
│  │  │  Queue System│ │  Scheduler    │ │  Notification Engine   │ │ │
│  │  │  Redis Driver│ │  Cron (daily) │ │  WA Gateway / SMTP     │ │ │
│  │  └──────────────┘ └───────────────┘ └────────────────────────┘ │ │
│  └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
│  ┌─────────────────────┐  ┌───────────────┐  ┌─────────────────────┐ │
│  │  MySQL 8.0          │  │  Redis 7.x    │  │  Filesystem         │ │
│  │  - Application DB   │  │  - Queue      │  │  - /storage/app     │ │
│  │  - 24 Tables        │  │  - Cache      │  │  - Uploads          │ │
│  │  - Full-text index  │  │  - Sessions   │  │  - Reports (PDF)    │ │
│  └─────────────────────┘  └───────────────┘  └─────────────────────┘ │
└────────────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTPS (Outbound API Call)
                              ▼
┌────────────────────────────────────────────────────────────────────────┐
│                     EXTERNAL SERVICES                                  │
│                                                                        │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────┐ │
│  │  WhatsApp Gateway│  │  SMTP Server     │  │  (Optional Future)   │ │
│  │  (wacenter.      │  │  (Gmail/Mailgun) │  │  S3 Object Storage   │ │
│  │  unisya.ac.id)   │  └──────────────────┘  └──────────────────────┘ │
│  └──────────────────┘
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. STRUKTUR DIREKTORI PROYEK

```
sitras-unisya/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── SendSurveyReminders.php
│   │       ├── CloseExpiredSurveyPeriods.php
│   │       └── GenerateMonthlyReport.php
│   ├── Events/
│   │   ├── AlumniSurveyCompleted.php
│   │   ├── EmployerSurveyCompleted.php
│   │   └── OtpRequested.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── Auth/
│   │   │           │   ├── AuthController.php
│   │   │           │   └── OtpController.php
│   │   │           ├── Admin/
│   │   │           │   ├── AlumniController.php
│   │   │           │   ├── EmployerController.php
│   │   │           │   ├── QuestionnaireController.php
│   │   │           │   ├── SurveyPeriodController.php
│   │   │           │   ├── NotificationController.php
│   │   │           │   ├── ReportController.php
│   │   │           │   ├── DashboardController.php
│   │   │           │   ├── UserController.php
│   │   │           │   ├── SettingController.php
│   │   │           │   ├── FacultyController.php
│   │   │           │   ├── StudyProgramController.php
│   │   │           │   ├── GraduationYearController.php
│   │   │           │   └── AuditLogController.php
│   │   │           ├── Alumni/
│   │   │           │   ├── ProfileController.php
│   │   │           │   ├── WorkHistoryController.php
│   │   │           │   └── SurveyController.php
│   │   │           ├── Employer/
│   │   │           │   ├── ProfileController.php
│   │   │           │   └── SurveyController.php
│   │   │           └── Public/
│   │   │               └── PublicController.php
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   ├── LogActivity.php
│   │   │   ├── EnsureAccountActive.php
│   │   │   └── ValidateEmployerToken.php
│   │   └── Requests/
│   │       ├── Auth/
│   │       │   ├── LoginRequest.php
│   │       │   ├── OtpRequestRequest.php
│   │       │   └── OtpVerifyRequest.php
│   │       ├── Alumni/
│   │       │   ├── StoreAlumniRequest.php
│   │       │   ├── UpdateAlumniRequest.php
│   │       │   └── StoreWorkHistoryRequest.php
│   │       ├── Employer/
│   │       │   ├── StoreEmployerRequest.php
│   │       │   └── UpdateEmployerRequest.php
│   │       ├── Questionnaire/
│   │       │   ├── StoreQuestionnaireRequest.php
│   │       │   ├── StoreSectionRequest.php
│   │       │   └── StoreQuestionRequest.php
│   │       └── Survey/
│   │           ├── SaveDraftRequest.php
│   │           └── SubmitSurveyRequest.php
│   ├── Jobs/
│   │   ├── SendWhatsAppNotification.php
│   │   ├── SendEmailNotification.php
│   │   ├── ProcessSurveyBlast.php
│   │   └── GenerateReportExport.php
│   ├── Listeners/
│   │   ├── SendSurveyInvitation.php
│   │   ├── NotifyAdminOnCompletion.php
│   │   └── HandleOtpRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── OtpCode.php
│   │   ├── AuditLog.php
│   │   ├── Faculty.php
│   │   ├── StudyProgram.php
│   │   ├── GraduationYear.php
│   │   ├── SurveyPeriod.php
│   │   ├── Alumni.php
│   │   ├── AlumniWorkHistory.php
│   │   ├── Employer.php
│   │   ├── Questionnaire.php
│   │   ├── QuestionnaireSection.php
│   │   ├── Question.php
│   │   ├── QuestionOption.php
│   │   ├── SurveyResponse.php
│   │   ├── SurveyAnswer.php
│   │   ├── NotificationTemplate.php
│   │   ├── NotificationLog.php
│   │   ├── SystemSetting.php
│   │   ├── IndustrySector.php
│   │   └── SalaryRange.php
│   ├── Notifications/
│   │   ├── OtpNotification.php
│   │   ├── SurveyInvitationNotification.php
│   │   └── SurveyReminderNotification.php
│   ├── Observers/
│   │   ├── AlumniObserver.php
│   │   ├── EmployerObserver.php
│   │   ├── SurveyResponseObserver.php
│   │   └── UserObserver.php
│   ├── Policies/
│   │   ├── AlumniPolicy.php
│   │   ├── EmployerPolicy.php
│   │   ├── QuestionnairePolicy.php
│   │   └── SurveyResponsePolicy.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   ├── AlumniRepositoryInterface.php
│   │   │   └── EmployerRepositoryInterface.php
│   │   ├── AlumniRepository.php
│   │   └── EmployerRepository.php
│   └── Services/
│       ├── AuthService.php
│       ├── OtpService.php
│       ├── AlumniService.php
│       ├── EmployerService.php
│       ├── QuestionnaireService.php
│       ├── SurveyService.php
│       ├── NotificationService.php
│       ├── WhatsAppService.php
│       ├── ReportService.php
│       ├── DashboardService.php
│       └── ImportExportService.php
├── bootstrap/
│   ├── app.php
│   └── cache/
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cors.php
│   ├── database.php
│   ├── queue.php
│   ├── sanctum.php
│   ├── tracer.php          ← Konfigurasi khusus aplikasi (OTP, lockout, dll.)
│   └── whatsapp.php        ← Konfigurasi WA Gateway UNISYA (url, api_key, sender)
├── database/
│   ├── migrations/         ← 24+ file migrasi (urutan sesuai dependency)
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── SuperadminSeeder.php
│   │   ├── FacultySeeder.php
│   │   ├── StudyProgramSeeder.php
│   │   ├── GraduationYearSeeder.php
│   │   ├── IndustrySectorSeeder.php
│   │   ├── SalaryRangeSeeder.php
│   │   └── SystemSettingSeeder.php
│   └── factories/
├── resources/
│   └── views/
│       ├── app.blade.php   ← Entry point SPA (minimal, hanya load Vue)
│       └── reports/
│           ├── alumni-report.blade.php
│           └── employer-report.blade.php
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
├── storage/
│   ├── app/
│   │   ├── private/        ← File sensitif (akses via signed URL)
│   │   │   ├── photos/
│   │   │   ├── logos/
│   │   │   ├── imports/
│   │   │   └── uploads/
│   │   └── reports/        ← PDF/Excel yang di-generate
│   └── logs/
├── frontend/               ← Vue 3 SPA source
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── common/
│   │   │   │   ├── DataTable.vue
│   │   │   │   ├── FilterBar.vue
│   │   │   │   ├── ConfirmModal.vue
│   │   │   │   ├── Toast.vue
│   │   │   │   ├── FileUpload.vue
│   │   │   │   ├── Pagination.vue
│   │   │   │   └── Badge.vue
│   │   │   ├── charts/
│   │   │   │   ├── BarChart.vue
│   │   │   │   ├── DonutChart.vue
│   │   │   │   ├── LineChart.vue
│   │   │   │   └── AlumniMap.vue
│   │   │   ├── forms/
│   │   │   │   ├── QuestionEditor.vue
│   │   │   │   ├── QuestionRenderer.vue
│   │   │   │   └── ConditionalLogicEditor.vue
│   │   │   └── survey/
│   │   │       ├── SurveyProgressBar.vue
│   │   │       └── QuestionPreview.vue
│   │   ├── composables/
│   │   │   ├── useAuth.js
│   │   │   ├── useAlumni.js
│   │   │   ├── useToast.js
│   │   │   └── useConfirm.js
│   │   ├── layouts/
│   │   │   ├── AdminLayout.vue
│   │   │   ├── AlumniLayout.vue
│   │   │   ├── EmployerLayout.vue
│   │   │   └── AuthLayout.vue
│   │   ├── pages/
│   │   │   ├── auth/
│   │   │   │   ├── LoginPage.vue
│   │   │   │   ├── OtpRequestPage.vue
│   │   │   │   ├── OtpVerifyPage.vue
│   │   │   │   └── EmployerTokenPage.vue
│   │   │   ├── admin/
│   │   │   │   ├── DashboardPage.vue
│   │   │   │   ├── dashboard/
│   │   │   │   │   └── StatisticsPage.vue
│   │   │   │   ├── alumni/
│   │   │   │   │   ├── AlumniIndexPage.vue
│   │   │   │   │   ├── AlumniDetailPage.vue
│   │   │   │   │   ├── AlumniFormPage.vue
│   │   │   │   │   └── AlumniImportPage.vue
│   │   │   │   ├── employers/
│   │   │   │   │   ├── EmployerIndexPage.vue
│   │   │   │   │   ├── EmployerDetailPage.vue
│   │   │   │   │   └── EmployerFormPage.vue
│   │   │   │   ├── questionnaires/
│   │   │   │   │   ├── QuestionnaireIndexPage.vue
│   │   │   │   │   ├── QuestionnaireBuilderPage.vue
│   │   │   │   │   └── QuestionnairePreviewPage.vue
│   │   │   │   ├── survey-periods/
│   │   │   │   │   ├── SurveyPeriodIndexPage.vue
│   │   │   │   │   └── SurveyPeriodDetailPage.vue
│   │   │   │   ├── reports/
│   │   │   │   │   └── ReportPage.vue
│   │   │   │   ├── notifications/
│   │   │   │   │   ├── NotificationTemplatePage.vue
│   │   │   │   │   └── NotificationLogPage.vue
│   │   │   │   └── settings/
│   │   │   │       ├── SystemSettingPage.vue
│   │   │   │       ├── FacultyPage.vue
│   │   │   │       ├── StudyProgramPage.vue
│   │   │   │       ├── GraduationYearPage.vue
│   │   │   │       ├── UserManagementPage.vue
│   │   │   │       └── AuditLogPage.vue
│   │   │   ├── alumni/
│   │   │   │   ├── DashboardPage.vue
│   │   │   │   ├── ProfilePage.vue
│   │   │   │   ├── ProfileEditPage.vue
│   │   │   │   ├── WorkHistoryPage.vue
│   │   │   │   ├── SurveyPage.vue
│   │   │   │   └── SurveyDonePage.vue
│   │   │   └── employer/
│   │   │       ├── SurveyPage.vue
│   │   │       └── DonePage.vue
│   │   ├── router/
│   │   │   └── index.js
│   │   ├── stores/
│   │   │   ├── auth.js
│   │   │   ├── alumni.js
│   │   │   ├── employer.js
│   │   │   ├── questionnaire.js
│   │   │   ├── survey.js
│   │   │   ├── dashboard.js
│   │   │   └── notification.js
│   │   ├── services/
│   │   │   └── api.js      ← Axios instance + interceptors
│   │   └── main.js
│   ├── public/
│   ├── index.html
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── package.json
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   ├── Admin/
│   │   └── Survey/
│   └── Unit/
├── .env.example
├── artisan
├── composer.json
└── package.json
```

---

## 3. LAYER ARSITEKTUR BACKEND

### 3.1 Request Lifecycle
```
HTTP Request (HTTPS)
    ↓
Nginx (reverse proxy, rate limit, security headers)
    ↓
PHP-FPM 8.3 (FastCGI)
    ↓
Laravel Bootstrap (app.php, service providers)
    ↓
Middleware Stack:
  [1] TrustProxies
  [2] HandleCors
  [3] PreventRequestsDuringMaintenance
  [4] ValidatePostSize
  [5] TrimStrings
  [6] ConvertEmptyStringsToNull
  [7] Authenticate (Sanctum) — 401 jika tidak terautentikasi
  [8] EnsureAccountActive — 403 jika akun nonaktif
  [9] CheckRole (RBAC) — 403 jika role tidak sesuai
  [10] ThrottleRequests (rate limiting per endpoint)
  [11] LogActivity (audit logging)
    ↓
Route Dispatcher
    ↓
Form Request Validation (auto-inject, 422 jika gagal)
    ↓
Controller Method
    ↓
Service Layer (business logic)
    ↓
Repository Layer (data access abstraction)
    ↓
Eloquent Model (ORM)
    ↓
MySQL 8.0
    ↓
JSON Resource (API Response transformer)
    ↓
HTTP Response (JSON, format standar)
```

### 3.2 Service Layer Pattern
Setiap modul memiliki dedicated Service class yang mengisolasi business logic:

```php
// Contoh pola Service:
class AlumniService
{
    public function __construct(
        private readonly AlumniRepository $repository,
        private readonly NotificationService $notificationService,
        private readonly ImportExportService $importService,
    ) {}

    public function createAlumni(array $data): Alumni
    {
        // 1. Buat user account
        // 2. Buat profil alumni
        // 3. Log ke audit_logs
        // 4. Return alumni
    }

    public function updateAlumni(int $id, array $data): Alumni { ... }

    public function importFromExcel(UploadedFile $file): ImportResult { ... }

    public function sendSurveyInvitation(Alumni $alumni, int $questionnaireId): void { ... }
}
```

### 3.3 Repository Pattern
Untuk abstraksi akses data pada modul yang kompleks (Alumni, Employer):

```php
interface AlumniRepositoryInterface
{
    public function findByNim(string $nim): ?Alumni;
    public function findWithFilters(array $filters): LengthAwarePaginator;
    public function getStatisticsByPeriod(int $periodId): array;
    public function getMapCoordinates(): Collection;
}
```

---

## 4. LAYER ARSITEKTUR FRONTEND

### 4.1 Vue 3 + Composition API Pattern
```javascript
// Contoh struktur composable
// composables/useAlumni.js
export function useAlumni() {
  const store = useAlumniStore()
  const { loading, error } = storeToRefs(store)

  const fetchAlumni = async (filters) => {
    await store.fetchAlumni(filters)
  }

  const updateAlumni = async (id, data) => {
    await store.updateAlumni(id, data)
  }

  return { loading, error, fetchAlumni, updateAlumni }
}
```

### 4.2 Pinia Store Pattern
```javascript
// stores/alumni.js
export const useAlumniStore = defineStore('alumni', {
  state: () => ({
    list: [],
    current: null,
    pagination: {},
    filters: {},
    loading: false,
    error: null,
  }),
  getters: {
    totalAlumni: (state) => state.pagination.total ?? 0,
  },
  actions: {
    async fetchAlumni(params) {
      this.loading = true
      try {
        const response = await api.get('/admin/alumni', { params })
        this.list = response.data.data
        this.pagination = response.data.meta
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Terjadi kesalahan'
      } finally {
        this.loading = false
      }
    },
    async createAlumni(data) { ... },
  }
})
```

### 4.3 Vue Router Guards
```javascript
// router/index.js
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Cek autentikasi
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'login', query: { redirect: to.fullPath } })
    return
  }

  // Cek peran
  if (to.meta.roles && !to.meta.roles.includes(authStore.user?.role)) {
    next({ name: 'unauthorized' })
    return
  }

  next()
})
```

### 4.4 Axios Instance + Interceptors
```javascript
// services/api.js
import axios from 'axios'
import router from '@/router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL + '/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor: inject token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Response interceptor: handle global errors
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      router.push({ name: 'login' })
    }
    if (error.response?.status === 403) {
      router.push({ name: 'unauthorized' })
    }
    return Promise.reject(error)
  }
)

export default api
```

---

## 5. QUEUE & SCHEDULER ARCHITECTURE

### 5.1 Queue Jobs & Priority
```
Queue: high (prioritas tinggi, diproses pertama)
├── SendOtpNotification       ← OTP harus cepat sampai
└── SendCriticalAlert

Queue: default (prioritas normal)
├── SendEmailNotification     ← Notifikasi email biasa
├── SendWhatsAppNotification  ← Notifikasi WA biasa
└── GenerateReportExport      ← Generate PDF/Excel

Queue: low (prioritas rendah)
└── ProcessSurveyBlast        ← Kirim massal (ratusan alumni)
```

### 5.2 Scheduler (app/Console/Kernel.php)
```php
// Jadwal task otomatis:
$schedule->command('tracer:send-reminders')->dailyAt('08:00');
$schedule->command('tracer:close-expired-periods')->dailyAt('00:00');
$schedule->command('tracer:generate-monthly-report')->monthlyOn(1, '07:00');
$schedule->command('queue:prune-failed')->daily();
$schedule->command('sanctum:prune-expired')->daily();
// Cleanup OTP kedaluwarsa
$schedule->command('tracer:cleanup-expired-otps')->hourly();
```

### 5.3 Queue Worker Configuration (Supervisor)
```ini
[program:sitras-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sitras-unisya/artisan queue:work redis --queue=high,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sitras/worker-default.log
stopwaitsecs=3600

[program:sitras-worker-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sitras-unisya/artisan queue:work redis --queue=low --sleep=5 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/sitras/worker-low.log
stopwaitsecs=3600
```

---

## 6. KONFIGURASI NGINX

> **Catatan:** Konfigurasi Content-Security-Policy (CSP) yang lengkap dan otoritatif ada di
> **07_SECURITY.md Section 9**. Nginx config di bawah me-reference CSP tersebut.

```nginx
# /etc/nginx/sites-available/sitras-unisya

# Rate limiting zones
limit_req_zone $binary_remote_addr zone=api:10m  rate=60r/m;
limit_req_zone $binary_remote_addr zone=auth:10m rate=10r/m;
limit_req_zone $binary_remote_addr zone=otp:10m  rate=5r/m;

server {
    listen 80;
    server_name tracer.unisya.ac.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tracer.unisya.ac.id;

    root /var/www/sitras-unisya/public;
    index index.php;

    # SSL
    ssl_certificate     /etc/letsencrypt/live/tracer.unisya.ac.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tracer.unisya.ac.id/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers on;
    ssl_session_cache   shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security headers (detail CSP lihat 07_SECURITY.md Section 9)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';" always;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1024;

    # OTP endpoint — rate limit ketat
    location /api/v1/auth/otp {
        limit_req zone=otp burst=3 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Auth endpoints — rate limit sedang
    location /api/v1/auth {
        limit_req zone=auth burst=5 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # API umum
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static assets dari Vue build (cache 1 tahun, immutable)
    location /assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # SPA fallback (semua route non-file dikembalikan ke index.php)
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }

    # Blokir akses file sensitif
    location ~ /\.(env|git|htaccess|gitignore) {
        deny all;
        return 404;
    }

    location ~ ^/(storage/logs|bootstrap/cache|vendor|node_modules) {
        deny all;
        return 404;
    }

    # Nonaktifkan directory listing
    autoindex off;
}
```

---

## 7. KONFIGURASI PHP-FPM

```ini
; /etc/php/8.3/fpm/pool.d/sitras.conf
[sitras]
user  = www-data
group = www-data
listen = /var/run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children      = 20
pm.start_servers     = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 8
pm.max_requests      = 500

php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size]       = 12M
php_admin_value[memory_limit]        = 256M
php_admin_value[max_execution_time]  = 60
php_admin_value[expose_php]          = Off
php_admin_flag[display_errors]       = off
php_admin_value[error_log]           = /var/log/php8.3-fpm-sitras.log
```

---

## 8. ENVIRONMENT CONFIGURATION (.env.example)

```dotenv
APP_NAME="Sistem Tracer Study UNISYA"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tracer.unisya.ac.id
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sitras_unisya
DB_USERNAME=sitras_user
DB_PASSWORD=

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tracerstudy@unisya.ac.id
MAIL_FROM_NAME="Tracer Study UNISYA"

WHATSAPP_GATEWAY_URL=https://wacenter.unisya.ac.id/send-message
WHATSAPP_API_KEY=
WHATSAPP_SENDER=

SANCTUM_STATEFUL_DOMAINS=tracer.unisya.ac.id
SESSION_DOMAIN=.unisya.ac.id
FRONTEND_URL=https://tracer.unisya.ac.id

# Konfigurasi OTP (sesuai 07_SECURITY.md)
OTP_EXPIRY_MINUTES=5
OTP_MAX_ATTEMPTS=3
OTP_RESEND_COOLDOWN_SECONDS=60

# Konfigurasi Login Lockout (sesuai 07_SECURITY.md)
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15

# Konfigurasi File Upload
FILESYSTEM_DISK=local
MAX_UPLOAD_SIZE_KB=10240

# Konfigurasi Employer Token
EMPLOYER_TOKEN_EXPIRY_DAYS=30

TELESCOPE_ENABLED=false
```

---

## 9. STRATEGI CACHING

| Data | Driver | TTL | Keterangan |
|---|---|---|---|
| Session user | Redis | 2 jam | Session aktif (reset on activity) |
| Dashboard statistik | Redis | 30 menit | Data agregat berat |
| Konfigurasi sistem (system_settings) | Redis | 60 menit | Data jarang berubah |
| Master data (prodi, fakultas, angkatan) | Redis | 24 jam | Data sangat jarang berubah |
| Laporan (PDF/Excel) | File | 1 jam | Hasil generate (invalidate on new data) |
| OTP | Redis | 5 menit | Auto-expire via TTL |
| Rate limit counters | Redis | Per window | Auto-expire via Laravel RateLimiter |

---

## 10. API VERSIONING

- Semua endpoint API diawali dengan `/api/v1/`
- Versi baru (`v2`) dibuat di direktori terpisah tanpa menghapus `v1`
- Header `X-API-Version: 1.0` disertakan di setiap respons
- Endpoint publik tidak memerlukan autentikasi (prefix `/api/v1/public/`)

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Align CSP header dengan 07_SECURITY.md; tambah GraduationYearSeeder & GraduationYearController di struktur; tambah `Public/PublicController.php`; pisah queue worker high/low; tambah OTP cleanup command; perjelas storage path (`private/`); tambah EMPLOYER_TOKEN_EXPIRY_DAYS di .env |
| 1.0.2 | 2026-06-08 | Update .env: WHATSAPP_API_TOKEN → WHATSAPP_API_KEY; update WHATSAPP_GATEWAY_URL ke endpoint gateway UNISYA (wacenter.unisya.ac.id); update komentar config/whatsapp.php |
| 1.0.3 | 2026-06-09 | Fix diagram arsitektur: label WA Gateway `(Fonnte/Wablas)` → `(wacenter.unisya.ac.id)` yang terlewat dari audit v1.0.2 (INC-02); lengkapi folder structure pages frontend — tambah semua nama file .vue yang terdefinisi di Phase Tracker & UI/UX spec (INC-07) |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
