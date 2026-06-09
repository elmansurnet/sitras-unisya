# 03_ERD.md
# ENTITY RELATIONSHIP DIAGRAM — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.2 | Tanggal: 2026-06-08

---

## 1. DIAGRAM ERD (Notasi Tekstual)

```
╔══════════════════════════════════════════════════════════════════════════╗
║                    SISTEM TRACER STUDY UNISYA                           ║
║                    Entity Relationship Diagram                           ║
╚══════════════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────────┐
│                         CLUSTER: AUTENTIKASI                        │
└─────────────────────────────────────────────────────────────────────┘

[users]─────────────────────────────────────────────────────────────────
    │ PK: id                                                           │
    │ name, email, phone                                               │
    │ role: ENUM('superadmin','admin','alumni','employer')             │
    │ password (bcrypt, nullable untuk employer)                       │
    │ is_active, login_attempts, locked_until                          │
    │                                                                  │
    │ 1                                            1                   │
    │ │ has_many                                   │ has_many          │
    │ ▼                                            ▼                   │
[otp_codes]                           [personal_access_tokens]        │
    │ PK: id                           PK: id                         │
    │ user_id (FK, nullable)           tokenable_id (polymorphic)     │
    │ identifier (email/phone)         tokenable_type                 │
    │ code: VARCHAR(64) ← SHA-256 hash name, token, abilities         │
    │ purpose: ENUM('login',           expires_at                     │
    │          'verify','reset')                                       │
    │ channel: ENUM('email','whatsapp')                                │
    │ attempts (max 3)                                                 │
    │ is_used, expires_at (+5 menit)                                   │
    │                                                                  │
    │ 1                                                                │
    │ │ has_many                                                       │
    │ ▼                                                                │
[audit_logs]                                                           │
    │ PK: id                                                           │
    │ user_id (FK, nullable)                                           │
    │ user_role, action, module                                        │
    │ model_type, model_id (polymorphic)                               │
    │ old_values, new_values (JSON)                                    │
    │ ip_address, user_agent                                           │
────────────────────────────────────────────────────────────────────────

┌─────────────────────────────────────────────────────────────────────┐
│                      CLUSTER: AKADEMIK                              │
└─────────────────────────────────────────────────────────────────────┘

[faculties]
    │ PK: id
    │ code (UNIQUE), name, dean_name, is_active
    │
    │ 1
    │ │ has_many
    │ ▼
[study_programs]
    │ PK: id
    │ faculty_id (FK)
    │ code (UNIQUE), name
    │ degree_level: ENUM('D3','D4','S1','S2','S3','Profesi')
    │ accreditation, head_name, is_active
    │
    │ 1
    │ │ has_many
    │ ▼
[alumni] ◄──────────── [graduation_years]
    │                   PK: id
    │ PK: id            year, academic_year
    │ user_id (FK)      semester: ENUM('Ganjil','Genap')
    │ nim (UNIQUE)      is_active
    │ study_program_id (FK)
    │ graduation_year_id (FK)
    │ survey_status: ENUM(
    │   'belum_disurvei',  ← default saat data dibuat
    │   'terkirim',        ← setelah undangan dikirim
    │   'sedang_mengisi',  ← setelah alumni buka link
    │   'selesai'          ← setelah alumni submit
    │ )

[survey_periods]
    │ PK: id
    │ name, year, start_date, end_date
    │ target_graduation_years (JSON array of graduation_year ids)
    │ status: ENUM('draft','active','closed')
    │ created_by (FK → users)
    │
    │                     ┌──────────────────────┐
    │                     │ alumni_survey_period  │
    │ 1 ─── many-to-many ─│ (pivot table)        │─── many-to-many ─── [alumni]
    │                     │ alumni_id (FK)        │
    │                     │ survey_period_id (FK) │
    │                     │ invitation_sent_at    │
    │                     │ invitation_channel    │
    │                     │ reminder_count        │
    │                     │ last_reminder_at      │
    │                     └──────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      CLUSTER: ALUMNI & KARIR                        │
└─────────────────────────────────────────────────────────────────────┘

[alumni]
    │ 1
    │ │ has_many
    │ ▼
[alumni_work_histories]
    │ PK: id
    │ alumni_id (FK)
    │ employer_id (FK, nullable)  ← terhubung jika employer terdaftar
    │ company_name, position
    │ employment_type, industry_sector
    │ start_date, end_date, is_current
    │ monthly_salary_range
    │ is_relevant_to_study (boolean)
    │ waiting_time_months

[alumni] ◄──── [alumni_employer] ────► [employers]
                (pivot table)
                alumni_id (FK)          PK: id
                employer_id (FK)        user_id (FK, nullable)
                is_verified             company_name, company_type
                                        industry_sector, company_scale
                                        address_*, phone, email
                                        contact_person_*
                                        survey_status: ENUM(
                                          'belum_disurvei',
                                          'terkirim',
                                          'selesai'
                                        )
                                        survey_token: VARCHAR(64)
                                        survey_token_expires_at
                                        survey_token_used_at

┌─────────────────────────────────────────────────────────────────────┐
│                      CLUSTER: KUESIONER                             │
└─────────────────────────────────────────────────────────────────────┘

[questionnaires]
    │ PK: id
    │ title, description
    │ type: ENUM('alumni','employer')
    │ version, status: ENUM('draft','aktif','arsip')
    │ is_paginated, estimated_minutes
    │ created_by (FK → users)
    │
    │ 1
    │ │ has_many
    │ ▼
[questionnaire_sections]
    │ PK: id
    │ questionnaire_id (FK)
    │ title, description, order_number
    │
    │ 1
    │ │ has_many
    │ ▼
[questions]
    │ PK: id
    │ questionnaire_id (FK)
    │ section_id (FK, nullable)  ← pertanyaan bisa tanpa seksi
    │ question_text
    │ question_type: ENUM('text','textarea','radio','checkbox',
    │                     'select','likert','rating','date',
    │                     'file','number')
    │ is_required, order_number
    │ help_text, placeholder
    │ validation_rules (JSON)
    │ conditional_logic (JSON)  ← show_if based on other question answer
    │
    │ 1
    │ │ has_many (hanya jika type: radio/checkbox/select/likert)
    │ ▼
[question_options]
    │ PK: id
    │ question_id (FK)
    │ option_text: VARCHAR(500)
    │ option_value: VARCHAR(255)
    │ order_number, is_other

┌─────────────────────────────────────────────────────────────────────┐
│                      CLUSTER: SURVEI                                │
└─────────────────────────────────────────────────────────────────────┘

[survey_responses]
    │ PK: id
    │ questionnaire_id (FK)
    │ survey_period_id (FK, nullable)
    │ respondent_type: ENUM('alumni','employer')
    │ alumni_id (FK, nullable)     ← populated jika respondent_type='alumni'
    │ employer_id (FK, nullable)   ← populated jika respondent_type='employer'
    │ status: ENUM('draft','selesai')
    │ started_at, submitted_at
    │ completion_percentage (0–100)
    │ ip_address, user_agent
    │
    │ 1
    │ │ has_many
    │ ▼
[survey_answers]
    │ PK: id
    │ survey_response_id (FK)
    │ question_id (FK)
    │ UNIQUE(survey_response_id, question_id)  ← satu jawaban per pertanyaan per respons
    │ answer_text (TEXT)        ← untuk tipe text, textarea
    │ answer_options (JSON)     ← array option_id untuk tipe radio, checkbox
    │ answer_value (VARCHAR)    ← untuk tipe select, likert, rating, number, date
    │ file_path (VARCHAR)       ← untuk tipe file

┌─────────────────────────────────────────────────────────────────────┐
│                      CLUSTER: NOTIFIKASI                            │
└─────────────────────────────────────────────────────────────────────┘

[notification_templates]
    │ PK: id
    │ name, type: ENUM('email','whatsapp')
    │ event: VARCHAR(100)
    │ UNIQUE(type, event)  ← satu template per channel per event
    │ subject (nullable, khusus email)
    │ body (variabel: {{variable_name}})
    │ variables (JSON)
    │ is_active
    │
    │ 1
    │ │ has_many
    │ ▼
[notification_logs]
    │ PK: id
    │ template_id (FK, nullable)  ← nullable untuk pesan ad-hoc
    │ type, recipient
    │ recipient_type, recipient_id
    │ subject, body
    │ status: ENUM('pending','sent','failed','delivered')
    │   ← WA Gateway UNISYA: hanya 'sent'/'failed' yang diisi otomatis
    │   ← 'delivered' dipertahankan untuk Email & kompatibilitas masa depan
    │ error_message, sent_at
    │ provider_response (JSON)  ← menyimpan raw response gateway + message_id

┌─────────────────────────────────────────────────────────────────────┐
│                      CLUSTER: SISTEM                                │
└─────────────────────────────────────────────────────────────────────┘

[system_settings]               [industry_sectors]    [salary_ranges]
PK: id                          PK: id                PK: id
key (UNIQUE)                    name, code            label
value, type                     is_active             min_value, max_value
group, label                                          order_number
is_encrypted                                          is_active
```

