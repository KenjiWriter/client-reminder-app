<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        $services = \App\Models\Service::where('is_active', true)
            ->select('id', 'name', 'description', 'duration_minutes') // Explicitly selecting fields, excluding price just in case
            ->get();

        return \Inertia\Inertia::render('Landing/Index', [
            'services' => $services,
        ]);
    }

    public function store(\App\Http\Requests\StoreLeadRequest $request)
    {
        \App\Models\Lead::create([
            'full_name' => $request->full_name,
            'phone_e164' => $request->phone_e164,
            'email' => $request->email,
            'source' => 'landing',
            'status' => \App\Models\Lead::STATUS_NEW,
            'note' => $request->note,
        ]);

        return redirect()->back()->with('message', 'Dziękujemy! Skontaktujemy się wkrótce.');
    }
}
