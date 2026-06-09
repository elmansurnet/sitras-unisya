# 01_BLUEPRINT.md
# SISTEM TRACER STUDY — UNIVERSITAS ISLAM SYARIFUDDIN (UNISYA)
# Versi: 1.0.3 | Tanggal: 2026-06-09 | Status: DISETUJUI

---

## 1. GAMBARAN UMUM SISTEM

### 1.1 Identitas Proyek
| Atribut | Nilai |
|---|---|
| Nama Sistem | Sistem Tracer Study UNISYA |
| Nama Singkat | SITRAS-UNISYA |
| Institusi | Universitas Islam Syarifuddin |
| Versi | 1.0.3 |
| Tanggal Dokumen | 2026-06-09 |
| Arsitektur | Monolitik Enterprise |
| Bahasa Antarmuka | Bahasa Indonesia |

### 1.2 Latar Belakang
Tracer Study adalah kegiatan penelusuran alumni yang dilakukan oleh perguruan tinggi untuk mengetahui kondisi dan situasi lulusan setelah mereka meninggalkan bangku kuliah. Hasil Tracer Study digunakan untuk:
- Evaluasi relevansi kurikulum terhadap kebutuhan industri
- Pemenuhan persyaratan akreditasi BAN-PT / LAM
- Peningkatan kualitas layanan pendidikan
- Pemetaan serapan tenaga kerja alumni

### 1.3 Tujuan Sistem
1. Membangun database alumni yang terpusat dan terstruktur
2. Melacak status ketenagakerjaan alumni secara berkala
3. Mengelola data pengguna/pemberi kerja alumni (employer)
4. Menyediakan sistem kuesioner dinamis untuk survei alumni dan employer
5. Menghasilkan laporan statistik dan analitik berbasis data
6. Mengintegrasikan notifikasi via WhatsApp dan Email
7. Menyediakan dashboard eksekutif untuk pengambilan keputusan

---

## 2. AKTOR SISTEM

> **Catatan:** Sistem memiliki 4 role pengguna: `superadmin`, `admin`, `alumni`, `employer`.
> Superadmin dan Admin keduanya adalah staf institusi, namun dengan cakupan izin berbeda.
> Perbedaan detail izin didokumentasikan di 07_SECURITY.md (Matriks Izin).

### 2.1 Superadmin
**Deskripsi:** Pengelola sistem tertinggi dari pihak universitas (Kepala Biro/Unit Tracer Study).

**Hak Akses:**
- Semua hak akses Admin
- Hapus data permanen (alumni, employer)
- Manajemen pengguna sistem (tambah/nonaktifkan akun admin)
- Konfigurasi sistem lengkap (SMTP, WA Gateway, pengaturan umum)
- Melihat dan mengekspor audit log
- Konfigurasi keamanan (batas percobaan login, ekspirasi OTP)

### 2.2 Admin
**Deskripsi:** Staf operasional Tracer Study dari pihak universitas yang mengelola data sehari-hari.

**Hak Akses:**
- Manajemen data alumni (tambah, impor, edit — tanpa hapus permanen)
- Manajemen data employer
- Konfigurasi kuesioner dinamis
- Melihat dan mengekspor laporan
- Mengelola periode survei
- Mengirim notifikasi massal (blast) via WA/Email
- Melihat log notifikasi

### 2.3 Alumni
**Deskripsi:** Lulusan Universitas Islam Syarifuddin yang menjadi objek utama tracer study.

**Hak Akses:**
- Login dengan NIM + OTP via WhatsApp/Email
- Melengkapi profil diri
- Mengisi kuesioner survei alumni
- Menambahkan data pekerjaan / riwayat karir
- Memberikan referensi employer
- Melihat status pengisian survei

### 2.4 Employer (Pengguna Alumni)
**Deskripsi:** Perusahaan/instansi/lembaga yang mempekerjakan alumni UNISYA.

**Hak Akses:**
- Login via secure token (link unik dari email/WA) — tanpa registrasi akun
- Mengisi kuesioner survei employer
- Melihat profil alumni yang direferensikan
- Memperbarui data perusahaan

---

## 3. MODUL SISTEM

