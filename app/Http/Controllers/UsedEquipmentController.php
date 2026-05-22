<?php

namespace App\Http\Controllers;

use App\Models\UsedEquipment;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class UsedEquipmentController extends Controller
{
    public function show(UsedEquipment $usedEquipment): View
    {
        return view('used-equipment.show', [
            'equipment' => $usedEquipment->load('movements.user'),
            'qrSvg' => $this->makeQrSvg($usedEquipment->public_url),
            'photoUrls' => collect($usedEquipment->photos ?? [])
                ->filter()
                ->map(fn (string $path): string => Storage::disk('public')->url($path))
                ->values()
                ->all(),
        ]);
    }

    public function label(UsedEquipment $usedEquipment): View
    {
        return view('used-equipment.label', [
            'equipment' => $usedEquipment,
            'qrSvg' => $this->makeQrSvg($usedEquipment->public_url),
        ]);
    }

    protected function makeQrSvg(string $url): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
            'scale' => 6,
        ]);

        return (new QRCode($options))->render($url);
    }
}
