# 02_DATABASE.md
# DESAIN DATABASE — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.2 | Tanggal: 2026-06-08

---

## 1. KONVENSI PENAMAAN

| Aturan | Keterangan |
|---|---|
| Nama tabel | snake_case, plural, Bahasa Inggris |
| Nama kolom | snake_case, Bahasa Inggris |
| Primary Key | `id` (BIGINT UNSIGNED AUTO_INCREMENT) |
| Foreign Key | `{table_singular}_id` |
| Timestamp | `created_at`, `updated_at` (nullable) |
| Soft Delete | `deleted_at` (nullable) |
| Boolean | TINYINT(1) atau kolom `is_*` / `has_*` |
| Enum | VARCHAR dengan constraint atau ENUM MySQL |
| UUID | Untuk token & identifikasi eksternal |
| OTP Hash | SHA-256 hex digest → VARCHAR(64) |

---

## 2. DAFTAR TABEL

### 2.1 Tabel Sistem & Autentikasi

#### `users`
Menyimpan semua pengguna sistem (Superadmin, Admin, Alumni, Employer).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| name | VARCHAR(255) | NO | — | Nama lengkap |
| email | VARCHAR(255) | YES | NULL | Email unik |
| phone | VARCHAR(20) | YES | NULL | Nomor WhatsApp |
| role | ENUM('superadmin','admin','alumni','employer') | NO | — | Peran pengguna |
| password | VARCHAR(255) | YES | NULL | Hash bcrypt (nullable untuk employer token login) |
| email_verified_at | TIMESTAMP | YES | NULL | Verifikasi email |
| is_active | TINYINT(1) | NO | 1 | Status aktif |
| last_login_at | TIMESTAMP | YES | NULL | Login terakhir |
| login_attempts | TINYINT UNSIGNED | NO | 0 | Hitungan gagal login |
| locked_until | TIMESTAMP | YES | NULL | Waktu unlock akun (NULL = tidak terkunci) |
| remember_token | VARCHAR(100) | YES | NULL | Token remember me |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete |

**Index:** UNIQUE(email), INDEX(role), INDEX(phone)

---

#### `otp_codes`
Menyimpan kode OTP untuk autentikasi.

> **Catatan Keamanan:** Kolom `code` menyimpan hash SHA-256 dari OTP plaintext (bukan OTP itu sendiri).
> SHA-256 menghasilkan 64 karakter hex, sehingga tipe kolom adalah VARCHAR(64).
> OTP plaintext hanya dikirim ke user via WA/Email dan tidak pernah disimpan ke database.

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| user_id | BIGINT UNSIGNED | YES | NULL | FK ke users (nullable jika identifier belum terdaftar) |
| identifier | VARCHAR(255) | NO | — | Email atau nomor HP yang digunakan |
| code | VARCHAR(64) | NO | — | Hash SHA-256 dari kode OTP (bukan plaintext) |
| purpose | ENUM('login','verify','reset') | NO | login | Tujuan OTP |
| channel | ENUM('email','whatsapp') | NO | email | Saluran pengiriman |
| attempts | TINYINT UNSIGNED | NO | 0 | Percobaan verifikasi gagal |
| is_used | TINYINT(1) | NO | 0 | Sudah digunakan (1 = tidak bisa dipakai lagi) |
| expires_at | TIMESTAMP | NO | — | Waktu kedaluwarsa (default +5 menit dari created_at) |
| created_at | TIMESTAMP | YES | NULL | — |

**Index:** INDEX(identifier), INDEX(expires_at), INDEX(user_id)

---

#### `personal_access_tokens`
Tabel standar Laravel Sanctum.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| tokenable_type | VARCHAR(255) | NO | Morph type |
| tokenable_id | BIGINT UNSIGNED | NO | Morph id |
| name | VARCHAR(255) | NO | Nama token |
| token | VARCHAR(64) | NO | Hash token (unique) |
| abilities | TEXT | YES | JSON abilities |
| last_used_at | TIMESTAMP | YES | — |
| expires_at | TIMESTAMP | YES | — |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

---

