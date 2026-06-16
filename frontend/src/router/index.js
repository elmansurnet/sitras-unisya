/**
 * router/index.js — Vue Router 4
 * Sesuai 04_ARCHITECTURE.md (struktur direktori) + 06_UI_UX.md §8 (routing URL)
 *
 * Route Groups:
 *  - /login/*          → AuthLayout   (guest only)
 *  - /admin/*          → AdminLayout  (role: superadmin, admin)
 *  - /alumni/*         → AlumniLayout (role: alumni)
 *  - /employer/*       → EmployerLayout (token-based)
 *  - /                 → redirect ke dashboard berdasarkan role
 *  - /unauthorized     → halaman 403
 *  - /:pathMatch(.*)* → halaman 404
 */

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// ---------------------------------------------------------------------------
// Lazy-loaded pages (code splitting per route)
// ---------------------------------------------------------------------------

// Auth — sesuai 04_ARCHITECTURE.md: pages/auth/
const LoginPage          = () => import('@/pages/auth/LoginPage.vue')
const OtpRequestPage     = () => import('@/pages/auth/OtpRequestPage.vue')
const OtpVerifyPage      = () => import('@/pages/auth/OtpVerifyPage.vue')
const EmployerAccessPage = () => import('@/pages/auth/EmployerAccessPage.vue')

// Admin — sesuai 04_ARCHITECTURE.md: pages/admin/
const AdminDashboardPage         = () => import('@/pages/admin/DashboardPage.vue')
const AdminStatisticsPage        = () => import('@/pages/admin/dashboard/StatisticsPage.vue')

// Admin — Alumni: pages/admin/alumni/
const AlumniIndexPage            = () => import('@/pages/admin/alumni/AlumniIndexPage.vue')
const AlumniDetailPage           = () => import('@/pages/admin/alumni/AlumniDetailPage.vue')
const AlumniFormPage             = () => import('@/pages/admin/alumni/AlumniFormPage.vue')
const AlumniImportPage           = () => import('@/pages/admin/alumni/AlumniImportPage.vue')

// Admin — Employers: pages/admin/employers/
const EmployerIndexPage          = () => import('@/pages/admin/employers/EmployerIndexPage.vue')
const EmployerDetailPage         = () => import('@/pages/admin/employers/EmployerDetailPage.vue')
const EmployerFormPage           = () => import('@/pages/admin/employers/EmployerFormPage.vue')

// Admin — Questionnaires: pages/admin/questionnaires/
const QuestionnaireIndexPage     = () => import('@/pages/admin/questionnaires/QuestionnaireIndexPage.vue')
// Bug #6 fix: pisah antara halaman create (form dasar) dan builder (full editor)
const QuestionnaireCreatePage    = () => import('@/pages/admin/questionnaires/QuestionnaireCreatePage.vue')
const QuestionnaireBuilderPage   = () => import('@/pages/admin/questionnaires/QuestionnaireBuilderPage.vue')
const QuestionnairePreviewPage   = () => import('@/pages/admin/questionnaires/QuestionnairePreviewPage.vue')

// Admin — Survey Periods: pages/admin/survey-periods/
const SurveyPeriodIndexPage      = () => import('@/pages/admin/survey-periods/SurveyPeriodIndexPage.vue')
const SurveyPeriodDetailPage     = () => import('@/pages/admin/survey-periods/SurveyPeriodDetailPage.vue')

// Admin — Reports: pages/admin/reports/
const ReportPage                 = () => import('@/pages/admin/reports/ReportPage.vue')

// Admin — Notifications: pages/admin/notifications/
const NotificationTemplatePage   = () => import('@/pages/admin/notifications/NotificationTemplatePage.vue')
const NotificationLogPage        = () => import('@/pages/admin/notifications/NotificationLogPage.vue')

