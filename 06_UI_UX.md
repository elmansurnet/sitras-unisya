# 06_UI_UX.md
# SPESIFIKASI UI/UX — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.2 | Tanggal: 2026-06-08

---

## 1. DESIGN SYSTEM

### 1.1 Identitas Visual
| Atribut | Nilai |
|---|---|
| Nama Sistem | SITRAS UNISYA |
| Tagline | "Menelusuri Jejak, Meraih Mutu" |
| Karakter | Profesional, Terpercaya, Modern, Islami |
| Tema Warna | Hijau teal institusional + aksen emas |

### 1.2 Palet Warna
```css
/* Warna Primer (Teal) */
--color-primary-50:  #f0fdf9;
--color-primary-100: #ccfbef;
--color-primary-200: #99f6e0;
--color-primary-300: #5eead4;
--color-primary-400: #2dd4bf;
--color-primary-500: #14b8a6;  /* Utama */
--color-primary-600: #0d9488;  /* Hover */
--color-primary-700: #0f766e;  /* Active */
--color-primary-800: #115e59;
--color-primary-900: #134e4a;

/* Warna Sekunder (Emas) */
--color-secondary-400: #fbbf24;
--color-secondary-500: #f59e0b;  /* Aksen utama */
--color-secondary-600: #d97706;

/* Warna Netral (Slate) */
--color-gray-50:  #f8fafc;
--color-gray-100: #f1f5f9;
--color-gray-200: #e2e8f0;
--color-gray-300: #cbd5e1;
--color-gray-400: #94a3b8;
--color-gray-500: #64748b;
--color-gray-600: #475569;
--color-gray-700: #334155;
--color-gray-800: #1e293b;
--color-gray-900: #0f172a;

/* Warna Status */
--color-success: #22c55e;
--color-warning: #f59e0b;
--color-danger:  #ef4444;
--color-info:    #3b82f6;

/* Warna Background */
--color-bg-body:    #f8fafc;
--color-bg-card:    #ffffff;
--color-bg-sidebar: #0f172a;
```

### 1.3 Tipografi
```css
/* Font Families */
--font-heading: 'Plus Jakarta Sans', sans-serif;
--font-body:    'Inter', sans-serif;
--font-mono:    'JetBrains Mono', monospace;

/* Font Sizes */
--text-xs:   0.75rem;    /* 12px */
--text-sm:   0.875rem;   /* 14px */
--text-base: 1rem;       /* 16px */
--text-lg:   1.125rem;   /* 18px */
--text-xl:   1.25rem;    /* 20px */
--text-2xl:  1.5rem;     /* 24px */
--text-3xl:  1.875rem;   /* 30px */
--text-4xl:  2.25rem;    /* 36px */

/* Font Weight */
--font-normal:    400;
--font-medium:    500;
--font-semibold:  600;
--font-bold:      700;
--font-extrabold: 800;
```

### 1.4 Spacing & Border Radius
```css
/* Spacing: Mengikuti skala Tailwind (4px base unit) */

/* Border Radius */
--radius-sm:   0.25rem;  /* 4px  */
--radius-md:   0.5rem;   /* 8px  */
--radius-lg:   0.75rem;  /* 12px */
--radius-xl:   1rem;     /* 16px */
--radius-2xl:  1.5rem;   /* 24px */
--radius-full: 9999px;

/* Shadow */
--shadow-sm:   0 1px 2px rgba(0,0,0,0.05);
--shadow-md:   0 4px 6px rgba(0,0,0,0.07);
--shadow-lg:   0 10px 15px rgba(0,0,0,0.1);
--shadow-card: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
```

### 1.5 Komponen Dasar (Design Tokens)

**Tombol:**
```
Primer:    bg-primary-600 text-white hover:bg-primary-700 rounded-lg px-4 py-2
Sekunder:  bg-white border border-gray-300 text-gray-700 hover:bg-gray-50
Bahaya:    bg-red-600 text-white hover:bg-red-700
Teks:      text-primary-600 hover:text-primary-700 bg-transparent
Ukuran SM: px-3 py-1.5 text-sm
Ukuran LG: px-6 py-3 text-base
Disabled:  opacity-50 cursor-not-allowed
```

