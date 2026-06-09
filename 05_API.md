# 05_API.md
# SPESIFIKASI REST API — SISTEM TRACER STUDY UNISYA
# Versi: 1.0.3 | Tanggal: 2026-06-09

---

## 1. KONVENSI GLOBAL API

### 1.1 Base URL
```
https://tracer.unisya.ac.id/api/v1
```

### 1.2 Format Request & Response
- Content-Type: `application/json`
- Accept: `application/json`
- Authorization: `Bearer {sanctum_token}` (untuk endpoint terproteksi)
- Semua timestamp dalam format ISO 8601: `2024-01-15T10:30:00+07:00`
- Semua angka pecahan (IPK, persentase, koordinat) bertipe **number**, bukan string

### 1.3 Struktur Respons Standar

**Sukses (single resource):**
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": { ... }
}
```

**Sukses (koleksi/paginasi):**
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "...?page=1",
    "last":  "...?page=10",
    "prev":  null,
    "next":  "...?page=2"
  }
}
```

**Gagal (validasi):**
```json
{
  "success": false,
  "message": "Data tidak valid",
  "errors": {
    "nim":   ["NIM sudah terdaftar."],
    "email": ["Format email tidak valid."]
  }
}
```

**Gagal (umum):**
```json
{
  "success": false,
  "message": "Pesan error yang deskriptif",
  "error_code": "ALUMNI_NOT_FOUND"
}
```

### 1.4 HTTP Status Codes
| Kode | Makna |
|---|---|
| 200 | OK – Berhasil |
| 201 | Created – Data baru dibuat |
| 204 | No Content – Berhasil, tanpa body |
| 400 | Bad Request – Request tidak valid |
| 401 | Unauthorized – Tidak terautentikasi |
| 403 | Forbidden – Tidak punya izin |
| 404 | Not Found – Data tidak ditemukan |
| 409 | Conflict – Konflik data (duplikat) |
| 422 | Unprocessable Entity – Validasi gagal |
| 429 | Too Many Requests – Rate limit terlampaui |
| 500 | Internal Server Error |

### 1.5 Rate Limiting
| Endpoint Group | Limit | Window |
|---|---|---|
| `/auth/otp/request` | 5 req | 1 menit |
| `/auth/*` | 10 req | 1 menit |
| `/api/v1/*` (umum, terautentikasi) | 60 req | 1 menit |
| `/api/v1/*` (umum, publik) | 20 req | 1 menit |
| Export & laporan | 5 req | 5 menit |

---

## 2. ENDPOINT AUTENTIKASI

### 2.1 Request OTP Login
```
POST /api/v1/auth/otp/request
Rate limit: 5/menit per IP
Auth: Tidak diperlukan
```

**Request Body:**
```json
{
  "identifier": "20210001",
  "identifier_type": "nim",
  "channel": "whatsapp"
}
```
> `identifier_type`: `nim` | `email` | `phone`
> `channel`: `whatsapp` | `email`

**Response 200:**
```json
{
  "success": true,
  "message": "Kode OTP telah dikirim ke WhatsApp Anda",
  "data": {
    "expires_in": 300,
    "channel": "whatsapp",
    "masked_destination": "08**********78",
    "resend_available_in": 60
  }
}
```

**Response 429 (cooldown aktif):**
```json
{
  "success": false,
  "message": "OTP sudah dikirim. Tunggu 45 detik sebelum request ulang.",
  "data": { "retry_after_seconds": 45 }
}
```

---

### 2.2 Verifikasi OTP & Login
```
POST /api/v1/auth/otp/verify
Rate limit: 10/menit per IP
Auth: Tidak diperlukan
```

**Request Body:**
```json
{
  "identifier": "20210001",
  "identifier_type": "nim",
  "otp_code": "847291"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|abc123xyz...",
    "token_type": "Bearer",
    "expires_at": "2024-01-16T10:30:00+07:00",
    "user": {
      "id": 5,
      "name": "Ahmad Fauzi",
      "role": "alumni",
      "email": "ahmad@email.com",
      "is_profile_complete": false
    }
  }
}
```

**Response 401 (OTP salah):**
```json
{
  "success": false,
  "message": "Kode OTP salah. Sisa percobaan: 2",
  "data": { "remaining_attempts": 2 }
}
```

**Response 401 (OTP kedaluwarsa):**
```json
{
  "success": false,
  "message": "Kode OTP sudah kedaluwarsa. Silakan request OTP baru.",
  "error_code": "OTP_EXPIRED"
}
```

---

### 2.3 Login Superadmin/Admin (Email + Password)
```
POST /api/v1/auth/login
Rate limit: 10/menit per IP
Auth: Tidak diperlukan
```

**Request Body:**
```json
{
  "email": "admin@unisya.ac.id",
  "password": "rahasia123"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "2|def456uvw...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Administrator",
      "role": "superadmin",
      "email": "admin@unisya.ac.id",
      "last_login_at": "2024-01-15T08:00:00+07:00"
    }
  }
}
```

**Response 423 (akun terkunci):**
```json
{
  "success": false,
  "message": "Akun terkunci hingga 10:45. Terlalu banyak percobaan login gagal.",
  "data": { "locked_until": "2024-01-15T10:45:00+07:00" }
}
```

---

### 2.4 Login Employer via Token
```
GET /api/v1/auth/employer/token/{token}
Auth: Tidak diperlukan
```

**Response 200:**
```json
{
  "success": true,
  "message": "Akses diberikan",
  "data": {
    "token": "3|ghi789rst...",
    "token_type": "Bearer",
    "employer": {
      "id": 12,
      "company_name": "PT Maju Bersama",
      "contact_person_name": "Budi Santoso"
    },
    "survey_url": "/employer/survey"
  }
}
```

**Response 401 (token tidak valid/kedaluwarsa):**
```json
{
  "success": false,
  "message": "Link survei tidak valid atau sudah kedaluwarsa.",
  "error_code": "INVALID_EMPLOYER_TOKEN"
}
```

---

### 2.5 Logout
```
POST /api/v1/auth/logout
Auth: Bearer Token
```

**Response 200:**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

---

### 2.6 Profil Pengguna Saat Ini
```
GET /api/v1/auth/me
Auth: Bearer Token
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "Ahmad Fauzi",
    "email": "ahmad@email.com",
    "phone": "081234567890",
    "role": "alumni",
    "is_active": true,
    "last_login_at": "2024-01-15T08:00:00+07:00",
    "alumni": { "id": 3, "nim": "20210001", "survey_status": "sedang_mengisi" }
  }
}
```

