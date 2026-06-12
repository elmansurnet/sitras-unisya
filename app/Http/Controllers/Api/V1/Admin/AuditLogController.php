<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AuditLogController — Read-only.
 * Tidak ada store, update, atau destroy.
 * Sesuai 07_SECURITY.md §8.3: audit log adalah append-only.
 */
class AuditLogController extends Controller
{
    /**
     * GET /api/v1/admin/audit-logs
     * Daftar audit log dengan filter & pagination.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $query = AuditLog::with('user:id,name,email,role')
            ->latest('created_at');

        if ($request->filled('module')) {
            $query->where('module', $request->input('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('user_role')) {
            $query->where('user_role', $request->input('user_role'));
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->input('model_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $perPage = $request->integer('per_page', 20);
        $logs    = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $logs->items(),
            'meta'    => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/admin/audit-logs/{auditLog}
     * Detail satu entri audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $auditLog->load('user:id,name,email,role');

        return response()->json([
            'success' => true,
            'data'    => $auditLog,
        ]);
    }

    /**
     * GET /api/v1/admin/audit-logs/modules
     * Daftar modul unik yang pernah dilog — untuk dropdown filter.
     */
    public function modules(): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $modules = AuditLog::distinct()
            ->orderBy('module')
            ->pluck('module');

        return response()->json([
            'success' => true,
            'data'    => $modules,
        ]);
    }

    /**
     * GET /api/v1/admin/audit-logs/actions
     * Daftar action unik yang pernah dilog — untuk dropdown filter.
     */
    public function actions(): JsonResponse
    {
        Gate::authorize('superadmin-only');

        $actions = AuditLog::distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json([
            'success' => true,
            'data'    => $actions,
        ]);
    }
}