**Input:**
```
Border:       border border-gray-300 rounded-lg
Focus:        focus:ring-2 focus:ring-primary-500 focus:border-primary-500
Error:        border-red-500 focus:ring-red-500
Placeholder:  text-gray-400
Label:        text-sm font-medium text-gray-700 mb-1
Helper text:  text-xs text-gray-500 mt-1
Error text:   text-xs text-red-600 mt-1
```

**Badge/Pill — Status Survey Alumni:**
```
belum_disurvei: bg-gray-100    text-gray-600   → "Belum Disurvei"
terkirim:       bg-blue-100    text-blue-700   → "Terkirim"
sedang_mengisi: bg-yellow-100  text-yellow-700 → "Sedang Mengisi"
selesai:        bg-green-100   text-green-700  → "Selesai"
```

**Badge/Pill — Status Survey Employer:**
```
belum_disurvei: bg-gray-100    text-gray-600   → "Belum Disurvei"
terkirim:       bg-blue-100    text-blue-700   → "Terkirim"
selesai:        bg-green-100   text-green-700  → "Selesai"
```

**Badge/Pill — Status Umum:**
```
Aktif:     bg-emerald-100 text-emerald-700
Nonaktif:  bg-red-100     text-red-700
Draft:     bg-gray-100    text-gray-600
```

**Kartu (Card):**
```
bg-white rounded-xl shadow-card border border-gray-100 p-6
```

---

## 2. LAYOUT SISTEM

### 2.1 Layout Admin (Superadmin/Admin)
```
┌────────────────────────────────────────────────────────────┐
│  TOPBAR                                                    │
│  [Logo SITRAS UNISYA]    [Breadcrumb]    [Notif] [Avatar] │
├──────────────┬─────────────────────────────────────────────┤
│              │                                             │
│   SIDEBAR    │              KONTEN UTAMA                   │
│   (240px)    │                                             │
│              │   ┌─────────────────────────────────────┐  │
│  Dashboard   │   │  PAGE HEADER                        │  │
│  Alumni      │   │  Judul Halaman + Aksi (Tombol)      │  │
│  Employer    │   └─────────────────────────────────────┘  │
│  Kuesioner   │                                             │
│  Survei      │   ┌─────────────────────────────────────┐  │
│  Laporan     │   │  KONTEN                             │  │
│  Notifikasi  │   │  (Tabel / Form / Chart / dll.)      │  │
│  Pengaturan  │   └─────────────────────────────────────┘  │
│              │                                             │
└──────────────┴─────────────────────────────────────────────┘
```

**Sidebar Items (lengkap dengan sub-menu):**
```
▸ Dashboard
    ▸ Ringkasan
    ▸ Statistik Ketenagakerjaan

▸ Data Alumni
    ▸ Daftar Alumni
    ▸ Import Alumni
    ▸ Riwayat Kerja Alumni

▸ Data Employer
    ▸ Daftar Employer

▸ Kuesioner
    ▸ Kuesioner Alumni
    ▸ Kuesioner Employer

▸ Periode Survei
    ▸ Daftar Periode
    ▸ Kirim Undangan

▸ Laporan
    ▸ Generate Laporan

▸ Notifikasi
    ▸ Template Pesan
    ▸ Log Pengiriman

▸ Pengaturan                     ← Hanya terlihat untuk superadmin & admin
    ▸ Program Studi
    ▸ Angkatan
    ▸ Pengguna Admin              ← Hanya superadmin
    ▸ Konfigurasi Sistem          ← Hanya superadmin
    ▸ Audit Log                   ← Hanya superadmin
```

