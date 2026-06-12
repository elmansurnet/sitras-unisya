<script setup>
/**
 * pages/alumni/SurveyDonePage.vue — Halaman Survei Selesai (Alumni)
 *
 * Ditampilkan setelah alumni berhasil submit survei.
 * Menampilkan animasi konfetti CSS, info submitted_at,
 * dan tombol kembali ke dashboard.
 *
 * Route  : alumni.survey.done  (06_UI_UX.md §8, §10.4)
 * Store  : useSurveyStore (ambil submitted_at & questionnaire title)
 * Layout : AlumniLayout
 */
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'

const router = useRouter()
const store  = useSurveyStore()

/** Tanggal & waktu submit — format lokal Indonesia */
const submittedAt = computed(() => {
  const raw = store.response?.submitted_at
  if (!raw) return null
  return new Date(raw).toLocaleString('id-ID', {
    weekday: 'long',
    day    : 'numeric',
    month  : 'long',
    year   : 'numeric',
    hour   : '2-digit',
    minute : '2-digit',
    timeZone: 'Asia/Makassar',
  }) + ' WITA'
})

const surveyTitle = computed(() => store.questionnaire?.title ?? 'Survei')

onMounted(() => {
  // Bersihkan store setelah render selesai agar tidak ada data tersisa
  // saat alumni kembali ke halaman lain
  // Tidak langsung reset agar animasi dan data masih bisa dibaca saat render pertama
})

function goToDashboard() {
  store.resetStore()
  router.push({ name: 'alumni.dashboard' })
}
</script>

<template>
  <div class="done-page" aria-live="polite">
    <!-- Konfetti animasi CSS -->
    <div class="confetti-wrap" aria-hidden="true">
      <span v-for="n in 18" :key="n" class="confetti-piece" :style="{
        '--x'    : `${Math.random() * 100}%`,
        '--delay': `${(n * 0.12).toFixed(2)}s`,
        '--dur'  : `${1.2 + (n % 5) * 0.3}s`,
        '--color': ['#0d9488','#f59e0b','#3b82f6','#ec4899','#22c55e','#a855f7'][n % 6],
        '--size' : `${8 + (n % 4) * 3}px`,
      }"></span>
    </div>

    <!-- Ikon sukses -->
    <div class="success-icon" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>

    <!-- Judul -->
    <h1 class="done-title">Terima Kasih!</h1>

    <!-- Pesan -->
    <p class="done-message">
      Survei <strong>{{ surveyTitle }}</strong> Anda telah berhasil dikirim.
      Kontribusi Anda sangat berarti bagi pengembangan kualitas institusi kami.
    </p>

    <!-- Waktu submit -->
    <div v-if="submittedAt" class="submitted-at">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/>
      </svg>
      <span>Dikirim pada: {{ submittedAt }}</span>
    </div>

    <!-- CTA -->
    <button type="button" class="btn-dashboard" @click="goToDashboard">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd"/>
      </svg>
      Kembali ke Beranda
    </button>

    <!-- Quote islami -->
    <p class="islamic-quote" lang="ar" dir="rtl">
      وَمَنْ جَاهَدَ فَإِنَّمَا يُجَاهِدُ لِنَفْسِهِ
    </p>
    <p class="islamic-quote-trans">"Barangsiapa yang bersungguh-sungguh, sesungguhnya itu untuk dirinya sendiri." — QS. Al-Ankabut: 6</p>
  </div>
</template>

<style scoped>
/* ===== Layout ===== */
.done-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 3rem 1.5rem 4rem;
  max-width: 560px;
  margin: 0 auto;
  position: relative;
  overflow: hidden;
}

/* ===== Konfetti ===== */
.confetti-wrap {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.confetti-piece {
  position: absolute;
  top: -16px;
  left: var(--x);
  width: var(--size);
  height: var(--size);
  background: var(--color);
  border-radius: 2px;
  animation: confettiFall var(--dur) var(--delay) ease-in forwards;
  opacity: 0;
}

@keyframes confettiFall {
  0%   { opacity: 1; transform: translateY(0) rotate(0deg); }
  100% { opacity: 0; transform: translateY(110vh) rotate(540deg); }
}

/* ===== Success icon ===== */
.success-icon {
  position: relative;
  z-index: 1;
  width: 5rem;
  height: 5rem;
  background: #f0fdf4;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
  animation: popIn 400ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes popIn {
  from { transform: scale(0.5); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

.success-icon svg {
  width: 3rem;
  height: 3rem;
  color: #16a34a;
}

/* ===== Text ===== */
.done-title {
  position: relative;
  z-index: 1;
  font-size: 2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 0.75rem;
  animation: slideUp 400ms 100ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.done-message {
  position: relative;
  z-index: 1;
  font-size: 1rem;
  color: #475569;
  max-width: 38ch;
  line-height: 1.7;
  margin: 0 0 1.25rem;
  animation: slideUp 400ms 180ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

/* ===== Submitted at ===== */
.submitted-at {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 9999px;
  padding: 0.375rem 1rem;
  font-size: 0.8125rem;
  color: #475569;
  margin-bottom: 1.5rem;
  animation: slideUp 400ms 240ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.submitted-at svg {
  width: 1rem;
  height: 1rem;
  color: #0d9488;
  flex-shrink: 0;
}

/* ===== CTA button ===== */
.btn-dashboard {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.5rem;
  background: #0d9488;
  color: #fff;
  font-size: 0.9375rem;
  font-weight: 600;
  border: none;
  border-radius: 0.625rem;
  cursor: pointer;
  transition: background 150ms ease, transform 100ms ease;
  animation: slideUp 400ms 300ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.btn-dashboard:hover { background: #0f766e; }
.btn-dashboard:active { transform: scale(0.97); }
.btn-dashboard svg { width: 1.125rem; height: 1.125rem; }

/* ===== Islamic quote ===== */
.islamic-quote {
  position: relative;
  z-index: 1;
  margin-top: 2.5rem;
  font-size: 1.25rem;
  color: #0d9488;
  font-weight: 600;
  line-height: 1.8;
  animation: slideUp 400ms 380ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.islamic-quote-trans {
  position: relative;
  z-index: 1;
  font-size: 0.8125rem;
  color: #94a3b8;
  font-style: italic;
  margin-top: 0.25rem;
  max-width: 44ch;
  animation: slideUp 400ms 420ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes slideUp {
  from { transform: translateY(16px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ===== Reduced motion ===== */
@media (prefers-reduced-motion: reduce) {
  .confetti-piece,
  .success-icon,
  .done-title,
  .done-message,
  .submitted-at,
  .btn-dashboard,
  .islamic-quote,
  .islamic-quote-trans { animation: none; opacity: 1; transform: none; }
}
</style>
