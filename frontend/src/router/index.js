/**
 * router/index.js — Vue Router 4
 * Sesuai 06_UI_UX.md §8 (Route Definitions)
 *
 * Route Groups:
 *  - /auth/*          → AuthLayout   (guest only)
 *  - /admin/*         → AdminLayout  (role: superadmin, admin)
 *  - /alumni/*        → AlumniLayout (role: alumni)
 *  - /employer/*      → EmployerLayout (role: employer, token-based)
 *  - /                → redirect ke dashboard berdasarkan role
 *  - /unauthorized    → halaman 403
 *  - /:pathMatch(.*)* → halaman 404
 */

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// ---------------------------------------------------------------------------
// Lazy-loaded pages (code splitting per route)
// ---------------------------------------------------------------------------

// Auth
const LoginPage          = () => import('@/pages/auth/LoginPage.vue')
const OtpRequestPage     = () => import('@/pages/auth/OtpRequestPage.vue')
const OtpVerifyPage      = () => import('@/pages/auth/OtpVerifyPage.vue')
const EmployerAccessPage = () => import('@/pages/auth/EmployerAccessPage.vue')

// Admin
const AdminDashboard         = () => import('@/pages/admin/DashboardPage.vue')
const AdminAlumniIndex       = () => import('@/pages/admin/alumni/IndexPage.vue')
const AdminAlumniDetail      = () => import('@/pages/admin/alumni/DetailPage.vue')
const AdminAlumniImport      = () => import('@/pages/admin/alumni/ImportPage.vue')
const AdminAlumniForm        = () => import('@/pages/admin/alumni/FormPage.vue')
const AdminEmployerIndex     = () => import('@/pages/admin/employer/IndexPage.vue')
const AdminEmployerDetail    = () => import('@/pages/admin/employer/DetailPage.vue')
const AdminSurveyPeriods     = () => import('@/pages/admin/survey/PeriodsPage.vue')
const AdminQuestionnaires    = () => import('@/pages/admin/survey/QuestionnairesPage.vue')
const AdminQuestionnaireEdit = () => import('@/pages/admin/survey/QuestionnaireEditPage.vue')
const AdminInvitations       = () => import('@/pages/admin/survey/InvitationsPage.vue')
const AdminReports           = () => import('@/pages/admin/ReportsPage.vue')
const AdminNotifications     = () => import('@/pages/admin/NotificationsPage.vue')
const AdminUsers             = () => import('@/pages/admin/system/UsersPage.vue')
const AdminSettings          = () => import('@/pages/admin/system/SettingsPage.vue')
const AdminAuditLog          = () => import('@/pages/admin/system/AuditLogPage.vue')

// Alumni
const AlumniHome       = () => import('@/pages/alumni/HomePage.vue')
const AlumniProfile    = () => import('@/pages/alumni/ProfilePage.vue')
const AlumniEmployment = () => import('@/pages/alumni/EmploymentPage.vue')
const AlumniSurvey     = () => import('@/pages/alumni/SurveyPage.vue')
const AlumniSurveyFill = () => import('@/pages/alumni/SurveyFillPage.vue')

// Employer
const EmployerSurveyFill = () => import('@/pages/employer/SurveyFillPage.vue')
const EmployerSurveyDone = () => import('@/pages/employer/SurveyDonePage.vue')

// Misc
const UnauthorizedPage = () => import('@/pages/errors/UnauthorizedPage.vue')
const NotFoundPage     = () => import('@/pages/errors/NotFoundPage.vue')

// ---------------------------------------------------------------------------
// Layouts
// ---------------------------------------------------------------------------
import AuthLayout     from '@/layouts/AuthLayout.vue'
import AdminLayout    from '@/layouts/AdminLayout.vue'
import AlumniLayout   from '@/layouts/AlumniLayout.vue'
import EmployerLayout from '@/layouts/EmployerLayout.vue'