### 2.2 Layout Alumni
```
┌────────────────────────────────────────────────────────────┐
│  TOPBAR: [Logo]  [Menu: Beranda|Profil|Survei]  [Avatar]  │
├────────────────────────────────────────────────────────────┤
│                                                            │
│                  KONTEN ALUMNI                             │
│  ┌────────────────────────────────────────────────────┐   │
│  │  Dashboard Alumni: status survei, info akun        │   │
│  └────────────────────────────────────────────────────┘   │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

**Menu Alumni:**
- Beranda (dashboard status survei)
- Profil Saya (data pribadi & akademik)
- Riwayat Pekerjaan
- Isi Survei

### 2.3 Layout Employer (Minimal)
```
┌────────────────────────────────────────────────────────────┐
│  HEADER: [Logo UNISYA]                   [Nama Perusahaan] │
├────────────────────────────────────────────────────────────┤
│                                                            │
│               FORM SURVEI EMPLOYER                         │
│  ┌────────────────────────────────────────────────────┐   │
│  │  Progress bar + Nomor halaman (seksi X dari Y)     │   │
│  │  Pertanyaan 1...                                   │   │
│  │  Pertanyaan 2...                                   │   │
│  │  [← Sebelumnya]          [Selanjutnya → / Kirim]  │   │
│  └────────────────────────────────────────────────────┘   │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### 2.4 Layout Autentikasi
```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│     ┌──────────────────┐   ┌────────────────────────────┐  │
│     │  PANEL KIRI      │   │  PANEL KANAN               │  │
│     │  (50% layar)     │   │  (50% layar)               │  │
│     │                  │   │                            │  │
│     │  Ilustrasi /     │   │  [Logo UNISYA]             │  │
│     │  Background      │   │  "Selamat Datang di        │  │
│     │  Pattern UNISYA  │   │   SITRAS UNISYA"           │  │
│     │                  │   │                            │  │
│     │  Quote islami    │   │  [Form Input]              │  │
│     │  + Logo          │   │  [Tombol Login/OTP]        │  │
│     │                  │   │                            │  │
│     └──────────────────┘   └────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```
> Di mobile (< lg): hanya panel kanan yang ditampilkan; panel kiri disembunyikan.

---

## 3. HALAMAN PER MODUL

### 3.1 Halaman Dashboard Admin
**Komponen:**
- 4 Kartu KPI Utama:
  - Total Alumni (ikon users, link ke /admin/alumni)
  - Tingkat Respons Survei aktif (progress ring, persentase)
  - Total Employer (ikon building, link ke /admin/employers)
  - Alumni Sudah Bekerja (persentase dari data survei)
- Grafik Line Chart: Tren respons survei per bulan (12 bulan terakhir)
- Grafik Donut Chart: Status pekerjaan alumni (bekerja/wirausaha/belum/lanjut studi)
- Grafik Bar Chart: Top 10 industri penyerap alumni terbanyak
- Tabel: 5 aktivitas sistem terbaru (dari audit_logs)
- Kartu: Periode survei aktif + tombol aksi cepat (Kirim Undangan, Lihat Progress)

---

### 3.2 Halaman Daftar Alumni
**Komponen:**
- Filter Bar:
  - Input pencarian (NIM, nama, email)
  - Dropdown: Program Studi
  - Dropdown: Angkatan
  - Dropdown: Status Survei (belum_disurvei | terkirim | sedang_mengisi | selesai)
  - Tombol [Filter] & [Reset]
- Tabel Data:
  - Kolom: No, NIM, Nama, Prodi, Angkatan, IPK, Status Survei, Aksi
  - Sortable columns (klik header)
  - Row selection (checkbox) untuk aksi massal
- Paginasi (per_page: 15/25/50)
- Tombol Aksi Header: [+ Tambah Alumni] [Import Excel] [Export Excel]
- Aksi per baris: [Lihat Detail] [Edit] [Kirim Undangan] [Hapus] *(Hapus hanya superadmin)*
- Modal konfirmasi hapus

---

### 3.3 Halaman Form Alumni (Tambah/Edit)
**Tabs Form (5 tab):**
1. **Data Pribadi:** NIK, nama lengkap, gender, tempat lahir, tanggal lahir
2. **Data Akademik:** NIM, prodi, angkatan, IPK, judul tugas akhir, predikat kelulusan
3. **Alamat:** Jalan, kelurahan, kecamatan, kota, provinsi, kode pos, (opsional: koordinat peta)
4. **Kontak:** Nomor WA, email, URL LinkedIn
5. **Foto:** Upload foto profil (preview sebelum simpan)

**Validasi real-time:** feedback border merah + pesan error langsung di bawah field

---