// Admin — Settings: pages/admin/settings/
const SystemSettingPage          = () => import('@/pages/admin/settings/SystemSettingPage.vue')
const FacultyPage                = () => import('@/pages/admin/settings/FacultyPage.vue')
const StudyProgramPage           = () => import('@/pages/admin/settings/StudyProgramPage.vue')
const GraduationYearPage         = () => import('@/pages/admin/settings/GraduationYearPage.vue')
const IndustrySectorPage         = () => import('@/pages/admin/settings/IndustrySectorPage.vue')
const SalaryRangePage            = () => import('@/pages/admin/settings/SalaryRangePage.vue')
const UserManagementPage         = () => import('@/pages/admin/settings/UserManagementPage.vue')
const AuditLogPage               = () => import('@/pages/admin/settings/AuditLogPage.vue')

// Alumni — sesuai 04_ARCHITECTURE.md: pages/alumni/
const AlumniDashboardPage        = () => import('@/pages/alumni/DashboardPage.vue')
const AlumniProfilePage          = () => import('@/pages/alumni/ProfilePage.vue')
const AlumniProfileEditPage      = () => import('@/pages/alumni/ProfileEditPage.vue')
const AlumniWorkHistoryPage      = () => import('@/pages/alumni/WorkHistoryPage.vue')
const AlumniSurveyPage           = () => import('@/pages/alumni/SurveyPage.vue')
const AlumniSurveyDonePage       = () => import('@/pages/alumni/SurveyDonePage.vue')

// Employer — sesuai 04_ARCHITECTURE.md: pages/employer/
const EmployerSurveyPage         = () => import('@/pages/employer/SurveyPage.vue')
const EmployerDonePage           = () => import('@/pages/employer/DonePage.vue')

// Error pages
const UnauthorizedPage           = () => import('@/pages/errors/UnauthorizedPage.vue')
const NotFoundPage               = () => import('@/pages/errors/NotFoundPage.vue')

// ---------------------------------------------------------------------------
// Layouts
// ---------------------------------------------------------------------------
import AuthLayout     from '@/layouts/AuthLayout.vue'
import AdminLayout    from '@/layouts/AdminLayout.vue'
import AlumniLayout   from '@/layouts/AlumniLayout.vue'
import EmployerLayout from '@/layouts/EmployerLayout.vue'

