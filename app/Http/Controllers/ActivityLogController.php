<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('ActivityLogs/Index', ['logs' => ActivityLog::query()->with('user:id,name')->when($search, fn ($query) => $query->where('action', 'like', "%{$search}%")->orWhere('module', 'like', "%{$search}%")->orWhere('subject_label', 'like', "%{$search}%"))->latest()->paginate(100)->withQueryString(), 'filters' => ['search' => $search]]);
    }
}
