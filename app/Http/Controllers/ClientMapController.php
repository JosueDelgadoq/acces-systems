<?php

namespace App\Http\Controllers;

use App\Services\ClientMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientMapController extends Controller
{
    public function __construct(
        protected ClientMapService $clientMapService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('tracking.view'), 403);

        return response()->json(
            $this->clientMapService->buildPayload(),
        );
    }
}