#### `audit_logs`
Mencatat seluruh aktivitas penting dalam sistem (append-only, tidak bisa dihapus).

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| user_id | BIGINT UNSIGNED | YES | FK ke users (nullable untuk guest/sistem) |
| user_role | VARCHAR(50) | YES | Peran saat aksi dilakukan |
| action | VARCHAR(100) | NO | Nama aksi (create, update, delete, login, dll.) |
| module | VARCHAR(100) | NO | Modul terkait (Alumni, Employer, Auth, dll.) |
| model_type | VARCHAR(255) | YES | Morph type (nama class model) |
| model_id | BIGINT UNSIGNED | YES | Morph id (id record yang terpengaruh) |
| old_values | JSON | YES | Nilai sebelum perubahan |
| new_values | JSON | YES | Nilai setelah perubahan |
| ip_address | VARCHAR(45) | YES | IP address (support IPv6) |
| user_agent | TEXT | YES | Browser/user agent |
| created_at | TIMESTAMP | YES | — |

**Index:** INDEX(user_id), INDEX(action), INDEX(module), INDEX(created_at), INDEX(model_type, model_id)

---

### 2.2 Tabel Konfigurasi Akademik

#### `faculties`
Fakultas di universitas.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| code | VARCHAR(20) | NO | Kode fakultas (unik) |
| name | VARCHAR(255) | NO | Nama fakultas |
| dean_name | VARCHAR(255) | YES | Nama dekan |
| is_active | TINYINT(1) | NO | 1 | Status aktif |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** UNIQUE(code)

---

#### `study_programs`
Program studi / jurusan.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| faculty_id | BIGINT UNSIGNED | NO | FK ke faculties |
| code | VARCHAR(20) | NO | Kode prodi (unik) |
| name | VARCHAR(255) | NO | Nama prodi |
| degree_level | ENUM('D3','D4','S1','S2','S3','Profesi') | NO | Jenjang pendidikan |
| accreditation | VARCHAR(10) | YES | Nilai akreditasi (A, B, C, Unggul, Baik Sekali, dll.) |
| head_name | VARCHAR(255) | YES | Nama ketua prodi |
| is_active | TINYINT(1) | NO | 1 | Status aktif |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** UNIQUE(code), INDEX(faculty_id)

---

#### `graduation_years`
Tahun akademik / angkatan kelulusan.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| year | SMALLINT UNSIGNED | NO | Tahun lulus (contoh: 2023) |
| academic_year | VARCHAR(20) | NO | Tahun akademik (contoh: 2022/2023) |
| semester | ENUM('Ganjil','Genap') | NO | Semester wisuda |
| is_active | TINYINT(1) | NO | 1 | Status aktif |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** UNIQUE(year, semester)

---

#### `survey_periods`
Periode/gelombang survei tracer study.

> **Catatan Desain:** Satu periode survei tidak terikat pada satu kuesioner tertentu di level tabel.
> Kuesioner yang digunakan dipilih saat admin mengirim undangan massal (lihat API: send-invitations).
> Hal ini memungkinkan fleksibilitas penggunaan kuesioner yang berbeda dalam satu periode.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| name | VARCHAR(255) | NO | Nama periode (contoh: Tracer Study 2024) |
| year | SMALLINT UNSIGNED | NO | Tahun periode |
| start_date | DATE | NO | Tanggal mulai |
| end_date | DATE | NO | Tanggal berakhir |
| target_graduation_years | JSON | YES | Array ID graduation_years yang disasar |
| status | ENUM('draft','active','closed') | NO | draft | Status periode |
| description | TEXT | YES | Keterangan tambahan |
| created_by | BIGINT UNSIGNED | NO | FK ke users (admin yang membuat) |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** INDEX(status), INDEX(year), INDEX(created_by)

---

### 2.3 Tabel Alumni

