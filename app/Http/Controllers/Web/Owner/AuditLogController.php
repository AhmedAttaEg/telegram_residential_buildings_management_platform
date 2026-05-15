<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('owner.audit-logs.index', [
            'auditLogs' => AuditLog::query()
                ->with(['tenant', 'actor', 'subject'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->loadMissing(['tenant', 'actor', 'subject']);

        return view('owner.audit-logs.show', [
            'auditLog' => $auditLog,
        ]);
    }
}
