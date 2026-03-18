<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * AuthController
 * Migrated from: autorith.php, auth.php
 *
 * Password migration: old = md5($pass), new = bcrypt.
 * On first successful login with md5 hash → rehash to bcrypt and save.
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');

    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'login' => 'required|string',
            'pass' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $pass = trim($request->input('pass'));
        $name = trim($request->input('name', ''));
        $surname = trim($request->input('surname', ''));


        /** @var User|null $user */
        $user = User::where(function ($q) use ($login) {
            $q->where('login', $login)->orWhere('phone', $login);
        })->first();

        if ($user) {
            return back()->withErrors(['login' => 'Користувач вже існує']);
        }

        $user = User::create([
            'login' => $login,
            'pass' => Hash::make($pass),
            'name' => $name,
            'fathername' => $surname,
        ]);

        session([
            'id' => $user->id,
            'fid' => $user->idfirma,
            'userid' => $user->id,
            'idstatus' => $user->idstatus,
            'doc' => (int)$user->idstatus === 2 ? 'WO1' : 'ZOUT',
            'idkassa' => $user->idkassa,
            'idsklad' => $user->idsklad,
            'idreestr' => $user->idreestr,
            'domen' => $user->domen,
            'bonus' => $user->bonus,
            'balans' => $user->balans,
            'name1' => convert_from_base($user->name),
            'fname' => convert_from_base($user->fathername),
            'login' => $user->login,
            // navigation / filter defaults
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'pass' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $pass = trim($request->input('pass'));


        if ($login === '' || $pass === '') {
            return back()->withErrors(['login' => 'Введіть логін і пароль']);
        }

        /** @var User|null $user */
        $user = User::where(function ($q) use ($login) {
            $q->where('login', $login)->orWhere('phone', $login);
        })->first();

        if (!$user) {
            // Користувач не знайдений — створюємо нового
            $user = User::create([
                'login' => $login,
                'pass' => Hash::make($pass),
            ]);
        }
        else {
            // Перевірка пароля для існуючого користувача
            $valid = Hash::check($pass, $user->pass) // bcrypt
                || $user->pass === md5($pass) // legacy md5
                || $user->pass === md5(md5($pass)); // double-md5 variant

            if (!$valid) {
                echo "Invalid login attempt for user: $login";
                return back()->withErrors(['login' => 'Невірний логін або пароль']);
            }

            // Migrate md5 → bcrypt silently
            if ($user->pass === md5($pass) || $user->pass === md5(md5($pass))) {
                $user->update(['pass' => Hash::make($pass)]);
            }
        }

        session([
            'id' => $user->id,
            'fid' => $user->idfirma,
            'userid' => $user->id,
            'idstatus' => $user->idstatus,
            'doc' => (int)$user->idstatus === 2 ? 'WO1' : 'ZOUT',
            'idkassa' => $user->idkassa,
            'idsklad' => $user->idsklad,
            'idreestr' => $user->idreestr,
            'domen' => $user->domen,
            'bonus' => $user->bonus,
            'balans' => $user->balans,
            'name1' => convert_from_base($user->name),
            'fname' => convert_from_base($user->fathername),
            'login' => $user->login,
            // navigation / filter defaults
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}