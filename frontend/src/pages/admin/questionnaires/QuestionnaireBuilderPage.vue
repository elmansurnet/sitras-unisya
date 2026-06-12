<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50">

    <!-- ═══ TOP BAR ═══════════════════════════════════════════════════════════ -->
    <header class="flex flex-shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
      <!-- Kiri: breadcrumb + judul -->
      <div class="flex min-w-0 items-center gap-3">
        <RouterLink
          :to="{ name: 'admin.questionnaires.index' }"
          class="flex shrink-0 items-center gap-1 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
          title="Kembali ke daftar kuesioner"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
          </svg>
        </RouterLink>

        <div class="min-w-0">
          <div v-if="store.loadingDetail" class="h-4 w-48 animate-pulse rounded bg-gray-200" />
          <template v-else>
            <h1 class="truncate text-sm font-semibold text-gray-800">
              {{ store.current?.title ?? 'Builder Kuesioner' }}
            </h1>
            <p class="text-xs text-gray-400">
              {{ store.current?.type === 'alumni' ? 'Kuesioner Alumni' : 'Kuesioner Employer' }}
              · {{ store.totalQuestions }} pertanyaan
              · {{ store.sections.length }} seksi
            </p>
          </template>
        </div>
      </div>

      <!-- Kanan: status + aksi -->
      <div class="flex shrink-0 items-center gap-2">
        <!-- Badge status -->
        <span
          v-if="store.current"
          :class="{
            'bg-amber-100 text-amber-700': store.isDraft,
            'bg-teal-100 text-teal-700':   store.isPublished,
            'bg-gray-100 text-gray-500':    store.isArchived,
          }"
          class="hidden rounded-full px-2.5 py-0.5 text-xs font-medium sm:inline-flex"
        >
          {{ statusLabel }}
        </span>

        <!-- Preview -->
        <RouterLink
          v-if="store.current"
          :to="{ name: 'admin.questionnaires.preview', params: { id: qId } }"
          class="hidden items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 sm:inline-flex"
          target="_blank"
        >
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.964-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          </svg>
          Preview
        </RouterLink>

        <!-- Publish / Archive -->
        <button
          v-if="store.isDraft"
          @click="handlePublish"
          :disabled="store.loadingPublish || store.totalQuestions === 0"
          class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-50"
          :title="store.totalQuestions === 0 ? 'Tambah minimal 1 pertanyaan dulu' : 'Publikasikan kuesioner'"
        >
          <svg v-if="store.loadingPublish" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
          </svg>
          <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
          </svg>
          Publikasikan
        </button>

        <button
          v-else-if="store.isPublished"
          @click="handleArchive"
          :disabled="store.loadingPublish"
          class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50"
        >
          Arsipkan
        </button>

        <!-- Edit metadata -->
        <button
          @click="openMetaModal"
          class="rounded-lg border border-gray-300 p-1.5 text-gray-500 hover:bg-gray-50"
          title="Edit metadata kuesioner"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
          </svg>
        </button>
      </div>
    </header>

    <!-- ═══ MAIN CANVAS ═══════════════════════════════════════════════════════ -->
    <div class="flex min-h-0 flex-1 overflow-hidden">

      <!-- Sidebar kiri: daftar seksi -->
      <aside class="hidden w-56 flex-shrink-0 flex-col border-r border-gray-200 bg-white lg:flex">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2.5">
          <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Seksi</span>
          <button
            @click="openAddSection"
            :disabled="store.loadingSection"
            class="rounded p-1 text-teal-600 hover:bg-teal-50 disabled:opacity-40"
            title="Tambah seksi baru"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
          </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-2 space-y-0.5">
          <div
            v-for="(sec, i) in store.sections"
            :key="sec.id"
            @click="activeSection = sec.id"
            :class="[
              'group flex cursor-pointer items-center gap-2 rounded-lg px-2.5 py-2 text-sm transition-colors',
              activeSection === sec.id
                ? 'bg-teal-50 text-teal-700 font-medium'
                : 'text-gray-600 hover:bg-gray-100',
            ]"
          >
            <span class="w-5 shrink-0 text-center text-xs tabular-nums text-gray-400">{{ i + 1 }}</span>
            <span class="min-w-0 flex-1 truncate">{{ sec.title || 'Seksi tanpa judul' }}</span>
            <!-- Edit seksi -->
            <button
              @click.stop="openEditSection(sec)"
              class="hidden rounded p-0.5 text-gray-300 hover:text-gray-600 group-hover:block"
              title="Edit seksi"
            >
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
              </svg>
            </button>
          </div>

          <div v-if="!store.sections.length" class="px-3 py-6 text-center text-xs text-gray-400">
            Belum ada seksi.<br />Klik + untuk tambah.
          </div>
        </nav>
      </aside>

      <!-- Area tengah: pertanyaan per seksi -->
      <main class="flex flex-1 flex-col overflow-hidden">
        <!-- Loading awal -->
        <div v-if="store.loadingDetail" class="flex flex-1 items-center justify-center">
          <svg class="h-8 w-8 animate-spin text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
          </svg>
        </div>

        <!-- Error state -->
        <div v-else-if="store.error && !store.current" class="flex flex-1 flex-col items-center justify-center gap-3 p-8">
          <p class="text-sm text-red-600">{{ store.error }}</p>
          <button @click="loadData" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700">Coba lagi</button>
        </div>

        <!-- Empty: belum ada seksi -->
        <div v-else-if="store.sections.length === 0" class="flex flex-1 flex-col items-center justify-center gap-4 p-8 text-center">
          <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
          </svg>
          <div>
            <p class="text-sm font-medium text-gray-700">Kuesioner masih kosong</p>
            <p class="mt-1 text-xs text-gray-400">Mulai dengan menambah seksi pertama</p>
          </div>
          <button
            @click="openAddSection"
            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Seksi Pertama
          </button>
        </div>

        <!-- Konten seksi -->
        <template v-else>
          <!-- Mobile: selector seksi -->
          <div class="flex items-center gap-2 border-b border-gray-200 bg-white px-4 py-2 lg:hidden">
            <select
              v-model="activeSection"
              class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
              <option v-for="sec in store.sections" :key="sec.id" :value="sec.id">
                {{ sec.title || 'Seksi tanpa judul' }}
              </option>
            </select>
            <button @click="openAddSection" class="rounded-lg border border-gray-300 p-1.5 text-gray-500 hover:bg-gray-50">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>
          </div>

          <!-- Scrollable question list -->
          <div class="flex-1 overflow-y-auto p-4 lg:p-6">
            <template v-for="sec in store.sections" :key="sec.id">
              <div v-show="activeSection === sec.id" class="space-y-3">

                <!-- Header seksi -->
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <h2 class="truncate text-sm font-semibold text-gray-800">
                      {{ sec.title || 'Seksi tanpa judul' }}
                    </h2>
                    <p v-if="sec.description" class="mt-0.5 text-xs text-gray-500">{{ sec.description }}</p>
                  </div>
                  <div class="flex shrink-0 items-center gap-1">
                    <button
                      @click="openEditSection(sec)"
                      class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                      title="Edit seksi"
                    >
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                      </svg>
                    </button>
                    <button
                      @click="confirmDeleteSection(sec)"
                      class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500"
                      title="Hapus seksi"
                    >
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Sortable question list -->
                <div
                  :ref="el => sortableRefs[sec.id] = el"
                  :data-section-id="sec.id"
                  class="space-y-2"
                >
                  <QuestionRenderer
                    v-for="(q, qi) in questionsBySectionLocal[sec.id] ?? []"
                    :key="q.id"
                    :data-id="q.id"
                    :question="q"
                    mode="builder"
                    :is-active="activeQuestion?.id === q.id"
                    :is-first="qi === 0"
                    :is-last="qi === (questionsBySectionLocal[sec.id].length - 1)"
                    @click="selectQuestion(q)"
                    @edit="openEditQuestion(q)"
                    @delete="confirmDeleteQuestion(q)"
                    @move-up="moveQuestionUp(q, sec.id)"
                    @move-down="moveQuestionDown(q, sec.id)"
                    @toggle-logic="openLogicPanel(q)"
                  />
                </div>

                <!-- Empty seksi -->
                <div
                  v-if="!(questionsBySectionLocal[sec.id]?.length)"
                  class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-8 text-center"
                >
                  <p class="text-xs text-gray-400">Seksi ini belum punya pertanyaan</p>
                  <button
                    @click="openAddQuestion(sec.id)"
                    class="mt-2 text-xs font-medium text-teal-600 hover:text-teal-800"
                  >+ Tambah pertanyaan pertama</button>
                </div>

                <!-- Tombol tambah pertanyaan -->
                <button
                  v-if="questionsBySectionLocal[sec.id]?.length"
                  @click="openAddQuestion(sec.id)"
                  class="flex w-full items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-teal-200 py-3 text-sm font-medium text-teal-600 transition-colors hover:border-teal-400 hover:bg-teal-50"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                  </svg>
                  Tambah Pertanyaan
                </button>
              </div>
            </template>
          </div>
        </template>
      </main>

      <!-- Panel kanan: QuestionEditor / ConditionalLogicEditor -->
      <aside
        v-if="rightPanel !== null"
        class="flex w-80 flex-shrink-0 flex-col overflow-hidden border-l border-gray-200 bg-white xl:w-96"
      >
        <div class="flex-1 overflow-y-auto p-3">
          <!-- Question Editor -->
          <QuestionEditor
            v-if="rightPanel === 'editor'"
            :question="editingQuestion"
            :section-id="editingSectionId"
            :default-order="nextOrder"
            :saving="store.loadingQuestion"
            @save="handleSaveQuestion"
            @cancel="closeRightPanel"
            @delete="confirmDeleteQuestion"
          />

          <!-- Conditional Logic Editor -->
          <ConditionalLogicEditor
            v-else-if="rightPanel === 'logic'"
            :question="activeQuestion"
            :all-questions="store.questions"
            :saving="store.loadingQuestion"
            @save="handleSaveLogic"
            @cancel="closeRightPanel"
          />
        </div>
      </aside>
    </div>

    <!-- ═══ MODAL: Tambah / Edit Seksi ════════════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="sectionModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="closeSectionModal"
      >
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl" @click.stop>
          <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-800">
              {{ sectionModal.isNew ? 'Tambah Seksi' : 'Edit Seksi' }}
            </h3>
            <button @click="closeSectionModal" class="rounded p-1 text-gray-400 hover:bg-gray-100">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="space-y-3 p-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Judul Seksi <span class="text-red-500">*</span></label>
              <input
                v-model="sectionModal.title"
                type="text"
                placeholder="cth: Data Pekerjaan"
                autofocus
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              />
              <p v-if="sectionModal.error" class="mt-1 text-xs text-red-500">{{ sectionModal.error }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi <span class="text-gray-400 font-normal">(opsional)</span></label>
              <textarea
                v-model="sectionModal.description"
                rows="2"
                placeholder="Petunjuk untuk responden..."
                class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
              />
            </div>
          </div>
          <div class="flex justify-end gap-2 border-t border-gray-100 px-4 py-3">
            <button @click="closeSectionModal" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
            <button
              @click="submitSection"
              :disabled="store.loadingSection"
              class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
            >
              <svg v-if="store.loadingSection" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
              </svg>
              Simpan
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ MODAL: Edit Metadata Kuesioner ═══════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="metaModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="metaModal.open = false"
      >
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl" @click.stop>
          <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-800">Edit Metadata Kuesioner</h3>
            <button @click="metaModal.open = false" class="rounded p-1 text-gray-400 hover:bg-gray-100">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="space-y-3 p-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Judul Kuesioner <span class="text-red-500">*</span></label>
              <input v-model="metaModal.title" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
              <p v-if="metaModal.error" class="mt-1 text-xs text-red-500">{{ metaModal.error }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
              <textarea v-model="metaModal.description" rows="3" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
            </div>
          </div>
          <div class="flex justify-end gap-2 border-t border-gray-100 px-4 py-3">
            <button @click="metaModal.open = false" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
            <button
              @click="submitMeta"
              :disabled="store.loading"
              class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
            >
              <svg v-if="store.loading" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
              </svg>
              Simpan
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ MODAL: Konfirmasi Hapus ═══════════════════════════════════════════ -->
    <Teleport to="body">
      <div
        v-if="deleteModal.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="deleteModal.open = false"
      >
        <div class="w-full max-w-sm rounded-xl bg-white shadow-xl" @click.stop>
          <div class="p-5 text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
              <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
              </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-800">{{ deleteModal.title }}</h3>
            <p class="mt-1 text-xs text-gray-500">{{ deleteModal.message }}</p>
          </div>
          <div class="flex gap-2 border-t border-gray-100 px-4 py-3">
            <button @click="deleteModal.open = false" class="flex-1 rounded-lg border border-gray-300 py-1.5 text-sm text-gray-600 hover:bg-gray-50">Batal</button>
            <button
              @click="executeDelete"
              :disabled="deleteModal.loading"
              class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
            >
              <svg v-if="deleteModal.loading" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" d="M12 3a9 9 0 1 0 9 9" />
              </svg>
              Hapus
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, watch, reactive, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import Sortable from 'sortablejs'

import { useQuestionnaireStore } from '@/stores/questionnaire'
import QuestionRenderer from '@/components/forms/QuestionRenderer.vue'
import QuestionEditor from '@/components/forms/QuestionEditor.vue'
import ConditionalLogicEditor from '@/components/forms/ConditionalLogicEditor.vue'

// ─── Route & Store ────────────────────────────────────────────────────────────
const route  = useRoute()
const router = useRouter()
const store  = useQuestionnaireStore()
const qId    = computed(() => Number(route.params.id))

// ─── Aktif seksi & pertanyaan ─────────────────────────────────────────────────
const activeSection  = ref(null)
const activeQuestion = ref(null)

// Computed: pertanyaan dikelompokkan per seksi, sorted by order
const questionsBySectionLocal = computed(() => {
  return store.sections.reduce((acc, sec) => {
    acc[sec.id] = (sec.questions ?? []).slice().sort(
      (a, b) => (a.order_number ?? a.order ?? 0) - (b.order_number ?? b.order ?? 0)
    )
    return acc
  }, {})
})

// Set aktif seksi ke seksi pertama saat sections berubah
watch(() => store.sections, (secs) => {
  if (secs.length && !activeSection.value) {
    activeSection.value = secs[0].id
  }
  // Jika seksi aktif dihapus, fallback ke seksi pertama
  if (activeSection.value && !secs.find(s => s.id === activeSection.value)) {
    activeSection.value = secs[0]?.id ?? null
  }
}, { immediate: true })

// ─── Status label ─────────────────────────────────────────────────────────────
const statusLabel = computed(() => {
  const map = { draft: 'Draft', aktif: 'Aktif', arsip: 'Diarsipkan' }
  return map[store.current?.status] ?? store.current?.status ?? ''
})

// ─── Panel kanan ──────────────────────────────────────────────────────────────
// 'editor' | 'logic' | null
const rightPanel      = ref(null)
const editingQuestion = ref(null)
const editingSectionId = ref(null)

const nextOrder = computed(() => {
  if (!editingSectionId.value) return 1
  const qs = questionsBySectionLocal.value[editingSectionId.value] ?? []
  return qs.length + 1
})

function selectQuestion(q) {
  activeQuestion.value = q
}

function openAddQuestion(sectionId) {
  editingQuestion.value  = null
  editingSectionId.value = sectionId
  rightPanel.value       = 'editor'
}

function openEditQuestion(q) {
  activeQuestion.value   = q
  editingQuestion.value  = q
  editingSectionId.value = q.section_id
  rightPanel.value       = 'editor'
}

function openLogicPanel(q) {
  activeQuestion.value = q
  rightPanel.value     = 'logic'
}

function closeRightPanel() {
  rightPanel.value      = null
  editingQuestion.value = null
}

// ─── Save question (tambah / edit) ────────────────────────────────────────────
async function handleSaveQuestion(payload) {
  try {
    if (editingQuestion.value?.id) {
      await store.updateQuestion(qId.value, editingQuestion.value.id, payload)
    } else {
      await store.addQuestion(qId.value, { ...payload, section_id: editingSectionId.value })
    }
    closeRightPanel()
  } catch {
    // error sudah ada di store.error, QuestionEditor menampilkan dari luar bila perlu
  }
}

// ─── Save conditional logic ───────────────────────────────────────────────────
async function handleSaveLogic(payload) {
  if (!activeQuestion.value?.id) return
  try {
    await store.updateQuestion(qId.value, activeQuestion.value.id, {
      conditional_logic: payload,
    })
    closeRightPanel()
  } catch {
    // error di store.error
  }
}

// ─── Move up / down (tanpa drag) ─────────────────────────────────────────────
async function moveQuestionUp(q, sectionId) {
  const list = (questionsBySectionLocal.value[sectionId] ?? []).slice()
  const idx  = list.findIndex(item => item.id === q.id)
  if (idx <= 0) return
  ;[list[idx - 1], list[idx]] = [list[idx], list[idx - 1]]
  await store.reorderQuestions(qId.value, list.map(item => item.id))
}

async function moveQuestionDown(q, sectionId) {
  const list = (questionsBySectionLocal.value[sectionId] ?? []).slice()
  const idx  = list.findIndex(item => item.id === q.id)
  if (idx < 0 || idx >= list.length - 1) return
  ;[list[idx], list[idx + 1]] = [list[idx + 1], list[idx]]
  await store.reorderQuestions(qId.value, list.map(item => item.id))
}

// ─── Delete modal ─────────────────────────────────────────────────────────────
const deleteModal = reactive({
  open:    false,
  loading: false,
  title:   '',
  message: '',
  type:    '',   // 'question' | 'section'
  target:  null,
})

function confirmDeleteQuestion(q) {
  Object.assign(deleteModal, {
    open:    true,
    loading: false,
    title:   'Hapus Pertanyaan?',
    message: `Pertanyaan "${q.question_text.slice(0, 60)}" akan dihapus permanen.`,
    type:    'question',
    target:  q,
  })
}

function confirmDeleteSection(sec) {
  const count = questionsBySectionLocal.value[sec.id]?.length ?? 0
  Object.assign(deleteModal, {
    open:    true,
    loading: false,
    title:   'Hapus Seksi?',
    message: `Seksi "${sec.title}" dan ${count} pertanyaan di dalamnya akan dihapus permanen.`,
    type:    'section',
    target:  sec,
  })
}

async function executeDelete() {
  deleteModal.loading = true
  try {
    if (deleteModal.type === 'question') {
      await store.removeQuestion(qId.value, deleteModal.target.id)
      if (activeQuestion.value?.id === deleteModal.target.id) closeRightPanel()
    } else {
      await store.removeSection(qId.value, deleteModal.target.id)
    }
    deleteModal.open = false
  } catch {
    deleteModal.loading = false
  }
}

// ─── Seksi modal ──────────────────────────────────────────────────────────────
const sectionModal = reactive({
  open:        false,
  isNew:       true,
  title:       '',
  description: '',
  error:       '',
  target:      null,
})

function openAddSection() {
  Object.assign(sectionModal, { open: true, isNew: true, title: '', description: '', error: '', target: null })
}

function openEditSection(sec) {
  Object.assign(sectionModal, {
    open: true, isNew: false,
    title: sec.title ?? '',
    description: sec.description ?? '',
    error: '', target: sec,
  })
}

function closeSectionModal() {
  sectionModal.open = false
}

async function submitSection() {
  if (!sectionModal.title.trim()) {
    sectionModal.error = 'Judul seksi wajib diisi.'
    return
  }
  sectionModal.error = ''
  try {
    const payload = {
      title:       sectionModal.title.trim(),
      description: sectionModal.description.trim() || null,
      order:       store.sections.length + 1,
    }
    if (sectionModal.isNew) {
      const sec = await store.addSection(qId.value, payload)
      activeSection.value = sec.id
    } else {
      await store.updateSection(qId.value, sectionModal.target.id, payload)
    }
    closeSectionModal()
  } catch {
    sectionModal.error = store.error ?? 'Gagal menyimpan seksi.'
  }
}

// ─── Metadata kuesioner modal ─────────────────────────────────────────────────
const metaModal = reactive({ open: false, title: '', description: '', error: '' })

function openMetaModal() {
  metaModal.title       = store.current?.title ?? ''
  metaModal.description = store.current?.description ?? ''
  metaModal.error       = ''
  metaModal.open        = true
}

async function submitMeta() {
  if (!metaModal.title.trim()) {
    metaModal.error = 'Judul kuesioner wajib diisi.'
    return
  }
  metaModal.error = ''
  try {
    await store.update(qId.value, {
      title:       metaModal.title.trim(),
      description: metaModal.description.trim() || null,
    })
    metaModal.open = false
  } catch {
    metaModal.error = store.error ?? 'Gagal menyimpan.'
  }
}

// ─── Publish & Archive ────────────────────────────────────────────────────────
async function handlePublish() {
  if (store.totalQuestions === 0) return
  try {
    await store.publish(qId.value)
  } catch {
    // store.error menampung pesan
  }
}

async function handleArchive() {
  try {
    await store.archive(qId.value)
  } catch {
    // store.error menampung pesan
  }
}

// ─── SortableJS drag-drop ─────────────────────────────────────────────────────
const sortableRefs      = ref({})
const sortableInstances = {}

// Inisialisasi Sortable ketika el terpasang atau pertanyaan berubah
watch([() => store.sections, activeSection], async () => {
  await nextTick()
  initSortables()
}, { flush: 'post' })

function initSortables() {
  for (const sec of store.sections) {
    const el = sortableRefs.value[sec.id]
    if (!el || sortableInstances[sec.id]) continue

    sortableInstances[sec.id] = Sortable.create(el, {
      animation:    150,
      handle:       '[title="Seret untuk mengurutkan"]',
      ghostClass:   'opacity-40',
      chosenClass:  'ring-2 ring-teal-400',
      dragClass:    'shadow-lg',
      onEnd: async (evt) => {
        const sectionId = Number(evt.from.dataset.sectionId)
        const list      = (questionsBySectionLocal.value[sectionId] ?? []).slice()

        // Reorder lokal sesuai drag
        const moved = list.splice(evt.oldIndex, 1)[0]
        list.splice(evt.newIndex, 0, moved)

        await store.reorderQuestions(qId.value, list.map(q => q.id))
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

// ─── Lifecycle ────────────────────────────────────────────────────────────────
async function loadData() {
  store.clearBuilder()
  try {
    await store.fetchById(qId.value)
  } catch {
    // store.error muncul di template
  }
}

onMounted(loadData)

onUnmounted(() => {
  destroySortables()
  store.clearBuilder()
})
</script>
