<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    // ─── TEMPLATE CRUD ─────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/notifications/templates
     */
    public function indexTemplates(Request $request): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $request->validate([
            'channel'  => ['nullable', 'in:whatsapp,email'],
            'event'    => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $templates = NotificationTemplate::query()
            ->when($request->channel,  fn ($q, $v) => $q->forChannel($v))
            ->when($request->event,    fn ($q, $v) => $q->forEvent($v))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('channel')
            ->orderBy('event')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $templates,
        ]);
    }

    /**
     * POST /api/v1/admin/notifications/templates
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $this->authorize('superadmin-only');

        $data = $request->validate([
            'channel'    => ['required', 'in:whatsapp,email'],
            'event'      => ['required', 'string', 'max:100'],
            'name'       => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'footer'     => ['nullable', 'string'],
            'variables'  => ['nullable', 'array'],
            'variables.*' => ['string', 'max:50'],
            'is_active'  => ['boolean'],
        ]);

        // Unique constraint: (channel, event)
        $exists = NotificationTemplate::where('channel', $data['channel'])
            ->where('event', $data['event'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Template untuk channel dan event ini sudah ada.',
                'errors'  => ['event' => ['Kombinasi channel + event sudah terdaftar.']],
            ], 422);
        }

        $template = NotificationTemplate::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Template notifikasi berhasil dibuat.',
            'data'    => $template,
        ], 201);
    }

    /**
     * GET /api/v1/admin/notifications/templates/{template}
     */
    public function showTemplate(int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $template = NotificationTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $template,
        ]);
    }

    /**
     * PUT /api/v1/admin/notifications/templates/{template}
     */
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $this->authorize('superadmin-only');

        $template = NotificationTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template tidak ditemukan.'], 404);
        }

        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'body'       => ['sometimes', 'string'],
            'footer'     => ['nullable', 'string'],
            'variables'  => ['nullable', 'array'],
            'variables.*' => ['string', 'max:50'],
            'is_active'  => ['boolean'],
        ]);

        $template->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Template notifikasi berhasil diperbarui.',
            'data'    => $template->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/admin/notifications/templates/{template}
     */
    public function destroyTemplate(int $id): JsonResponse
    {
        $this->authorize('superadmin-only');

        $template = NotificationTemplate::find($id);

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template tidak ditemukan.'], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template notifikasi berhasil dihapus.',
        ]);
    }

    // ─── NOTIFICATION LOGS ─────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/notifications/logs
     * Log notifikasi dengan filter channel, status, recipient_type.
     */
    public function indexLogs(Request $request): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $request->validate([
            'channel'        => ['nullable', 'in:whatsapp,email'],
            'status'         => ['nullable', 'in:pending,sent,failed,delivered'],
            'recipient_type' => ['nullable', 'in:alumni,employer'],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = NotificationLog::query()
            ->with('template:id,name,event,channel')
            ->when($request->channel,        fn ($q, $v) => $q->where('channel', $v))
            ->when($request->status,         fn ($q, $v) => $q->where('status', $v))
            ->when($request->recipient_type, fn ($q, $v) => $q->where('recipient_type', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('sent_at', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->whereDate('sent_at', '<=', $v))
            ->latest('sent_at');

        $result = $query->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $result->items(),
            'meta'    => [
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/notifications/logs/{log}
     */
    public function showLog(int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $log = NotificationLog::with('template')->find($id);

        if (! $log) {
            return response()->json(['success' => false, 'message' => 'Log tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $log,
        ]);
    }

    /**
     * POST /api/v1/admin/notifications/logs/{log}/resend
     * Kirim ulang notifikasi yang gagal.
     */
    public function resend(int $id): JsonResponse
    {
        $this->authorize('admin-or-superadmin');

        $log = NotificationLog::find($id);

        if (! $log) {
            return response()->json(['success' => false, 'message' => 'Log tidak ditemukan.'], 404);
        }

        if ($log->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya notifikasi gagal yang dapat dikirim ulang.',
            ], 422);
        }

        $newLog = $this->notificationService->send(
            channel  : $log->channel,
            event    : $log->template?->event ?? 'resend',
            variables: [],
            recipient: [
                'recipient_type' => $log->recipient_type,
                'recipient_id'   => $log->recipient_id,
                'phone'          => $log->phone,
                'email'          => $log->email,
                'name'           => $log->phone ?? $log->email ?? '-',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dikirim ulang.',
            'data'    => $newLog,
        ]);
    }
}
