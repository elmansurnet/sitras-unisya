<script setup>
/**
 * QuestionnaireBuilderPage.vue
 * Full builder untuk mengedit pertanyaan kuesioner.
 *
 * Akses via route: /admin/questionnaires/:id/builder
 * Wajib ada route.params.id yang valid.
 * Jika tidak ada ID → redirect ke index (tidak lagi tampil pesan aneh).
 */
import { ref, computed, watch, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import Sortable from 'sortablejs'
import { useQuestionnaireStore } from '@/stores/questionnaire'
import { useToast } from '@/composables/useToast'
import QuestionEditor from '@/components/forms/QuestionEditor.vue'
import SkeletonLoader from '@/components/common/SkeletonLoader.vue'

const route = useRoute()
const router = useRouter()
const store = useQuestionnaireStore()
const { toast } = useToast()

const qId = computed(() => {
  const id = Number(route.params.id)
  return Number.isFinite(id) && id > 0 ? id : null
})

const activeSection = ref(null)
const rightPanel = ref(null)
const editingQuestion = ref(null)
const editingSectionId = ref(null)
const sortableRefs = ref({})
const sortableInstances = {}

const deleteModal = reactive({
  open: false,
  loading: false,
  title: '',
  message: '',
  type: '',
  target: null,
})

const sectionModal = reactive({
  open: false,
  isNew: true,
  title: '',
  description: '',
  error: '',
  target: null,
})

const questionsBySectionLocal = computed(() => {
  return store.sections.reduce((acc, sec) => {
    acc[sec.id] = (sec.questions ?? [])
      .slice()
      .sort((a, b) => (a.order_number ?? a.order ?? 0) - (b.order_number ?? b.order ?? 0))
    return acc
  }, {})
})

watch(
  () => store.sections,
  (secs) => {
    if (secs.length && !activeSection.value) activeSection.value = secs[0].id
    if (activeSection.value && !secs.find((s) => s.id === activeSection.value)) {
      activeSection.value = secs[0]?.id ?? null
    }
  },
  { immediate: true }
)

watch([() => store.sections, activeSection], async () => {
  await nextTick()
  initSortables()
}, { flush: 'post' })

const statusLabel = computed(() => {
  const map = { draft: 'Draft', aktif: 'Aktif', arsip: 'Diarsipkan' }
  return map[store.current?.status] ?? store.current?.status ?? ''
})

const nextOrder = computed(() => {
  if (!editingSectionId.value) return 1
  const qs = questionsBySectionLocal.value[editingSectionId.value] ?? []
  return qs.length + 1
})

function closeRightPanel() {
  rightPanel.value = null
  editingQuestion.value = null
}

function openAddQuestion(sectionId) {
  editingSectionId.value = sectionId
  editingQuestion.value = null
  rightPanel.value = 'question'
}

function openEditQuestion(question, sectionId) {
  editingSectionId.value = sectionId
  editingQuestion.value = { ...question }
  rightPanel.value = 'question'
}

function openAddSection() {
  sectionModal.isNew = true
  sectionModal.title = ''
  sectionModal.description = ''
  sectionModal.error = ''
  sectionModal.target = null
  sectionModal.open = true
}

function openEditSection(sec) {
  sectionModal.isNew = false
  sectionModal.title = sec.title
  sectionModal.description = sec.description ?? ''
  sectionModal.error = ''
  sectionModal.target = sec
  sectionModal.open = true
}

async function handleSectionSubmit() {
  if (!sectionModal.title.trim()) {
    sectionModal.error = 'Judul section wajib diisi.'
    return
  }
  sectionModal.error = ''
  try {
    if (sectionModal.isNew) {
      await store.addSection(qId.value, {
        title: sectionModal.title.trim(),
        description: sectionModal.description.trim() || null,
      })
      toast.success('Section berhasil ditambahkan.')
    } else {
      await store.updateSection(qId.value, sectionModal.target.id, {
        title: sectionModal.title.trim(),
        description: sectionModal.description.trim() || null,
      })
      toast.success('Section berhasil diperbarui.')
    }
    sectionModal.open = false
  } catch {
    sectionModal.error = 'Gagal menyimpan section. Coba lagi.'
  }
}

function confirmDeleteSection(sec) {
  deleteModal.type = 'section'
  deleteModal.target = sec
  deleteModal.title = 'Hapus Section'
  deleteModal.message = `Hapus section "${sec.title}" beserta semua pertanyaan di dalamnya? Tindakan tidak dapat dibatalkan.`
  deleteModal.open = true
}

function confirmDeleteQuestion(q) {
  deleteModal.type = 'question'
  deleteModal.target = q
  deleteModal.title = 'Hapus Pertanyaan'
  deleteModal.message = 'Hapus pertanyaan ini? Tindakan tidak dapat dibatalkan.'
  deleteModal.open = true
}

async function handleDelete() {
  deleteModal.loading = true
  try {
    if (deleteModal.type === 'section') {
      await store.deleteSection(qId.value, deleteModal.target.id)
      toast.success('Section berhasil dihapus.')
    } else {
      await store.deleteQuestion(qId.value, deleteModal.target.id)
      toast.success('Pertanyaan berhasil dihapus.')
    }
  } catch {
    toast.error('Gagal menghapus. Coba lagi.')
  } finally {
    deleteModal.loading = false
    deleteModal.open = false
  }
}

async function handleQuestionSave(payload) {
  try {
    if (editingQuestion.value?.id) {
      await store.updateQuestion(qId.value, editingQuestion.value.id, payload)
      toast.success('Pertanyaan berhasil diperbarui.')
    } else {
      await store.addQuestion(qId.value, editingSectionId.value, payload)
      toast.success('Pertanyaan berhasil ditambahkan.')
    }
    closeRightPanel()
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'Gagal menyimpan pertanyaan.')
  }
}

