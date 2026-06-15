<script setup>
import { ref, computed, watch, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import Sortable from 'sortablejs'

import { useQuestionnaireStore } from '@/stores/questionnaire'
import QuestionRenderer from '@/components/forms/QuestionRenderer.vue'
import QuestionEditor from '@/components/forms/QuestionEditor.vue'
import ConditionalLogicEditor from '@/components/forms/ConditionalLogicEditor.vue'

const route = useRoute()
const router = useRouter()
const store = useQuestionnaireStore()
const qId = computed(() => {
  const id = Number(route.params.id)
  return Number.isFinite(id) ? id : null
})

const activeSection = ref(null)
const activeQuestion = ref(null)

const questionsBySectionLocal = computed(() => {
  return store.sections.reduce((acc, sec) => {
    acc[sec.id] = (sec.questions ?? []).slice().sort((a, b) => (a.order_number ?? a.order ?? 0) - (b.order_number ?? b.order ?? 0))
    return acc
  }, {})
})

watch(() => store.sections, (secs) => {
  if (secs.length && !activeSection.value) activeSection.value = secs[0].id
  if (activeSection.value && !secs.find((s) => s.id === activeSection.value)) activeSection.value = secs[0]?.id ?? null
}, { immediate: true })

const statusLabel = computed(() => {
  const map = { draft: 'Draft', aktif: 'Aktif', arsip: 'Diarsipkan' }
  return map[store.current?.status] ?? store.current?.status ?? ''
})

const rightPanel = ref(null)
const editingQuestion = ref(null)
const editingSectionId = ref(null)

const nextOrder = computed(() => {
  if (!editingSectionId.value) return 1
  const qs = questionsBySectionLocal.value[editingSectionId.value] ?? []
  return qs.length + 1
})

function closeRightPanel() {
  rightPanel.value = null
  editingQuestion.value = null
}

const deleteModal = reactive({ open: false, loading: false, title: '', message: '', type: '', target: null })
const sectionModal = reactive({ open: false, isNew: true, title: '', description: '', error: '', target: null })
const metaModal = reactive({ open: false, title: '', description: '', error: '' })
const sortableRefs = ref({})
const sortableInstances = {}

watch([() => store.sections, activeSection], async () => {
  await nextTick()
  initSortables()
}, { flush: 'post' })

function initSortables() {
  for (const sec of store.sections) {
    const el = sortableRefs.value[sec.id]
    if (!el || sortableInstances[sec.id]) continue
    sortableInstances[sec.id] = Sortable.create(el, {
      animation: 150,
      handle: '[title="Seret untuk mengurutkan"]',
      ghostClass: 'opacity-40',
      chosenClass: 'ring-2 ring-teal-400',
      dragClass: 'shadow-lg',
      onEnd: async (evt) => {
        if (!qId.value) return
        const sectionId = Number(evt.from.dataset.sectionId)
        const list = (questionsBySectionLocal.value[sectionId] ?? []).slice()
        const moved = list.splice(evt.oldIndex, 1)[0]
        list.splice(evt.newIndex, 0, moved)
        await store.reorderQuestions(qId.value, list.map((q) => q.id))
      },
    })
  }
}

function destroySortables() {
  for (const key of Object.keys(sortableInstances)) {
    sortableInstances[key]?.destroy()
    delete sortableInstances[key]
  }
}

async function loadData() {
  store.clearBuilder()
  if (!qId.value) return
  try {
    await store.fetchById(qId.value)
  } catch {
  }
}

onMounted(loadData)
onUnmounted(() => {
  destroySortables()
  store.clearBuilder()
})
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50">
    <header class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
      <div class="flex min-w-0 items-center gap-3">
        <RouterLink :to="{ name: 'admin.questionnaires.index' }" class="flex shrink-0 items-center gap-1 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="Kembali ke daftar kuesioner">Kembali</RouterLink>
        <div class="min-w-0">
          <h1 class="truncate text-sm font-semibold text-gray-800">{{ store.current?.title ?? 'Builder Kuesioner' }}</h1>
          <p class="text-xs text-gray-400">{{ statusLabel }}</p>
        </div>
      </div>
    </header>
    <div class="flex min-h-0 flex-1 overflow-hidden">
      <main class="flex flex-1 flex-col overflow-hidden">
        <div v-if="store.loadingDetail" class="flex flex-1 items-center justify-center">Loading...</div>
        <div v-else-if="store.error && !store.current" class="flex flex-1 flex-col items-center justify-center gap-3 p-8">
          <p class="text-sm text-red-600">{{ store.error }}</p>
          <button @click="loadData" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">Coba lagi</button>
        </div>
        <div v-else class="flex flex-1 items-center justify-center text-sm text-gray-500">
          {{ qId ? 'Builder siap digunakan.' : 'Mode create terdeteksi. Data detail tidak dimuat sampai ID kuesioner tersedia.' }}
        </div>
      </main>
    </div>
  </div>
</template>