---

## 3. ENDPOINT ADMIN — MANAJEMEN ALUMNI

### 3.1 Daftar Alumni
```
GET /api/v1/admin/alumni
Auth: Bearer Token (superadmin, admin)
```

**Query Parameters:**
| Parameter | Tipe | Keterangan |
|---|---|---|
| page | integer | Nomor halaman (default: 1) |
| per_page | integer | Item per halaman (default: 15, max: 100) |
| search | string | Cari nama, NIM, email |
| study_program_id | integer | Filter prodi |
| graduation_year_id | integer | Filter angkatan |
| survey_status | string | belum_disurvei \| terkirim \| sedang_mengisi \| selesai |
| gender | string | L \| P |
| sort_by | string | Field urutan (default: created_at) |
| sort_dir | string | asc \| desc |

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nim": "20210001",
      "full_name": "Ahmad Fauzi",
      "gender": "L",
      "study_program": { "id": 2, "name": "Teknik Informatika", "degree_level": "S1" },
      "graduation_year": { "id": 3, "year": 2024, "academic_year": "2023/2024" },
      "gpa": 3.75,
      "phone": "081234567890",
      "email": "ahmad@email.com",
      "survey_status": "selesai",
      "created_at": "2024-01-01T00:00:00+07:00"
    }
  ],
  "meta": { "current_page": 1, "per_page": 15, "total": 150, "last_page": 10 }
}
```

---

### 3.2 Detail Alumni
```
GET /api/v1/admin/alumni/{id}
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nim": "20210001",
    "nik": "3201234567890001",
    "full_name": "Ahmad Fauzi",
    "gender": "L",
    "birth_place": "Lumajang",
    "birth_date": "2000-03-15",
    "study_program": { "id": 2, "name": "Teknik Informatika", "code": "TI", "degree_level": "S1" },
    "graduation_year": { "id": 3, "year": 2024, "academic_year": "2023/2024", "semester": "Genap" },
    "thesis_title": "Sistem Informasi XYZ",
    "gpa": 3.75,
    "graduation_predicate": "Cumlaude",
    "address": {
      "street": "Jl. Mawar No. 5",
      "village": "Sukodono",
      "district": "Lumajang",
      "city": "Lumajang",
      "province": "Jawa Timur",
      "postal_code": "67311",
      "latitude": -8.1234,
      "longitude": 113.2234
    },
    "phone": "081234567890",
    "email": "ahmad@email.com",
    "linkedin_url": "https://linkedin.com/in/ahmad",
    "photo_url": "https://tracer.unisya.ac.id/storage/photos/abc.jpg",
    "survey_status": "selesai",
    "work_histories": [ { "id": 1, "company_name": "PT Teknologi Maju", "position": "Backend Developer", "is_current": true } ],
    "survey_responses": [ { "id": 45, "status": "selesai", "submitted_at": "2024-01-15T14:30:00+07:00" } ],
    "created_at": "2024-01-01T00:00:00+07:00",
    "updated_at": "2024-01-15T00:00:00+07:00"
  }
}
```

---

### 3.3 Tambah Alumni
```
POST /api/v1/admin/alumni
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "nim": "20210001",
  "nik": "3201234567890001",
  "full_name": "Ahmad Fauzi",
  "gender": "L",
  "birth_place": "Lumajang",
  "birth_date": "2000-03-15",
  "study_program_id": 2,
  "graduation_year_id": 3,
  "thesis_title": "Sistem Informasi XYZ",
  "gpa": 3.75,
  "graduation_predicate": "Cumlaude",
  "address_street": "Jl. Mawar No. 5",
  "address_village": "Sukodono",
  "address_district": "Lumajang",
  "address_city": "Lumajang",
  "address_province": "Jawa Timur",
  "address_postal_code": "67311",
  "phone": "081234567890",
  "email": "ahmad@email.com"
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Data alumni berhasil ditambahkan",
  "data": { "id": 1, "nim": "20210001", "full_name": "Ahmad Fauzi" }
}
```

---

### 3.4 Update Alumni
```
PUT /api/v1/admin/alumni/{id}
Auth: Bearer Token (superadmin, admin)
```
Body: sama seperti POST, semua field opsional.

**Response 200:**
```json
{
  "success": true,
  "message": "Data alumni berhasil diperbarui",
  "data": { ... }
}
```

---

### 3.5 Hapus Alumni (Soft Delete)
```
DELETE /api/v1/admin/alumni/{id}
Auth: Bearer Token (superadmin)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Data alumni berhasil dihapus"
}
```

---

### 3.6 Import Alumni dari Excel
```
POST /api/v1/admin/alumni/import
Auth: Bearer Token (superadmin, admin)
Content-Type: multipart/form-data
```

**Request:**
```
file:               [file .xlsx/.csv, max 10MB]
study_program_id:   2   (opsional, override kolom di file)
graduation_year_id: 3   (opsional, override kolom di file)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Import selesai",
  "data": {
    "total_rows": 150,
    "imported": 145,
    "skipped": 3,
    "failed": 2,
    "errors": [
      { "row": 5,  "nim": "20210005", "message": "NIM sudah terdaftar" },
      { "row": 12, "nim": null,       "message": "NIM wajib diisi" }
    ]
  }
}
```

---

### 3.7 Export Alumni ke Excel
```
GET /api/v1/admin/alumni/export
Auth: Bearer Token (superadmin, admin)
Rate limit: 5 req / 5 menit
```
Query: sama seperti filter daftar alumni.

**Response:** File download `.xlsx`

---

### 3.8 Template Import Alumni
```
GET /api/v1/admin/alumni/import/template
Auth: Bearer Token (superadmin, admin)
```
**Response:** File download template `.xlsx`

---

### 3.9 Kirim Undangan Survei ke Alumni Tertentu
```
POST /api/v1/admin/alumni/{id}/send-invitation
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "channel": "whatsapp",
  "questionnaire_id": 1
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Undangan survei berhasil dikirim ke alumni"
}
```

---

## 4. ENDPOINT ADMIN — MANAJEMEN EMPLOYER

### 4.1 Daftar Employer
```
GET /api/v1/admin/employers
Auth: Bearer Token (superadmin, admin)
```
**Query Parameters:** page, per_page, search, company_type, industry_sector, survey_status, sort_by, sort_dir

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_name": "PT Maju Bersama",
      "company_type": "swasta",
      "industry_sector": "Teknologi Informasi",
      "company_scale": "menengah",
      "address_city": "Surabaya",
      "survey_status": "selesai",
      "contact_person_name": "Budi Santoso"
    }
  ],
  "meta": { ... }
}
```