#### `alumni`
Data utama alumni.

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| user_id | BIGINT UNSIGNED | NO | — | FK ke users (one-to-one) |
| nim | VARCHAR(20) | NO | — | Nomor Induk Mahasiswa (unik) |
| nik | VARCHAR(20) | YES | NULL | Nomor Induk Kependudukan |
| full_name | VARCHAR(255) | NO | — | Nama lengkap |
| gender | ENUM('L','P') | NO | — | Jenis kelamin (L=Laki-laki, P=Perempuan) |
| birth_place | VARCHAR(100) | YES | NULL | Tempat lahir |
| birth_date | DATE | YES | NULL | Tanggal lahir |
| study_program_id | BIGINT UNSIGNED | NO | — | FK ke study_programs |
| graduation_year_id | BIGINT UNSIGNED | NO | — | FK ke graduation_years |
| thesis_title | TEXT | YES | NULL | Judul skripsi/tugas akhir |
| gpa | DECIMAL(4,2) | YES | NULL | IPK (0.00–4.00) |
| graduation_predicate | VARCHAR(50) | YES | NULL | Predikat kelulusan |
| address_street | TEXT | YES | NULL | Alamat jalan |
| address_village | VARCHAR(100) | YES | NULL | Kelurahan/Desa |
| address_district | VARCHAR(100) | YES | NULL | Kecamatan |
| address_city | VARCHAR(100) | YES | NULL | Kota/Kabupaten |
| address_province | VARCHAR(100) | YES | NULL | Provinsi |
| address_postal_code | VARCHAR(10) | YES | NULL | Kode pos |
| address_latitude | DECIMAL(10,7) | YES | NULL | Koordinat latitude |
| address_longitude | DECIMAL(10,7) | YES | NULL | Koordinat longitude |
| phone | VARCHAR(20) | YES | NULL | Nomor WA aktif |
| email | VARCHAR(255) | YES | NULL | Email aktif |
| linkedin_url | VARCHAR(255) | YES | NULL | Profil LinkedIn |
| photo | VARCHAR(255) | YES | NULL | Path foto (relative ke storage) |
| survey_status | ENUM('belum_disurvei','terkirim','sedang_mengisi','selesai') | NO | belum_disurvei | Status survei alumni |
| import_batch | VARCHAR(50) | YES | NULL | ID batch import (untuk tracing) |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete |

**Index:** UNIQUE(nim), UNIQUE(user_id), INDEX(study_program_id), INDEX(graduation_year_id), INDEX(survey_status)

**Keterangan survey_status:**
- `belum_disurvei`: Default saat data alumni dibuat
- `terkirim`: Setelah undangan survei berhasil dikirim
- `sedang_mengisi`: Setelah alumni membuka link dan memulai pengisian
- `selesai`: Setelah alumni berhasil submit survei

---

#### `alumni_work_histories`
Riwayat pekerjaan alumni (satu alumni bisa punya lebih dari satu entri).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| alumni_id | BIGINT UNSIGNED | NO | — | FK ke alumni |
| employer_id | BIGINT UNSIGNED | YES | NULL | FK ke employers (jika employer sudah terdaftar) |
| company_name | VARCHAR(255) | NO | — | Nama perusahaan |
| position | VARCHAR(255) | NO | — | Jabatan/posisi |
| industry_sector | VARCHAR(100) | YES | NULL | Sektor industri |
| employment_type | ENUM('penuh_waktu','paruh_waktu','kontrak','freelance','wirausaha','magang') | YES | NULL | Jenis pekerjaan |
| start_date | DATE | NO | — | Tanggal mulai bekerja |
| end_date | DATE | YES | NULL | Tanggal berakhir (NULL = masih bekerja) |
| is_current | TINYINT(1) | NO | 0 | Status pekerjaan saat ini |
| city | VARCHAR(100) | YES | NULL | Kota tempat bekerja |
| province | VARCHAR(100) | YES | NULL | Provinsi tempat bekerja |
| country | VARCHAR(100) | YES | NULL | Negara tempat bekerja |
| monthly_salary_range | VARCHAR(50) | YES | NULL | Rentang gaji bulanan (kode, misal: '3_5jt') |
| is_relevant_to_study | TINYINT(1) | YES | NULL | Relevansi dengan bidang studi (1=ya, 0=tidak) |
| waiting_time_months | TINYINT UNSIGNED | YES | NULL | Bulan menunggu setelah lulus sampai bekerja |
| description | TEXT | YES | NULL | Deskripsi pekerjaan |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

**Index:** INDEX(alumni_id), INDEX(employer_id), INDEX(is_current)

---

### 2.4 Tabel Employer

