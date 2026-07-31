<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings;

use App\Http\Controllers\Controller;
use App\ViewModels\Settings\EmailsSentViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailSentController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.emails', [
            'viewModel' => new EmailsSentViewModel(
                user: $request->user(),
                employee: $request->user()->employee,
            ),
        ]);
    }
}