---

### 4.2 Detail Employer
```
GET /api/v1/admin/employers/{id}
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "company_name": "PT Maju Bersama",
    "company_type": "swasta",
    "industry_sector": "Teknologi Informasi",
    "company_scale": "menengah",
    "address_street": "Jl. Industri No. 10",
    "address_city": "Surabaya",
    "address_province": "Jawa Timur",
    "address_country": "Indonesia",
    "phone": "0312345678",
    "email": "hr@majubersama.com",
    "website": "https://majubersama.com",
    "contact_person_name": "Budi Santoso",
    "contact_person_position": "HRD Manager",
    "contact_person_email": "budi@majubersama.com",
    "contact_person_phone": "081298765432",
    "survey_status": "selesai",
    "survey_token_expires_at": null,
    "alumni_relations": [ { "id": 3, "full_name": "Ahmad Fauzi", "nim": "20210001" } ],
    "created_at": "2024-01-01T00:00:00+07:00"
  }
}
```

---

### 4.3 Tambah Employer
```
POST /api/v1/admin/employers
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "company_name": "PT Maju Bersama",
  "company_type": "swasta",
  "industry_sector": "Teknologi Informasi",
  "company_scale": "menengah",
  "address_city": "Surabaya",
  "address_province": "Jawa Timur",
  "email": "hr@majubersama.com",
  "contact_person_name": "Budi Santoso",
  "contact_person_position": "HRD Manager",
  "contact_person_email": "budi@majubersama.com",
  "contact_person_phone": "081298765432"
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Data employer berhasil ditambahkan",
  "data": { "id": 1, "company_name": "PT Maju Bersama" }
}
```

---

### 4.4 Update Employer
```
PUT /api/v1/admin/employers/{id}
Auth: Bearer Token (superadmin, admin)
```
Body: sama seperti POST, semua field opsional.

---

### 4.5 Hapus Employer (Soft Delete)
```
DELETE /api/v1/admin/employers/{id}
Auth: Bearer Token (superadmin)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Data employer berhasil dihapus"
}
```

---

### 4.6 Kirim Token Survei Employer
```
POST /api/v1/admin/employers/{id}/send-survey-token
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "channel": "email",
  "message_override": "Opsional pesan kustom (isi kosong untuk gunakan template default)"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Link survei berhasil dikirim ke employer",
  "data": {
    "token_expires_at": "2024-02-15T23:59:59+07:00",
    "channel_used": "email"
  }
}
```

---

### 4.7 Regenerate Token Survei Employer
```
POST /api/v1/admin/employers/{id}/regenerate-token
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Token survei berhasil di-generate ulang",
  "data": {
    "new_token_expires_at": "2024-02-20T23:59:59+07:00"
  }
}
```

---

## 5. ENDPOINT ADMIN — KUESIONER DINAMIS

### 5.1 Daftar Kuesioner
```
GET /api/v1/admin/questionnaires
Auth: Bearer Token (superadmin, admin)
```
Query: `type` (alumni/employer), `status` (draft/aktif/arsip)

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Survei Tracer Study Alumni 2024",
      "type": "alumni",
      "version": 1,
      "status": "aktif",
      "estimated_minutes": 10,
      "total_questions": 25,
      "total_responses": 142,
      "published_at": "2024-01-10T09:00:00+07:00"
    }
  ]
}
```

---

### 5.2 Detail Kuesioner (dengan seksi & pertanyaan)
```
GET /api/v1/admin/questionnaires/{id}
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Survei Tracer Study Alumni 2024",
    "description": "Survei ini bertujuan untuk...",
    "type": "alumni",
    "version": 1,
    "status": "aktif",
    "is_paginated": true,
    "estimated_minutes": 10,
    "sections": [
      {
        "id": 1,
        "title": "Data Pekerjaan Saat Ini",
        "order_number": 1,
        "questions": [
          {
            "id": 1,
            "question_text": "Apakah Anda saat ini sudah bekerja?",
            "question_type": "radio",
            "is_required": true,
            "order_number": 1,
            "options": [
              { "id": 1, "option_text": "Ya", "option_value": "ya", "order_number": 1 },
              { "id": 2, "option_text": "Belum", "option_value": "belum", "order_number": 2 }
            ],
            "conditional_logic": null
          }
        ]
      }
    ]
  }
}
```

---

### 5.3 Buat Kuesioner
```
POST /api/v1/admin/questionnaires
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "title": "Survei Tracer Study Alumni 2024",
  "description": "Survei ini bertujuan untuk...",
  "type": "alumni",
  "is_paginated": true,
  "estimated_minutes": 10
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Kuesioner berhasil dibuat",
  "data": { "id": 1, "title": "Survei Tracer Study Alumni 2024", "status": "draft" }
}
```

---

### 5.4 Update Kuesioner
```
PUT /api/v1/admin/questionnaires/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 5.5 Publikasikan Kuesioner
```
POST /api/v1/admin/questionnaires/{id}/publish
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Kuesioner berhasil dipublikasikan",
  "data": { "status": "aktif", "published_at": "2024-01-15T09:00:00+07:00" }
}
```

---

### 5.6 Arsipkan Kuesioner
```
POST /api/v1/admin/questionnaires/{id}/archive
Auth: Bearer Token (superadmin, admin)
```

---

### 5.7 Tambah Seksi
```
POST /api/v1/admin/questionnaires/{id}/sections
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "title": "Data Pekerjaan",
  "description": "Isi dengan informasi pekerjaan Anda saat ini.",
  "order_number": 1
}
```

---

### 5.8 Update Seksi
```
PUT /api/v1/admin/questionnaires/{id}/sections/{section_id}
Auth: Bearer Token (superadmin, admin)
```

---

### 5.9 Hapus Seksi
```
DELETE /api/v1/admin/questionnaires/{id}/sections/{section_id}
Auth: Bearer Token (superadmin, admin)
```

---

