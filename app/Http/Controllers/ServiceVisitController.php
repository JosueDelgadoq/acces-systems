<?php

namespace App\Http\Controllers;

use App\Models\ServiceVisit;
use App\Services\ServiceVisitWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceVisitController extends Controller
{
    public function __construct(
        protected ServiceVisitWorkflowService $visitWorkflow,
    ) {
    }

    public function start(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $visit = $this->visitWorkflow->start(
            ServiceVisit::query()->findOrFail($id),
            $request->user() ?? auth()->user(),
            $data,
        );

        return response()->json(['success' => true, 'visit_id' => $visit->id]);
    }

    public function finish(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'report' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $visit = $this->visitWorkflow->finish(
            ServiceVisit::query()->findOrFail($id),
            $request->user() ?? auth()->user(),
            $data,
        );

        return response()->json(['success' => true, 'visit_id' => $visit->id]);
    }

    public function uploadArrival(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'visit_id' => ['required', 'exists:service_visits,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $path = $request->file('photo')->store('visits', 'public');

        $visit = $this->visitWorkflow->start(
            ServiceVisit::query()->findOrFail((int) $data['visit_id']),
            $request->user() ?? auth()->user(),
            [
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'arrival_photo' => $path,
            ],
        );

        return response()->json(['success' => true, 'visit_id' => $visit->id]);
    }

    public function uploadDeparture(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'report' => ['required', 'string'],
            'visit_id' => ['required', 'exists:service_visits,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $path = $request->file('photo')->store('visits', 'public');

        $visit = $this->visitWorkflow->finish(
            ServiceVisit::query()->findOrFail((int) $data['visit_id']),
            $request->user() ?? auth()->user(),
            [
                'report' => $data['report'],
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'departure_photo' => $path,
            ],
        );

        return response()->json(['success' => true, 'visit_id' => $visit->id]);
    }
}
