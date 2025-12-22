<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index()
    {
        $leads = \App\Models\Lead::query()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return \Inertia\Inertia::render('Admin/Leads/Index', [
            'leads' => $leads,
        ]);
    }

    public function update(Request $request, \App\Models\Lead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:new,contacted,closed',
            'note' => 'nullable|string'
        ]);

        $lead->update($validated);

        return back()->with('message', 'Lead updated successfully.');
    }
}