// ---------------------------------------------------------------------------
// Routes — URL sesuai 06_UI_UX.md §8
// ---------------------------------------------------------------------------
const routes = [
  // Root redirect berdasarkan role
  {
    path: '/',
    name: 'home',
    redirect: () => ({ name: 'login' }),
  },

  // ── AUTH ──────────────────────────────────────────────────────────────────
  {
    path: '/login',
    component: AuthLayout,
    meta: { requiresGuest: true },
    children: [
      {
        path: '',
        name: 'login',
        component: LoginPage,
        meta: { title: 'Login Admin', requiresGuest: true },
      },
      {
        path: 'otp',
        name: 'auth.otp.request',
        component: OtpRequestPage,
        meta: { title: 'Login Alumni — Kirim OTP', requiresGuest: true },
      },
      {
        path: 'otp/verify',
        name: 'auth.otp.verify',
        component: OtpVerifyPage,
        meta: { title: 'Login Alumni — Verifikasi OTP', requiresGuest: true },
      },
    ],
  },

  // Employer token — akses form survei langsung via URL token
  {
    path: '/login/employer/:token',
    name: 'employer.access',
    component: EmployerAccessPage,
    meta: { title: 'Akses Survei Employer' },
  },

  // ── ADMIN ─────────────────────────────────────────────────────────────────
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, roles: ['superadmin', 'admin'] },
    children: [
      { path: '', redirect: { name: 'admin.dashboard' } },

      // Dashboard
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: AdminDashboardPage,
        meta: {
          title: 'Dashboard',
          breadcrumbs: [{ label: 'Dashboard' }],
        },
      },
      {
        path: 'dashboard/stats',
        name: 'admin.dashboard.stats',
        component: AdminStatisticsPage,
        meta: {
          title: 'Statistik Ketenagakerjaan',
          breadcrumbs: [
            { label: 'Dashboard', to: { name: 'admin.dashboard' } },
            { label: 'Statistik' },
          ],
        },
      },

      // ── Alumni ──
      {
        path: 'alumni',
        name: 'admin.alumni.index',
        component: AlumniIndexPage,
        meta: {
          title: 'Daftar Alumni',
          breadcrumbs: [{ label: 'Alumni' }],
        },
      },
      {
        path: 'alumni/import',
        name: 'admin.alumni.import',
        component: AlumniImportPage,
        meta: {
          title: 'Import Alumni',
          breadcrumbs: [
            { label: 'Alumni', to: { name: 'admin.alumni.index' } },
            { label: 'Import' },
          ],
        },
      },
      {
        path: 'alumni/create',
        name: 'admin.alumni.create',
        component: AlumniFormPage,
        meta: {
          title: 'Tambah Alumni',
          breadcrumbs: [
            { label: 'Alumni', to: { name: 'admin.alumni.index' } },
            { label: 'Tambah' },
          ],
        },
      },
      {
        path: 'alumni/:id',
        name: 'admin.alumni.detail',
        component: AlumniDetailPage,
        meta: {
          title: 'Detail Alumni',
          breadcrumbs: [
            { label: 'Alumni', to: { name: 'admin.alumni.index' } },
            { label: 'Detail' },
          ],
        },
      },
      {
        path: 'alumni/:id/edit',
        name: 'admin.alumni.edit',
        component: AlumniFormPage,
        meta: {
          title: 'Edit Alumni',
          breadcrumbs: [
            { label: 'Alumni', to: { name: 'admin.alumni.index' } },
            { label: 'Edit' },
          ],
        },
      },

      // ── Employers ──
      {
        path: 'employers',
        name: 'admin.employer.index',
        component: EmployerIndexPage,
        meta: {
          title: 'Daftar Employer',
          breadcrumbs: [{ label: 'Employer' }],
        },
      },
      {
        path: 'employers/create',
        name: 'admin.employer.create',
        component: EmployerFormPage,
        meta: {
          title: 'Tambah Employer',
          breadcrumbs: [
            { label: 'Employer', to: { name: 'admin.employer.index' } },
            { label: 'Tambah' },
          ],
        },
      },
      {
        path: 'employers/:id',
        name: 'admin.employer.detail',
        component: EmployerDetailPage,
        meta: {
          title: 'Detail Employer',
          breadcrumbs: [
            { label: 'Employer', to: { name: 'admin.employer.index' } },
            { label: 'Detail' },
          ],
        },
      },
      {
        path: 'employers/:id/edit',
        name: 'admin.employer.edit',
        component: EmployerFormPage,
        meta: {
          title: 'Edit Employer',
          breadcrumbs: [
            { label: 'Employer', to: { name: 'admin.employer.index' } },
            { label: 'Edit' },
          ],
        },
      },

      // ── Questionnaires ──
      {
        path: 'questionnaires',
        name: 'admin.questionnaires.index',
        component: QuestionnaireIndexPage,
        meta: {
          title: 'Kuesioner',
          breadcrumbs: [{ label: 'Kuesioner' }],
        },
      },
      // Bug #6 fix: /create → QuestionnaireCreatePage (bukan langsung builder)
      {
        path: 'questionnaires/create',
        name: 'admin.questionnaires.create',
        component: QuestionnaireCreatePage,
        meta: {
          title: 'Buat Kuesioner',
          breadcrumbs: [
            { label: 'Kuesioner', to: { name: 'admin.questionnaires.index' } },
            { label: 'Buat' },
          ],
        },
      },
      // /builder/:id → full builder (selalu butuh ID yang valid)
      {
        path: 'questionnaires/:id/builder',
        name: 'admin.questionnaires.builder',
        component: QuestionnaireBuilderPage,
        meta: {
          title: 'Builder Kuesioner',
          breadcrumbs: [
            { label: 'Kuesioner', to: { name: 'admin.questionnaires.index' } },
            { label: 'Builder' },
          ],
        },
      },
      {
        path: 'questionnaires/:id/preview',
        name: 'admin.questionnaires.preview',
        component: QuestionnairePreviewPage,
        meta: {
          title: 'Preview Kuesioner',
          breadcrumbs: [
            { label: 'Kuesioner', to: { name: 'admin.questionnaires.index' } },
            { label: 'Preview' },
          ],
        },
      },

      // ── Survey Periods ──
      {
        path: 'survey-periods',
        name: 'admin.survey-periods.index',
        component: SurveyPeriodIndexPage,
        meta: {
          title: 'Periode Survei',
          breadcrumbs: [{ label: 'Periode Survei' }],
        },
      },
      {
        path: 'survey-periods/create',
        name: 'admin.survey-periods.create',
        component: SurveyPeriodDetailPage,
        meta: {
          title: 'Buat Periode Survei',
          breadcrumbs: [
            { label: 'Periode Survei', to: { name: 'admin.survey-periods.index' } },
            { label: 'Buat' },
          ],
        },
      },
      {
        path: 'survey-periods/:id',
        name: 'admin.survey-periods.detail',
        component: SurveyPeriodDetailPage,
        meta: {
          title: 'Detail Periode Survei',
          breadcrumbs: [
            { label: 'Periode Survei', to: { name: 'admin.survey-periods.index' } },
            { label: 'Detail' },
          ],
        },
      },

      // ── Reports ──
      {
        path: 'reports',
        name: 'admin.reports',
        component: ReportPage,
        meta: {
          title: 'Laporan',
          breadcrumbs: [{ label: 'Laporan' }],
        },
      },

      // ── Notifications ──
      {
        path: 'notifications/templates',
        name: 'admin.notifications.templates',
        component: NotificationTemplatePage,
        meta: {
          title: 'Template Notifikasi',
          breadcrumbs: [{ label: 'Notifikasi' }, { label: 'Template' }],
        },
      },
      {
        path: 'notifications/logs',
        name: 'admin.notifications.logs',
        component: NotificationLogPage,
        meta: {
          title: 'Log Notifikasi',
          breadcrumbs: [{ label: 'Notifikasi' }, { label: 'Log' }],
        },
      },

      // ── Settings (superadmin only) ──
      {
        path: 'settings',
        name: 'admin.settings',
        component: SystemSettingPage,
        meta: {
          title: 'Pengaturan Sistem',
          roles: ['superadmin'],
          breadcrumbs: [{ label: 'Pengaturan' }],
        },
      },
      {
        path: 'settings/faculties',
        name: 'admin.settings.faculties',
        component: FacultyPage,
        meta: {
          title: 'Kelola Fakultas',
          roles: ['superadmin'],
          breadcrumbs: [
            { label: 'Pengaturan', to: { name: 'admin.settings' } },
            { label: 'Fakultas' },
          ],
        },
      },
      {
        path: 'settings/study-programs',
        name: 'admin.settings.study-programs',
        component: StudyProgramPage,
        meta: {
          title: 'Kelola Program Studi',
          roles: ['superadmin'],
          breadcrumbs: [
            { label: 'Pengaturan', to: { name: 'admin.settings' } },
            { label: 'Program Studi' },
          ],
        },
      },
      {
        path: 'settings/graduation-years',
        name: 'admin.settings.graduation-years',
        component: GraduationYearPage,
        meta: {
          title: 'Kelola Tahun Lulus',
          roles: ['superadmin'],
          breadcrumbs: [
            { label: 'Pengaturan', to: { name: 'admin.settings' } },
            { label: 'Tahun Lulus' },
          ],
        },
      },
      {
        path: 'settings/industry-sectors',
        name: 'admin.settings.industry-sectors',
        component: IndustrySectorPage,
        meta: {
          title: 'Kelola Sektor Industri',
          roles: ['superadmin'],
          breadcrumbs: [
            { label: 'Pengaturan', to: { name: 'admin.settings' } },
            { label: 'Sektor Industri' },
          ],
        },
      },
      {
        path: 'settings/salary-ranges',
        name: 'admin.settings.salary-ranges',
        component: SalaryRangePage,
        meta: {
          title: 'Kelola Rentang Gaji',
          roles: ['superadmin'],
          breadcrumbs: [
            { label: 'Pengaturan', to: { name: 'admin.settings' } },
            { label: 'Rentang Gaji' },
          ],
        },
      },
      {
        path: 'users',
        name: 'admin.users',
        component: UserManagementPage,
        meta: {
          title: 'Manajemen Admin',
          roles: ['superadmin'],
          breadcrumbs: [{ label: 'Manajemen Admin' }],
        },
      },
      {
        path: 'audit-logs',
        name: 'admin.audit-logs',
        component: AuditLogPage,
        meta: {
          title: 'Audit Log',
          roles: ['superadmin'],
          breadcrumbs: [{ label: 'Audit Log' }],
        },
      },
    ],
  },

  // ── ALUMNI ────────────────────────────────────────────────────────────────
  {
    path: '/alumni',
    component: AlumniLayout,
    meta: { requiresAuth: true, roles: ['alumni'] },
    children: [
      { path: '', redirect: { name: 'alumni.dashboard' } },
      {
        path: 'dashboard',
        name: 'alumni.dashboard',
        component: AlumniDashboardPage,
        meta: { title: 'Beranda Alumni' },
      },
      {
        path: 'profile',
        name: 'alumni.profile',
        component: AlumniProfilePage,
        meta: { title: 'Profil Saya' },
      },
      {
        path: 'profile/edit',
        name: 'alumni.profile.edit',
        component: AlumniProfileEditPage,
        meta: { title: 'Edit Profil' },
      },
      {
        path: 'work-histories',
        name: 'alumni.work-histories',
        component: AlumniWorkHistoryPage,
        meta: { title: 'Riwayat Pekerjaan' },
      },
      {
        path: 'survey',
        name: 'alumni.survey',
        component: AlumniSurveyPage,
        meta: { title: 'Pengisian Survei' },
      },
      {
        path: 'survey/done',
        name: 'alumni.survey.done',
        component: AlumniSurveyDonePage,
        meta: { title: 'Survei Selesai' },
      },
    ],
  },

  // ── EMPLOYER ──────────────────────────────────────────────────────────────
  {
    path: '/employer',
    component: EmployerLayout,
    children: [
      { path: '', redirect: { name: 'employer.survey' } },
      {
        path: 'survey',
        name: 'employer.survey',
        component: EmployerSurveyPage,
        meta: { title: 'Survei Employer' },
      },
      {
        path: 'done',
        name: 'employer.done',
        component: EmployerDonePage,
        meta: { title: 'Survei Selesai' },
      },
    ],
  },

  // ── ERROR PAGES ───────────────────────────────────────────────────────────
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: UnauthorizedPage,
    meta: { title: 'Akses Ditolak' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundPage,
    meta: { title: 'Halaman Tidak Ditemukan' },
  },
]

