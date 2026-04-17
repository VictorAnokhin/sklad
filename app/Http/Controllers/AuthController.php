<?php

namespace App\Http\Controllers;

use App\Models\User;
use Elliptic\EC;
use Illuminate\Mail\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use kornrunner\Keccak;
use Throwable;

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
            return redirect()->route('document.index');
        }
        return view('auth.login');

    }

    public function dashboard()
    {
        $fid = session('fid', '');
        $today = now()->format('d-m-Y');

        $cashboxes = DB::table('conf')
            ->where('type', 'oplata')
            ->where('vision', '1')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();

        $dailyIncome = (float) DB::table('z_document')
            ->where('firma', $fid)
            ->where('type', 'PO')
            ->where('provodka', 1)
            ->where('data', $today)
            ->sum('summa');

        $currentMonthYear = now()->format('m-Y');

        $newOrders = DB::table('document as d')
            ->select('d.*', 'u.orgname', 'u.secondname', 'u.name as u_name', 'u.fathername', 'u.phone')
            ->leftJoin('users as u', 'd.client1', '=', 'u.id')
            ->where('d.firma', $fid)
            ->where('d.type', 'ZOUT')
            ->where('d.provodka', 0)
            ->where('d.data', 'LIKE', '%-' . $currentMonthYear)
            ->where(function ($query) {
                $query->where('d.status', 0)
                    ->orWhereNull('d.status');
            })
            ->orderByDesc('d.id')
            ->limit(10)
            ->get();

        return view('dashboard', compact('cashboxes', 'dailyIncome', 'newOrders', 'today'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'pass' => 'required|string|min:6|confirmed',
        ];

        if (User::hasUsersColumn('login')) {
            $validationRules['login'] = 'required|string|max:255';
        }

        $request->validate($validationRules);

        $login = trim((string) $request->input('login', $request->input('email', '')));
        $pass = trim($request->input('pass'));
        $name = trim($request->input('name', ''));
        $surname = trim($request->input('surname', ''));
        $email = trim($request->input('email', ''));
        $phone = trim($request->input('phone', ''));


        /** @var User|null $user */
        $user = User::forLogin($login)->first();

        if ($user) {
            return back()->withErrors(['login' => 'Користувач вже існує']);
        }

        if (User::where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'Користувач з таким email вже існує']);
        }

        if ($phone !== '' && User::where('phone', $phone)->exists()) {
            return back()->withErrors(['phone' => 'Користувач з таким телефоном вже існує']);
        }

        $passwordHash = Hash::make($pass);

        $userData = [
            'login' => $login,
            'email' => $email,
            'phone' => $phone,
            'pass' => $passwordHash,
            'password' => $passwordHash,
            'name' => $name,
            'secondname' => $surname,
            'fathername' => $surname,
            'idstatus' => 1,
            'ustype' => 1,
        ];

        $nextFirma = $this->nextFirma();

        if (Schema::hasColumn('users', 'firma')) {
            $userData['firma'] = $nextFirma;
        }

        if (Schema::hasColumn('users', 'idfirma')) {
            $userData['idfirma'] = $nextFirma;
        }

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        $user = User::create(User::filterUsersColumns($userData));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'nullable|string',
            'login' => 'nullable|string',
            'pass' => 'required|string',
        ]);

        $login = trim((string) $request->input('email', $request->input('login', '')));
        $pass = trim($request->input('pass'));


        if ($login === '' || $pass === '') {
            return back()->withErrors(['email' => 'Введіть email і пароль']);
        }

        /** @var User|null $user */
        $user = User::forLogin($login)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Користувача з таким email не знайдено. Спочатку зареєструйтесь.']);
        }

        if (!$user->passwordMatches($pass)) {
            return back()->withErrors(['email' => 'Невірний email або пароль']);
        }

        if ($user->usesLegacyPasswordHash()) {
            $user->syncPasswordHash($pass);
        }

        $this->syncUserRoleStatus($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = trim((string) $request->input('email'));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Користувача з таким email не знайдено.'])->withInput();
        }

        $newPassword = Str::password(12);
        $user->syncPasswordHash($newPassword);
        $this->syncUserRoleStatus($user);

        try {
            Mail::send('emails.auth.new-password', [
                'user' => $user,
                'newPassword' => $newPassword,
            ], function (Message $message) use ($user) {
                $message->to($user->email)
                    ->subject('Новий пароль для входу');
            });
        } catch (Throwable $e) {
            return back()->withErrors([
                'email' => 'Не вдалося відправити лист. Перевірте налаштування пошти.',
            ])->withInput();
        }

        return back()->with('success', 'Новий пароль відправлено на ваш email.');
    }

    private function nextFirma(): int
    {
        if (!Schema::hasColumn('users', 'firma')) {
            return 1;
        }

        $maxFirma = (int) DB::table('users')
            ->selectRaw('MAX(CAST(COALESCE(NULLIF(firma, \'\'), \'0\') AS UNSIGNED)) as max_firma')
            ->value('max_firma');

        return $maxFirma + 1;
    }

    public function apiLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::forLogin(trim($request->string('login')->toString()))->first();

        if (!$user || !$user->passwordMatches($request->string('password')->toString())) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        if ($user->usesLegacyPasswordHash()) {
            $user->syncPasswordHash($request->string('password')->toString());
        }

        $this->syncUserRoleStatus($user);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function apiLogout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function apiUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => $this->serializeUser($user),
        ]);
    }

    public function apiRegister(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6|confirmed',
        ];

        if (User::hasUsersColumn('login')) {
            $validationRules['login'] = 'required|string|max:255';
        }

        $validated = $request->validate($validationRules);

        $login = trim((string) ($validated['login'] ?? $validated['email']));
        $pass = trim($validated['password']);
        $name = trim($validated['name']);
        $surname = trim($validated['surname'] ?? '');
        $email = trim($validated['email']);
        $phone = trim($validated['phone'] ?? '');

        /** @var User|null $user */
        $user = User::forLogin($login)->first();

        if ($user) {
            return response()->json(['message' => 'Користувач вже існує'], 422);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json(['message' => 'Користувач з таким email вже існує'], 422);
        }

        if ($phone !== '' && User::where('phone', $phone)->exists()) {
            return response()->json(['message' => 'Користувач з таким телефоном вже існує'], 422);
        }

        $passwordHash = Hash::make($pass);

        $userData = [
            'login' => $login,
            'email' => $email,
            'phone' => $phone,
            'pass' => $passwordHash,
            'password' => $passwordHash,
            'name' => $name,
            'secondname' => $surname,
            'fathername' => $surname,
            'idstatus' => 1,
            'ustype' => 1,
        ];

        $nextFirma = $this->nextFirma();

        if (Schema::hasColumn('users', 'firma')) {
            $userData['firma'] = $nextFirma;
        }

        if (Schema::hasColumn('users', 'idfirma')) {
            $userData['idfirma'] = $nextFirma;
        }

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        $user = User::create(User::filterUsersColumns($userData));

        // Auto login after registration
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Реєстрація успішна',
            'user' => $this->serializeUser($user->fresh()),
        ], 201);
    }

    public function linkWallet(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'signature' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{130}$/'],
            'network' => 'nullable|string|max:80',
        ]);

        $address = strtolower($validated['address']);
        $nonce = Cache::get($this->web3LinkNonceKey($user->id, $address));

        if (!$nonce) {
            return response()->json(['message' => 'Запрос на привязку устарел. Повторите попытку.'], 422);
        }

        $message = $this->makeWeb3Message('link', $nonce, $address);

        if (!$this->verifyEthereumSignature($message, $validated['signature'], $address)) {
            return response()->json(['message' => 'Подпись кошелька не прошла проверку.'], 422);
        }

        $this->bindWalletToUser($user, $address, $validated['network'] ?? null);
        Cache::forget($this->web3LinkNonceKey($user->id, $address));

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function web3LoginChallenge(Request $request)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        $address = strtolower($validated['address']);
        $nonce = Str::random(32);

        Cache::put($this->web3LoginNonceKey($address), $nonce, now()->addMinutes(10));

        return response()->json([
            'nonce' => $nonce,
            'message' => $this->makeWeb3Message('login', $nonce, $address),
        ]);
    }

    public function web3LinkChallenge(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        $address = strtolower($validated['address']);
        $nonce = Str::random(32);

        Cache::put($this->web3LinkNonceKey($user->id, $address), $nonce, now()->addMinutes(10));

        return response()->json([
            'nonce' => $nonce,
            'message' => $this->makeWeb3Message('link', $nonce, $address),
        ]);
    }

    public function web3Login(Request $request)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'signature' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{130}$/'],
        ]);

        $address = strtolower($validated['address']);
        $nonce = Cache::get($this->web3LoginNonceKey($address));

        if (!$nonce) {
            return response()->json(['message' => 'Запрос на вход устарел. Повторите попытку.'], 422);
        }

        $message = $this->makeWeb3Message('login', $nonce, $address);

        if (!$this->verifyEthereumSignature($message, $validated['signature'], $address)) {
            return response()->json(['message' => 'Подпись кошелька не прошла проверку.'], 422);
        }

        /** @var User|null $user */
        if (Schema::hasTable('user_wallets')) {
            $userId = DB::table('user_wallets')
                ->where('address', $address)
                ->value('user_id');
            $user = $userId ? User::find($userId) : null;
        } else {
            $user = User::where('wallet_address', $address)->first();
        }

        if (!$user) {
            return response()->json(['message' => 'Этот кошелек еще не привязан к аккаунту контрагента.'], 404);
        }

        $this->syncUserRoleStatus($user);
        Auth::login($user);
        $request->session()->regenerate();
        Cache::forget($this->web3LoginNonceKey($address));

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function unlinkWallet(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'address' => ['nullable', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        if (Schema::hasTable('user_wallets')) {
            $query = DB::table('user_wallets')->where('user_id', $user->id);

            if (!empty($validated['address'])) {
                $query->where('address', strtolower($validated['address']));
            }

            $query->delete();
            $this->syncPrimaryWalletColumns($user->fresh() ?? $user);
        } else {
            $user->forceFill([
                'wallet_address' => null,
                'wallet_network' => null,
                'wallet_connected_at' => null,
            ])->save();
        }

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    private function serializeUser(User $user): array
    {
        $wallets = $this->userWallets($user->id);
        $primaryWallet = $wallets[0] ?? null;

        return [
            'id' => $user->id,
            'login' => $user->login,
            'phone' => $user->phone,
            'name' => $user->name,
            'secondname' => $user->secondname,
            'fathername' => $user->fathername,
            'email' => $user->email,
            'fid' => $user->firma ?: $user->fid ?: $user->idfirma,
            'idstatus' => $user->idstatus ?: $user->ustype,
            'wallet_address' => $primaryWallet['address'] ?? $user->wallet_address,
            'wallet_network' => $primaryWallet['network'] ?? $user->wallet_network,
            'wallet_connected_at' => $primaryWallet['connected_at'] ?? optional($user->wallet_connected_at)->toIso8601String(),
            'wallets' => $wallets,
            'idkassa' => $user->idkassa,
            'idsklad' => $user->idsklad,
            'idreestr' => $user->idreestr,
            'domen' => $user->domen,
            'bonus' => $user->bonus,
            'balans' => $user->balans,
        ];
    }

    private function bindWalletToUser(User $user, string $address, ?string $network = null): void
    {
        if (Schema::hasTable('user_wallets')) {
            $exists = DB::table('user_wallets')
                ->where('address', $address)
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($exists) {
                abort(response()->json([
                    'message' => 'Цей гаманець вже прив’язаний до іншого акаунта.',
                ], 422));
            }

            DB::table('user_wallets')->updateOrInsert(
                ['address' => $address],
                [
                    'user_id' => $user->id,
                    'network' => $network,
                    'connected_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->syncPrimaryWalletColumns($user->fresh() ?? $user);
            return;
        }

        $exists = User::query()
            ->where('wallet_address', $address)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            abort(response()->json([
                'message' => 'Цей гаманець вже прив’язаний до іншого акаунта.',
            ], 422));
        }

        $user->forceFill([
            'wallet_address' => $address,
            'wallet_network' => $network,
            'wallet_connected_at' => now(),
        ])->save();
    }

    private function userWallets(int|string $userId): array
    {
        if (!Schema::hasTable('user_wallets')) {
            $fallback = [];

            if (!empty($userId)) {
                $user = User::find($userId);
                if ($user && $user->wallet_address) {
                    $fallback[] = [
                        'address' => $user->wallet_address,
                        'network' => $user->wallet_network,
                        'connected_at' => optional($user->wallet_connected_at)->toIso8601String(),
                    ];
                }
            }

            return $fallback;
        }

        return DB::table('user_wallets')
            ->where('user_id', $userId)
            ->orderByDesc('connected_at')
            ->orderByDesc('id')
            ->get(['address', 'network', 'connected_at'])
            ->map(function ($wallet) {
                return [
                    'address' => $wallet->address,
                    'network' => $wallet->network,
                    'connected_at' => optional($wallet->connected_at)->toIso8601String(),
                ];
            })
            ->all();
    }

    private function syncPrimaryWalletColumns(User $user): void
    {
        $primaryWallet = null;

        if (Schema::hasTable('user_wallets')) {
            $primaryWallet = DB::table('user_wallets')
                ->where('user_id', $user->id)
                ->orderByDesc('connected_at')
                ->orderByDesc('id')
                ->first();
        }

        $user->forceFill([
            'wallet_address' => $primaryWallet->address ?? null,
            'wallet_network' => $primaryWallet->network ?? null,
            'wallet_connected_at' => $primaryWallet->connected_at ?? null,
        ])->save();
    }

    private function makeWeb3Message(string $purpose, string $nonce, string $address): string
    {
        $action = $purpose === 'link' ? 'Bind wallet' : 'Login';

        return implode("\n", [
            "AV8 Capital DAO",
            "{$action} with wallet",
            "Address: {$address}",
            "Nonce: {$nonce}",
        ]);
    }

    private function web3LoginNonceKey(string $address): string
    {
        return "web3:login:nonce:{$address}";
    }

    private function web3LinkNonceKey(int|string $userId, string $address): string
    {
        return "web3:link:nonce:{$userId}:{$address}";
    }

    private function verifyEthereumSignature(string $message, string $signature, string $address): bool
    {
        $hash = Keccak::hash("\x19Ethereum Signed Message:\n" . strlen($message) . $message, 256);
        $signature = strtolower($signature);

        $r = substr($signature, 2, 64);
        $s = substr($signature, 66, 64);
        $v = hexdec(substr($signature, 130, 2));

        if ($v >= 27) {
            $recId = $v - 27;
        } else {
            $recId = $v;
        }

        if ($recId !== ($recId & 1)) {
            return false;
        }

        $ec = new EC('secp256k1');
        $pubKey = $ec->recoverPubKey($hash, ['r' => $r, 's' => $s], $recId);
        $derivedAddress = '0x' . substr(
            Keccak::hash(substr(hex2bin($pubKey->encode('hex')), 1), 256),
            24
        );

        return strtolower($derivedAddress) === strtolower($address);
    }

    private function syncUserRoleStatus(User $user): void
    {
        $roleStatus = (int) ($user->ustype ?: $user->idstatus ?: 1);

        if ((int) $user->idstatus === $roleStatus && (int) $user->ustype === $roleStatus) {
            return;
        }

        $user->forceFill([
            'idstatus' => $roleStatus,
            'ustype' => $roleStatus,
        ])->save();
    }
}