---

## 2. RELASI ANTAR ENTITAS

### 2.1 Relasi One-to-One
| Entitas A | Entitas B | Foreign Key | Keterangan |
|---|---|---|---|
| users | alumni | alumni.user_id | Satu user alumni memiliki satu profil alumni |
| users | employers | employers.user_id (nullable) | User employer opsional (employer bisa tanpa akun) |

### 2.2 Relasi One-to-Many
| Parent | Child | Foreign Key | Keterangan |
|---|---|---|---|
| faculties | study_programs | study_programs.faculty_id | Fakultas punya banyak prodi |
| study_programs | alumni | alumni.study_program_id | Prodi punya banyak alumni |
| graduation_years | alumni | alumni.graduation_year_id | Angkatan punya banyak alumni |
| users | otp_codes | otp_codes.user_id | User punya banyak OTP (cleanup harian) |
| users | audit_logs | audit_logs.user_id | User punya banyak log |
| alumni | alumni_work_histories | work_histories.alumni_id | Alumni punya banyak riwayat kerja |
| employers | alumni_work_histories | work_histories.employer_id | Employer direferensikan di banyak riwayat kerja |
| questionnaires | questionnaire_sections | sections.questionnaire_id | Kuesioner punya banyak seksi |
| questionnaires | questions | questions.questionnaire_id | Kuesioner punya banyak pertanyaan |
| questionnaire_sections | questions | questions.section_id | Seksi punya banyak pertanyaan |
| questions | question_options | options.question_id | Pertanyaan punya banyak opsi |
| survey_responses | survey_answers | answers.survey_response_id | Respons punya banyak jawaban |
| notification_templates | notification_logs | logs.template_id | Template punya banyak log kirim |

