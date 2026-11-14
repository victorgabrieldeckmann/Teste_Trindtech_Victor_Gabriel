<?php

namespace App\Http\Controllers;

use App\Models\Indicator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'indicators' => Indicator::query()->orderBy('name')->get(),
        ]);
    }
}
