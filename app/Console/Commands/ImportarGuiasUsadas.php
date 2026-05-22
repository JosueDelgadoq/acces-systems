<?php

namespace App\Console\Commands;

use App\Models\GuiaItem;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarGuiasUsadas extends Command
{
    protected $signature = 'guias:importar {--reset : Borra las guias actuales antes de importar}';
    protected $description = 'Importa guias usadas y las relaciona con producto_variantes';

    public function handle(): int
    {
        $texto = <<<'TEXT'
ACORN
1.53
1.71
1.74
1.95
1.96
1.99 (rebatible con ángulo)
2.17
2.57 (con ángulo)
2.57 (con ángulo)
3.39 (con patas y unida)
2.59( patas y ángulo)
2.57 ( patas y angulo)
2.53 (angulo)
1.93 (patas y angulo)
1.26
46
1.92
50
24
2.80

BESPOKE
1.64
1.84 (con 2 patas)
2.23 (con ángulo)
2.56 (con ángulo)
2.56 (con ángulo)
3.10
4.19 (con ángulo)
4.19 (con ángulo)
4.26 (con ángulo)

BESPOKE MAS CHICA
225
194
225
173
193
147
154
225
173
154
139
124
108
123
83
89
89
112
84
80
98
69
65
56
54
50
31
62
168 patas
225 patas

MEDITEK modelo 1
3.39 (con patas y unida)
1.74
2.50(angulo)
80
37
60

MEDITEK modelo 2
1.15
2.00
3.00

VIMEC
1.98

K2
18 guías K2 – 2,40 mts
2.20
2.20 angulo
1.86
2.0 angulo
2.0
1.99
1.91
1.73
2.14
1.78
1.99
1.87
1.51
1.19
1.26
1.27
1.40
1.06
1.33
1.28
TEXT;

        $map = [
            'ACORN' => ['producto' => 'Acorn', 'modelo' => null],
            'BESPOKE' => ['producto' => 'BESPOKE 1', 'modelo' => null],
            'BESPOKE MAS CHICA' => ['producto' => 'BESPOKE 2', 'modelo' => null],
            'MEDITEK MODELO 1' => ['producto' => 'MEDITEK', 'modelo' => 'modelo 1'],
            'MEDITEK MODELO 2' => ['producto' => 'MEDITEK', 'modelo' => 'modelo 2'],
            'VIMEC' => ['producto' => 'VIMEC', 'modelo' => null],
            'K2' => ['producto' => 'K2', 'modelo' => null],
        ];

        $items = $this->parseItems($texto);
        $totales = [];

        DB::transaction(function () use ($items, $map, &$totales) {
            $variantes = [];

            foreach ($map as $key => $config) {
                $producto = Producto::query()
                    ->where('tipo', 'guia')
                    ->where('nombre', $config['producto'])
                    ->first();

                if (!$producto) {
                    throw new \RuntimeException("No existe el producto guía {$config['producto']}.");
                }

                $variantes[$key] = ProductoVariante::firstOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'modelo' => $config['modelo'],
                        'lado' => null,
                        'uso' => null,
                        'estado' => 'usado',
                    ],
                    [
                        'codigo_barra' => null,
                    ]
                );
            }

            if ($this->option('reset')) {
                GuiaItem::query()->delete();
            }

            foreach ($items as $item) {
                $variante = $variantes[$item['modelo']];

                GuiaItem::create([
                    'producto_variante_id' => $variante->id,
                    'longitud' => $item['longitud'],
                    'tiene_angulo' => $item['tiene_angulo'],
                    'tiene_patas' => $item['tiene_patas'],
                    'rebatible' => $item['rebatible'],
                    'estado' => 'usado',
                    'observaciones' => $item['observaciones'],
                ]);

                if (!isset($totales[$item['modelo']])) {
                    $totales[$item['modelo']] = ['cantidad' => 0, 'metros' => 0];
                }

                $totales[$item['modelo']]['cantidad']++;
                $totales[$item['modelo']]['metros'] += $item['longitud'];
            }
        });

        $this->info('Importación terminada.');

        foreach ($totales as $modelo => $data) {
            $this->line(
                sprintf(
                    '%s -> Cantidad: %d | Metros: %.2f',
                    $modelo,
                    $data['cantidad'],
                    $data['metros']
                )
            );
        }

        return self::SUCCESS;
    }

    private function parseItems(string $texto): array
    {
        $items = [];
        $modeloActual = null;

        foreach (preg_split('/\R/u', $texto) as $lineaRaw) {
            $lineaOriginal = trim($lineaRaw);

            if ($lineaOriginal === '') {
                continue;
            }

            $linea = mb_strtolower($lineaOriginal);
            $linea = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $linea);
            $linea = preg_replace('/\s+/', ' ', $linea);

            if (!preg_match('/^\d/', $linea)) {
                $modeloActual = strtoupper($linea);
                continue;
            }

            if (!$modeloActual) {
                continue;
            }

            $cantidad = 1;
            $observaciones = [];

            if (preg_match('/^(\d+)\s+guias?.*?(\d+(?:[.,]\d+)?)\s*mts?$/u', $linea, $matches)) {
                $cantidad = (int) $matches[1];
                $valor = (float) str_replace(',', '.', $matches[2]);
            } else {
                preg_match('/(\d+(?:[.,]\d+)?)/', $linea, $matches);

                if (!isset($matches[1])) {
                    continue;
                }

                $valor = (float) str_replace(',', '.', $matches[1]);
            }

            if ($valor > 20) {
                $valor /= 100;
            }

            $tieneAngulo = str_contains($linea, 'angulo');
            $tienePatas = str_contains($linea, 'pata');
            $rebatible = str_contains($linea, 'rebatible');

            if (str_contains($linea, 'unida')) {
                $observaciones[] = 'unida';
            }

            for ($i = 0; $i < $cantidad; $i++) {
                $items[] = [
                    'modelo' => $modeloActual,
                    'longitud' => round($valor, 2),
                    'tiene_angulo' => $tieneAngulo,
                    'tiene_patas' => $tienePatas,
                    'rebatible' => $rebatible,
                    'observaciones' => $observaciones ? implode(', ', $observaciones) : null,
                ];
            }
        }

        return $items;
    }
}
