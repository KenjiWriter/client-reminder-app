<?php

namespace App\Http\Controllers;

use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = SmsMessage::with(['client', 'appointment'])
            ->latest('sent_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('to_e164', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Messages/Index', [
            'messages' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}