### 3.1 Modul Autentikasi & Otorisasi
- Login multi-role (Superadmin, Admin, Alumni, Employer)
- OTP via WhatsApp dan Email
- Secure Token Login untuk Employer (tanpa akun)
- RBAC (Role-Based Access Control)
- Session Management
- Login Lockout setelah 5 percobaan gagal (terkunci 15 menit)
- Remember Device

### 3.2 Modul Manajemen Alumni
- Import alumni via Excel/CSV
- Input manual alumni
- Profil alumni lengkap (data pribadi, akademik, kontak)
- Pencarian & filter lanjutan
- Status alumni:
  - `belum_disurvei` — Belum pernah dikirim undangan
  - `terkirim` — Undangan survei sudah dikirim
  - `sedang_mengisi` — Alumni membuka dan mulai mengisi survei
  - `selesai` — Alumni berhasil mengirim survei
- Riwayat perubahan data
- Export data alumni

### 3.3 Modul Manajemen Employer
- Input manual employer
- Profil employer (data perusahaan, sektor, skala)
- Relasi employer-alumni
- Status survei employer:
  - `belum_disurvei` — Belum pernah dikirim link survei
  - `terkirim` — Link survei sudah dikirim
  - `selesai` — Employer berhasil mengirim survei
- Secure token management
- Export data employer

### 3.4 Modul Kuesioner Dinamis
- Pembuatan form kuesioner berbasis builder
- Tipe pertanyaan:
  - Teks pendek (`text`)
  - Teks panjang (`textarea`)
  - Pilihan ganda (`radio`)
  - Kotak centang (`checkbox`)
  - Skala Likert 1–5 (`likert`)
  - Dropdown (`select`)
  - Upload file (`file`)
  - Tanggal (`date`)
  - Rating bintang 1–5 (`rating`)
  - Angka (`number`)
- Pengaturan kondisi/logika (conditional logic)
- Versi kuesioner
- Kuesioner Alumni vs Kuesioner Employer
- Preview kuesioner

### 3.5 Modul Survei Alumni
- Undangan survei (WA/Email)
- Pengisian survei online
- Progress saving (simpan draft)
- Validasi per halaman
- Konfirmasi pengiriman
- Riwayat pengisian

### 3.6 Modul Survei Employer
- Undangan survei via secure link
- Pengisian survei tanpa registrasi
- Token tracking
- Konfirmasi pengiriman

### 3.7 Modul Notifikasi
- Notifikasi WhatsApp (via API gateway pihak ketiga)
- Notifikasi Email (SMTP)
- Template notifikasi yang dapat dikustomisasi
- Blast/massal notifikasi
- Jadwal kirim (scheduler)
- Log pengiriman notifikasi
- Queue-based processing

### 3.8 Modul Analitik & Statistik
- Dashboard ringkasan (total alumni, tingkat respons, distribusi kerja)
- Grafik distribusi pekerjaan per angkatan
- Grafik sebaran bidang industri
- Grafik masa tunggu kerja
- Grafik kesesuaian bidang kerja vs jurusan
- Grafik gaji rata-rata per prodi
- Peta sebaran alumni (peta Indonesia dengan Leaflet.js)
- Tabel pivot lanjutan

### 3.9 Modul Pelaporan
- Laporan Tracer Study per periode
- Laporan per Program Studi
- Laporan per Angkatan
- Format PDF (DomPDF)
- Format Excel (Laravel Excel)
- Template laporan yang bisa dikustomisasi
- Laporan untuk keperluan akreditasi BAN-PT

### 3.10 Modul Konfigurasi Sistem
- Manajemen Program Studi & Fakultas
- Manajemen Tahun Angkatan/Periode
- Manajemen Pengguna Admin (superadmin only)
- Konfigurasi SMTP
- Konfigurasi WhatsApp Gateway
- Pengaturan umum sistem (logo, nama institusi, dll.)
- Audit Log (superadmin only)

---

## 4. ALUR BISNIS (BUSINESS FLOW)

