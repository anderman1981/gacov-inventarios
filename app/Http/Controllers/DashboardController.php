<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Query\Dashboard\GetDashboardOverview;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(GetDashboardOverview $query): View
    {
        return view('dashboard', $query->handle());
    }
}
