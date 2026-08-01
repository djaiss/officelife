<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\LogsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.logs', [
            'viewModel' => new LogsViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }
}