### 5.10 Tambah Pertanyaan
```
POST /api/v1/admin/questionnaires/{questionnaire_id}/questions
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "section_id": 1,
  "question_text": "Berapa gaji pertama Anda?",
  "question_type": "select",
  "is_required": true,
  "order_number": 5,
  "help_text": "Pilih rentang yang paling mendekati",
  "options": [
    { "option_text": "< Rp 1 Juta",    "option_value": "lt_1jt",  "order_number": 1 },
    { "option_text": "Rp 1 – 3 Juta",  "option_value": "1_3jt",   "order_number": 2 },
    { "option_text": "Rp 3 – 5 Juta",  "option_value": "3_5jt",   "order_number": 3 },
    { "option_text": "> Rp 5 Juta",    "option_value": "gt_5jt",  "order_number": 4 }
  ],
  "conditional_logic": {
    "show_if": { "question_id": 1, "operator": "equals", "value": "ya" }
  }
}
```

---

### 5.11 Update Pertanyaan
```
PUT /api/v1/admin/questionnaires/{questionnaire_id}/questions/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 5.12 Hapus Pertanyaan
```
DELETE /api/v1/admin/questionnaires/{questionnaire_id}/questions/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 5.13 Update Urutan Pertanyaan (Reorder)
```
PUT /api/v1/admin/questionnaires/{id}/questions/reorder
Auth: Bearer Token (superadmin, admin)
```

> **⚠️ Catatan Implementasi Laravel (WAJIB DIBACA):**
> Route `/questions/reorder` harus didaftarkan **SEBELUM** route resource `questions/{id}`
> di `routes/api.php`. Jika tidak, Laravel akan menganggap `reorder` sebagai nilai `{id}`
> dan meneruskan ke method `show` / `update` alih-alih method reorder.
>
> Implementasi yang benar di `routes/api.php`:
> ```php
> // Daftarkan custom route SEBELUM apiResource
> Route::put('questionnaires/{id}/questions/reorder', [QuestionnaireController::class, 'reorderQuestions']);
> Route::apiResource('questionnaires/{id}/questions', QuestionController::class);
> ```

```json
{ "order": [3, 1, 5, 2, 4] }
```

---

## 6. ENDPOINT ADMIN — PERIODE SURVEI

### 6.1 Daftar Periode
```
GET /api/v1/admin/survey-periods
Auth: Bearer Token (superadmin, admin)
```
Query: `status` (draft/active/closed), `year`

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Tracer Study 2024",
      "year": 2024,
      "start_date": "2024-01-15",
      "end_date": "2024-03-31",
      "status": "active",
      "total_alumni": 250,
      "response_rate": 68.5
    }
  ]
}
```

---

### 6.2 Detail Periode
```
GET /api/v1/admin/survey-periods/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 6.3 Buat Periode
```
POST /api/v1/admin/survey-periods
Auth: Bearer Token (superadmin, admin)
```
```json
{
  "name": "Tracer Study 2024",
  "year": 2024,
  "start_date": "2024-01-15",
  "end_date": "2024-03-31",
  "target_graduation_years": [3, 4],
  "description": "Survei untuk angkatan 2022 dan 2023"
}
```

---

### 6.4 Update Periode
```
PUT /api/v1/admin/survey-periods/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 6.5 Aktivasi Periode
```
POST /api/v1/admin/survey-periods/{id}/activate
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Periode survei berhasil diaktifkan",
  "data": { "status": "active" }
}
```

---

### 6.6 Tutup Periode
```
POST /api/v1/admin/survey-periods/{id}/close
Auth: Bearer Token (superadmin, admin)
```

---

### 6.7 Kirim Undangan Massal
```
POST /api/v1/admin/survey-periods/{id}/send-invitations
Auth: Bearer Token (superadmin, admin)
```

> **Catatan Desain:** Parameter `questionnaire_id` wajib karena `survey_periods` tidak menyimpan
> FK ke `questionnaires`. Ini memungkinkan satu periode menggunakan kuesioner berbeda untuk tiap batch pengiriman.

```json
{
  "channel": "both",
  "questionnaire_id": 1,
  "filter_status": "belum_disurvei"
}
```
> `filter_status`: `belum_disurvei` | `terkirim` (untuk reminder)
> `channel`: `email` | `whatsapp` | `both`

**Response 200:**
```json
{
  "success": true,
  "message": "Undangan sedang diproses via queue",
  "data": {
    "queued": 250,
    "estimated_completion_minutes": 5
  }
}
```

---

## 7. ENDPOINT ADMIN — DASHBOARD & STATISTIK

### 7.1 Ringkasan Dashboard
```
GET /api/v1/admin/dashboard/summary
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "total_alumni": 1250,
    "total_employers": 87,
    "active_survey_period": {
      "id": 3,
      "name": "Tracer Study 2024",
      "response_rate": 68.5,
      "responses_completed": 342,
      "responses_pending": 158,
      "end_date": "2024-03-31"
    },
    "employment_stats": {
      "employed": 280,
      "self_employed": 35,
      "continuing_study": 15,
      "not_working": 12
    },
    "recent_activities": [
      {
        "action": "submit_survey",
        "description": "Ahmad Fauzi menyelesaikan survei",
        "created_at": "2024-01-15T14:30:00+07:00"
      }
    ]
  }
}
```

---

### 7.2 Statistik Ketenagakerjaan
```
GET /api/v1/admin/dashboard/employment-stats
Auth: Bearer Token (superadmin, admin)
```
Query: `period_id`, `graduation_year_id`, `study_program_id`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "employment_rate": 82.5,
    "average_waiting_months": 3.2,
    "relevance_rate": 71.3,
    "by_industry": [
      { "sector": "Teknologi Informasi", "count": 85, "percentage": 24.9 }
    ],
    "by_salary_range": [
      { "range": "Rp 3–5 Juta", "count": 120, "percentage": 35.1 }
    ],
    "by_graduation_year": [
      { "year": 2024, "academic_year": "2023/2024", "employed": 95, "total": 120, "rate": 79.2 }
    ],
    "by_study_program": [
      { "id": 2, "name": "Teknik Informatika", "employed": 85, "total": 98, "rate": 86.7 }
    ]
  }
}
```

---

