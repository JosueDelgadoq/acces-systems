<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "Running migrations...\n";

// Check if columns exist
$columns = Schema::getColumnListing('clients');
echo "Current columns: " . implode(', ', $columns) . "\n";

exit(0);