#### `employers`
Data perusahaan / pemberi kerja alumni.

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| user_id | BIGINT UNSIGNED | YES | NULL | FK ke users (jika employer punya akun — opsional) |
| company_name | VARCHAR(255) | NO | — | Nama perusahaan |
| company_type | ENUM('swasta','bumn','pemerintah','ngo','startup','lainnya') | YES | NULL | Jenis perusahaan |
| industry_sector | VARCHAR(100) | YES | NULL | Sektor industri |
| company_scale | ENUM('mikro','kecil','menengah','besar','multinasional') | YES | NULL | Skala perusahaan |
| address_street | TEXT | YES | NULL | Alamat lengkap |
| address_city | VARCHAR(100) | YES | NULL | Kota |
| address_province | VARCHAR(100) | YES | NULL | Provinsi |
| address_country | VARCHAR(100) | YES | NULL | Negara |
| phone | VARCHAR(20) | YES | NULL | Nomor telepon perusahaan |
| email | VARCHAR(255) | YES | NULL | Email perusahaan |
| website | VARCHAR(255) | YES | NULL | Website perusahaan |
| contact_person_name | VARCHAR(255) | YES | NULL | Nama PIC (Person in Charge) |
| contact_person_position | VARCHAR(100) | YES | NULL | Jabatan PIC |
| contact_person_email | VARCHAR(255) | YES | NULL | Email PIC |
| contact_person_phone | VARCHAR(20) | YES | NULL | Nomor WA PIC |
| survey_status | ENUM('belum_disurvei','terkirim','selesai') | NO | belum_disurvei | Status survei employer |
| survey_token | VARCHAR(64) | YES | NULL | Token akses survei unik (CSPRNG, hex) |
| survey_token_expires_at | TIMESTAMP | YES | NULL | Waktu kedaluwarsa token (default +30 hari) |
| survey_token_used_at | TIMESTAMP | YES | NULL | Waktu token pertama kali diakses |
| logo | VARCHAR(255) | YES | NULL | Path logo perusahaan |
| notes | TEXT | YES | NULL | Catatan internal admin |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |
| deleted_at | TIMESTAMP | YES | NULL | Soft delete |

**Index:** UNIQUE(survey_token), INDEX(survey_status), INDEX(company_name)

**Keterangan survey_status:**
- `belum_disurvei`: Default saat data employer dibuat
- `terkirim`: Setelah link survei berhasil dikirim ke employer
- `selesai`: Setelah employer berhasil submit survei

---

### 2.5 Tabel Kuesioner

#### `questionnaires`
Master kuesioner (template survei).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| title | VARCHAR(255) | NO | — | Judul kuesioner |
| description | TEXT | YES | NULL | Deskripsi/instruksi pengisian |
| type | ENUM('alumni','employer') | NO | — | Jenis kuesioner |
| version | SMALLINT UNSIGNED | NO | 1 | Versi kuesioner |
| status | ENUM('draft','aktif','arsip') | NO | draft | Status kuesioner |
| is_paginated | TINYINT(1) | NO | 0 | Multi halaman (1 seksi = 1 halaman) |
| estimated_minutes | TINYINT UNSIGNED | YES | NULL | Estimasi waktu pengisian (menit) |
| created_by | BIGINT UNSIGNED | NO | — | FK ke users (admin yang membuat) |
| published_at | TIMESTAMP | YES | NULL | Waktu dipublikasi (status → aktif) |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

**Index:** INDEX(type), INDEX(status), INDEX(created_by)

---

#### `questionnaire_sections`
Seksi/halaman dalam kuesioner.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| questionnaire_id | BIGINT UNSIGNED | NO | FK ke questionnaires |
| title | VARCHAR(255) | NO | Judul seksi |
| description | TEXT | YES | Instruksi seksi |
| order_number | SMALLINT UNSIGNED | NO | Urutan tampil (ascending) |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** INDEX(questionnaire_id)

---

#### `questions`
Pertanyaan dalam kuesioner.

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| questionnaire_id | BIGINT UNSIGNED | NO | — | FK ke questionnaires |
| section_id | BIGINT UNSIGNED | YES | NULL | FK ke questionnaire_sections |
| question_text | TEXT | NO | — | Teks pertanyaan |
| question_type | ENUM('text','textarea','radio','checkbox','select','likert','rating','date','file','number') | NO | — | Tipe pertanyaan |
| is_required | TINYINT(1) | NO | 1 | Wajib diisi |
| order_number | SMALLINT UNSIGNED | NO | — | Urutan tampil |
| help_text | TEXT | YES | NULL | Teks bantuan di bawah pertanyaan |
| placeholder | VARCHAR(255) | YES | NULL | Placeholder input teks |
| validation_rules | JSON | YES | NULL | Aturan validasi tambahan (min, max, regex, dll.) |
| conditional_logic | JSON | YES | NULL | Logika kondisional (show_if berdasarkan jawaban pertanyaan lain) |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