### 7.3 Data Peta Sebaran Alumni
```
GET /api/v1/admin/dashboard/alumni-map
Auth: Bearer Token (superadmin, admin)
```
Query: `graduation_year_id`, `study_program_id`

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "province": "Jawa Timur",
      "city": "Lumajang",
      "count": 145,
      "coordinates": { "lat": -8.1234, "lng": 113.2234 }
    }
  ]
}
```

---

## 8. ENDPOINT ADMIN — LAPORAN

### 8.1 Generate Laporan PDF
```
POST /api/v1/admin/reports/generate/pdf
Auth: Bearer Token (superadmin, admin)
Rate limit: 5 req / 5 menit
```
```json
{
  "type": "tracer_study",
  "period_id": 3,
  "study_program_id": null,
  "graduation_year_id": null
}
```
**Response:** File PDF download (Content-Disposition: attachment)

---

### 8.2 Generate Laporan Excel
```
POST /api/v1/admin/reports/generate/excel
Auth: Bearer Token (superadmin, admin)
Rate limit: 5 req / 5 menit
```
```json
{
  "type": "tracer_study",
  "period_id": 3,
  "study_program_id": null,
  "graduation_year_id": null
}
```
**Response:** File `.xlsx` download

---

### 8.3 Daftar Laporan Tersimpan
```
GET /api/v1/admin/reports
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "tracer_study",
      "format": "pdf",
      "filename": "tracer-study-2024-periode3.pdf",
      "file_size_kb": 245,
      "generated_by": "Administrator",
      "created_at": "2024-01-15T10:00:00+07:00",
      "download_url": "/api/v1/admin/reports/1/download"
    }
  ]
}
```

---

### 8.4 Download Laporan Tersimpan
```
GET /api/v1/admin/reports/{id}/download
Auth: Bearer Token (superadmin, admin)
```
**Response:** File download

---

## 9. ENDPOINT ADMIN — NOTIFIKASI

> **Catatan:** Endpoint ini mengatur template notifikasi dan melihat log pengiriman.
> Pengiriman notifikasi massal dilakukan via endpoint Periode Survei (Section 6.7).

### 9.1 Daftar Template Notifikasi
```
GET /api/v1/admin/notifications/templates
Auth: Bearer Token (superadmin, admin)
```
Query: `type` (email/whatsapp), `event`

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Undangan Survei Alumni - WhatsApp",
      "type": "whatsapp",
      "event": "survey_invitation",
      "is_active": true,
      "variables": ["{{alumni_name}}", "{{survey_url}}", "{{period_name}}"],
      "updated_at": "2024-01-01T00:00:00+07:00"
    },
    {
      "id": 2,
      "name": "OTP Login - Email",
      "type": "email",
      "event": "otp_login",
      "is_active": true,
      "variables": ["{{otp_code}}", "{{alumni_name}}", "{{expires_in_minutes}}"],
      "updated_at": "2024-01-01T00:00:00+07:00"
    }
  ]
}
```

---

### 9.2 Detail Template Notifikasi
```
GET /api/v1/admin/notifications/templates/{id}
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Undangan Survei Alumni - WhatsApp",
    "type": "whatsapp",
    "event": "survey_invitation",
    "subject": null,
    "body": "Assalamu'alaikum {{alumni_name}},\n\nUniversitas Islam Syarifuddin mengundang Anda mengisi survei tracer study *{{period_name}}*.\n\nLink survei: {{survey_url}}\n\nJazakallah khairan.",
    "variables": {
      "alumni_name":  "Nama lengkap alumni",
      "period_name":  "Nama periode survei",
      "survey_url":   "URL link survei alumni"
    },
    "is_active": true,
    "created_at": "2024-01-01T00:00:00+07:00",
    "updated_at": "2024-01-01T00:00:00+07:00"
  }
}
```

---

### 9.3 Buat Template Notifikasi
```
POST /api/v1/admin/notifications/templates
Auth: Bearer Token (superadmin, admin)
```

**Request Body:**
```json
{
  "name": "Reminder Survei Alumni - Email",
  "type": "email",
  "event": "survey_reminder",
  "subject": "Reminder: Mohon Lengkapi Survei Tracer Study {{period_name}}",
  "body": "Yth. {{alumni_name}},\n\nKami mengingatkan Anda untuk segera mengisi survei tracer study...",
  "variables": {
    "alumni_name": "Nama lengkap alumni",
    "period_name": "Nama periode survei",
    "survey_url":  "URL link survei alumni"
  },
  "is_active": true
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Template notifikasi berhasil dibuat",
  "data": { "id": 3, "name": "Reminder Survei Alumni - Email" }
}
```

---

### 9.4 Update Template Notifikasi
```
PUT /api/v1/admin/notifications/templates/{id}
Auth: Bearer Token (superadmin, admin)
```
Body: sama seperti POST, semua field opsional.

**Response 200:**
```json
{
  "success": true,
  "message": "Template notifikasi berhasil diperbarui",
  "data": { ... }
}
```

---

### 9.5 Hapus Template Notifikasi
```
DELETE /api/v1/admin/notifications/templates/{id}
Auth: Bearer Token (superadmin, admin)
```

**Response 200:**
```json
{
  "success": true,
  "message": "Template notifikasi berhasil dihapus"
}
```

---

### 9.6 Log Pengiriman Notifikasi
```
GET /api/v1/admin/notifications/logs
Auth: Bearer Token (superadmin, admin)
```