### 3.4 Halaman Builder Kuesioner
**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│  HEADER FORM: [Judul] [Deskripsi] [Tipe] [Estimasi waktu]  │
│  [Status: Draft]  [Simpan Draft]  [Publikasikan]            │
├──────────────────────┬──────────────────────────────────────┤
│  PANEL KIRI          │  PANEL KANAN                         │
│  (Daftar Pertanyaan) │  (Preview Responden)                 │
│                      │                                      │
│  ┌──────────────┐    │  ┌──────────────────────────────┐   │
│  │ Seksi 1      │    │  │  Tampilan seperti yang akan  │   │
│  │  Q1 [✏️] [🗑️]│    │  │  dilihat responden saat     │   │
│  │  Q2 [✏️] [🗑️]│    │  │  mengisi survei              │   │
│  │  [+ Pertanyaan]    │  └──────────────────────────────┘   │
│  └──────────────┘    │                                      │
│  [+ Seksi Baru]      │                                      │
├──────────────────────┴──────────────────────────────────────┤
│  TOOLBAR TIPE PERTANYAAN:                                    │
│  [Teks] [Paragraf] [Radio] [Checkbox] [Dropdown]            │
│  [Likert] [Rating ★] [Tanggal] [Upload] [Angka]             │
└─────────────────────────────────────────────────────────────┘
```

---

### 3.5 Halaman Dashboard Alumni
**Komponen:**
- Banner sambutan dengan nama alumni (contoh: "Assalamu'alaikum, Ahmad Fauzi")
- Kartu Status Survei (CTA adaptif berdasarkan survey_status):
  - `belum_disurvei` → tombol [Mulai Isi Survei] + ilustrasi
  - `terkirim` → tombol [Mulai Isi Survei] + keterangan "Undangan telah dikirim"
  - `sedang_mengisi` → tombol [Lanjutkan Survei] + progress bar + persentase selesai
  - `selesai` → badge sukses ✅ + tanggal submit + ucapan terima kasih
- Kartu Kelengkapan Profil (persentase + list field belum diisi + tombol Lengkapi Profil)
- Kartu Riwayat Pekerjaan (ringkasan pekerjaan terkini + tombol Kelola)

---

### 3.6 Halaman Pengisian Survei (Alumni & Employer)
**Tampilan:**
- Header: Nama survei + logo UNISYA
- Progress bar (X dari Y seksi) + persentase penyelesaian
- Area pertanyaan (satu seksi per halaman jika is_paginated = true)
- Komponen per tipe pertanyaan:
  - **text:** input field dengan counter karakter
  - **textarea:** area teks besar dengan counter karakter
  - **radio:** tombol bulat dengan label, satu pilihan
  - **checkbox:** kotak centang dengan label, multi pilihan
  - **select:** dropdown pilihan
  - **likert:** skala 1–5 dengan label ekstrem (Sangat Tidak Setuju ↔ Sangat Setuju), pill button
  - **rating:** bintang interaktif 1–5 yang bisa diklik
  - **date:** date picker dengan format DD/MM/YYYY
  - **file:** area drag-and-drop upload dengan preview nama file
  - **number:** input numerik dengan validation min/max
- Navigasi bawah halaman:
  - `[← Sebelumnya]`  `[Simpan Draft]`  `[Selanjutnya →]`
  - Tombol `[Kirim Survei]` hanya tampil di halaman/seksi terakhir
- Modal konfirmasi sebelum submit final

---

### 3.7 Halaman Periode Survei
**Komponen:**
- Tabel daftar periode (nama, tahun, tanggal mulai/akhir, status, jumlah alumni, tingkat respons)
- Tombol [+ Buat Periode Baru]
- Per baris: [Detail] [Edit] [Aktivasi/Tutup] [Kirim Undangan]
- Halaman detail periode:
  - Statistik progres (total alumni sasaran, terkirim, sedang mengisi, selesai)
  - Progress bar response rate
  - Tombol [Kirim Undangan Massal] dengan pilihan:
    - Channel: Email / WhatsApp / Keduanya
    - Kuesioner: (dropdown kuesioner aktif)
    - Filter: Belum Disurvei / Sudah Terkirim (reminder)

---

### 3.8 Halaman Notifikasi

#### 3.8.1 Template Pesan
- Tabel template (nama, channel, event, status aktif)
- Tombol [+ Buat Template]
- Per baris: [Lihat] [Edit] [Hapus]
- Form template:
  - Nama, Channel (email/whatsapp), Event trigger
  - Subject (khusus email)
  - Body editor dengan highlight variabel `{{variable_name}}`
  - Preview rendered template dengan data contoh
  - Daftar variabel tersedia (sebagai referensi)

#### 3.8.2 Log Pengiriman
- Filter: type, status, recipient_type, date_from, date_to
- Tabel log (channel, penerima, status, waktu kirim)
- Status badge: Pending / Terkirim / Gagal / Delivered
- Klik baris: modal detail (body pesan + provider response jika error)

---

### 3.9 Halaman Laporan
**Komponen:**
- Form filter laporan:
  - Periode survei (required)
  - Program studi (opsional, default: semua)
  - Angkatan (opsional, default: semua)
  - Format output: PDF / Excel
- Tombol [Generate Laporan]
- Loading indicator + estimasi waktu selama proses generate
- Auto-download setelah selesai
- Tabel laporan yang sudah pernah dibuat (nama file, ukuran, tanggal, tombol Download)

---

### 3.10 Halaman Konfigurasi Sistem
**Tabs (hanya superadmin):**
1. **Umum:** Nama universitas, logo, tagline, alamat institusi
2. **Email (SMTP):** Host, port, username, password (masked), encryption, from name/email, tombol Test Kirim
3. **WhatsApp Gateway:** URL API (`wa_gateway_url`), API Key (`wa_api_key`, masked), Nomor Pengirim (`wa_sender`), tombol Test Kirim
4. **Keamanan:** Maks percobaan login, durasi lockout (menit), masa berlaku OTP (menit)
5. **Notifikasi:** Toggle aktif/nonaktif per event notifikasi

---

## 4. KOMPONEN REUSABLE

### 4.1 DataTable
```vue
<DataTable
  :columns="columns"
  :data="alumni"
  :loading="loading"
  :pagination="pagination"
  :selectable="true"
  empty-text="Belum ada data alumni."
  @sort="handleSort"
  @page-change="handlePageChange"
  @row-select="handleRowSelect"
