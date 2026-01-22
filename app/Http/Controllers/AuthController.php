<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Fixed credentials - change these as needed
    private const FIXED_USERNAME = 'admin';
    private const FIXED_PASSWORD = 'predraft2024';

    public function showLogin()
    {
        if (session('authenticated')) {
            return redirect()->route('leagues.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        if ($username === self::FIXED_USERNAME && $password === self::FIXED_PASSWORD) {
            $request->session()->regenerate();
            session(['authenticated' => true]);

            return redirect()->intended(route('leagues.index'));
        }

        return back()->withErrors([
            'credentials' => 'Invalid username or password.',
        ])->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

