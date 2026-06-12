<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

/**
 * NotificationTemplateSeeder — Seed template notifikasi default SITRAS UNISYA.
 *
 * Template menggunakan variabel dalam format {{nama_variabel}}.
 * Sistem akan mereplace variabel ini saat mengirim notifikasi.
 *
 * Template yang diseed:
 *   1. survey_invitation_alumni    — Undangan survei ke alumni
 *   2. survey_invitation_employer  — Undangan survei ke employer
 *   3. survey_reminder_alumni      — Reminder survei ke alumni
 *   4. survey_reminder_employer    — Reminder survei ke employer
 *   5. otp_login                   — Kode OTP login
 *   6. alumni_account_created      — Notifikasi akun alumni baru dibuat
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ------------------------------------------------------------------
            // 1. Undangan Survei Alumni
            // ------------------------------------------------------------------
            [
                'code'         => 'survey_invitation_alumni',
                'name'         => 'Undangan Survei Alumni',
                'description'  => 'Template undangan mengisi survei tracer study untuk alumni.',
                'channel'      => 'both',
                'subject'      => 'Undangan Mengisi Survei Tracer Study — {{nama_period}}',
                'body_whatsapp' => "Assalamu'alaikum *{{nama_penerima}}*,\n\nKami mengundang Anda untuk mengisi *Survei Tracer Study {{nama_period}}* dari Universitas Islam Syarifuddin.\n\nSurvei ini bertujuan untuk mengetahui kondisi lulusan dan meningkatkan kualitas pendidikan kami.\n\n📅 *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Link Survei:* {{link_survei}}\n\nJawaban Anda sangat berarti bagi pengembangan kampus kita.\n\nTerima kasih.\n_Tim SITRAS UNISYA_",
                'body_email'   => "<p>Assalamu'alaikum <strong>{{nama_penerima}}</strong>,</p>\n<p>Kami mengundang Anda untuk mengisi <strong>Survei Tracer Study {{nama_period}}</strong> dari Universitas Islam Syarifuddin.</p>\n<p>Survei ini bertujuan untuk mengetahui kondisi lulusan dan meningkatkan kualitas pendidikan kami.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Link Survei:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Jawaban Anda sangat berarti bagi pengembangan kampus kita.</p>\n<p>Terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables'    => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active'    => true,
            ],

            // ------------------------------------------------------------------
            // 2. Undangan Survei Employer
            // ------------------------------------------------------------------
            [
                'code'         => 'survey_invitation_employer',
                'name'         => 'Undangan Survei Employer',
                'description'  => 'Template undangan penilaian alumni untuk pihak employer/pengguna lulusan.',
                'channel'      => 'both',
                'subject'      => 'Undangan Penilaian Alumni — {{nama_period}}',
                'body_whatsapp' => "Yth. *{{nama_penerima}}*,\n\nUniversitas Islam Syarifuddin mengundang Anda untuk memberikan penilaian terhadap alumni kami dalam *Survei {{nama_period}}*.\n\nPenilaian Anda sangat membantu kami dalam meningkatkan kualitas lulusan.\n\n📅 *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Link Survei:* {{link_survei}}\n\nAtas perhatian dan kerja sama Anda, kami ucapkan terima kasih.\n_Tim SITRAS UNISYA_",
                'body_email'   => "<p>Yth. <strong>{{nama_penerima}}</strong>,</p>\n<p>Universitas Islam Syarifuddin mengundang Anda untuk memberikan penilaian terhadap alumni kami dalam <strong>Survei {{nama_period}}</strong>.</p>\n<p>Penilaian Anda sangat membantu kami dalam meningkatkan kualitas lulusan.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Link Survei:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Atas perhatian dan kerja sama Anda, kami ucapkan terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables'    => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active'    => true,
            ],

            // ------------------------------------------------------------------
            // 3. Reminder Survei Alumni
            // ------------------------------------------------------------------
            [
                'code'         => 'survey_reminder_alumni',
                'name'         => 'Reminder Survei Alumni',
                'description'  => 'Pengingat untuk alumni yang belum menyelesaikan survei.',
                'channel'      => 'both',
                'subject'      => 'Pengingat: Survei Tracer Study {{nama_period}} Segera Berakhir',
                'body_whatsapp' => "Assalamu'alaikum *{{nama_penerima}}*,\n\nIni adalah pengingat bahwa *Survei Tracer Study {{nama_period}}* akan segera berakhir.\n\n⚠️ *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Lanjutkan Survei:* {{link_survei}}\n\nMohon segera selesaikan survei Anda sebelum batas waktu.\n\nTerima kasih.\n_Tim SITRAS UNISYA_",
                'body_email'   => "<p>Assalamu'alaikum <strong>{{nama_penerima}}</strong>,</p>\n<p>Ini adalah pengingat bahwa <strong>Survei Tracer Study {{nama_period}}</strong> akan segera berakhir.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Lanjutkan Survei:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Mohon segera selesaikan survei Anda sebelum batas waktu.</p>\n<p>Terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables'    => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active'    => true,
            ],

            // ------------------------------------------------------------------
            // 4. Reminder Survei Employer
            // ------------------------------------------------------------------
            [
                'code'         => 'survey_reminder_employer',
                'name'         => 'Reminder Survei Employer',
                'description'  => 'Pengingat untuk employer yang belum menyelesaikan penilaian.',
                'channel'      => 'both',
                'subject'      => 'Pengingat: Penilaian Alumni {{nama_period}} Segera Berakhir',
                'body_whatsapp' => "Yth. *{{nama_penerima}}*,\n\nIni adalah pengingat bahwa *Survei Penilaian Alumni {{nama_period}}* akan segera berakhir.\n\n⚠️ *Batas Waktu:* {{tanggal_selesai}}\n🔗 *Lanjutkan Penilaian:* {{link_survei}}\n\nMohon segera selesaikan penilaian sebelum batas waktu.\n\nTerima kasih.\n_Tim SITRAS UNISYA_",
                'body_email'   => "<p>Yth. <strong>{{nama_penerima}}</strong>,</p>\n<p>Ini adalah pengingat bahwa <strong>Survei Penilaian Alumni {{nama_period}}</strong> akan segera berakhir.</p>\n<ul>\n  <li><strong>Batas Waktu:</strong> {{tanggal_selesai}}</li>\n  <li><strong>Lanjutkan Penilaian:</strong> <a href=\"{{link_survei}}\">Klik di sini</a></li>\n</ul>\n<p>Mohon segera selesaikan penilaian sebelum batas waktu.</p>\n<p>Terima kasih.<br><em>Tim SITRAS UNISYA</em></p>",
                'variables'    => ['nama_penerima', 'nama_period', 'tanggal_selesai', 'link_survei'],
                'is_active'    => true,
            ],

            // ------------------------------------------------------------------
            // 5. OTP Login
            // ------------------------------------------------------------------
            [
                'code'         => 'otp_login',
                'name'         => 'Kode OTP Login',
                'description'  => 'Template pengiriman kode OTP untuk proses login.',
                'channel'      => 'whatsapp',
                'subject'      => null,
                'body_whatsapp' => "Kode OTP login SITRAS UNISYA Anda adalah:\n\n*{{kode_otp}}*\n\nKode berlaku selama *5 menit*. Jangan bagikan kode ini kepada siapapun.\n\n_SITRAS UNISYA_",
                'body_email'   => null,
                'variables'    => ['kode_otp'],
                'is_active'    => true,
            ],

            // ------------------------------------------------------------------
            // 6. Akun Alumni Dibuat
            // ------------------------------------------------------------------
            [
                'code'         => 'alumni_account_created',
                'name'         => 'Notifikasi Akun Alumni Dibuat',
                'description'  => 'Dikirim kepada alumni saat akun mereka dibuat oleh admin.',
                'channel'      => 'whatsapp',
                'subject'      => null,
                'body_whatsapp' => "Assalamu'alaikum *{{nama_alumni}}*,\n\nAkun SITRAS UNISYA Anda telah dibuat.\n\n📱 *Nomor HP:* {{nomor_hp}}\n\nSilakan login menggunakan nomor HP Anda dan kode OTP yang akan dikirim saat login.\n\n🔗 *Login:* {{link_login}}\n\n_Tim SITRAS UNISYA_",
                'body_email'   => null,
                'variables'    => ['nama_alumni', 'nomor_hp', 'link_login'],
                'is_active'    => true,
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::updateOrCreate(
                ['code' => $data['code']],
                $data,
            );
        }

        $this->command->info('NotificationTemplateSeeder: ' . count($templates) . ' template berhasil di-seed.');
    }
}