**Query Parameters:**
| Parameter | Tipe | Keterangan |
|---|---|---|
| page | integer | Nomor halaman (default: 1) |
| per_page | integer | Default: 20, max: 100 |
| type | string | email \| whatsapp |
| status | string | pending \| sent \| failed \| delivered |
| recipient_type | string | alumni \| employer |
| date_from | date | Filter dari tanggal (YYYY-MM-DD) |
| date_to | date | Filter sampai tanggal (YYYY-MM-DD) |

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "whatsapp",
      "recipient": "08**********78",
      "recipient_type": "alumni",
      "subject": null,
      "status": "delivered",
      "error_message": null,
      "sent_at": "2024-01-15T08:00:00+07:00",
      "template": { "id": 1, "name": "Undangan Survei Alumni - WhatsApp" }
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 500, "last_page": 25 }
}
```

---

## 10. ENDPOINT ADMIN — KONFIGURASI SISTEM

### 10.1 Daftar & Update Pengaturan Sistem
```
GET /api/v1/admin/settings
PUT /api/v1/admin/settings
Auth: Bearer Token (superadmin)
```

**GET Response 200:**
```json
{
  "success": true,
  "data": {
    "general": [
      { "key": "university_name", "value": "Universitas Islam Syarifuddin", "label": "Nama Universitas", "type": "string" },
      { "key": "university_tagline", "value": "Menelusuri Jejak, Meraih Mutu", "label": "Tagline", "type": "string" }
    ],
    "smtp": [
      { "key": "smtp_host", "value": "smtp.gmail.com", "label": "SMTP Host", "type": "string" },
      { "key": "smtp_port", "value": "587", "label": "SMTP Port", "type": "integer" }
    ],
    "whatsapp": [
      { "key": "wa_gateway_url", "value": "https://wacenter.unisya.ac.id/send-message", "label": "URL Gateway WA", "type": "string" },
      { "key": "wa_api_key",     "value": "••••••••••", "label": "API Key Gateway WA", "type": "string" },
      { "key": "wa_sender",      "value": "62888xxxx", "label": "Nomor Pengirim WA", "type": "string" }
    ]
  }
}
```

**PUT Request Body:**
```json
{
  "settings": [
    { "key": "university_name", "value": "Universitas Islam Syarifuddin" },
    { "key": "wa_api_key",      "value": "abc123xyz" },
    { "key": "wa_sender",       "value": "62888xxxx" }
  ]
}
```

---

### 10.2 CRUD Program Studi
```
GET    /api/v1/admin/study-programs
POST   /api/v1/admin/study-programs
GET    /api/v1/admin/study-programs/{id}
PUT    /api/v1/admin/study-programs/{id}
DELETE /api/v1/admin/study-programs/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 10.3 CRUD Fakultas
```
GET    /api/v1/admin/faculties
POST   /api/v1/admin/faculties
GET    /api/v1/admin/faculties/{id}
PUT    /api/v1/admin/faculties/{id}
DELETE /api/v1/admin/faculties/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 10.4 CRUD Tahun Kelulusan
```
GET    /api/v1/admin/graduation-years
POST   /api/v1/admin/graduation-years
GET    /api/v1/admin/graduation-years/{id}
PUT    /api/v1/admin/graduation-years/{id}
DELETE /api/v1/admin/graduation-years/{id}
Auth: Bearer Token (superadmin, admin)
```

---

### 10.5 Audit Log
```
GET /api/v1/admin/audit-logs
Auth: Bearer Token (superadmin)
```
**Query Parameters:** user_id, action, module, date_from, date_to, page, per_page

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": { "id": 1, "name": "Administrator", "role": "superadmin" },
      "action": "update",
      "module": "Alumni",
      "model_type": "App\\Models\\Alumni",
      "model_id": 5,
      "old_values": { "full_name": "Ahmad" },
      "new_values": { "full_name": "Ahmad Fauzi" },
      "ip_address": "192.168.1.1",
      "created_at": "2024-01-15T10:00:00+07:00"
    }
  ],
  "meta": { ... }
}
```

---

### 10.6 Manajemen Pengguna Admin
```
GET    /api/v1/admin/users
POST   /api/v1/admin/users
GET    /api/v1/admin/users/{id}
PUT    /api/v1/admin/users/{id}
DELETE /api/v1/admin/users/{id}
POST   /api/v1/admin/users/{id}/toggle-active
Auth: Bearer Token (superadmin)
```

**POST Request Body:**
```json
{
  "name": "Staf Tracer",
  "email": "staf@unisya.ac.id",
  "password": "password123",
  "role": "admin",
  "phone": "081234567890"
}
```

---

## 11. ENDPOINT ALUMNI

### 11.1 Profil Alumni (diri sendiri)
```
GET /api/v1/alumni/profile
PUT /api/v1/alumni/profile
Auth: Bearer Token (alumni)
```

**PUT Request Body:**
```json
{
  "birth_place": "Lumajang",
  "birth_date": "2000-03-15",
  "address_street": "Jl. Mawar No. 5",
  "address_village": "Sukodono",
  "address_district": "Lumajang",
  "address_city": "Lumajang",
  "address_province": "Jawa Timur",
  "address_postal_code": "67311",
  "phone": "081234567890",
  "email": "ahmad@email.com",
  "linkedin_url": "https://linkedin.com/in/ahmad"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Profil berhasil diperbarui",
  "data": { "profile_completion_percentage": 85 }
}
```

---

### 11.2 Upload Foto Profil
```
POST /api/v1/alumni/profile/photo
Auth: Bearer Token (alumni)
Content-Type: multipart/form-data
```
```
photo: [file jpeg/jpg/png, max 2MB, max 2000x2000px]
```

**Response 200:**
```json
{
  "success": true,
  "message": "Foto profil berhasil diperbarui",
  "data": { "photo_url": "https://tracer.unisya.ac.id/storage/photos/uuid.jpg" }
}
```

---

### 11.3 Riwayat Pekerjaan
```
GET    /api/v1/alumni/work-histories
POST   /api/v1/alumni/work-histories
PUT    /api/v1/alumni/work-histories/{id}
DELETE /api/v1/alumni/work-histories/{id}
Auth: Bearer Token (alumni)
```

**POST Request Body:**
```json
{
  "company_name": "PT Teknologi Maju",
  "position": "Junior Backend Developer",
  "employment_type": "penuh_waktu",
  "industry_sector": "Teknologi Informasi",
  "start_date": "2024-02-01",
  "end_date": null,
  "is_current": true,
  "city": "Surabaya",
  "province": "Jawa Timur",
  "country": "Indonesia",
  "monthly_salary_range": "3_5jt",
  "is_relevant_to_study": true,
  "waiting_time_months": 2,
  "description": "Mengembangkan aplikasi backend menggunakan Laravel."
}
```