**Index:** INDEX(questionnaire_id), INDEX(section_id)

---

#### `question_options`
Opsi jawaban untuk pertanyaan tipe radio, checkbox, select, likert.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| question_id | BIGINT UNSIGNED | NO | FK ke questions |
| option_text | VARCHAR(500) | NO | Teks opsi yang ditampilkan |
| option_value | VARCHAR(255) | NO | Nilai opsi yang disimpan ke survey_answers |
| order_number | SMALLINT UNSIGNED | NO | Urutan tampil |
| is_other | TINYINT(1) | NO | 0 | Opsi "Lainnya" (dengan input teks bebas) |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** INDEX(question_id)

---

### 2.6 Tabel Respons Survei

#### `survey_responses`
Header respons survei per alumni/employer (satu respons per responden per kuesioner per periode).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| questionnaire_id | BIGINT UNSIGNED | NO | — | FK ke questionnaires |
| survey_period_id | BIGINT UNSIGNED | YES | NULL | FK ke survey_periods |
| respondent_type | ENUM('alumni','employer') | NO | — | Jenis responden |
| alumni_id | BIGINT UNSIGNED | YES | NULL | FK ke alumni (jika respondent_type='alumni') |
| employer_id | BIGINT UNSIGNED | YES | NULL | FK ke employers (jika respondent_type='employer') |
| status | ENUM('draft','selesai') | NO | draft | Status pengisian |
| started_at | TIMESTAMP | YES | NULL | Waktu mulai mengisi |
| submitted_at | TIMESTAMP | YES | NULL | Waktu submit |
| ip_address | VARCHAR(45) | YES | NULL | IP address responden (IPv4/IPv6) |
| user_agent | TEXT | YES | NULL | Browser info |
| completion_percentage | TINYINT UNSIGNED | NO | 0 | Persentase penyelesaian (0–100) |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

**Index:** INDEX(questionnaire_id), INDEX(alumni_id), INDEX(employer_id), INDEX(survey_period_id), INDEX(status)

---

#### `survey_answers`
Jawaban per pertanyaan dari responden.

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| survey_response_id | BIGINT UNSIGNED | NO | FK ke survey_responses |
| question_id | BIGINT UNSIGNED | NO | FK ke questions |
| answer_text | TEXT | YES | Jawaban teks bebas (tipe text, textarea) |
| answer_options | JSON | YES | Array ID question_options yang dipilih (tipe radio, checkbox) |
| answer_value | VARCHAR(255) | YES | Nilai jawaban untuk skala/rating/angka/select |
| file_path | VARCHAR(255) | YES | Path file upload (tipe file) |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

**Index:** INDEX(survey_response_id), INDEX(question_id), UNIQUE(survey_response_id, question_id)

---

### 2.7 Tabel Notifikasi

#### `notification_templates`
Template pesan notifikasi yang dapat dikustomisasi admin.

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| name | VARCHAR(255) | NO | — | Nama template (unik per type+event) |
| type | ENUM('email','whatsapp') | NO | — | Jenis channel |
| event | VARCHAR(100) | NO | — | Event trigger (survey_invitation, otp_login, survey_reminder, employer_survey_invitation, dll.) |
| subject | VARCHAR(255) | YES | NULL | Subject email (NULL untuk whatsapp) |
| body | TEXT | NO | — | Isi pesan (variabel menggunakan format {{variable_name}}) |
| variables | JSON | YES | NULL | Daftar variabel yang tersedia beserta deskripsinya |
| is_active | TINYINT(1) | NO | 1 | Status aktif |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

**Index:** INDEX(type), INDEX(event), UNIQUE(type, event)

---