### 2.3 Relasi Many-to-Many
| Entitas A | Entitas B | Tabel Pivot | Keterangan |
|---|---|---|---|
| alumni | survey_periods | alumni_survey_period | Alumni terdaftar di banyak periode survei |
| alumni | employers | alumni_employer | Alumni bisa bekerja di banyak employer; employer bisa punya banyak alumni |

### 2.4 Relasi Polimorfik
| Kolom | Relasi | Keterangan |
|---|---|---|
| audit_logs.(model_type, model_id) | Polymorphic | Log bisa merujuk ke model apapun |
| personal_access_tokens.(tokenable_type, tokenable_id) | Polymorphic | Token bisa milik model apapun |

---

## 3. ALUR DATA KRITIS

### 3.1 Alur Pengisian Survei Alumni
```
users (role='alumni')
    └→ alumni (profil)
        └→ alumni_survey_period (terdaftar di periode)
            └→ survey_responses (mulai mengisi, status=draft)
                └→ survey_answers (per pertanyaan)
                    └→ questions (referensi pertanyaan)
                        └→ question_options (opsi jawaban jika ada)
                    → [submit] survey_responses.status = 'selesai'
                    → alumni.survey_status = 'selesai'
```

### 3.2 Alur Data Karir & Employer
```
alumni (profil)
    └→ alumni_work_histories (riwayat kerja)
        └→ employers (referensi employer)
            └→ alumni_employer (verifikasi relasi oleh admin)
                → employers.survey_token di-generate
                → link dikirim via notification_logs
                    └→ survey_responses (employer mengisi survei)
                        → employers.survey_status = 'selesai'
```

### 3.3 Alur Notifikasi
```
survey_periods (admin kirim undangan)
    └→ [pilih questionnaire_id saat kirim]
        └→ notification_templates (pilih template sesuai event)
            └→ [render body dengan variabel alumni]
                └→ notification_logs (catat per pengiriman)
                    └→ alumni_survey_period.invitation_sent_at diperbarui
                    → alumni.survey_status = 'terkirim'
```

### 3.4 Alur OTP Authentication
```
Request OTP:
    users.phone / users.email → identifier
        └→ otp_codes INSERT {
               code: hash('sha256', random_int(100000, 999999)),
               expires_at: now() + 5 menit,
               attempts: 0,
               is_used: 0
           }
        └→ Queue: kirim plaintext OTP ke user via WA/Email

Verifikasi OTP:
    input_otp → hash('sha256', input_otp)
        └→ bandingkan dengan otp_codes.code
        └→ cek: expires_at > now() AND is_used = 0 AND attempts < 3
            VALID → otp_codes.is_used = 1
                  → personal_access_tokens INSERT (Sanctum token)
                  → users.last_login_at = now()
            GAGAL → otp_codes.attempts++
                  → jika attempts >= 3: otp_codes invalid (harus request ulang)
```

