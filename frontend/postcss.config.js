/**
 * postcss.config.js — PostCSS configuration
 *
 * CATATAN: File ini tidak aktif digunakan saat npm run dev dari root project.
 * PostCSS dikonfigurasi langsung di vite.config.js (root) via css.postcss inline.
 * File ini hanya dipertahankan sebagai referensi jika dijalankan standalone
 * dari dalam folder frontend/ (cd frontend && npm run dev).
 */
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