#### `notification_logs`
Log pengiriman notifikasi (append-only).

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| template_id | BIGINT UNSIGNED | YES | FK ke notification_templates (NULL jika template ad-hoc) |
| type | ENUM('email','whatsapp') | NO | Jenis channel |
| recipient | VARCHAR(255) | NO | Alamat tujuan (email atau nomor WA) |
| recipient_type | VARCHAR(50) | YES | Jenis penerima (alumni/employer) |
| recipient_id | BIGINT UNSIGNED | YES | ID alumni atau employer |
| subject | VARCHAR(255) | YES | Subject (khusus email) |
| body | TEXT | NO | Isi pesan yang benar-benar dikirim |
| status | ENUM('pending','sent','failed','delivered') | NO | Status pengiriman |
| error_message | TEXT | YES | Pesan error jika status failed |
| sent_at | TIMESTAMP | YES | Waktu terkirim dari server |
| provider_response | JSON | YES | Raw response dari WA Gateway atau SMTP. Untuk WA (gateway UNISYA), menyimpan seluruh JSON response termasuk `message_id` jika tersedia |
| created_at | TIMESTAMP | YES | — |
| updated_at | TIMESTAMP | YES | — |

> **Catatan Status WA Gateway UNISYA:** Gateway `wacenter.unisya.ac.id` tidak menyediakan webhook
> delivery callback. Status `delivered` tidak dapat diisi secara otomatis dari gateway ini.
> Status yang relevan untuk channel WhatsApp hanya `pending`, `sent` (response `status: true`),
> dan `failed` (response `status: false` atau exception). Nilai `delivered` dipertahankan di ENUM
> untuk kompatibilitas masa depan (jika gateway kelak mendukung webhook) dan untuk channel Email
> yang mendukung delivery tracking via SMTP.

**Index:** INDEX(type), INDEX(status), INDEX(recipient_id), INDEX(created_at), INDEX(template_id)

---

### 2.8 Tabel Konfigurasi Sistem

#### `system_settings`
Konfigurasi global sistem (key-value store).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| key | VARCHAR(100) | NO | — | Kunci pengaturan (unik) |
| value | TEXT | YES | NULL | Nilai pengaturan |
| type | ENUM('string','integer','boolean','json','text') | NO | string | Tipe data nilai |
| group | VARCHAR(50) | YES | NULL | Grup (smtp, whatsapp, general, security, dll.) |
| label | VARCHAR(255) | YES | NULL | Label tampilan di UI |
| description | TEXT | YES | NULL | Deskripsi pengaturan |
| is_encrypted | TINYINT(1) | NO | 0 | Nilai dienkripsi menggunakan Laravel encrypt() |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

**Index:** UNIQUE(key)

---

#### `industry_sectors`
Master sektor industri (referensi untuk dropdown).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| name | VARCHAR(255) | NO | — | Nama sektor |
| code | VARCHAR(20) | YES | NULL | Kode sektor |
| is_active | TINYINT(1) | NO | 1 | Status aktif |
| created_at | TIMESTAMP | YES | NULL | — |
| updated_at | TIMESTAMP | YES | NULL | — |

---

#### `salary_ranges`
Master rentang gaji (referensi untuk dropdown).

| Kolom | Tipe | Nullable | Keterangan |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | Primary Key |
| label | VARCHAR(100) | NO | Label (contoh: "Rp 1–3 Juta") |
| min_value | BIGINT UNSIGNED | YES | Nilai minimum dalam rupiah |
| max_value | BIGINT UNSIGNED | YES | Nilai maksimum dalam rupiah (NULL = tidak terbatas) |
| order_number | TINYINT UNSIGNED | NO | Urutan tampil |
| is_active | TINYINT(1) | NO | 1 | Status aktif |

---

### 2.9 Tabel Relasi Tambahan

#### `alumni_survey_period`
Pivot: alumni yang terdaftar/termasuk dalam periode survei tertentu.

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| alumni_id | BIGINT UNSIGNED | NO | — | FK ke alumni |
| survey_period_id | BIGINT UNSIGNED | NO | — | FK ke survey_periods |
| invitation_sent_at | TIMESTAMP | YES | NULL | Waktu undangan pertama dikirim |
| invitation_channel | ENUM('email','whatsapp','both') | YES | NULL | Saluran undangan |
| reminder_count | TINYINT UNSIGNED | NO | 0 | Jumlah reminder yang sudah terkirim |
| last_reminder_at | TIMESTAMP | YES | NULL | Waktu reminder terakhir |