/>
```

### 4.2 FilterBar
```vue
<FilterBar
  :filters="filterConfig"
  v-model="activeFilters"
  @filter="applyFilter"
  @reset="resetFilter"
/>
```

### 4.3 ConfirmModal
```vue
<ConfirmModal
  v-model="showDelete"
  title="Hapus Alumni"
  message="Apakah Anda yakin ingin menghapus data alumni ini? Tindakan ini tidak dapat dibatalkan."
  confirm-text="Ya, Hapus"
  confirm-variant="danger"
  @confirm="deleteAlumni"
/>
```

### 4.4 Toast Notification
```javascript
// Digunakan via composable useToast
const { showToast } = useToast()
showToast('Data berhasil disimpan', 'success')   // auto-dismiss 4 detik
showToast('Terjadi kesalahan', 'error')
showToast('Peringatan!', 'warning')
showToast('Informasi', 'info')
```

### 4.5 FileUpload
```vue
<FileUpload
  accept=".xlsx,.csv"
  :max-size-kb="10240"
  label="Upload File Excel"
  hint="Format: .xlsx atau .csv, maks. 10MB"
  @upload="handleUpload"
/>
```

### 4.6 Komponen Grafik (ApexCharts)
```vue
<!-- Bar Chart: Top Industri -->
<BarChart :series="industrySeries" :categories="industryLabels" height="350" />

<!-- Donut Chart: Status Pekerjaan -->
<DonutChart :series="employmentSeries" :labels="employmentLabels" />

<!-- Line Chart: Tren Respons -->
<LineChart :series="trendSeries" :categories="monthLabels" />
```

### 4.7 AlumniMap (Leaflet.js)
```vue
<!-- Peta sebaran alumni berbasis koordinat provinsi/kota -->
<AlumniMap
  :markers="mapData"
  center="[-2.5, 118]"
  :zoom="5"
/>
```

### 4.8 SurveyProgressBar
```vue
<SurveyProgressBar
  :current-section="2"
  :total-sections="4"
  :percentage="55"
/>
```

### 4.9 QuestionRenderer
```vue
<!-- Merender pertanyaan berdasarkan question_type secara dinamis -->
<QuestionRenderer
  :question="question"
  v-model="answers[question.id]"
  :show-error="hasError(question.id)"
