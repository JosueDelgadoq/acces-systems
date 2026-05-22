<?php

namespace App\Http\Controllers;

use App\Services\TechnicianLiveMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapaTecnicosController extends Controller
{
    public function __construct(
        protected TechnicianLiveMapService $liveMapService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('tracking.view'), 403);

        return response()->json($this->liveMapService->livePayload($request->user()));
    }
}