**Index:** UNIQUE(alumni_id, survey_period_id)

---

#### `alumni_employer`
Pivot: relasi alumni dengan employer (alumni referensikan employer).

| Kolom | Tipe | Nullable | Default | Keterangan |
|---|---|---|---|---|
| id | BIGINT UNSIGNED | NO | AUTO | Primary Key |
| alumni_id | BIGINT UNSIGNED | NO | — | FK ke alumni |
| employer_id | BIGINT UNSIGNED | NO | — | FK ke employers |
| is_verified | TINYINT(1) | NO | 0 | Diverifikasi admin (1 = relasi sah) |
| created_at | TIMESTAMP | YES | NULL | — |

**Index:** UNIQUE(alumni_id, employer_id)

---

## 3. RINGKASAN TABEL

| No | Nama Tabel | Kategori | Keterangan |
|---|---|---|---|
| 1 | users | Autentikasi | Semua pengguna sistem (4 role) |
| 2 | otp_codes | Autentikasi | Kode OTP (hash SHA-256, VARCHAR(64)) |
| 3 | personal_access_tokens | Autentikasi | Token Sanctum |
| 4 | audit_logs | Sistem | Log aktivitas (append-only) |
| 5 | faculties | Akademik | Fakultas |
| 6 | study_programs | Akademik | Program studi |
| 7 | graduation_years | Akademik | Tahun kelulusan |
| 8 | survey_periods | Akademik | Periode survei |
| 9 | alumni | Alumni | Data alumni |
| 10 | alumni_work_histories | Alumni | Riwayat kerja alumni |
| 11 | employers | Employer | Data perusahaan/employer |
| 12 | questionnaires | Kuesioner | Master kuesioner |
| 13 | questionnaire_sections | Kuesioner | Seksi kuesioner |
| 14 | questions | Kuesioner | Pertanyaan |
| 15 | question_options | Kuesioner | Opsi jawaban |
| 16 | survey_responses | Survei | Header respons survei |
| 17 | survey_answers | Survei | Jawaban survei per pertanyaan |
| 18 | notification_templates | Notifikasi | Template pesan |
| 19 | notification_logs | Notifikasi | Log pengiriman (append-only) |
| 20 | system_settings | Konfigurasi | Pengaturan global |
| 21 | industry_sectors | Referensi | Master sektor industri |
| 22 | salary_ranges | Referensi | Master rentang gaji |
| 23 | alumni_survey_period | Relasi | Pivot alumni ↔ periode |
| 24 | alumni_employer | Relasi | Pivot alumni ↔ employer |

**Total: 24 tabel**

---

## 4. STRATEGI PENGINDEKSAN

- Semua foreign key memiliki index
- Kolom pencarian sering (nim, email, phone, survey_token) diindeks
- Kolom filter sering (status, role, type, survey_status) diindeks
- Kolom sort sering (created_at) diindeks pada tabel besar (audit_logs, notification_logs)
- Composite UNIQUE index untuk relasi pivot (alumni_survey_period, alumni_employer)
- Composite index (survey_response_id, question_id) di survey_answers untuk mencegah jawaban duplikat

## 5. STRATEGI BACKUP

- Full backup harian (mysqldump dengan gzip, dienkripsi GPG)
- Incremental backup setiap 6 jam
- Retensi backup: 30 hari
- Backup disimpan di direktori terpisah dari aplikasi (`/backup/sitras/`)
- Verifikasi restore dilakukan setiap 7 hari (uji restore ke staging)

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal, 24 tabel |
| 1.0.1 | 2026-06-06 | Fix: otp_codes.code VARCHAR(10) → VARCHAR(64) (SHA-256 hash); tambah UNIQUE index di notification_templates(type,event); tambah UNIQUE index di survey_answers(survey_response_id, question_id); perjelas kolom survey_status; tambah catatan desain survey_periods |
| 1.0.2 | 2026-06-08 | Tambah catatan status WA Gateway UNISYA di tabel notification_logs (status `delivered` tidak diisi otomatis dari gateway); perjelas kolom provider_response untuk menyimpan message_id dari gateway |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
