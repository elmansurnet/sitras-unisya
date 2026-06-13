/**
 * useConfirm.js
 * Composable konfirmasi dialog ringan untuk SITRAS UNISYA.
 *
 * Dua mode penggunaan:
 *
 * 1. String sederhana:
 *    const ok = await confirm('Yakin ingin menghapus data ini?')
 *
 * 2. Object options (dipakai NotificationTemplatePage):
 *    const ok = await confirm({
 *      title          : 'Hapus Template',
 *      message        : 'Tindakan ini tidak dapat dibatalkan.',
 *      confirmText    : 'Ya, Hapus',
 *      confirmVariant : 'danger',   // 'danger' | 'primary'
 *    })
 *
 * Implementasi memakai window.confirm sebagai fallback ringan
 * agar tidak membutuhkan komponen modal tambahan.
 * ConfirmModal.vue (jika ada) dapat meng-override dengan
 * provide/inject di masa mendatang.
 */

import { inject, ref } from 'vue'

// ─── Symbol untuk provide/inject (opsional, future use) ──────────────────────
export const CONFIRM_KEY = Symbol('useConfirm')

// ─── State global singleton ───────────────────────────────────────────────────
const _pending  = ref(null) // { resolve, title, message, confirmText, confirmVariant }

/**
 * Dipakai oleh ConfirmModal.vue untuk me-resolve dialog.
 */
export function useConfirmState() {
  return { _pending }
}

/**
 * Composable utama yang dipanggil di setiap komponen.
 */
export function useConfirm() {
  /**
   * Tampilkan dialog konfirmasi.
   * @param {string|Object} options - pesan string atau object { title, message, confirmText, confirmVariant }
   * @returns {Promise<boolean>}
   */
  async function confirm(options) {
    // Normalise input
    const isString = typeof options === 'string'
    const title         = isString ? 'Konfirmasi'  : (options.title         ?? 'Konfirmasi')
    const message       = isString ? options        : (options.message       ?? 'Apakah Anda yakin?')
    const confirmText   = isString ? 'Ya'           : (options.confirmText   ?? 'Ya')
    const cancelText    = isString ? 'Batal'        : (options.cancelText    ?? 'Batal')
    const confirmVariant = isString ? 'primary'     : (options.confirmVariant ?? 'primary')

    // Coba inject ConfirmModal jika tersedia (future enhancement)
    const injected = inject(CONFIRM_KEY, null)
    if (injected) {
      return injected({ title, message, confirmText, cancelText, confirmVariant })
    }

    // Fallback: native browser confirm
    // Memformat pesan agar title ikut muncul
    const fullMessage = title !== 'Konfirmasi'
      ? `${title}\n\n${message}`
      : message

    return Promise.resolve(window.confirm(fullMessage))
  }

  return { confirm }
}

export default useConfirm
