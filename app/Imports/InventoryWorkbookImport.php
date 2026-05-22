<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithFormatData;

class InventoryWorkbookImport implements SkipsEmptyRows, WithCalculatedFormulas, WithFormatData
{
}