**GET Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_name": "PT Teknologi Maju",
      "position": "Junior Backend Developer",
      "employment_type": "penuh_waktu",
      "start_date": "2024-02-01",
      "end_date": null,
      "is_current": true,
      "city": "Surabaya",
      "monthly_salary_range": "3_5jt",
      "is_relevant_to_study": true,
      "waiting_time_months": 2
    }
  ]
}
```

---

### 11.4 Kuesioner Survei Alumni (Ambil/Lanjutkan)
```
GET /api/v1/alumni/survey
Auth: Bearer Token (alumni)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "questionnaire": {
      "id": 1,
      "title": "Survei Tracer Study Alumni 2024",
      "estimated_minutes": 10,
      "sections": [ { ... } ]
    },
    "response": {
      "id": 45,
      "status": "draft",
      "completion_percentage": 40,
      "answers": {
        "1": { "answer_value": "ya" },
        "2": { "answer_options": [1, 3] },
        "3": { "answer_text": "Saya bekerja di bidang teknologi." }
      },
      "started_at": "2024-01-15T10:00:00+07:00"
    },
    "period": { "id": 3, "name": "Tracer Study 2024", "end_date": "2024-03-31" }
  }
}
```

**Response 404 (tidak ada survei aktif):**
```json
{
  "success": false,
  "message": "Tidak ada periode survei aktif saat ini.",
  "error_code": "NO_ACTIVE_SURVEY"
}
```

---

### 11.5 Simpan Jawaban Survei (Draft)
```
POST /api/v1/alumni/survey/save-draft
Auth: Bearer Token (alumni)
```

```json
{
  "response_id": 45,
  "answers": [
    { "question_id": 1, "answer_value": "ya" },
    { "question_id": 2, "answer_options": [1, 3] },
    { "question_id": 3, "answer_text": "Saya bekerja di bidang teknologi." }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Draft survei berhasil disimpan",
  "data": { "completion_percentage": 60 }
}
```

---

### 11.6 Submit Survei Alumni
```
POST /api/v1/alumni/survey/submit
Auth: Bearer Token (alumni)
```
```json
{
  "response_id": 45,
  "answers": [
    { "question_id": 1, "answer_value": "ya" },
    { "question_id": 2, "answer_options": [1, 3] }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Survei berhasil dikirim. Terima kasih atas partisipasi Anda!",
  "data": {
    "submitted_at": "2024-01-15T14:30:00+07:00",
    "completion_percentage": 100
  }
}
```

---

## 12. ENDPOINT EMPLOYER

### 12.1 Profil Employer (via Bearer Token)
```
GET /api/v1/employer/profile
PUT /api/v1/employer/profile
Auth: Bearer Token (employer)
```

**PUT Request Body:**
```json
{
  "company_name": "PT Maju Bersama",
  "address_city": "Surabaya",
  "address_province": "Jawa Timur",
  "phone": "0312345678",
  "email": "hr@majubersama.com",
  "contact_person_name": "Budi Santoso",
  "contact_person_position": "HRD Manager",
  "contact_person_email": "budi@majubersama.com",
  "contact_person_phone": "081298765432"
}
```

---

### 12.2 Kuesioner Survei Employer
```
GET /api/v1/employer/survey
Auth: Bearer Token (employer)
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "questionnaire": {
      "id": 2,
      "title": "Survei Kepuasan Employer 2024",
      "estimated_minutes": 7,
      "sections": [ { ... } ]
    },
    "response": {
      "id": 12,
      "status": "draft",
      "completion_percentage": 0,
      "answers": {}
    }
  }
}
```

---

### 12.3 Simpan Draft Survei Employer
```
POST /api/v1/employer/survey/save-draft
Auth: Bearer Token (employer)
```

```json
{
  "response_id": 12,
  "answers": [
    { "question_id": 10, "answer_value": "4" },
    { "question_id": 11, "answer_text": "Alumni memiliki kompetensi yang baik." }
  ]
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Draft survei berhasil disimpan",
  "data": { "completion_percentage": 50 }
}
```

---

### 12.4 Submit Survei Employer
```
POST /api/v1/employer/survey/submit
Auth: Bearer Token (employer)
```
```json
{
  "response_id": 12,
  "answers": [ ... ]
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Survei berhasil dikirim. Terima kasih atas partisipasi perusahaan Anda!",
  "data": {
    "submitted_at": "2024-01-20T11:00:00+07:00",
    "completion_percentage": 100
  }
}
```

---

## 13. ENDPOINT PUBLIK (Tanpa Auth)

### 13.1 Validasi Token Employer
```
GET /api/v1/public/employer-token/{token}/validate
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "company_name": "PT Maju Bersama",
    "expires_at": "2024-02-15T23:59:59+07:00",
    "survey_completed": false
  }
}
```

**Response 200 (token tidak valid):**
```json
{
  "success": true,
  "data": {
    "is_valid": false,
    "reason": "Token sudah kedaluwarsa"
  }
}
```

---

### 13.2 Master Data (untuk form publik/dropdown)
```
GET /api/v1/public/study-programs
GET /api/v1/public/faculties
GET /api/v1/public/industry-sectors
GET /api/v1/public/graduation-years
GET /api/v1/public/salary-ranges
```

**Response 200 (contoh study-programs):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Teknik Informatika",
      "code": "TI",
      "degree_level": "S1",
      "faculty": { "id": 1, "name": "Fakultas Sains dan Teknologi" }
    }
  ]
}
```

---

## 14. RINGKASAN ENDPOINT