### 4.1 Alur Utama Tracer Study Alumni
```
[Admin/Superadmin] → Import/Input Data Alumni
    ↓
[Sistem] → Generate Akun Alumni (NIM sebagai username)
    ↓
[Admin/Superadmin] → Buat Periode Survei + Pilih Kuesioner Aktif
    ↓
[Admin/Superadmin] → Kirim Undangan Massal via WA/Email (Queue)
    ↓
[Sistem] → Update status alumni: belum_disurvei → terkirim
    ↓
[Alumni] → Buka Link → Login via OTP
    ↓
[Sistem] → Update status alumni: terkirim → sedang_mengisi
    ↓
[Alumni] → Lengkapi Profil
    ↓
[Alumni] → Isi Kuesioner Survei (bisa simpan draft)
    ↓
[Alumni] → Submit Survei
    ↓
[Sistem] → Simpan Respons → Update status alumni: sedang_mengisi → selesai
    ↓
[Sistem] → Kirim Konfirmasi ke Alumni
    ↓
[Admin/Superadmin] → Analisis & Generate Laporan
```

### 4.2 Alur Survei Employer
```
[Alumni] → Mengisi nama employer dalam profil/survei
    ↓
[Admin/Superadmin] → Verifikasi data employer
    ↓
[Sistem] → Generate Secure Token (64 karakter) untuk Employer
    ↓
[Admin/Superadmin] → Kirim Link Survei Employer (Email/WA)
    ↓
[Sistem] → Update status employer: belum_disurvei → terkirim
    ↓
[Employer] → Buka Secure Link → Validasi token
    ↓
[Employer] → Isi Kuesioner Employer
    ↓
[Employer] → Submit Survei
    ↓
[Sistem] → Simpan Respons → Update status employer: terkirim → selesai
    ↓
[Admin/Superadmin] → Analisis Data Employer
```

### 4.3 Alur OTP Authentication (Alumni)
```
[Alumni] → Masukkan NIM / Email / Nomor WA
    ↓
[Sistem] → Validasi identitas (cari di tabel users/alumni)
    ↓
[Sistem] → Generate OTP (6 digit, random_int, hash SHA-256)
    ↓
[Sistem] → Kirim OTP via WA/Email (Queue)
    ↓
[Alumni] → Masukkan OTP
    ↓
[Sistem] → Validasi OTP (max 3 percobaan, expiry 5 menit)
    ↓
[Sistem] → Generate Sanctum Token → Login Berhasil
```

### 4.4 Alur Login Admin (Email + Password)
```
[Admin/Superadmin] → Masukkan Email + Password
    ↓
[Sistem] → Cek lockout status (login_attempts, locked_until)
    ↓
[Sistem] → Verifikasi kredensial (bcrypt)
    ↓
  GAGAL → Increment login_attempts
         → Jika ≥ 5: set locked_until = +15 menit
         → Return error
    ↓
  BERHASIL → Reset login_attempts
           → Generate Sanctum Token
           → Update last_login_at
           → Log ke audit_logs
```

---

## 5. BATASAN & ASUMSI

### 5.1 Batasan Sistem
- Sistem berjalan di server Linux Ubuntu 22.04 dengan Nginx (tanpa Docker)
- Arsitektur monolitik (bukan microservices)
- WhatsApp integration menggunakan WA Gateway UNISYA (`wacenter.unisya.ac.id`); konfigurasi `wa_gateway_url`, `wa_api_key`, dan `wa_sender` dikelola via menu Pengaturan Sistem
- Peta alumni menggunakan Leaflet.js (tidak bergantung Google Maps API berbayar)
- Sistem dioptimalkan untuk penggunaan browser modern (Chrome, Firefox, Edge terbaru)
- Periode survei tidak terikat pada satu kuesioner tertentu; kuesioner dipilih saat pengiriman undangan

### 5.2 Asumsi
- Server memiliki PHP 8.3, MySQL 8+, Redis 7+, Composer, Node.js 20+ terinstal
- Domain dan SSL sudah dikonfigurasi sebelum deployment
- Admin memiliki akses ke SMTP server yang valid
- WhatsApp Gateway API aktif dan tersedia

---

## 6. FASE PENGEMBANGAN

### Fase 0: Dokumentasi Desain (✅ Selesai)
- Blueprint, Database, ERD, Arsitektur, API, UI/UX, Keamanan, Phase Tracker, Changelog

### Fase 1: Fondasi & Autentikasi
- Setup Laravel 12 + Vue 3 + TailwindCSS
- Migrasi database (tabel auth + konfigurasi akademik)
- Sistem RBAC (4 role)
- Modul Login Admin (Email + Password)
- Modul Login Alumni (NIM + OTP via WA/Email)
- Modul Login Employer (Secure Token)
- Layout dasar (AuthLayout)

