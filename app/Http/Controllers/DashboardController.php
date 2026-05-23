<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $service): Response
    {
        return Inertia::render('Dashboard/Index', [
            'dashboard' => $service->data(),
        ]);
    }
}
