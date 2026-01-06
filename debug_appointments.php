<?php

use App\Models\Appointment;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$appointments = Appointment::orderBy('starts_at', 'desc')->get();

echo "Current Time (UTC): " . now()->toDateTimeString() . "\n";
echo "Current Time (App TZ): " . now()->timezone(config('app.timezone'))->toDateTimeString() . "\n";
echo "Range 30d Start: " . now()->subDays(29)->startOfDay()->utc()->toDateTimeString() . "\n";
echo "Range 30d End: " . now()->endOfDay()->utc()->toDateTimeString() . "\n\n";

echo "Appointments:\n";
foreach ($appointments as $a) {
    echo "ID: {$a->id} | Start: {$a->starts_at} (UTC) | Status: {$a->status}\n";
}