### Fase 2: Manajemen Data Inti
- Modul Manajemen Alumni (CRUD lengkap + Import/Export Excel)
- Modul Manajemen Employer (CRUD lengkap)
- Konfigurasi Program Studi, Fakultas & Angkatan
- Layout Admin (Sidebar + Topbar)

### Fase 3: Kuesioner Dinamis
- Builder kuesioner (drag-and-drop, semua tipe pertanyaan)
- Conditional logic per pertanyaan
- Preview & publikasi kuesioner

### Fase 4: Survei & Notifikasi
- Modul Survei Alumni (multi-step, save draft, submit)
- Modul Survei Employer (token-based, submit)
- Modul Notifikasi (template, blast, queue, log)
- Periode Survei + Kirim Undangan Massal

### Fase 5: Analitik & Pelaporan
- Dashboard statistik (KPI, 4 chart types)
- Peta sebaran alumni (Leaflet.js)
- Laporan PDF (DomPDF)
- Laporan Excel (Laravel Excel)

### Fase 6: Keamanan & Hardening
- Audit log lengkap (semua event kritis)
- Rate limiting semua endpoint
- Security hardening
- Testing & QA (Feature + Unit test)

### Fase 7: Deployment & Optimasi
- Setup Nginx + PHP-FPM production
- Scheduler & Queue worker (Supervisor)
- Performance optimization (cache, build)
- Final testing & go-live

---

## 7. KRITERIA KEBERHASILAN

| Kriteria | Target |
|---|---|
| Tingkat Response Survei | ≥ 70% dari alumni aktif |
| Uptime Sistem | ≥ 99.5% |
| Waktu Load Halaman | < 3 detik |
| Keamanan | 0 critical vulnerability |
| Kompatibilitas Browser | Chrome, Firefox, Edge (2 versi terakhir) |
| Ekspor Laporan | PDF + Excel tersedia |
| Notifikasi Delivery | ≥ 95% terkirim |

---

## 8. STACK TEKNOLOGI FINAL

| Layer | Teknologi | Versi |
|---|---|---|
| Backend Framework | Laravel | 12.x |
| PHP | PHP | 8.3.x |
| Database | MySQL | 8.0+ / MariaDB 10.6+ |
| Auth | Laravel Sanctum | 4.x |
| Queue | Laravel Queue + Redis | — |
| Frontend Framework | Vue.js | 3.x |
| Build Tool | Vite | 5.x |
| CSS Framework | TailwindCSS | 3.x |
| State Management | Pinia | 2.x |
| Routing | Vue Router | 4.x |
| Charts | ApexCharts | 3.x |
| Maps | Leaflet.js | 1.x |
| PDF | DomPDF (barryvdh) | 3.x |
| Excel | Laravel Excel (Maatwebsite) | 3.x |
| Web Server | Nginx | 1.24+ |
| OS | Ubuntu | 22.04 LTS |
| Node.js | Node.js | 20.x LTS |
| Cache/Queue | Redis | 7.x |

---

## 9. DOKUMEN TERKAIT

| Dokumen | Keterangan |
|---|---|
| 02_DATABASE.md | Skema database 24 tabel |
| 03_ERD.md | Entity Relationship Diagram |
| 04_ARCHITECTURE.md | Arsitektur sistem & folder structure |
| 05_API.md | Spesifikasi REST API endpoint |
| 06_UI_UX.md | Design system & layout halaman |
| 07_SECURITY.md | Keamanan, RBAC, OWASP mitigasi |
| 08_PHASE_TRACKER.md | Status task per fase & sesi |
| 09_CHANGELOG.md | Riwayat perubahan semua dokumen |

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Tambah Actor 2.2 Admin; perjelas status alumni ENUM; tambah alur login admin (4.4); tambah Leaflet.js & Redis ke stack |
| 1.0.2 | 2026-06-08 | Update referensi WA Gateway di Section 5.1: dari Fonnte/WA Gateway pihak ketiga → WA Gateway UNISYA (`wacenter.unisya.ac.id`); tambah keterangan konfigurasi via menu Pengaturan Sistem |
| 1.0.3 | 2026-06-09 | Fix tabel identitas proyek (Section 1.1): Versi 1.0.1→1.0.2 dan tanggal 2026-06-06→2026-06-08 yang tertinggal dari audit v1.0.2 (INC-01) |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
