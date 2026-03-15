<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
        if (session()->has('login') && session('login') !== '') {
            return redirect()->route('document.index');
        }
        return view('auth.login');
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
                return back()->withErrors(['login' => 'Невірний логін або пароль']);
            }

            // Migrate md5 → bcrypt silently
            if ($user->pass === md5($pass) || $user->pass === md5(md5($pass))) {
                $user->update(['pass' => Hash::make($pass)]);
            }
        }

        // Birthday day.month for greeting
        $hbd = convert_from_base($user->hbd ?? '');
        $iHbd = '';
        if (strlen($hbd) >= 10) {
            $iHbd = substr($hbd, 8, 2) . '.' . substr($hbd, 5, 2); // dd.mm
        }

        session([
            'id' => $user->id,
            'fid' => $user->fid,
            'userid' => $user->id,
            'idstatus' => $user->idstatus,
            'doc' => (int)$user->idstatus === 2 ? 'WO1' : 'ZOUT',
            'i_hbd' => $iHbd,
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
            'pos' => 0,
            'num' => '0',
            'doc_id' => '0',
            'numz' => '0',
            'typez' => '',
            'client1' => '0',
            'client2' => '0',
            'year' => date('Y'),
            'sklads' => $user->idsklad,
            'reteil' => '',
            'reestr' => $user->idreestr,
        ]);

        return redirect()->route('document.index');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login');
    }
}