// ---------------------------------------------------------------------------
// Routes
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
    path: '/auth',
    component: AuthLayout,
    meta: { requiresGuest: true },
    children: [
      {
        path: 'login',
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
      { path: '', redirect: { name: 'login' } },
    ],
  },

  // Employer token (tidak pakai AuthLayout — langsung akses form)
  {
    path: '/survey/:token',
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
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        component: AdminDashboard,
        meta: {
          title: 'Dashboard',
          breadcrumbs: [{ label: 'Dashboard' }],
        },
      },

      // Alumni
      {
        path: 'alumni',
        name: 'admin.alumni.index',
        component: AdminAlumniIndex,
        meta: {
          title: 'Daftar Alumni',
          breadcrumbs: [{ label: 'Alumni' }],
        },
      },
      {
        path: 'alumni/import',
        name: 'admin.alumni.import',
        component: AdminAlumniImport,
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
        component: AdminAlumniForm,
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
        component: AdminAlumniDetail,
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
        component: AdminAlumniForm,
        meta: {
          title: 'Edit Alumni',
          breadcrumbs: [
            { label: 'Alumni', to: { name: 'admin.alumni.index' } },
            { label: 'Edit' },
          ],
        },
      },

      // Employer
      {
        path: 'employer',
        name: 'admin.employer.index',
        component: AdminEmployerIndex,
        meta: {
          title: 'Daftar Employer',
          breadcrumbs: [{ label: 'Employer' }],
        },
      },
      {
        path: 'employer/:id',
        name: 'admin.employer.detail',
        component: AdminEmployerDetail,
        meta: {
          title: 'Detail Employer',
          breadcrumbs: [
            { label: 'Employer', to: { name: 'admin.employer.index' } },
            { label: 'Detail' },
          ],
        },
      },

      // Survei
      {
        path: 'survey/periods',
        name: 'admin.survey.periods',
        component: AdminSurveyPeriods,
        meta: {
          title: 'Periode Survei',
          breadcrumbs: [{ label: 'Survei' }, { label: 'Periode' }],
        },
      },
      {
        path: 'survey/questionnaires',
        name: 'admin.survey.questionnaires',
        component: AdminQuestionnaires,
        meta: {
          title: 'Kuesioner',
          breadcrumbs: [{ label: 'Survei' }, { label: 'Kuesioner' }],
        },
      },
      {
        path: 'survey/questionnaires/:id/edit',
        name: 'admin.survey.questionnaire.edit',
        component: AdminQuestionnaireEdit,
        meta: {
          title: 'Edit Kuesioner',
          breadcrumbs: [
            { label: 'Survei' },
            { label: 'Kuesioner', to: { name: 'admin.survey.questionnaires' } },
            { label: 'Edit' },
          ],
        },
      },
      {
        path: 'survey/invitations',
        name: 'admin.survey.invitations',
        component: AdminInvitations,
        meta: {
          title: 'Undangan Massal',
          breadcrumbs: [{ label: 'Survei' }, { label: 'Undangan' }],
        },
      },

      // Laporan
      {
        path: 'reports',
        name: 'admin.reports',
        component: AdminReports,
        meta: {
          title: 'Laporan',
          breadcrumbs: [{ label: 'Laporan' }],
        },
      },

      // Notifikasi
      {
        path: 'notifications',
        name: 'admin.notifications',
        component: AdminNotifications,
        meta: {
          title: 'Notifikasi',
          breadcrumbs: [{ label: 'Notifikasi' }],
        },
      },

      // Sistem (superadmin only)
      {
        path: 'users',
        name: 'admin.users',
        component: AdminUsers,
        meta: {
          title: 'Kelola Admin',
          roles: ['superadmin'],
          breadcrumbs: [{ label: 'Sistem' }, { label: 'Admin' }],
        },
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: AdminSettings,
        meta: {
          title: 'Pengaturan',
          roles: ['superadmin'],
          breadcrumbs: [{ label: 'Sistem' }, { label: 'Pengaturan' }],
        },
      },
      {
        path: 'audit',
        name: 'admin.audit',
        component: AdminAuditLog,
        meta: {
          title: 'Audit Log',
          roles: ['superadmin'],
          breadcrumbs: [{ label: 'Sistem' }, { label: 'Audit Log' }],
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
      { path: '', redirect: { name: 'alumni.home' } },
      {
        path: 'home',
        name: 'alumni.home',
        component: AlumniHome,
        meta: { title: 'Beranda Alumni' },
      },
      {
        path: 'profile',
        name: 'alumni.profile',
        component: AlumniProfile,
        meta: { title: 'Profil Saya' },
      },
      {
        path: 'employment',
        name: 'alumni.employment',
        component: AlumniEmployment,
        meta: { title: 'Riwayat Pekerjaan' },
      },
      {
        path: 'survey',
        name: 'alumni.survey',
        component: AlumniSurvey,
        meta: { title: 'Survei Tersedia' },
      },
      {
        path: 'survey/:id/fill',
        name: 'alumni.survey.fill',
        component: AlumniSurveyFill,
        meta: { title: 'Isi Survei' },
      },
    ],
  },

  // ── EMPLOYER ──────────────────────────────────────────────────────────────
  {
    path: '/employer',
    component: EmployerLayout,
    meta: { requiresAuth: true, roles: ['employer'] },
    children: [
      { path: '', redirect: { name: 'employer.survey.fill' } },
      {
        path: 'survey/fill',
        name: 'employer.survey.fill',
        component: EmployerSurveyFill,
        meta: { title: 'Isi Survei Employer' },
      },
      {
        path: 'survey/done',
        name: 'employer.survey.done',
        component: EmployerSurveyDone,
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
      return { name: 'alumni.home' }
    case 'employer':
      return { name: 'employer.survey.fill' }
    default:
      return { name: 'login' }
  }
}

export default router
