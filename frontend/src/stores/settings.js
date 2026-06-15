import { defineStore } from 'pinia'
import api from '@/services/api'

/**
 * Store untuk modul Sistem:
 * - System Settings (GET /admin/settings, PUT /admin/settings)
 * - Users admin (GET/POST/PUT/DELETE /admin/users + toggle-active)
 * - Audit Logs (GET /admin/audit-logs)
 */
export const useSettingsStore = defineStore('settings', {
  state: () => ({
    // ─── System Settings ─────────────────────────────────────────────────────
    /** @type {Record<string, Array<{key:string,value:string,label:string,is_encrypted:number}>>} */
    settingGroups: {},
    loadingSettings: false,
    savingSettings: false,

    // ─── Users ───────────────────────────────────────────────────────────────
    /** @type {Array<{id:number,name:string,email:string,role:string,is_active:boolean}>} */
    users: [],
    userPagination: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
    userFilters: { search: '', page: 1 },
    loadingUsers: false,
    savingUser: false,

    // ─── Audit Logs ──────────────────────────────────────────────────────────
    /** @type {Array} */
    auditLogs: [],
    auditPagination: { current_page: 1, last_page: 1, per_page: 50, total: 0 },
    auditFilters: {
      module: '',
      action: '',
      user_id: null,
      level: '',
      date_from: '',
      date_to: '',
      page: 1,
    },
    loadingAudit: false,

    error: null,
  }),

  getters: {
    isAnyLoading: (state) =>
      state.loadingSettings || state.loadingUsers || state.loadingAudit,
  },

  actions: {
    // ═══ SYSTEM SETTINGS ═════════════════════════════════════════════════════

    async fetchSettings() {
      this.loadingSettings = true
      this.error = null
      try {
        const { data } = await api.get('/admin/settings')
        this.settingGroups = data.data ?? {}
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat pengaturan'
      } finally {
        this.loadingSettings = false
      }
    },

    /**
     * Bulk update settings.
     * @param {Array<{key:string, value:string}>} settings
     */
    async updateSettings(settings) {
      this.savingSettings = true
      this.error = null
      try {
        const { data } = await api.put('/admin/settings', { settings })
        // Refresh setelah simpan
        await this.fetchSettings()
        return { success: true, message: data.message }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal menyimpan pengaturan'
        this.error = msg
        return { success: false, error: msg }
      } finally {
        this.savingSettings = false
      }
    },

    // ═══ USERS ═══════════════════════════════════════════════════════════════

    async fetchUsers() {
      this.loadingUsers = true
      this.error = null
      try {
        const params = {
          page: this.userFilters.page,
          ...(this.userFilters.search ? { search: this.userFilters.search } : {}),
        }
        const { data } = await api.get('/admin/users', { params })
        this.users = data.data ?? []
        if (data.meta) {
          this.userPagination = {
            current_page: data.meta.current_page,
            last_page: data.meta.last_page,
            per_page: data.meta.per_page,
            total: data.meta.total,
          }
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat pengguna'
      } finally {
        this.loadingUsers = false
      }
    },

    async createUser(payload) {
      this.savingUser = true
      this.error = null
      try {
        const { data } = await api.post('/admin/users', payload)
        await this.fetchUsers()
        return { success: true, data: data.data }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal membuat pengguna'
        this.error = msg
        return { success: false, error: msg, errors: err.response?.data?.errors }
      } finally {
        this.savingUser = false
      }
    },

    async updateUser(id, payload) {
      this.savingUser = true
      this.error = null
      try {
        const { data } = await api.put(`/admin/users/${id}`, payload)
        await this.fetchUsers()
        return { success: true, data: data.data }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal memperbarui pengguna'
        this.error = msg
        return { success: false, error: msg, errors: err.response?.data?.errors }
      } finally {
        this.savingUser = false
      }
    },

    async deleteUser(id) {
      this.error = null
      try {
        await api.delete(`/admin/users/${id}`)
        await this.fetchUsers()
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal menghapus pengguna'
        this.error = msg
        return { success: false, error: msg }
      }
    },

    async toggleUserActive(id) {
      this.error = null
      try {
        const { data } = await api.post(`/admin/users/${id}/toggle-active`)
        // Update lokal tanpa re-fetch
        const idx = this.users.findIndex((u) => u.id === id)
        if (idx !== -1) this.users[idx].is_active = data.data.is_active
        return { success: true, data: data.data }
      } catch (err) {
        const msg = err.response?.data?.message ?? 'Gagal mengubah status pengguna'
        this.error = msg
        return { success: false, error: msg }
      }
    },

    setUserFilter(key, value) {
      this.userFilters[key] = value
      if (key !== 'page') this.userFilters.page = 1
    },

    // ═══ AUDIT LOGS ══════════════════════════════════════════════════════════

    async fetchAuditLogs() {
      this.loadingAudit = true
      this.error = null
      try {
        const params = Object.fromEntries(
          Object.entries(this.auditFilters).filter(([, v]) => v !== '' && v !== null),
        )
        const { data } = await api.get('/admin/audit-logs', { params })
        this.auditLogs = data.data ?? []
        if (data.meta) {
          this.auditPagination = {
            current_page: data.meta.current_page,
            last_page: data.meta.last_page,
            per_page: data.meta.per_page,
            total: data.meta.total,
          }
        }
      } catch (err) {
        this.error = err.response?.data?.message ?? 'Gagal memuat audit log'
      } finally {
        this.loadingAudit = false
      }
    },

    setAuditFilter(key, value) {
      this.auditFilters[key] = value
      if (key !== 'page') this.auditFilters.page = 1
    },

    resetAuditFilters() {
      Object.assign(this.auditFilters, {
        module: '', action: '', user_id: null,
        level: '', date_from: '', date_to: '', page: 1,
      })
    },
  },
})
