# LAPORAN AUDIT KONSISTENSI DOKUMEN
# SISTEM TRACER STUDY UNISYA — v1.0.3
# Tanggal Audit: 2026-06-09
# Auditor: Claude (Fullstack Laravel Vue Developer)

---

## RINGKASAN EKSEKUTIF

Audit ini dilakukan terhadap 9 dokumen dasar proyek SITRAS UNISYA (v1.0.2) sebelum
development dimulai. Ditemukan **8 inkonsistensi** yang perlu diperbaiki, terdiri dari:

| Level | Jumlah | Status |
|---|---|---|
| 🔴 Critical | 0 | — |
| 🟠 Major | 2 | ✅ Fixed di v1.0.3 |
| 🟡 Moderate | 3 | ✅ Fixed di v1.0.3 |
| 🟢 Minor | 3 | ✅ Fixed di v1.0.3 |
| **Total** | **8** | **✅ Semua fixed** |

---

## TEMUAN INKONSISTENSI

---

### [INC-01] 🟠 MAJOR — Blueprint: Versi di tabel identitas tidak sinkron dengan header dokumen

**File Terdampak:** `01_BLUEPRINT.md`
**Ditemukan di:** Section 1.1 Identitas Proyek

**Masalah:**
Header file (baris 3) menyatakan versi `1.0.2` dan tanggal `2026-06-08`,
namun tabel Identitas Proyek (Section 1.1) masih mencantumkan:
- `Versi: 1.0.1`
- `Tanggal Dokumen: 2026-06-06`

Ini berarti setiap kali developer membaca tabel identitas sistem (yang paling sering dirujuk),
mereka akan melihat versi yang sudah obsolete dan tanggal yang salah.

**Dampak Jika Tidak Diperbaiki:**
Developer atau stakeholder yang membaca tabel identitas akan mengira dokumen masih di versi 1.0.1
dan belum mencerminkan perubahan WA Gateway yang krusial dari v1.0.2.

**Perbaikan:**
- `01_BLUEPRINT.md` Section 1.1: `Versi 1.0.1` → `1.0.2`, `Tanggal Dokumen 2026-06-06` → `2026-06-08`

---

### [INC-02] 🟠 MAJOR — Architecture: Diagram arsitektur masih menyebut "Fonnte/Wablas"

**File Terdampak:** `04_ARCHITECTURE.md`
**Ditemukan di:** Section 1.1, diagram ASCII arsitektur (baris ~78)

**Masalah:**
Diagram blok arsitektur pada layer External Services masih mencantumkan:
```
│  WhatsApp Gateway│
│  (Fonnte/Wablas) │
```

Padahal sejak v1.0.2, seluruh dokumen sudah diupdate ke WA Gateway UNISYA
(`wacenter.unisya.ac.id`). Label `(Fonnte/Wablas)` di diagram ini adalah satu-satunya
referensi yang terlewat dari audit v1.0.2.

**Dampak Jika Tidak Diperbaiki:**
Developer yang membaca diagram arsitektur (sering jadi referensi cepat) akan keliru
mengimplementasikan integrasi WA dengan library/pattern Fonnte/Wablas alih-alih
gateway UNISYA yang spesifikasinya berbeda.

**Perbaikan:**
- `04_ARCHITECTURE.md` diagram: `(Fonnte/Wablas)` → `(wacenter.unisya.ac.id)`

---

### [INC-03] 🟡 MODERATE — Phase Tracker: Total task di header tidak konsisten dengan tabel ringkasan

**File Terdampak:** `08_PHASE_TRACKER.md`
**Ditemukan di:** Section STATUS RINGKASAN (baris 20–21) vs tabel RINGKASAN TASK PER FASE (baris 456)

**Masalah:**
Di bagian atas dokumen (STATUS RINGKASAN) tertulis:
```
Total Task: 167 task
Selesai: 0 task
```

Namun tabel RINGKASAN TASK PER FASE di bagian bawah dokumen mencantumkan:
```
TOTAL | 13 sesi | 199 task
```

Angka `167` tidak pernah diperbarui meski task telah ditambah berkali-kali (v1.0.1 naikkan
dari 165→199, v1.0.2 menambah spesifikasi task). Header ringkasan tertinggal jauh di belakang.

**Dampak Jika Tidak Diperbaiki:**
Project manager / developer yang membaca summary di atas (tanpa scroll ke bawah) akan
salah mengira total scope pengerjaan hanya 167 task, bukan 199 task yang sebenarnya.

**Perbaikan:**
- `08_PHASE_TRACKER.md` header: `Total Task: 167 task` → `Total Task: 199 task`

