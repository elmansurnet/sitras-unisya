<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim pesan WhatsApp via gateway UNISYA (wacenter.unisya.ac.id).
     *
     * Endpoint : POST https://wacenter.unisya.ac.id/send-message
     * Auth     : api_key di body (bukan header)
     * Response : { status: true|false, data: { key: { id: "..." } } }
     *
     * Catatan: gateway ini TIDAK menyediakan webhook callback.
     * Status 'delivered' TIDAK diisi otomatis — hanya 'sent' atau 'failed'.
     *
     * @param  string      $number  Nomor tujuan (format: 628xxx atau 08xxx)
     * @param  string      $message Isi pesan
     * @param  string|null $footer  Teks footer opsional
     * @return array{status:bool, data?:array, error?:string}
     */
    public function send(string $number, string $message, ?string $footer = null): array
    {
        $url    = $this->getSetting('wa_gateway_url',
                    'https://wacenter.unisya.ac.id/send-message');
        $apiKey = $this->getSetting('wa_api_key', '');
        $sender = $this->getSetting('wa_sender', '');

        if (empty($apiKey) || empty($sender)) {
            Log::warning('[WhatsAppService] wa_api_key atau wa_sender belum dikonfigurasi');
            return ['status' => false, 'error' => 'WA gateway belum dikonfigurasi'];
        }

        // Normalisasi nomor: 08xxx → 628xxx
        $number = $this->normalizePhone($number);

        $payload = [
            'api_key' => $apiKey,
            'sender'  => $sender,
            'number'  => $number,
            'message' => $message,
            'full'    => 1,
        ];

        if ($footer !== null) {
            $payload['footer'] = $footer;
        }

        try {
            $response = Http::timeout(15)
                ->post($url, $payload);

            $body = $response->json();

            if ($response->successful() && isset($body['status']) && $body['status'] === true) {
                return [
                    'status' => true,
                    'data'   => $body['data'] ?? [],
                ];
            }

            Log::warning('[WhatsAppService] Respons gateway tidak berhasil', [
                'number'   => $number,
                'response' => $body,
            ]);

            return [
                'status' => false,
                'error'  => $body['message'] ?? 'Respons tidak dikenali dari gateway',
                'data'   => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('[WhatsAppService] Exception saat kirim pesan', [
                'number' => $number,
                'error'  => $e->getMessage(),
            ]);

            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalisasi nomor telepon ke format 628xxx.
     * - 08xxx   → 628xxx
     * - +628xxx → 628xxx
     * - 628xxx  → 628xxx (tidak berubah)
     */
    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '08')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        // Default: asumsikan nomor lokal tanpa kode negara
        return '62' . ltrim($phone, '0');
    }

    /**
     * Ambil nilai dari tabel system_settings.
     * Fallback ke $default jika baris tidak ditemukan.
     */
    private function getSetting(string $key, string $default = ''): string
    {
        try {
            $row = SystemSetting::where('key', $key)->first();
            return $row?->value ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }
}
