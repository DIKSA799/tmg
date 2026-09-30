<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardAnalytics $analytics): View
    {
        return view('admin.dashboard', [
            'analytics' => $analytics->forRange($this->range($request)),
        ]);
    }

    public function data(Request $request, DashboardAnalytics $analytics): JsonResponse
    {
        return response()
            ->json(['data' => $analytics->forRange($this->range($request))])
            ->header('Cache-Control', 'no-store');
    }

    private function range(Request $request): int
    {
        return (int) $request->integer('range', DashboardAnalytics::DEFAULT_RANGE);
    }
}