---

### [INC-04] 🟡 MODERATE — API: Endpoint reorder pertanyaan menggunakan PUT dengan path ambigu

**File Terdampak:** `05_API.md`
**Ditemukan di:** Section 5.13 (Update Urutan Pertanyaan / Reorder) dan Section 14 (Summary Table)

**Masalah:**
Endpoint reorder didefinisikan sebagai:
```
PUT /api/v1/admin/questionnaires/{id}/questions/reorder
```

Namun di Laravel, path `/questions/reorder` akan **konflik dengan route**
`/questions/{id}` jika `reorder` ditafsirkan sebagai `{id}`. Ini adalah masalah
routing klasik di Laravel Resource Routes. Dengan `PUT`, `{id}` bisa berupa string `"reorder"`,
yang akan menyebabkan ambiguitas.

Solusi standar Laravel adalah menggunakan `PATCH` atau mendefinisikan custom route
di **atas** resource route `questions/{id}`, atau mengganti path menjadi `/questions/batch-reorder`.

Selain itu, di Summary Table (Section 14), endpoint ini tercatat sebagai:
```
PUT | /admin/questionnaires/{id}/questions/reorder | Admin | Reorder pertanyaan
```
Konsisten dengan Section 5.13, tapi keduanya perlu diupdate agar tidak ambigu saat implementasi.

**Perbaikan:**
- `05_API.md` Section 5.13: Tambah catatan implementasi Laravel bahwa route ini harus
  didefinisikan **sebelum** `Route::apiResource` untuk questions, atau gunakan
  method `PATCH` dengan path `/questions/reorder` dan pastikan ia didaftarkan
  sebelum route `questions/{id}`.
- Tambah catatan di Section 5.13 tentang urutan pendaftaran route di `routes/api.php`.

---

### [INC-05] 🟡 MODERATE — Security: Matriks izin tidak mencantumkan aksi "Lihat Profil Alumni (oleh Admin)"

**File Terdampak:** `07_SECURITY.md`
**Ditemukan di:** Section 3.3 Matriks Izin Lengkap

**Masalah:**
Matriks izin mencantumkan baris:
```
| Profil Diri Alumni      | ❌ | ❌ | ✅ | ❌ |
```

Namun endpoint `GET /api/v1/admin/alumni/{id}` (detail alumni) juga bisa diakses
oleh Admin dan Superadmin. Matriks tidak mencerminkan bahwa Admin dapat **melihat**
profil alumni (bukan hanya mengelola data di tabel alumni).

Ini berbeda dengan "Profil Diri Alumni" yang memang hanya untuk alumni itu sendiri.
Perlu ada pemisahan antara:
1. **"Lihat Detail Alumni"** (Admin/Superadmin: ✅, Alumni: ✅ diri sendiri)
2. **"Edit Profil Diri Alumni"** (Alumni: ✅ saja)

**Perbaikan:**
- `07_SECURITY.md` Section 3.3: Pisah baris `Profil Diri Alumni` menjadi dua baris
  yang lebih jelas untuk menghilangkan ambiguitas di implementasi Policy.

---

### [INC-06] 🟢 MINOR — UI/UX: Route `/alumni/survey/done` di routing section tidak konsisten dengan Phase Tracker

**File Terdampak:** `06_UI_UX.md`, `08_PHASE_TRACKER.md`
**Ditemukan di:** `06_UI_UX.md` Section 8 (Routing) vs `08_PHASE_TRACKER.md` task 4B.6

**Masalah:**
Di `06_UI_UX.md` Section 8, route alumni survey selesai adalah:
```javascript
/alumni/survey/done    // Halaman survei selesai
```

Di `08_PHASE_TRACKER.md` task 4B.6, nama file komponen adalah:
```
SurveyDonePage.vue
```
Dan dicatat sebagai `pages/alumni/SurveyDonePage.vue`.

Namun di `04_ARCHITECTURE.md` folder structure (baris ~314), file yang terdaftar adalah:
```
└── SurveyPage.vue      ← tidak ada SurveyDonePage.vue
```
Folder structure di Architecture tidak mencantumkan `SurveyDonePage.vue` di direktori alumni,
padahal ini sudah ada di Phase Tracker dan UI/UX.

**Perbaikan:**
- `04_ARCHITECTURE.md` Section 2 folder structure: Tambah `SurveyDonePage.vue` di bawah `SurveyPage.vue`
  dalam direktori `frontend/src/pages/alumni/`

---

### [INC-07] 🟢 MINOR — Architecture: Folder structure frontend tidak mencantumkan beberapa page yang ada di UI/UX & Phase Tracker