// ---------------------------------------------------------------------------
// Router instance
// ---------------------------------------------------------------------------
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash)       return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
})

// ---------------------------------------------------------------------------
// Navigation Guards
// ---------------------------------------------------------------------------
router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  const isAuthenticated = authStore.isAuthenticated
  const userRole        = authStore.userRole

  // Update page title
  document.title = to.meta?.title
    ? `${to.meta.title} — SITRAS UNISYA`
    : 'SITRAS UNISYA'

  // Route membutuhkan guest (halaman auth)
  if (to.meta?.requiresGuest && isAuthenticated) {
    return _dashboardByRole(userRole)
  }

  // Route membutuhkan autentikasi
  if (to.meta?.requiresAuth) {
    if (!isAuthenticated) {
      return { name: 'login' }
    }
    const allowedRoles = to.meta?.roles
    if (allowedRoles && allowedRoles.length > 0 && !allowedRoles.includes(userRole)) {
      return { name: 'unauthorized' }
    }
  }

  // Root '/' redirect ke dashboard sesuai role
  if (to.name === 'home') {
    if (!isAuthenticated) return { name: 'login' }
    return _dashboardByRole(userRole)
  }

  return true
})

function _dashboardByRole(role) {
  switch (role) {
    case 'superadmin':
    case 'admin':
      return { name: 'admin.dashboard' }
    case 'alumni':
      return { name: 'alumni.dashboard' }
    case 'employer':
      return { name: 'employer.survey' }
    default:
      return { name: 'login' }
  }
}

export default router
