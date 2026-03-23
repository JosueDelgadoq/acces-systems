
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

User::create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
]);

User::create([
    'name' => 'Comercial',
    'email' => 'comercial@test.com',
    'password' => bcrypt('password'),
    'role' => 'commercial',
]);

echo "Usuarios creados:\n";
echo "admin@test.com / password\n";
echo "comercial@test.com / password\n";