/>
```

---

## 5. RESPONSIVITAS

| Breakpoint | Lebar | Perilaku |
|---|---|---|
| `sm` | ≥ 640px | Mobile landscape |
| `md` | ≥ 768px | Tablet |
| `lg` | ≥ 1024px | Desktop (sidebar muncul permanen) |
| `xl` | ≥ 1280px | Desktop lebar |
| `2xl` | ≥ 1536px | Layar besar |

**Strategi Mobile (< lg):**
- Sidebar berubah menjadi drawer/overlay (slide dari kiri, background overlay)
- Hamburger button di topbar untuk toggle sidebar
- Tabel besar di-scroll horizontal di mobile
- Form multi-kolom (2 col) menjadi satu kolom di mobile
- Grafik responsif menggunakan ApexCharts `responsive` config
- KPI cards: 2 kolom di mobile, 4 kolom di desktop

---

## 6. AKSESIBILITAS (A11Y)

- Semua form input memiliki `<label>` eksplisit atau `aria-label`
- Warna kontras minimal WCAG AA (4.5:1 untuk teks normal, 3:1 untuk teks besar)
- Focus ring terlihat pada semua elemen interaktif (`focus:ring-2 focus:ring-primary-500`)
- Loading states menggunakan `aria-busy="true"` dan `aria-live="polite"`
- Error messages dihubungkan ke input via `aria-describedby`
- Tabel menggunakan `<th scope="col">` dan `<th scope="row">` yang benar
- Modal menggunakan `role="dialog"`, `aria-modal="true"`, dan trap focus
- Badge/status chip menggunakan `role="status"` jika konten dinamis
- Ikon dekoratif menggunakan `aria-hidden="true"`

---

## 7. STATE UI

### 7.1 Loading States
- **Skeleton loader** untuk kartu KPI dan baris tabel (bukan spinner tunggal)
- **Tombol disabled + spinner** saat submit form (cegah double submit)
- **Progress bar** untuk upload file & generate laporan
- **Overlay spinner** untuk aksi modal yang butuh konfirmasi backend

### 7.2 Empty States
- Ilustrasi SVG + pesan deskriptif + tombol aksi utama
- Contoh tabel alumni kosong: "Belum ada data alumni. Mulai dengan menambahkan atau mengimpor data alumni." + [+ Tambah Alumni] [Import Excel]
- Contoh log kosong: "Belum ada log pengiriman notifikasi."

### 7.3 Error States
- Pesan error yang ramah pengguna (bukan kode teknis / stack trace)
- Tombol [Coba Lagi] untuk fetch error jaringan
- Toast notification untuk error non-kritis (update gagal, dll.)
- Halaman error khusus untuk 404, 401, 403

---

## 8. ALUR NAVIGASI (ROUTING)

> **Catatan:** Semua nama route menggunakan format yang konsisten dengan endpoint API.
> Plural form digunakan untuk resource collections.

```javascript
// ─── Auth Routes ───
/login                           // Login admin (email + password)
/login/otp                       // Request OTP alumni
/login/otp/verify                // Verifikasi OTP alumni
/login/employer/:token           // Login employer via token URL
/unauthorized                    // Halaman 403
/not-found                       // Halaman 404

// ─── Admin Routes ───
/admin/dashboard                 // Dashboard ringkasan + chart
/admin/dashboard/stats           // Statistik ketenagakerjaan detail

// Alumni
/admin/alumni                    // Daftar alumni + filter + tabel
/admin/alumni/create             // Form tambah alumni baru
/admin/alumni/:id                // Detail alumni (read-only)
/admin/alumni/:id/edit           // Form edit alumni

// Employer
/admin/employers                 // Daftar employer
/admin/employers/create          // Form tambah employer
/admin/employers/:id             // Detail employer
/admin/employers/:id/edit        // Form edit employer

// Kuesioner
/admin/questionnaires            // Daftar kuesioner (alumni + employer)
/admin/questionnaires/create     // Form buat kuesioner baru
/admin/questionnaires/:id/builder // Builder editor kuesioner
/admin/questionnaires/:id/preview // Preview tampilan responden

