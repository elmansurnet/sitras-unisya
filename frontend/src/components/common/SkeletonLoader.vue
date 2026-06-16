<script setup>
/**
 * SkeletonLoader.vue — Bug #9
 * Komponen shimmer skeleton untuk state loading.
 *
 * Props:
 *   variant: 'table' | 'card' | 'form' | 'text'  (default: 'table')
 *   rows: number  — jumlah baris/kartu yang ditampilkan (default: 5)
 *
 * Penggunaan:
 *   <SkeletonLoader variant="table" :rows="5" />
 *   <SkeletonLoader variant="card" :rows="3" />
 *   <SkeletonLoader variant="form" :rows="4" />
 */
defineProps({
  variant: {
    type: String,
    default: 'table',
    validator: (v) => ['table', 'card', 'form', 'text'].includes(v),
  },
  rows: {
    type: Number,
    default: 5,
  },
})
</script>

<template>
  <!-- TABLE skeleton -->
  <div v-if="variant === 'table'" class="w-full overflow-hidden rounded-lg border border-gray-100 bg-white">
    <!-- Header row -->
    <div class="flex gap-4 border-b border-gray-100 bg-gray-50 px-4 py-3">
      <div class="skeleton h-4 w-1/4" />
      <div class="skeleton h-4 w-1/4" />
      <div class="skeleton h-4 w-1/5" />
      <div class="skeleton h-4 w-1/6" />
      <div class="skeleton h-4 w-1/6" />
    </div>
    <!-- Data rows -->
    <div
      v-for="i in rows"
      :key="i"
      class="flex items-center gap-4 border-b border-gray-50 px-4 py-3 last:border-0"
    >
      <div class="skeleton h-4 w-1/4" />
      <div class="skeleton h-4 w-1/4" />
      <div class="skeleton h-4 w-1/5" />
      <div class="skeleton h-3 w-14 rounded-full" />
      <div class="skeleton h-4 w-16" />
    </div>
  </div>

  <!-- CARD skeleton -->
  <div v-else-if="variant === 'card'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div
      v-for="i in rows"
      :key="i"
      class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm"
    >
      <div class="mb-3 skeleton h-5 w-3/5" />
      <div class="mb-2 skeleton h-4 w-full" />
      <div class="mb-4 skeleton h-4 w-4/5" />
      <div class="flex justify-between">
        <div class="skeleton h-3 w-16 rounded-full" />
        <div class="skeleton h-8 w-20 rounded-lg" />
      </div>
    </div>
  </div>

  <!-- FORM skeleton -->
  <div v-else-if="variant === 'form'" class="space-y-5">
    <div v-for="i in rows" :key="i" class="space-y-1.5">
      <div class="skeleton h-4 w-32" />
      <div class="skeleton h-10 w-full rounded-lg" />
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <div class="skeleton h-9 w-20 rounded-lg" />
      <div class="skeleton h-9 w-28 rounded-lg" />
    </div>
  </div>

  <!-- TEXT skeleton -->
  <div v-else-if="variant === 'text'" class="space-y-2">
    <div
      v-for="i in rows"
      :key="i"
      class="skeleton h-4"
      :style="{ width: i === rows ? '60%' : '100%' }"
    />
  </div>
</template>

<style scoped>
.skeleton {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e0e0e0 50%,
    #f0f0f0 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
  border-radius: 4px;
}

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (prefers-reduced-motion: reduce) {
  .skeleton {
    animation: none;
    background: #f0f0f0;
  }
}
</style>
