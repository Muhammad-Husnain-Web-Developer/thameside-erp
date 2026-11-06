<?php

namespace App\Modules\Core\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'demoAccounts' => [
                ['role' => 'Administrator', 'email' => 'amelia.hart@thamesidekitchen.co.uk'],
                ['role' => 'Accountant', 'email' => 'oliver.bennett@thamesidekitchen.co.uk'],
                ['role' => 'Sales', 'email' => 'priya.shah@thamesidekitchen.co.uk'],
                ['role' => 'Warehouse', 'email' => 'tomasz.nowak@thamesidekitchen.co.uk'],
            ],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