// Periode Survei
/admin/survey-periods            // Daftar periode survei
/admin/survey-periods/create     // Form buat periode baru
/admin/survey-periods/:id        // Detail periode + progress + kirim undangan

// Laporan
/admin/reports                   // Generate laporan + daftar laporan tersimpan

// Notifikasi
/admin/notifications/templates   // Daftar + kelola template notifikasi
/admin/notifications/logs        // Log pengiriman notifikasi

// Pengaturan (superadmin only)
/admin/settings                  // Konfigurasi sistem (tabs: umum, smtp, wa, keamanan)
/admin/users                     // Manajemen pengguna admin
/admin/audit-logs                // Audit log aktivitas sistem

// ─── Alumni Routes ───
/alumni/dashboard                // Dashboard status survei
/alumni/profile                  // Lihat profil diri sendiri
/alumni/profile/edit             // Form edit profil
/alumni/work-histories           // Daftar + kelola riwayat pekerjaan
/alumni/survey                   // Pengisian survei (multi-step)
/alumni/survey/done              // Halaman survei selesai

// ─── Employer Routes ───
/employer/survey                 // Form survei employer (akses via token link)
/employer/done                   // Halaman survei employer selesai
```

---

## 9. MICROINTERACTIONS & ANIMASI

- **Sidebar:** slide-in dari kiri dengan `ease-out` (200ms) saat toggle di mobile
- **Drawer overlay:** fade-in background overlay (150ms)
- **Modal:** fade + scale dari tengah (`scale-95 → scale-100`, 150ms)
- **Toast:** slide-in dari kanan atas, auto-dismiss setelah 4 detik, slide-out
- **Tombol:** `scale(0.97)` on press (active state)
- **Input focus:** border + ring transition 150ms `ease-in-out`
- **Tabel row hover:** `bg-gray-50` transition 100ms
- **Progress bar survei:** smooth `width` transition saat maju seksi
- **Chart load:** ApexCharts built-in animation (easing: easeinout, 500ms)
- **Badge status update:** fade transition saat nilai berubah
- **Skeleton loader:** shimmer animation (pulse)

---

## 10. HALAMAN KHUSUS

### 10.1 Halaman 404 (Not Found)
- Ilustrasi universitas bergaya
- Heading: "Halaman Tidak Ditemukan"
- Deskripsi: "Maaf, halaman yang Anda cari tidak ada."
- Tombol: [Kembali ke Dashboard]

### 10.2 Halaman 401/403 (Unauthorized/Forbidden)
- Heading: "Akses Ditolak"
- Deskripsi: "Anda tidak memiliki izin untuk mengakses halaman ini."
- Tombol: [Kembali ke Halaman Sebelumnya]

### 10.3 Halaman Maintenance
- Estimasi waktu selesai (jika tersedia)
- Kontak admin (email/WA)
- Pesan: "Sistem sedang dalam pemeliharaan."

### 10.4 Halaman Survei Selesai (Alumni & Employer)
- Animasi konfetti/sukses (CSS animation)
- Heading: "Terima Kasih!"
- Pesan: "Survei Anda telah berhasil dikirim. Kontribusi Anda sangat berarti bagi pengembangan institusi."
- Tanggal & waktu submit
- Tombol: [Kembali ke Dashboard] *(untuk alumni)* atau halaman statis *(untuk employer)*

### 10.5 Halaman Token Tidak Valid (Employer)
- Pesan: "Link survei tidak valid atau sudah kedaluwarsa."
- Panduan menghubungi admin untuk mendapatkan link baru

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Fix route: `/alumni/work-history` → `/alumni/work-histories` (sesuai API); tambah badge status `terkirim` di alumni & employer; tambah halaman Notifikasi (3.8); tambah halaman Periode Survei (3.7); perjelas sidebar menu role-based; tambah komponen AlumniMap (4.7), SurveyProgressBar (4.8), QuestionRenderer (4.9); tambah route `/employer/survey/done`; tambah halaman Token Tidak Valid (10.5) |
| 1.0.2 | 2026-06-08 | Update tab WhatsApp Gateway (Section 3.10): label field dari "token" → "API Key (`wa_api_key`)", tambah keterangan key name `wa_gateway_url` dan `wa_sender` agar konsisten dengan system_settings |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