function initSortables() {
  for (const sec of store.sections) {
    const el = sortableRefs.value[sec.id]
    if (!el || sortableInstances[sec.id]) continue
    sortableInstances[sec.id] = Sortable.create(el, {
      animation: 150,
      handle: '[data-drag-handle]',
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
  if (!qId.value) {
    router.replace({ name: 'admin.questionnaires.index' })
    return
  }
  try {
    await store.fetchById(qId.value)
  } catch {
    toast.error('Gagal memuat data kuesioner.')
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
    <!-- Header builder -->
    <header class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
      <div class="flex min-w-0 items-center gap-3">
        <RouterLink
          :to="{ name: 'admin.questionnaires.index' }"
          class="flex shrink-0 items-center gap-1 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
          title="Kembali ke daftar kuesioner"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Kembali
        </RouterLink>
        <div class="h-4 w-px bg-gray-200" />
        <div class="min-w-0">
          <h1 class="truncate text-sm font-semibold text-gray-800">
            {{ store.current?.title ?? 'Builder Kuesioner' }}
          </h1>
          <p class="text-xs text-gray-400">{{ statusLabel }}</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <RouterLink
          v-if="qId"
          :to="{ name: 'admin.questionnaires.preview', params: { id: qId } }"
          class="btn btn-ghost btn--sm text-xs"
        >
          Preview
        </RouterLink>
      </div>
    </header>

    <!-- Loading state -->
    <div v-if="store.loadingDetail" class="flex flex-1 items-center justify-center p-8">
      <SkeletonLoader variant="form" :rows="4" />
    </div>

    <!-- Error state -->
    <div
      v-else-if="store.error && !store.current"
      class="flex flex-1 flex-col items-center justify-center gap-3 p-8"
    >
      <svg class="h-12 w-12 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-red-600">{{ store.error }}</p>
      <button class="btn btn-primary btn--sm" @click="loadData">Coba Lagi</button>
    </div>

    <!-- Main builder layout -->
    <div v-else class="flex min-h-0 flex-1 overflow-hidden">
      <!-- Sidebar — daftar sections -->
      <aside class="flex w-56 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
          <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Sections</span>
          <button
            class="rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-teal-600 transition-colors"
            title="Tambah Section"
            @click="openAddSection"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
        <nav class="flex-1 overflow-y-auto py-1">
          <div v-if="!store.sections.length" class="px-3 py-4 text-center text-xs text-gray-400">
            Belum ada section.<br />Klik + untuk menambah.
          </div>
          <button
            v-for="sec in store.sections"
            :key="sec.id"
            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition-colors"
            :class="[
              activeSection === sec.id
                ? 'bg-teal-50 font-medium text-teal-700'
                : 'text-gray-600 hover:bg-gray-50'
            ]"
            @click="activeSection = sec.id"
          >
            <span class="min-w-0 flex-1 truncate">{{ sec.title }}</span>
            <span class="shrink-0 text-xs text-gray-400">
              {{ (sec.questions ?? []).length }}
            </span>
          </button>
        </nav>
      </aside>

      <!-- Area utama — daftar pertanyaan section aktif -->
      <main class="flex flex-1 flex-col overflow-hidden">
        <template v-if="activeSection !== null">
          <div
            v-for="sec in store.sections"
            v-show="activeSection === sec.id"
            :key="sec.id"
            class="flex flex-1 flex-col overflow-hidden"
          >
            <!-- Section header -->
            <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-100 bg-white px-4 py-3">
              <div>
                <h2 class="text-sm font-semibold text-gray-800">{{ sec.title }}</h2>
                <p v-if="sec.description" class="text-xs text-gray-400">{{ sec.description }}</p>
              </div>
              <div class="flex items-center gap-1">
                <button
                  class="btn-icon btn-icon--ghost"
                  title="Edit Section"
                  @click="openEditSection(sec)"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  class="btn-icon btn-icon--danger"
                  title="Hapus Section"
                  @click="confirmDeleteSection(sec)"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Daftar pertanyaan (sortable) -->
            <div class="flex-1 overflow-y-auto p-4">
              <div
                :ref="(el) => { if (el) sortableRefs.value[sec.id] = el }"
                :data-section-id="sec.id"
                class="space-y-2"
              >
                <div
                  v-for="q in questionsBySectionLocal[sec.id]"
                  :key="q.id"
                  class="group flex items-start gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm hover:border-teal-300 transition-colors"
                >
                  <!-- Drag handle -->
                  <button
                    data-drag-handle
                    class="mt-0.5 cursor-grab touch-none text-gray-300 group-hover:text-gray-400"
                    title="Seret untuk mengurutkan"
                    aria-label="Geser pertanyaan"
                  >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M9 4.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM9 10.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM9 16.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z" />
                    </svg>
                  </button>
                  <!-- Konten pertanyaan -->
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-800">{{ q.question_text }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">
                      {{ q.question_type }}
                      <span v-if="q.is_required" class="ml-1 text-red-400">* wajib</span>
                    </p>
                  </div>
                  <!-- Tombol edit/hapus -->
                  <div class="flex shrink-0 items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      class="btn-icon btn-icon--ghost"
                      title="Edit Pertanyaan"
                      @click="openEditQuestion(q, sec.id)"
                    >
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      class="btn-icon btn-icon--danger"
                      title="Hapus Pertanyaan"
                      @click="confirmDeleteQuestion(q)"
                    >
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div
                v-if="!(questionsBySectionLocal[sec.id] ?? []).length"
                class="mt-4 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 p-8 text-center"
              >
                <svg class="mb-2 h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-gray-400">Belum ada pertanyaan di section ini.</p>
              </div>

              <!-- Tombol tambah pertanyaan -->
              <button
                class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-teal-300 py-2 text-sm text-teal-600 hover:bg-teal-50 transition-colors"
                @click="openAddQuestion(sec.id)"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pertanyaan
              </button>
            </div>
          </div>
        </template>

        <!-- State jika belum ada section -->
        <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center">
          <svg class="h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-sm text-gray-500">Kuesioner belum memiliki section.</p>
          <button class="btn btn-primary btn--sm" @click="openAddSection">+ Tambah Section</button>
        </div>
      </main>

      <!-- Right Panel — QuestionEditor -->
      <aside
        v-if="rightPanel === 'question'"
        class="flex w-80 flex-shrink-0 flex-col border-l border-gray-200 bg-white shadow-lg"
      >
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
          <h3 class="text-sm font-semibold text-gray-800">
            {{ editingQuestion?.id ? 'Edit Pertanyaan' : 'Tambah Pertanyaan' }}
          </h3>
          <button
            class="rounded p-1 text-gray-400 hover:text-gray-600"
            aria-label="Tutup panel"
            @click="closeRightPanel"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4">
          <QuestionEditor
            :question="editingQuestion"
            :section-id="editingSectionId"
            :order="nextOrder"
            @save="handleQuestionSave"
            @cancel="closeRightPanel"
          />
        </div>
      </aside>
    </div>

    <!-- Modal Section -->
    <div
      v-if="sectionModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      @click.self="sectionModal.open = false"
    >
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-4 text-base font-semibold text-gray-900">
          {{ sectionModal.isNew ? 'Tambah Section' : 'Edit Section' }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">
              Judul Section <span class="text-red-500">*</span>
            </label>
            <input
              v-model="sectionModal.title"
              type="text"
              class="input"
              :class="{ 'input--error': sectionModal.error }"
              placeholder="cth. Informasi Ketenagakerjaan"
              @keyup.enter="handleSectionSubmit"
            />
            <p v-if="sectionModal.error" class="mt-1 text-xs text-red-600">{{ sectionModal.error }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
            <textarea
              v-model="sectionModal.description"
              class="input resize-none"
              rows="2"
              placeholder="Opsional"
            />
          </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
          <button class="btn btn-ghost" @click="sectionModal.open = false">Batal</button>
          <button class="btn btn-primary" @click="handleSectionSubmit">
            {{ sectionModal.isNew ? 'Tambah' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Delete -->
    <div
      v-if="deleteModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-2 text-base font-semibold text-gray-900">{{ deleteModal.title }}</h3>
        <p class="mb-5 text-sm text-gray-600">{{ deleteModal.message }}</p>
        <div class="flex justify-end gap-2">
          <button
            class="btn btn-ghost"
            :disabled="deleteModal.loading"
            @click="deleteModal.open = false"
          >
            Batal
          </button>
          <button
            class="btn btn-danger"
            :disabled="deleteModal.loading"
            @click="handleDelete"
          >
            <span v-if="deleteModal.loading" class="flex items-center gap-1">
              <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
              </svg>
              Menghapus...
            </span>
            <span v-else>Ya, Hapus</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