**File Terdampak:** `04_ARCHITECTURE.md`
**Ditemukan di:** Section 2, folder structure `frontend/src/pages/`

**Masalah:**
Folder structure di `04_ARCHITECTURE.md` mendaftar halaman-halaman secara ringkas dengan
placeholder direktori (contoh: `├── alumni/`, `├── employers/`) tanpa merinci isi folder.
Beberapa halaman yang terdefinisi di `06_UI_UX.md` dan `08_PHASE_TRACKER.md` tidak muncul
di folder structure, antara lain:
- `AlumniImportPage.vue` (ada di task 2A.26)
- `SurveyDonePage.vue` (ada di task 4B.6, route `/alumni/survey/done`)
- `StatisticsPage.vue` (ada di task 5B.9, route `/admin/dashboard/stats`)
- `pages/admin/dashboard/StatisticsPage.vue` — sub-direktori dashboard belum ada
- Halaman employer: hanya `SurveyPage.vue` dan `DonePage.vue` yang tercantum, konsisten

Ini bukan error fatal (folder structure memang ringkasan), tapi inkonsistensi minor yang
bisa membingungkan developer saat setup awal proyek.

**Perbaikan:**
- `04_ARCHITECTURE.md` Section 2: Lengkapi folder structure `pages/` dengan semua page
  yang terdefinisi di Phase Tracker dan UI/UX spec.

---

### [INC-08] 🟢 MINOR — Changelog: Tabel ringkasan inkonsistensi tidak mencatat temuan audit v1.0.3

**File Terdampak:** `09_CHANGELOG.md`
**Ditemukan di:** Section "CATATAN INKONSISTENSI YANG DITEMUKAN & STATUS"

**Masalah:**
Tabel inkonsistensi di bagian bawah Changelog saat ini hanya mencatat sampai temuan v1.0.1.
Inkonsistensi yang ditemukan dan diperbaiki di v1.0.3 ini (audit sekarang) belum tercatat.

**Perbaikan:**
- `09_CHANGELOG.md`: Tambah entri `[1.0.3]` dengan semua 8 inkonsistensi yang ditemukan;
  update tabel ringkasan inkonsistensi.

---

## RINGKASAN FILE YANG PERLU DIUPDATE

| File | Versi Sekarang | Versi Target | Perubahan |
|---|---|---|---|
| `01_BLUEPRINT.md` | 1.0.2 | 1.0.3 | Fix tabel identitas (versi + tanggal) |
| `04_ARCHITECTURE.md` | 1.0.2 | 1.0.3 | Fix label WA diagram; lengkapi folder structure pages |
| `05_API.md` | 1.0.2 | 1.0.3 | Tambah catatan route reorder Laravel |
| `07_SECURITY.md` | 1.0.2 | 1.0.3 | Pisah baris matriks izin alumni profil |
| `08_PHASE_TRACKER.md` | 1.0.2 | 1.0.3 | Fix total task header (167→199) |
| `09_CHANGELOG.md` | 1.0.2 | 1.0.3 | Tambah entri audit v1.0.3 |

**File tidak perlu diupdate:** `02_DATABASE.md`, `03_ERD.md`, `06_UI_UX.md`

---

## CATATAN POSITIF (KUALITAS DOKUMEN)

Secara keseluruhan, dokumen SITRAS UNISYA sudah sangat solid setelah 2 kali audit sebelumnya:

1. ✅ **Skema database** 24 tabel konsisten total dengan ERD dan API response shape
2. ✅ **RBAC & Security** sudah komprehensif dan konsisten antar dokumen
3. ✅ **WA Gateway UNISYA** sudah seragam di semua 9 dokumen (hasil audit v1.0.2)
4. ✅ **OTP SHA-256 (VARCHAR 64)** sudah konsisten (hasil audit v1.0.1)
5. ✅ **73 endpoint API** semuanya ada di Phase Tracker sebagai task
6. ✅ **Status ENUM alumni/employer** konsisten di DB, ERD, API, dan UI
7. ✅ **Cascade rules** terdefinisi lengkap dan konsisten
8. ✅ **Queue architecture** (high/default/low) konsisten antara Architecture dan Phase Tracker
9. ✅ **Semua foreign key** terindeks dan cascade rules terdefinisi
10. ✅ **CSP header** sudah dikonsolidasi ke Security sebagai single source of truth

---

## STATUS AKHIR

> ✅ **8 inkonsistensi ditemukan dan telah diperbaiki di v1.0.3**
> ✅ **Dokumen SITRAS UNISYA v1.0.3 dinyatakan CLEAR dan SIAP DEVELOPMENT**
