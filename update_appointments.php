<?php

use App\Models\Appointment;
use App\Models\Service;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = Service::first();

if (!$service) {
    echo "No services found. Please create a service first.\n";
    exit(1);
}

$count = Appointment::whereNull('service_id')->count();
echo "Found $count appointments without service.\n";

if ($count > 0) {
    Appointment::whereNull('service_id')->update(['service_id' => $service->id]);
    echo "Updated $count appointments with service: {$service->name}\n";
} else {
    echo " All appointments already have a service.\n";
}