---

## 4. DIAGRAM INTEGRITAS REFERENSIAL

### Cascade Rules
| Relasi | ON DELETE | ON UPDATE | Alasan |
|---|---|---|---|
| study_programs → faculties | RESTRICT | CASCADE | Tidak boleh hapus fakultas yang punya prodi aktif |
| alumni → study_programs | RESTRICT | CASCADE | Tidak boleh hapus prodi yang punya alumni |
| alumni → graduation_years | RESTRICT | CASCADE | Tidak boleh hapus angkatan yang punya alumni |
| alumni → users | RESTRICT | CASCADE | Tidak boleh hapus user yang punya profil alumni |
| alumni_work_histories → alumni | CASCADE | CASCADE | Hapus alumni → hapus semua riwayat kerja |
| alumni_work_histories → employers | SET NULL | CASCADE | Hapus employer → set employer_id = NULL di riwayat |
| questions → questionnaires | CASCADE | CASCADE | Hapus kuesioner → hapus semua pertanyaan |
| questions → questionnaire_sections | SET NULL | CASCADE | Hapus seksi → pertanyaan menjadi tanpa seksi |
| question_options → questions | CASCADE | CASCADE | Hapus pertanyaan → hapus semua opsi |
| survey_answers → survey_responses | CASCADE | CASCADE | Hapus respons → hapus semua jawaban |
| survey_answers → questions | RESTRICT | CASCADE | Tidak boleh hapus pertanyaan yang sudah ada jawabannya |
| notification_logs → notification_templates | SET NULL | CASCADE | Hapus template → log tetap ada (template_id = NULL) |
| audit_logs → users | SET NULL | CASCADE | Hapus user → log tetap ada (user_id = NULL) |
| alumni_survey_period → alumni | CASCADE | CASCADE | Hapus alumni → hapus entri pivot |
| alumni_survey_period → survey_periods | CASCADE | CASCADE | Hapus periode → hapus entri pivot |
| alumni_employer → alumni | CASCADE | CASCADE | Hapus alumni → hapus entri pivot |
| alumni_employer → employers | CASCADE | CASCADE | Hapus employer → hapus entri pivot |

---

## 5. CATATAN PENTING DESAIN

### 5.1 Kode OTP dan Keamanan Hash
- OTP plaintext: 6 digit numerik (generated dengan `random_int(100000, 999999)`)
- Yang disimpan di `otp_codes.code`: `hash('sha256', $plaintextOtp)` → 64 karakter hex
- Verifikasi: `hash_equals(hash('sha256', $userInput), $storedHash)`
- VARCHAR(10) **tidak cukup** untuk SHA-256 hash → wajib VARCHAR(64)

### 5.2 Survey Token Employer
- Token: `Str::random(64)` (CSPRNG Laravel, menghasilkan 64 karakter alfanumerik)
- Masa berlaku: 30 hari dari tanggal kirim
- Satu sesi aktif: `survey_token_used_at` diisi saat pertama akses (tapi token masih valid sampai submit/expired)
- Setelah submit: `employers.survey_status = 'selesai'` (token tidak bisa digunakan lagi karena survey sudah selesai)

### 5.3 Desain Periode Survei vs Kuesioner
- `survey_periods` tidak memiliki FK ke `questionnaires`
- Kuesioner dipilih saat admin mengirim undangan massal (parameter `questionnaire_id` di API send-invitations)
- Ini memungkinkan satu periode menggunakan kuesioner berbeda untuk alumni dan employer
- `survey_responses.questionnaire_id` mencatat kuesioner yang benar-benar digunakan oleh setiap responden

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal |
| 1.0.1 | 2026-06-06 | Fix: otp_codes.code VARCHAR(64); tambah alur OTP detail (3.4); tambah catatan desain section 5; perjelas cascade rules employer; tambah UNIQUE(survey_response_id, question_id) di survey_answers |
| 1.0.2 | 2026-06-08 | Update diagram notification_logs: tambah catatan status 'delivered' tidak diisi otomatis dari WA Gateway UNISYA; perjelas kolom provider_response untuk menyimpan message_id |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
