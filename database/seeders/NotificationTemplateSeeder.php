<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * NotificationTemplateSeeder — Seed template notifikasi default SITRAS UNISYA.
 *
 * Skema tabel notification_templates:
 *   - type  : ENUM('email', 'whatsapp')
 *   - event : VARCHAR(100)  — unique per type+event
 *   - body  : TEXT          — satu kolom per row
 *
 * Template channel 'both' dipecah menjadi 2 row (whatsapp + email).
 *
 * Events yang diseed:
 *   survey_invitation_alumni   — Undangan survei ke alumni
 *   survey_invitation_employer — Undangan survei ke employer
 *   survey_reminder_alumni     — Reminder survei ke alumni
 *   survey_reminder_employer   — Reminder survei ke employer
 *   otp_login                  — Kode OTP login (whatsapp only)
 *   alumni_account_created     — Notifikasi akun alumni baru (whatsapp only)
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [

            // ------------------------------------------------------------------
            // 1. Undangan Survei Alumni — WhatsApp
            // ------------------------------------------------------------------
            [
                'type'      => 'whatsapp',
                'event'     => 'survey_invitation_alumni',
                'name'      => 'Undangan Survei Alumni (WhatsApp)',
                'subject'   => null,
                'body'      => "Assalamu'alaikum *{{nama_penerima}}*,\n\nKami mengundang Anda untuk mengisi *Survei Tracer Study {{nama_period}}* dari Universitas Islam Syarifuddin.\n\nSurvei ini bertujuan untuk mengetahui kondisi lulusan dan meningkatkan kualitas pendidikan kami.\n\n📅 *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Link Survei:* {{link_survei}}\n\nJawaban Anda sangat berarti bagi pengembangan kampus kita.\n\nTerima kasih.\n_Tim SITRAS UNISYA_",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 2. Undangan Survei Alumni — Email
            // ------------------------------------------------------------------
            [
                'type'      => 'email',
                'event'     => 'survey_invitation_alumni',
                'name'      => 'Undangan Survei Alumni (Email)',
                'subject'   => 'Undangan Mengisi Survei Tracer Study — {{nama_period}}',
                'body'      => "<p>Assalamu'alaikum <strong>{{nama_penerima}}</strong>,</p>\n<p>Kami mengundang Anda untuk mengisi <strong>Survei Tracer Study {{nama_period}}</strong> dari Universitas Islam Syarifuddin.</p>\n<p>Survei ini bertujuan untuk mengetahui kondisi lulusan dan meningkatkan kualitas pendidikan kami.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Link Survei:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Jawaban Anda sangat berarti bagi pengembangan kampus kita.</p>\n<p>Terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 3. Undangan Survei Employer — WhatsApp
            // ------------------------------------------------------------------
            [
                'type'      => 'whatsapp',
                'event'     => 'survey_invitation_employer',
                'name'      => 'Undangan Survei Employer (WhatsApp)',
                'subject'   => null,
                'body'      => "Yth. *{{nama_penerima}}*,\n\nUniversitas Islam Syarifuddin mengundang Anda untuk memberikan penilaian terhadap alumni kami dalam *Survei {{nama_period}}*.\n\nPenilaian Anda sangat membantu kami dalam meningkatkan kualitas lulusan.\n\n📅 *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Link Survei:* {{link_survei}}\n\nAtas perhatian dan kerja sama Anda, kami ucapkan terima kasih.\n_Tim SITRAS UNISYA_",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 4. Undangan Survei Employer — Email
            // ------------------------------------------------------------------
            [
                'type'      => 'email',
                'event'     => 'survey_invitation_employer',
                'name'      => 'Undangan Survei Employer (Email)',
                'subject'   => 'Undangan Penilaian Alumni — {{nama_period}}',
                'body'      => "<p>Yth. <strong>{{nama_penerima}}</strong>,</p>\n<p>Universitas Islam Syarifuddin mengundang Anda untuk memberikan penilaian terhadap alumni kami dalam <strong>Survei {{nama_period}}</strong>.</p>\n<p>Penilaian Anda sangat membantu kami dalam meningkatkan kualitas lulusan.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Link Survei:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Atas perhatian dan kerja sama Anda, kami ucapkan terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 5. Reminder Survei Alumni — WhatsApp
            // ------------------------------------------------------------------
            [
                'type'      => 'whatsapp',
                'event'     => 'survey_reminder_alumni',
                'name'      => 'Reminder Survei Alumni (WhatsApp)',
                'subject'   => null,
                'body'      => "Assalamu'alaikum *{{nama_penerima}}*,\n\nIni adalah pengingat bahwa *Survei Tracer Study {{nama_period}}* akan segera berakhir.\n\n⚠️ *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Lanjutkan Survei:* {{link_survei}}\n\nMohon segera selesaikan survei Anda sebelum batas waktu.\n\nTerima kasih.\n_Tim SITRAS UNISYA_",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 6. Reminder Survei Alumni — Email
            // ------------------------------------------------------------------
            [
                'type'      => 'email',
                'event'     => 'survey_reminder_alumni',
                'name'      => 'Reminder Survei Alumni (Email)',
                'subject'   => 'Pengingat: Survei Tracer Study {{nama_period}} Segera Berakhir',
                'body'      => "<p>Assalamu'alaikum <strong>{{nama_penerima}}</strong>,</p>\n<p>Ini adalah pengingat bahwa <strong>Survei Tracer Study {{nama_period}}</strong> akan segera berakhir.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Lanjutkan Survei:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Mohon segera selesaikan survei Anda sebelum batas waktu.</p>\n<p>Terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 7. Reminder Survei Employer — WhatsApp
            // ------------------------------------------------------------------
            [
                'type'      => 'whatsapp',
                'event'     => 'survey_reminder_employer',
                'name'      => 'Reminder Survei Employer (WhatsApp)',
                'subject'   => null,
                'body'      => "Yth. *{{nama_penerima}}*,\n\nIni adalah pengingat bahwa *Survei Penilaian Alumni {{nama_period}}* akan segera berakhir.\n\n⚠️ *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Lanjutkan Penilaian:* {{link_survei}}\n\nMohon segera selesaikan penilaian sebelum batas waktu.\n\nTerima kasih.\n_Tim SITRAS UNISYA_",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 8. Reminder Survei Employer — Email
            // ------------------------------------------------------------------
            [
                'type'      => 'email',
                'event'     => 'survey_reminder_employer',
                'name'      => 'Reminder Survei Employer (Email)',
                'subject'   => 'Pengingat: Penilaian Alumni {{nama_period}} Segera Berakhir',
                'body'      => "<p>Yth. <strong>{{nama_penerima}}</strong>,</p>\n<p>Ini adalah pengingat bahwa <strong>Survei Penilaian Alumni {{nama_period}}</strong> akan segera berakhir.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Lanjutkan Penilaian:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Mohon segera selesaikan penilaian sebelum batas waktu.</p>\n<p>Terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables' => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 9. OTP Login — WhatsApp only
            // ------------------------------------------------------------------
            [
                'type'      => 'whatsapp',
                'event'     => 'otp_login',
                'name'      => 'Kode OTP Login',
                'subject'   => null,
                'body'      => "Kode OTP login SITRAS UNISYA Anda adalah:\n\n*{{kode_otp}}*\n\nKode berlaku selama *5 menit*. Jangan bagikan kode ini kepada siapapun.\n\n_SITRAS UNISYA_",
                'variables' => ['kode_otp'],
                'is_active' => true,
            ],

            // ------------------------------------------------------------------
            // 10. Akun Alumni Dibuat — WhatsApp only
            // ------------------------------------------------------------------
            [
                'type'      => 'whatsapp',
                'event'     => 'alumni_account_created',
                'name'      => 'Notifikasi Akun Alumni Dibuat',
                'subject'   => null,
                'body'      => "Assalamu'alaikum *{{nama_alumni}}*,\n\nAkun SITRAS UNISYA Anda telah dibuat.\n\n📱 *Nomor HP:* {{nomor_hp}}\n\nSilakan login menggunakan nomor HP Anda dan kode OTP yang akan dikirim saat login.\n\n🔗 *Login:* {{link_login}}\n\n_Tim SITRAS UNISYA_",
                'variables' => ['nama_alumni', 'nomor_hp', 'link_login'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::updateOrCreate(
                [
                    'type'  => $data['type'],
                    'event' => $data['event'],
                ],
                $data,
            );
        }

        $this->command->info('NotificationTemplateSeeder: ' . count($templates) . ' template berhasil di-seed.');
    }
}