| Method | Endpoint | Akses | Keterangan |
|---|---|---|---|
| POST | /auth/otp/request | Publik | Minta OTP alumni |
| POST | /auth/otp/verify | Publik | Verifikasi OTP + login alumni |
| POST | /auth/login | Publik | Login admin/superadmin |
| GET | /auth/employer/token/{token} | Publik | Login employer via token |
| POST | /auth/logout | Auth | Logout |
| GET | /auth/me | Auth | Data user saat ini |
| GET | /admin/alumni | Admin | Daftar alumni |
| POST | /admin/alumni | Admin | Tambah alumni |
| GET | /admin/alumni/{id} | Admin | Detail alumni |
| PUT | /admin/alumni/{id} | Admin | Update alumni |
| DELETE | /admin/alumni/{id} | Superadmin | Hapus alumni (soft) |
| POST | /admin/alumni/import | Admin | Import Excel |
| GET | /admin/alumni/export | Admin | Export Excel |
| GET | /admin/alumni/import/template | Admin | Template import |
| POST | /admin/alumni/{id}/send-invitation | Admin | Kirim undangan ke alumni |
| GET | /admin/employers | Admin | Daftar employer |
| POST | /admin/employers | Admin | Tambah employer |
| GET | /admin/employers/{id} | Admin | Detail employer |
| PUT | /admin/employers/{id} | Admin | Update employer |
| DELETE | /admin/employers/{id} | Superadmin | Hapus employer (soft) |
| POST | /admin/employers/{id}/send-survey-token | Admin | Kirim token survei |
| POST | /admin/employers/{id}/regenerate-token | Admin | Regenerate token |
| GET | /admin/questionnaires | Admin | Daftar kuesioner |
| POST | /admin/questionnaires | Admin | Buat kuesioner |
| GET | /admin/questionnaires/{id} | Admin | Detail kuesioner |
| PUT | /admin/questionnaires/{id} | Admin | Update kuesioner |
| POST | /admin/questionnaires/{id}/publish | Admin | Publikasikan kuesioner |
| POST | /admin/questionnaires/{id}/archive | Admin | Arsipkan kuesioner |
| POST | /admin/questionnaires/{id}/sections | Admin | Tambah seksi |
| PUT | /admin/questionnaires/{id}/sections/{sid} | Admin | Update seksi |
| DELETE | /admin/questionnaires/{id}/sections/{sid} | Admin | Hapus seksi |
| POST | /admin/questionnaires/{id}/questions | Admin | Tambah pertanyaan |
| PUT | /admin/questionnaires/{id}/questions/{qid} | Admin | Update pertanyaan |
| DELETE | /admin/questionnaires/{id}/questions/{qid} | Admin | Hapus pertanyaan |
| PUT | /admin/questionnaires/{id}/questions/reorder | Admin | Reorder pertanyaan |
| GET | /admin/survey-periods | Admin | Daftar periode |
| POST | /admin/survey-periods | Admin | Buat periode |
| GET | /admin/survey-periods/{id} | Admin | Detail periode |
| PUT | /admin/survey-periods/{id} | Admin | Update periode |
| POST | /admin/survey-periods/{id}/activate | Admin | Aktivasi periode |
| POST | /admin/survey-periods/{id}/close | Admin | Tutup periode |
| POST | /admin/survey-periods/{id}/send-invitations | Admin | Kirim undangan massal |
| GET | /admin/dashboard/summary | Admin | Ringkasan dashboard |
| GET | /admin/dashboard/employment-stats | Admin | Statistik ketenagakerjaan |
| GET | /admin/dashboard/alumni-map | Admin | Data peta sebaran |
| POST | /admin/reports/generate/pdf | Admin | Generate laporan PDF |
| POST | /admin/reports/generate/excel | Admin | Generate laporan Excel |
| GET | /admin/reports | Admin | Daftar laporan tersimpan |
| GET | /admin/reports/{id}/download | Admin | Download laporan |
| GET | /admin/notifications/templates | Admin | Daftar template notifikasi |
| POST | /admin/notifications/templates | Admin | Buat template |
| GET | /admin/notifications/templates/{id} | Admin | Detail template |
| PUT | /admin/notifications/templates/{id} | Admin | Update template |
| DELETE | /admin/notifications/templates/{id} | Admin | Hapus template |
| GET | /admin/notifications/logs | Admin | Log pengiriman notifikasi |
| GET | /admin/settings | Superadmin | Lihat pengaturan sistem |
| PUT | /admin/settings | Superadmin | Update pengaturan sistem |
| GET/POST/PUT/DELETE | /admin/study-programs[/{id}] | Admin | CRUD prodi |
| GET/POST/PUT/DELETE | /admin/faculties[/{id}] | Admin | CRUD fakultas |
| GET/POST/PUT/DELETE | /admin/graduation-years[/{id}] | Admin | CRUD tahun kelulusan |
| GET | /admin/audit-logs | Superadmin | Audit log |
| GET/POST/PUT/DELETE | /admin/users[/{id}] | Superadmin | CRUD pengguna admin |
| POST | /admin/users/{id}/toggle-active | Superadmin | Aktif/nonaktifkan admin |
| GET | /alumni/profile | Alumni | Profil diri sendiri |
| PUT | /alumni/profile | Alumni | Update profil |
| POST | /alumni/profile/photo | Alumni | Upload foto |
| GET | /alumni/work-histories | Alumni | Daftar riwayat kerja |
| POST | /alumni/work-histories | Alumni | Tambah riwayat kerja |
| PUT | /alumni/work-histories/{id} | Alumni | Update riwayat kerja |
| DELETE | /alumni/work-histories/{id} | Alumni | Hapus riwayat kerja |
| GET | /alumni/survey | Alumni | Ambil kuesioner survei |
| POST | /alumni/survey/save-draft | Alumni | Simpan draft survei |
| POST | /alumni/survey/submit | Alumni | Submit survei |
| GET | /employer/profile | Employer | Profil employer |
| PUT | /employer/profile | Employer | Update profil |
| GET | /employer/survey | Employer | Ambil kuesioner survei |
| POST | /employer/survey/save-draft | Employer | Simpan draft survei |
| POST | /employer/survey/submit | Employer | Submit survei |
| GET | /public/employer-token/{token}/validate | Publik | Validasi token employer |
| GET | /public/study-programs | Publik | Master data prodi |
| GET | /public/faculties | Publik | Master data fakultas |
| GET | /public/industry-sectors | Publik | Master data sektor industri |
| GET | /public/graduation-years | Publik | Master data angkatan |
| GET | /public/salary-ranges | Publik | Master data rentang gaji |

**Total: 73 endpoint**

---

## RIWAYAT VERSI

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-06-04 | Dokumen awal, 30+ endpoint |
| 1.0.1 | 2026-06-06 | Tambah Section 9 (Notifikasi: CRUD template + log); fix gpa string→number; tambah DELETE employer (4.5); tambah employer save-draft (12.3); tambah send-invitation per alumni (3.9); tambah archive kuesioner (5.6); CRUD seksi kuesioner (5.7-5.9); update/hapus pertanyaan (5.11-5.12); tambah period detail+update (6.2,6.4); tambah close period (6.6); tambah report download (8.4); tambah toggle-active user (10.6); tambah catatan desain survey_periods; lengkapi summary table (Section 14 — 73 endpoint) |
| 1.0.2 | 2026-06-08 | Update contoh GET/PUT settings (Section 10.1): key `wa_gateway_url` → gateway UNISYA, tambah `wa_api_key` dan `wa_sender` sebagai key terpisah yang bisa dikonfigurasi via menu Setting; ganti contoh `wa_gateway_token` → `wa_api_key` di PUT request body |
| 1.0.3 | 2026-06-09 | Tambah catatan implementasi Laravel pada Section 5.13 (endpoint reorder pertanyaan): route `/questions/reorder` wajib didaftarkan sebelum route resource `questions/{id}` untuk menghindari konflik routing (INC-04) |

---

*Dokumen ini adalah dokumen hidup. Setiap perubahan harus dicatat di 09_CHANGELOG.md*
