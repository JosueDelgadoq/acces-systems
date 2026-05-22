<?php

namespace App\Http\Controllers;

use App\Services\CommercialMetricsExportService;
use App\Support\Modules\LeadPermissions;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CommercialMetricsExportController extends Controller
{
    public function xlsx(Request $request, CommercialMetricsExportService $service)
    {
        abort_unless($request->user()?->can(LeadPermissions::VIEW), 403);

        [$from, $until, $commercialId] = $this->resolveFilters($request);

        return $service->downloadXlsx($request->user(), $from, $until, $commercialId);
    }

    public function pdf(Request $request, CommercialMetricsExportService $service)
    {
        abort_unless($request->user()?->can(LeadPermissions::VIEW), 403);

        [$from, $until, $commercialId] = $this->resolveFilters($request);

        return $service->downloadPdf($request->user(), $from, $until, $commercialId);
    }

    protected function resolveFilters(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse((string) $request->query('from'))
            : now()->startOfMonth();

        $until = $request->filled('until')
            ? Carbon::parse((string) $request->query('until'))
            : now()->endOfMonth();

        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy(), $from->copy()];
        }

        return [
            $from,
            $until,
            $request->filled('commercial_id') ? (int) $request->query('commercial_id') : null,
        ];
    }
}
