<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $service): Response|StreamedResponse
    {
        if ($request->query('export') === 'overdue_clients') {
            return $service->exportOverdueClients();
        }

        return Inertia::render('Dashboard/Index', [
            'dashboard' => $service->data(),
        ]);
    }
}
