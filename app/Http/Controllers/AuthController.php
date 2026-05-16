<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ShinamiClient;
use App\Services\SuiLocalGasSponsorClient;
use App\Services\SmsClubService;
use Elliptic\EC;
use Illuminate\Mail\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use kornrunner\Keccak;
use Symfony\Component\Process\Process;
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
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('document.index');
        }

        $fid = $this->resolveAuthFid($request);

        return view('start', [
            'googleClientId' => (string) config('services.google.client_id', ''),
            'authFid' => $fid,
        ]);

    }

    public function dashboard()
    {
        $fid = session('fid', '');
        $today = now()->format('d-m-Y');
        $currentUserId = (int) (Auth::id() ?: session('userid', 0));
        $currentUserBalance = 0.0;

        if ($currentUserId > 0) {
            $currentUserBalance = (float) DB::table('users')
                ->where('id', $currentUserId)
                ->when($fid !== '', fn ($query) => $query->where('firma', $fid))
                ->value('balance');
        }

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

        // Фильтруем заказы текущего месяца (формат даты: d-m-Y, например 17-04-2026)
        $currentMonthYear = now()->format('m-Y');

        $newOrders = DB::table('document as d')
            ->select('d.*', 'u.orgname', 'u.secondname', 'u.name as u_name', 'u.fathername', 'u.phone')
            ->leftJoin('users as u', 'd.client1', '=', 'u.id')
            ->where('d.firma', $fid)
            ->where('d.type', 'ZOUT')
            ->where('d.provodka', 0)
            ->where('d.data', 'LIKE', '%-' . $currentMonthYear)
            ->orderByDesc('d.id')
            ->limit(20)
            ->get();

        return view('dashboard', compact('cashboxes', 'dailyIncome', 'newOrders', 'today', 'currentUserBalance'));
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


        $fid = $this->resolveAuthFid($request);

        /** @var User|null $user */
        $user = $this->userForLogin($login, $fid)->first();

        if ($user) {
            return back()->withErrors(['login' => 'Користувач вже існує']);
        }

        if ($this->userByEmail($email, $fid)->exists()) {
            return back()->withErrors(['email' => 'Користувач з таким email вже існує']);
        }

        if ($phone !== '' && $this->userByPhone($phone, $fid)->exists()) {
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

        $userFirma = $this->authFirmaForNewUser($fid);

        if (Schema::hasColumn('users', 'firma')) {
            $userData['firma'] = $userFirma;
        }

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        $user = User::create(User::filterUsersColumns($userData));

        Auth::login($user);
        $request->session()->regenerate();
        $this->syncAuthSessionFid($request, $user);

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

        $fid = $this->resolveAuthFid($request);

        /** @var User|null $user */
        $user = $this->userForLogin($login, $fid)->first();

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
        $this->syncAuthSessionFid($request, $user);

        return redirect()->route('dashboard');
    }

    public function googleLogin(Request $request)
    {
        $request->validate([
            'credential' => 'required|string',
        ]);

        $googleClientId = (string) config('services.google.client_id', '');

        if ($googleClientId === '') {
            return back()->withErrors([
                'email' => 'Вхід через Google не налаштований.',
            ]);
        }

        $payload = $this->verifyGoogleCredential($request->string('credential')->toString(), $googleClientId);

        if (!$payload) {
            return back()->withErrors([
                'email' => 'Не вдалося підтвердити вхід через Google.',
            ]);
        }

        $email = trim((string) ($payload['email'] ?? ''));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || !$emailVerified) {
            return back()->withErrors([
                'email' => 'Google не повернув підтверджений email для входу.',
            ]);
        }

        $fid = $this->resolveAuthFid($request);

        /** @var User|null $user */
        $user = $this->userByEmail($email, $fid)->first();

        if (!$user) {
            $givenName = trim((string) ($payload['given_name'] ?? ''));
            $familyName = trim((string) ($payload['family_name'] ?? ''));
            $fullName = trim((string) ($payload['name'] ?? ''));
            $passwordHash = Hash::make(Str::random(40));
            $fallbackName = $givenName !== '' ? $givenName : ($fullName !== '' ? $fullName : 'Google User');
            $userFirma = $this->authFirmaForNewUser($fid);

            $userData = [
                'login' => $email,
                'email' => $email,
                'pass' => $passwordHash,
                'password' => $passwordHash,
                'name' => $fallbackName,
                'secondname' => $familyName,
                'fathername' => $familyName,
                'idstatus' => 1,
                'ustype' => 1,
            ];

            if (Schema::hasColumn('users', 'email_verified_at')) {
                $userData['email_verified_at'] = now();
            }

            if (Schema::hasColumn('users', 'firma')) {
                $userData['firma'] = $userFirma;
            }

            if (Schema::hasColumn('users', 'status')) {
                $userData['status'] = 1;
            }

            $user = User::create(User::filterUsersColumns($userData));
        }

        $this->syncUserRoleStatus($user);

        Auth::login($user);
        $request->session()->regenerate();
        $this->syncAuthSessionFid($request, $user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('micro-business');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = trim((string) $request->input('email'));
        $fid = $this->resolveAuthFid($request);
        $user = $this->userByEmail($email, $fid)->first();

        if (!$user) {
            return back()->withErrors(['recovery_email' => 'Користувача з таким email не знайдено.'])->withInput();
        }

        $newPassword = Str::password(12);
        $previousPassword = (string) ($user->password ?? '');
        $previousPass = (string) ($user->pass ?? '');

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
            Log::error('Failed to send password recovery email.', [
                'email' => $user->email,
                'mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'exception' => $e->getMessage(),
            ]);

            if (app()->environment('local') || config('app.debug')) {
                return back()
                    ->with('success', 'Поштовий сервер недоступний. Тимчасовий пароль згенеровано локально.')
                    ->with('recovery_warning', 'Лист не було відправлено. Використайте тимчасовий пароль нижче для входу.')
                    ->with('temporary_password', $newPassword);
            }

            $user->forceFill([
                'password' => $previousPassword,
                'pass' => $previousPass,
            ])->save();

            return back()->withErrors([
                'recovery_email' => 'Не вдалося відправити лист. Перевірте налаштування пошти.',
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

    private function resolveAuthFid(Request $request): ?string
    {
        $fid = trim((string) $request->input('fid', ''));

        if ($fid === '' && $request->hasSession()) {
            $fid = trim((string) session('fid', ''));
        }

        return $fid !== '' ? $fid : null;
    }

    private function authFirmaForNewUser(?string $fid): int|string
    {
        return $fid !== null && $fid !== '' ? $fid : $this->nextFirma();
    }

    private function userForLogin(string $login, ?string $fid)
    {
        $query = User::forLogin($login);

        return $this->scopeUserQueryToFid($query, $fid);
    }

    private function userByEmail(string $email, ?string $fid)
    {
        $query = User::query()->where('email', $email);

        return $this->scopeUserQueryToFid($query, $fid);
    }

    private function userByPhone(string $phone, ?string $fid)
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $query = User::query()->where(function ($builder) use ($phone, $digits) {
            $builder->where('phone', $phone);

            if ($digits === '') {
                return;
            }

            $normalizedPhoneSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

            $builder->orWhereRaw("{$normalizedPhoneSql} = ?", [$digits]);

            if (str_starts_with($digits, '38')) {
                $builder->orWhereRaw("{$normalizedPhoneSql} = ?", [substr($digits, 2)]);
            } elseif (str_starts_with($digits, '0')) {
                $builder->orWhereRaw("{$normalizedPhoneSql} = ?", ['38' . $digits]);
            }
        });

        return $this->scopeUserQueryToFid($query, $fid);
    }

    private function scopeUserQueryToFid($query, ?string $fid)
    {
        if ($fid !== null && $fid !== '' && Schema::hasColumn('users', 'firma')) {
            $query->where('firma', $fid);
        }

        return $query;
    }

    private function syncAuthSessionFid(Request $request, User $user): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $fid = $user->firma ?: $user->fid;

        if ($fid !== null && $fid !== '') {
            $request->session()->put('fid', $fid);
        }
    }

    private function establishAuthenticatedSession(Request $request, User $user): void
    {
        if (!$request->hasSession()) {
            return;
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->syncAuthSessionFid($request, $user);
    }

    private function verifyGoogleCredential(string $credential, string $clientId): ?array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $credential,
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Google JWT HTTP Exception', ['error' => $e->getMessage()]);
            throw new \Exception('Google API unreachable: ' . $e->getMessage());
        }

        if (!$response->ok()) {
            \Illuminate\Support\Facades\Log::error('Google JWT HTTP not ok', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Google API rejected token. Status: ' . $response->status() . ' Body: ' . $response->body());
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            \Illuminate\Support\Facades\Log::error('Google JWT payload not array');
            throw new \Exception('Google API returned invalid JSON');
        }

        if ((string) ($payload['aud'] ?? '') !== $clientId) {
            \Illuminate\Support\Facades\Log::error('Google JWT audience mismatch', ['payload_aud' => $payload['aud'] ?? null, 'expected_client_id' => $clientId]);
            throw new \Exception('Audience mismatch! Expected: ' . $clientId . ', got: ' . ($payload['aud'] ?? 'none'));
        }

        if ((string) ($payload['iss'] ?? '') !== 'https://accounts.google.com'
            && (string) ($payload['iss'] ?? '') !== 'accounts.google.com') {
            \Illuminate\Support\Facades\Log::error('Google JWT issuer mismatch', ['payload_iss' => $payload['iss'] ?? null]);
            throw new \Exception('Issuer mismatch! Got: ' . ($payload['iss'] ?? 'none'));
        }

        return $payload;
    }



    private function resolveGoogleUser(array $payload, ?string $fid = null): ?User
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || !$emailVerified) {
            return null;
        }

        /** @var User|null $user */
        $user = $this->userByEmail($email, $fid)->first();

        if ($user) {
            if (Schema::hasColumn('users', 'email_verified_at') && !$user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        }

        $givenName = trim((string) ($payload['given_name'] ?? ''));
        $familyName = trim((string) ($payload['family_name'] ?? ''));
        $fullName = trim((string) ($payload['name'] ?? ''));
        $passwordHash = Hash::make(Str::random(40));
        $fallbackName = $givenName !== '' ? $givenName : ($fullName !== '' ? $fullName : 'Google User');
        $userFirma = $this->authFirmaForNewUser($fid);

        $userData = [
            'login' => $email,
            'email' => $email,
            'pass' => $passwordHash,
            'password' => $passwordHash,
            'name' => $fallbackName,
            'secondname' => $familyName,
            'fathername' => $familyName,
            'idstatus' => 1,
            'ustype' => 1,
        ];

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $userData['email_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'firma')) {
            $userData['firma'] = $userFirma;
        }

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        return User::create(User::filterUsersColumns($userData));
    }

    private function resolveExistingGoogleUser(array $payload, ?string $fid = null): ?User
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || !$emailVerified) {
            return null;
        }

        /** @var User|null $user */
        $user = $this->userByEmail($email, $fid)->first();

        if (!$user && Schema::hasColumn('users', 'email')) {
            $query = User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)]);
            $user = $this->scopeUserQueryToFid($query, $fid)->first();
        }

        if ($user && Schema::hasColumn('users', 'email_verified_at') && !$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    public function apiLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $fid = $this->resolveAuthFid($request);
        $user = $this->userForLogin(trim($request->string('login')->toString()), $fid)->first();

        if (!$user || !$user->passwordMatches($request->string('password')->toString())) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        if ($user->usesLegacyPasswordHash()) {
            $user->syncPasswordHash($request->string('password')->toString());
        }

        $this->syncUserRoleStatus($user);

        // Create Sanctum token instead of session login
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
            'token' => $token,
        ]);
    }

    public function apiSendPhoneCode(Request $request, SmsClubService $smsClub)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        if (!$this->isPhoneAuthEnabled()) {
            return response()->json(['message' => 'Phone authentication is not configured'], 503);
        }

        $normalizedPhone = $this->normalizePhoneNumber((string) $validated['phone']);

        if ($normalizedPhone === null) {
            return response()->json(['message' => 'Некоректний формат телефону.'], 422);
        }

        $requestFingerprint = $this->phoneOtpRateLimitKey($request, $normalizedPhone);

        if (Cache::has($requestFingerprint)) {
            return response()->json([
                'message' => 'Код уже відправлено. Спробуйте трохи пізніше.',
                'ttl' => 600,
            ]);
        }

        $code = $this->generatePhoneOtpCode();
        $message = $this->makePhoneOtpMessage($code);

        try {
            $smsClub->sendOtp($this->smsClubPhonePayload($normalizedPhone), $message);
        } catch (Throwable $e) {
            Log::error('Failed to send SMS Club OTP.', [
                'phone' => $normalizedPhone,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Не вдалося відправити SMS-код.',
            ], 502);
        }

        Cache::put($this->phoneOtpCodeKey($normalizedPhone), [
            'code' => Hash::make($code),
            'phone' => $normalizedPhone,
        ], now()->addMinutes(10));
        Cache::put($requestFingerprint, true, now()->addSeconds(120));

        return response()->json([
            'message' => 'Код підтвердження відправлено.',
            'ttl' => 600,
        ]);
    }

    public function apiVerifyPhoneCode(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'size:6'],
            'name' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        if (!$this->isPhoneAuthEnabled()) {
            return response()->json(['message' => 'Phone authentication is not configured'], 503);
        }

        $normalizedPhone = $this->normalizePhoneNumber((string) $validated['phone']);

        if ($normalizedPhone === null) {
            return response()->json(['message' => 'Некоректний формат телефону.'], 422);
        }

        $cachedOtp = Cache::get($this->phoneOtpCodeKey($normalizedPhone));

        if (!is_array($cachedOtp) || !Hash::check((string) $validated['code'], (string) ($cachedOtp['code'] ?? ''))) {
            return response()->json(['message' => 'Невірний або прострочений код підтвердження.'], 422);
        }

        $fid = $this->resolveAuthFid($request);
        $email = trim((string) ($validated['email'] ?? ''));
        $user = $this->userByPhone($normalizedPhone, $fid)->first();

        if (!$user && $email !== '' && $this->userByEmail($email, $fid)->exists()) {
            return response()->json(['message' => 'Користувач з таким email вже існує.'], 422);
        }

        if (!$user) {
            $user = $this->createPhoneAuthUser(
                $normalizedPhone,
                $fid,
                trim((string) ($validated['name'] ?? '')),
                trim((string) ($validated['surname'] ?? '')),
                $email !== '' ? $email : null,
            );
        } else {
            $profileUpdates = [];

            if ($email !== '' && empty($user->email)) {
                $profileUpdates['email'] = $email;
            }

            if (!empty($validated['name']) && empty($user->name)) {
                $profileUpdates['name'] = trim((string) $validated['name']);
            }

            if (!empty($validated['surname']) && empty($user->secondname)) {
                $profileUpdates['secondname'] = trim((string) $validated['surname']);
                $profileUpdates['fathername'] = trim((string) $validated['surname']);
            }

            if ($profileUpdates !== []) {
                $user->update(User::filterUsersColumns($profileUpdates));
                $user = $user->fresh();
            }
        }

        $this->syncUserRoleStatus($user);
        $this->establishAuthenticatedSession($request, $user);
        Cache::forget($this->phoneOtpCodeKey($normalizedPhone));

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Телефон підтверджено.',
            'user' => $this->serializeUser($user->fresh()),
            'token' => $token,
        ]);
    }

    public function apiGoogleLogin(Request $request)
    {
        $request->validate([
            'credential' => 'required|string',
        ]);

        $googleClientId = (string) config('services.google.client_id', '');

        if ($googleClientId === '') {
            return response()->json(['message' => 'Google login is not configured'], 503);
        }

        try {
            $payload = $this->verifyGoogleCredential($request->string('credential')->toString(), $googleClientId);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to verify Google account: ' . $e->getMessage()], 422);
        }

        if (!$payload) {
            return response()->json(['message' => 'Failed to verify Google account (unknown error)'], 422);
        }

        $user = $this->resolveExistingGoogleUser($payload, $this->resolveAuthFid($request));

        if (!$user) {
            return response()->json([
                'message' => 'Email Google не найден в базе users.',
            ], 404);
        }

        $this->syncUserRoleStatus($user);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
            'token' => $token,
        ]);
    }

    public function apiAuthConfig()
    {
        return response()->json([
            'googleClientId' => (string) config('services.google.client_id', ''),
            'phoneAuthEnabled' => $this->isPhoneAuthEnabled(),
        ]);
    }

    public function zkLoginConfig()
    {
        $shinamiProver = ShinamiClient::walletApiKey() !== null;

        return response()->json([
            'googleClientId' => (string) config('services.google.client_id', ''),
            'proverUrl' => $shinamiProver ? '' : (string) config('services.sui.zklogin_prover_url', ''),
            'proverProvider' => $shinamiProver ? 'shinami' : 'mysten',
            'gasSponsorshipEnabled' => SuiLocalGasSponsorClient::isConfigured() || ShinamiClient::gasApiKey() !== null,
            'gasSponsorshipProvider' => SuiLocalGasSponsorClient::isConfigured() ? 'local' : (ShinamiClient::gasApiKey() !== null ? 'shinami' : null),
            'enabled' => (string) config('services.google.client_id', '') !== '',
        ]);
    }

    public function shinamiSponsorSuiTransaction(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'transactionKindBase64' => ['required', 'string', 'max:131072'],
            'sender' => ['required', 'string', 'max:80'],
            'gasBudget' => ['nullable', 'string', 'max:32'],
            'gasPrice' => ['nullable', 'string', 'max:32'],
        ]);

        $sender = $this->normalizeSuiWalletAddress((string) $validated['sender']);

        if (! $this->userControlsSuiAddress($user, $sender)) {
            return response()->json(['message' => 'Sender address is not linked to this account'], 403);
        }

        $gasBudget = isset($validated['gasBudget']) && $validated['gasBudget'] !== '' ? (string) $validated['gasBudget'] : null;
        $gasPrice = isset($validated['gasPrice']) && $validated['gasPrice'] !== '' ? (string) $validated['gasPrice'] : null;
        $txKind = (string) $validated['transactionKindBase64'];

        Log::info('Sui gas sponsorship requested.', [
            'provider' => SuiLocalGasSponsorClient::isConfigured() ? 'local' : (ShinamiClient::gasApiKey() !== null ? 'shinami' : null),
            'fallback_provider' => SuiLocalGasSponsorClient::isConfigured() && ShinamiClient::gasApiKey() !== null ? 'shinami' : null,
            'sender' => $sender,
            'tx_kind_base64_len' => strlen($txKind),
            'gas_budget' => $gasBudget,
            'gas_price' => $gasPrice,
        ]);

        if (SuiLocalGasSponsorClient::isConfigured()) {
            try {
                $out = SuiLocalGasSponsorClient::sponsorTransactionBlock($txKind, $sender, $gasBudget, $gasPrice);
            } catch (\RuntimeException $e) {
                Log::warning('Local Sui gas sponsorship failed.', [
                    'sender' => $sender,
                    'message' => $e->getMessage(),
                ]);

                if (ShinamiClient::gasApiKey() === null) {
                    return response()->json(['message' => $e->getMessage()], 502);
                }

                Log::info('Falling back to Shinami gas sponsorship.', [
                    'sender' => $sender,
                    'local_error' => $e->getMessage(),
                ]);
            }

            if (isset($out)) {
                Log::info('Local Sui gas sponsorship succeeded.', [
                    'sender' => $sender,
                    'tx_digest' => $out['txDigest'] ?? null,
                ]);

                return response()->json($out);
            }
        }

        if (ShinamiClient::gasApiKey() === null) {
            return response()->json(['message' => 'Gas sponsorship is not configured (set SUI_GAS_SPONSOR_PRIVATE_KEY + SUI_RPC_URL, or SHINAMI_GAS_ACCESS_KEY)'], 503);
        }

        try {
            $out = ShinamiClient::sponsorTransactionBlock($txKind, $sender, $gasBudget, $gasPrice);
        } catch (\RuntimeException $e) {
            Log::warning('Shinami gas sponsorship failed.', [
                'sender' => $sender,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 502);
        }

        Log::info('Shinami gas sponsorship succeeded.', [
            'sender' => $sender,
            'tx_digest' => $out['txDigest'] ?? null,
        ]);

        return response()->json($out);
    }

    public function zkLoginGoogleSalt(Request $request)
    {
        $validated = $request->validate([
            'jwt' => ['required', 'string'],
        ]);

        $googleClientId = (string) config('services.google.client_id', '');

        if ($googleClientId === '') {
            return response()->json(['message' => 'Google zkLogin is not configured'], 503);
        }

        $payload = $this->verifyGoogleCredential((string) $validated['jwt'], $googleClientId);

        if (!$payload) {
            return response()->json(['message' => 'Failed to verify Google account'], 422);
        }

        $user = $this->resolveExistingGoogleUser($payload, $this->resolveAuthFid($request));

        if (!$user) {
            return response()->json([
                'message' => 'Email Google не найден в базе users.',
            ], 404);
        }

        $identity = $this->resolveOrCreateZkLoginIdentity($user, $payload);

        return response()->json([
            'salt' => $identity['salt'],
            'provider' => 'google',
            'iss' => (string) ($payload['iss'] ?? 'https://accounts.google.com'),
            'aud' => (string) ($payload['aud'] ?? $googleClientId),
            'sub' => (string) ($payload['sub'] ?? ''),
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function zkLoginGoogleProof(Request $request)
    {
        $validated = $request->validate([
            'jwt' => ['required', 'string'],
            'extendedEphemeralPublicKey' => ['required', 'string', 'max:512'],
            'maxEpoch' => ['required', 'integer', 'min:0'],
            'jwtRandomness' => ['required', 'string', 'max:256'],
            'salt' => ['required', 'string', 'max:128'],
            'keyClaimName' => ['nullable', 'string', 'max:32'],
        ]);

        $googleClientId = (string) config('services.google.client_id', '');

        if ($googleClientId === '') {
            return response()->json(['message' => 'Google zkLogin is not configured'], 503);
        }

        $payload = $this->verifyGoogleCredential((string) $validated['jwt'], $googleClientId);

        if (!$payload) {
            return response()->json(['message' => 'Failed to verify Google account'], 422);
        }

        $user = $this->resolveExistingGoogleUser($payload, $this->resolveAuthFid($request));

        if (!$user) {
            return response()->json([
                'message' => 'Email Google не найден в базе users.',
            ], 404);
        }

        $identity = $this->resolveOrCreateZkLoginIdentity($user, $payload);

        if ((string) $identity['salt'] !== (string) $validated['salt']) {
            return response()->json(['message' => 'Salt does not match the stored zkLogin identity.'], 422);
        }

        if (ShinamiClient::walletApiKey() !== null) {
            try {
                Log::info('Shinami zkLogin proof requested.', [
                    'max_epoch_type' => gettype($validated['maxEpoch']),
                    'extended_ephemeral_public_key_len' => strlen((string) $validated['extendedEphemeralPublicKey']),
                    'jwt_randomness_len' => strlen((string) $validated['jwtRandomness']),
                    'salt_len' => strlen((string) $validated['salt']),
                    'key_claim_name' => (string) ($validated['keyClaimName'] ?? 'sub'),
                ]);

                $result = ShinamiClient::zkProverRpc('shinami_zkp_createZkLoginProof', [
                    (string) $validated['jwt'],
                    (string) $validated['maxEpoch'],
                    (string) $validated['extendedEphemeralPublicKey'],
                    (string) $validated['jwtRandomness'],
                    (string) $validated['salt'],
                ]);
            } catch (\RuntimeException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 502);
            }

            $zkProof = $result['zkProof'] ?? null;
            if (! is_array($zkProof)) {
                return response()->json([
                    'message' => 'Shinami zkLogin prover returned an unexpected response.',
                    'details' => $result,
                ], 502);
            }

            return response()->json([
                'proofPoints' => $zkProof['proofPoints'] ?? null,
                'issBase64Details' => $zkProof['issBase64Details'] ?? null,
                'headerBase64' => $zkProof['headerBase64'] ?? null,
            ]);
        }

        $proverUrl = trim((string) config('services.sui.zklogin_prover_url', ''));

        if ($proverUrl === '') {
            return response()->json(['message' => 'zkLogin prover URL is not configured'], 503);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(45)
                ->connectTimeout(10)
                ->post($proverUrl, [
                    'jwt' => (string) $validated['jwt'],
                    'extendedEphemeralPublicKey' => (string) $validated['extendedEphemeralPublicKey'],
                    'maxEpoch' => (int) $validated['maxEpoch'],
                    'jwtRandomness' => (string) $validated['jwtRandomness'],
                    'salt' => (string) $validated['salt'],
                    'keyClaimName' => (string) ($validated['keyClaimName'] ?? 'sub'),
                ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'zkLogin proving service is unavailable.',
                'error' => $e->getMessage(),
            ], 502);
        }

        $json = $response->json();

        if (! $response->ok()) {
            $proverMessage = '';
            if (is_array($json)) {
                if (isset($json['message']) && is_scalar($json['message'])) {
                    $proverMessage = (string) $json['message'];
                } elseif (isset($json['error']) && is_scalar($json['error'])) {
                    $proverMessage = (string) $json['error'];
                }
            }
            $bodyPreview = $proverMessage === ''
                ? Str::limit(trim((string) preg_replace('/\s+/', ' ', $response->body())), 500)
                : '';
            $message = trim('zkLogin proving service rejected the request. ' . $proverMessage);

            if (str_contains(strtolower($message), 'audience') && str_contains(strtolower($message), 'not supported')) {
                $message .= ' Current backend prover is Mysten, not Shinami. Set SHINAMI_WALLET_ACCESS_KEY or SHINAMI_ZKPROVER_ACCESS_KEY on Laravel and run php artisan optimize:clear.';
            }

            return response()->json([
                'message' => $message,
                'status' => $response->status(),
                'details' => $json,
                'body' => $bodyPreview ?: null,
            ], 502);
        }

        if (! is_array($json)) {
            return response()->json([
                'message' => 'zkLogin proving service returned a non-JSON response.',
                'status' => $response->status(),
                'body' => Str::limit(trim((string) preg_replace('/\s+/', ' ', $response->body())), 500),
            ], 502);
        }

        return response()->json($json);
    }

    public function zkLoginGoogleLogin(Request $request)
    {
        $validated = $request->validate([
            'jwt' => ['required', 'string'],
            'address' => ['required', 'string', 'max:80'],
        ]);

        $googleClientId = (string) config('services.google.client_id', '');

        if ($googleClientId === '') {
            return response()->json(['message' => 'Google zkLogin is not configured'], 503);
        }

        $payload = $this->verifyGoogleCredential((string) $validated['jwt'], $googleClientId);

        if (!$payload) {
            return response()->json(['message' => 'Failed to verify Google account'], 422);
        }

        $user = $this->resolveExistingGoogleUser($payload, $this->resolveAuthFid($request));

        if (!$user) {
            return response()->json([
                'message' => 'Email Google не найден в базе users.',
            ], 404);
        }

        $address = $this->normalizeSuiWalletAddress((string) $validated['address']);

        if (!$this->isValidWalletAddress($address, 'solana') && !$this->looksLikeSuiAddress($address)) {
            return response()->json(['message' => 'Invalid zkLogin Sui address format.'], 422);
        }

        $this->syncUserRoleStatus($user);
        $identity = $this->resolveOrCreateZkLoginIdentity($user, $payload);

        $this->bindWalletToUser($user, $address, 'sui', 1);

        if (Schema::hasTable('zklogin_identities')) {
            DB::table('zklogin_identities')
                ->where('id', $identity['id'])
                ->update([
                    'wallet_address' => $address,
                    'last_login_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
            'token' => $token,
            'wallet_address' => $address,
            'salt' => $identity['salt'],
        ]);
    }

    public function apiLogout(Request $request)
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if ($currentToken && method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

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

    public function resolveUserByWallet(Request $request)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:120'],
        ]);

        $address = $this->normalizeLookupWalletAddress((string) $validated['address']);

        if ($address === '') {
            return response()->json([
                'found' => false,
                'user' => null,
            ]);
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

        if (! $user) {
            return response()->json([
                'found' => false,
                'user' => null,
            ]);
        }

        return response()->json([
            'found' => true,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function apiUpdateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validationRules = [
            'name' => 'nullable|string|max:255',
            'secondname' => 'nullable|string|max:255',
            'fathername' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
        ];

        $validated = $request->validate($validationRules);

        $user->update($validated);
        $user = $user->fresh();

        return response()->json([
            'user' => $this->serializeUser($user),
            'message' => 'Profile updated successfully',
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
        $fid = $this->resolveAuthFid($request);

        $user = $this->userForLogin($login, $fid)->first();

        if ($user) {
            return response()->json(['message' => 'Користувач вже існує'], 422);
        }

        if ($this->userByEmail($email, $fid)->exists()) {
            return response()->json(['message' => 'Користувач з таким email вже існує'], 422);
        }

        if ($phone !== '' && $this->userByPhone($phone, $fid)->exists()) {
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

        $userFirma = $this->authFirmaForNewUser($fid);

        if (Schema::hasColumn('users', 'firma')) {
            $userData['firma'] = $userFirma;
        }

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        $user = User::create(User::filterUsersColumns($userData));

        // Auto login for stateful web callers; API clients receive JSON and can authenticate separately.
        $this->establishAuthenticatedSession($request, $user);

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
            'address' => ['required', 'string', 'max:120'],
            'signature' => ['required', 'string', 'max:8192'],
            'network' => 'nullable|string|max:80',
            'wallet_type' => ['nullable', 'string', 'in:eth,arbitrum,base,polygon,bnb,solana,sui'],
        ]);

        $walletType = $this->resolveLinkWalletType($validated);
        $address = $this->normalizeWalletAddress((string) $validated['address'], $walletType);

        if (!$this->isValidWalletAddress($address, $walletType)) {
            return response()->json(['message' => 'Невірний формат адреси гаманця.'], 422);
        }

        $nonce = Cache::get($this->web3LinkNonceKey($user->id, $walletType, $address));

        if (!$nonce) {
            return response()->json(['message' => 'Запрос на привязку устарел. Повторите попытку.'], 422);
        }

        $message = $this->makeWeb3Message('link', $nonce, $address, $walletType);

        if (!$this->verifyWalletSignature($message, (string) $validated['signature'], $address, $walletType)) {
            return response()->json([
                'message' => 'Подпись кошелька не прошла проверку. Если сообщение повторяется, убедитесь что на API установлены Node.js и зависимости (npm install в каталоге laravel-api), либо откройте консоль браузера.',
            ], 422);
        }

        $this->bindWalletToUser($user, $address, $this->storageNetworkForLinkedWallet($walletType), 0);
        Cache::forget($this->web3LinkNonceKey($user->id, $walletType, $address));

        return response()->json([
            'user' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function web3LoginChallenge(Request $request)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:80'],
            'wallet_type' => ['nullable', 'string', 'in:evm,solana'],
        ]);

        $walletType = $this->normalizeWalletType($validated['wallet_type'] ?? null);
        $address = $this->normalizeWalletAddress((string) $validated['address'], $walletType);

        if (!$this->isValidWalletAddress($address, $walletType)) {
            return response()->json(['message' => 'Невірний формат адреси гаманця.'], 422);
        }

        $nonce = Str::random(32);

        Cache::put($this->web3LoginNonceKey($walletType, $address), $nonce, now()->addMinutes(10));

        return response()->json([
            'nonce' => $nonce,
            'message' => $this->makeWeb3Message('login', $nonce, $address, $walletType),
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
            'address' => ['required', 'string', 'max:120'],
            'wallet_type' => ['nullable', 'string', 'in:eth,arbitrum,base,polygon,bnb,solana,sui'],
            'network' => 'nullable|string|max:80',
        ]);

        $walletType = $this->resolveLinkWalletType($validated);
        $address = $this->normalizeWalletAddress((string) $validated['address'], $walletType);

        if (!$this->isValidWalletAddress($address, $walletType)) {
            return response()->json(['message' => 'Невірний формат адреси гаманця.'], 422);
        }

        $nonce = Str::random(32);

        Cache::put($this->web3LinkNonceKey($user->id, $walletType, $address), $nonce, now()->addMinutes(10));

        return response()->json([
            'nonce' => $nonce,
            'message' => $this->makeWeb3Message('link', $nonce, $address, $walletType),
        ]);
    }

    public function web3Login(Request $request)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:80'],
            'signature' => ['required', 'string', 'max:512'],
            'wallet_type' => ['nullable', 'string', 'in:evm,solana'],
        ]);

        $walletType = $this->normalizeWalletType($validated['wallet_type'] ?? null);
        $address = $this->normalizeWalletAddress((string) $validated['address'], $walletType);

        if (!$this->isValidWalletAddress($address, $walletType)) {
            return response()->json(['message' => 'Невірний формат адреси гаманця.'], 422);
        }

        $nonce = Cache::get($this->web3LoginNonceKey($walletType, $address));

        if (!$nonce) {
            return response()->json(['message' => 'Запрос на вход устарел. Повторите попытку.'], 422);
        }

        $message = $this->makeWeb3Message('login', $nonce, $address, $walletType);

        if (!$this->verifyWalletSignature($message, (string) $validated['signature'], $address, $walletType)) {
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
        Cache::forget($this->web3LoginNonceKey($walletType, $address));

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
            'address' => ['nullable', 'string', 'max:120'],
            'wallet_type' => ['nullable', 'string', 'in:eth,arbitrum,base,polygon,bnb,solana,sui'],
        ]);

        $walletTypeForNorm = $this->unlinkWalletTypeForAddressNormalization($validated['wallet_type'] ?? null);
        $address = !empty($validated['address'])
            ? $this->normalizeWalletAddress((string) $validated['address'], $walletTypeForNorm)
            : null;

        if (Schema::hasTable('user_wallets')) {
            $query = DB::table('user_wallets')->where('user_id', $user->id);

            if (!empty($address)) {
                $query->where('address', $address);
                $storageNetwork = $this->unlinkStorageNetworkFilter($validated['wallet_type'] ?? null);
                if ($storageNetwork !== null) {
                    $query->where('network', $storageNetwork);
                }
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

    private function isPhoneAuthEnabled(): bool
    {
        return trim((string) config('services.smsclub.token', '')) !== ''
            && trim((string) config('services.smsclub.sender', '')) !== '';
    }

    private function normalizePhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '38' . $digits;
        }

        if (!str_starts_with($digits, '38') && strlen($digits) === 10) {
            $digits = '38' . $digits;
        }

        if (!preg_match('/^380\d{9}$/', $digits)) {
            return null;
        }

        return '+' . $digits;
    }

    private function smsClubPhonePayload(string $phone): string
    {
        return ltrim($phone, '+');
    }

    private function generatePhoneOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function makePhoneOtpMessage(string $code): string
    {
        return "Код входу AV8 Capital: {$code}. Дійсний 10 хвилин.";
    }

    private function phoneOtpCodeKey(string $phone): string
    {
        return 'auth:phone-otp:' . sha1($phone);
    }

    private function phoneOtpRateLimitKey(Request $request, string $phone): string
    {
        return 'auth:phone-otp:send:' . sha1($phone . '|' . (string) $request->ip());
    }

    private function createPhoneAuthUser(
        string $phone,
        ?string $fid,
        string $name = '',
        string $surname = '',
        ?string $email = null
    ): User {
        $randomPassword = Str::random(40);
        $passwordHash = Hash::make($randomPassword);
        $userFirma = $this->authFirmaForNewUser($fid);
        $normalizedEmail = $email !== null ? trim($email) : '';
        $login = $normalizedEmail !== '' ? $normalizedEmail : $phone;

        $userData = [
            'login' => $login,
            'phone' => $phone,
            'pass' => $passwordHash,
            'password' => $passwordHash,
            'name' => $name !== '' ? $name : 'Phone User',
            'secondname' => $surname,
            'fathername' => $surname,
            'idstatus' => 1,
            'ustype' => 1,
        ];

        if (Schema::hasColumn('users', 'email')) {
            $userData['email'] = $normalizedEmail;
        }

        if (Schema::hasColumn('users', 'firma')) {
            $userData['firma'] = $userFirma;
        }

        if (Schema::hasColumn('users', 'status')) {
            $userData['status'] = 1;
        }

        if (Schema::hasColumn('users', 'email_verified_at') && $normalizedEmail !== '') {
            $userData['email_verified_at'] = now();
        }

        return User::create(User::filterUsersColumns($userData));
    }

    private function userControlsSuiAddress(User $user, string $normalizedSender): bool
    {
        $target = strtolower($normalizedSender);

        $zk = $this->resolveZkLoginWalletAddress($user->id);
        if ($zk && strtolower($zk) === $target) {
            return true;
        }

        foreach ($this->userWallets($user->id) as $wallet) {
            $addr = trim((string) ($wallet['address'] ?? ''));
            if ($addr === '') {
                continue;
            }
            $candidate = $this->normalizeSuiWalletAddress($addr);
            if ($candidate && strtolower($candidate) === $target) {
                return true;
            }
        }

        return false;
    }

    private function resolveZkLoginWalletAddress(int|string $userId): ?string
    {
        if (! Schema::hasTable('zklogin_identities')) {
            return null;
        }

        $row = DB::table('zklogin_identities')
            ->where('user_id', $userId)
            ->where('provider', 'google')
            ->whereNotNull('wallet_address')
            ->orderByDesc('updated_at')
            ->first();

        if (! $row || empty($row->wallet_address)) {
            return null;
        }

        return $this->normalizeSuiWalletAddress((string) $row->wallet_address);
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
            'fid' => $user->firma ?: $user->fid,
            'idstatus' => $user->idstatus ?: $user->ustype,
            'wallet_address' => $primaryWallet['address'] ?? $user->wallet_address,
            'wallet_network' => $primaryWallet['network'] ?? $user->wallet_network,
            'wallet_connected_at' => $primaryWallet['connected_at'] ?? optional($user->wallet_connected_at)->toIso8601String(),
            'wallets' => $wallets,
            'zklogin_wallet_address' => $this->resolveZkLoginWalletAddress($user->id),
            'idkassa' => $user->idkassa,
            'idsklad' => $user->idsklad,
            'idreestr' => $user->idreestr,
            'domen' => $user->domen,
            'bonus' => $user->bonus,
            'balans' => $user->balans,
        ];
    }

    private function bindWalletToUser(User $user, string $address, ?string $network = null, int $web3auth = 0): void
    {
        $web3authFlag = ((int) $web3auth) === 1 ? 1 : 0;

        if (Schema::hasTable('user_wallets')) {
            $networkKey = $network !== null && trim($network) !== '' ? trim((string) $network) : 'eth';

            $exists = DB::table('user_wallets')
                ->where('address', $address)
                ->where('network', $networkKey)
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($exists) {
                abort(response()->json([
                    'message' => 'Цей гаманець вже прив’язаний до іншого акаунта.',
                ], 422));
            }

            $now = now();

            $existing = DB::table('user_wallets')
                ->where('user_id', $user->id)
                ->where('address', $address)
                ->where('network', $networkKey)
                ->first();

            $walletRow = [
                'connected_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('user_wallets', 'web3auth')) {
                $walletRow['web3auth'] = $web3authFlag;
            }

            if ($existing) {
                DB::table('user_wallets')->where('id', $existing->id)->update($walletRow);
            } else {
                $insert = [
                    'user_id' => $user->id,
                    'address' => $address,
                    'network' => $networkKey,
                    'connected_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('user_wallets', 'web3auth')) {
                    $insert['web3auth'] = $web3authFlag;
                }

                DB::table('user_wallets')->insert($insert);
            }

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
                        'web3auth' => 0,
                    ];
                }
            }

            return $fallback;
        }

        return DB::table('user_wallets')
            ->where('user_id', $userId)
            ->orderByDesc('connected_at')
            ->orderByDesc('id')
            ->get($this->userWalletSelectColumns())
            ->map(function ($wallet) {
                return $this->mapUserWalletRow($wallet);
            })
            ->all();
    }

    /** @return string[] */
    private function userWalletSelectColumns(): array
    {
        $cols = ['address', 'network', 'connected_at'];

        if (Schema::hasTable('user_wallets') && Schema::hasColumn('user_wallets', 'web3auth')) {
            $cols[] = 'web3auth';
        }

        return $cols;
    }

    /**
     * DB::table rows expose timestamps as strings; optional()->toIso8601String() only works on DateTime.
     */
    private function formatWalletConnectedAt(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_string($value)) {
            try {
                return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
            } catch (\Throwable) {
                return $value;
            }
        }

        return null;
    }

    /** @param object $wallet */
    private function mapUserWalletRow($wallet): array
    {
        $row = [
            'address' => $wallet->address,
            'network' => $wallet->network,
            'connected_at' => $this->formatWalletConnectedAt($wallet->connected_at ?? null),
        ];

        if (property_exists($wallet, 'web3auth')) {
            $row['web3auth'] = (int) $wallet->web3auth;
        } else {
            $row['web3auth'] = 0;
        }

        return $row;
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

        $sync = [];
        if (Schema::hasColumn('users', 'wallet_address')) {
            $sync['wallet_address'] = $primaryWallet->address ?? null;
        }
        if (Schema::hasColumn('users', 'wallet_network')) {
            $sync['wallet_network'] = $primaryWallet->network ?? null;
        }
        if (Schema::hasColumn('users', 'wallet_connected_at')) {
            $sync['wallet_connected_at'] = $primaryWallet->connected_at ?? null;
        }

        if ($sync !== []) {
            $user->forceFill($sync)->save();
        }
    }

    private function makeWeb3Message(string $purpose, string $nonce, string $address, string $walletType = 'eth'): string
    {
        $action = $purpose === 'link' ? 'Bind wallet' : 'Login';
        $networkLabel = match ($walletType) {
            'solana' => 'Solana',
            'sui' => 'Sui',
            'arbitrum' => 'Arbitrum',
            'base' => 'Base',
            'polygon' => 'Polygon',
            'bnb' => 'BNB Chain',
            'eth' => 'Ethereum',
            default => 'Ethereum',
        };

        return implode("\n", [
            'AV8 Capital DAO',
            "{$action} with wallet",
            "Network: {$networkLabel}",
            "Address: {$address}",
            "Nonce: {$nonce}",
        ]);
    }

    private function web3LoginNonceKey(string $walletType, string $address): string
    {
        return "web3:login:nonce:{$walletType}:{$address}";
    }

    private function web3LinkNonceKey(int|string $userId, string $walletType, string $address): string
    {
        return "web3:link:nonce:{$userId}:{$walletType}:{$address}";
    }

    private function normalizeWalletType(?string $walletType): string
    {
        $t = strtolower(trim((string) $walletType));

        if ($t === 'solana') {
            return 'solana';
        }

        return 'eth';
    }

    private function resolveLinkWalletType(array $validated): string
    {
        $raw = strtolower(trim((string) ($validated['wallet_type'] ?? '')));

        if ($raw === 'solana') {
            return 'solana';
        }

        if ($raw === 'sui') {
            return 'sui';
        }

        if (in_array($raw, ['eth', 'arbitrum', 'base', 'polygon', 'bnb'], true)) {
            return $raw;
        }

        $fromHint = $this->resolveEvmChainSlugFromNetworkHint($validated['network'] ?? null);
        if ($fromHint !== null) {
            return $fromHint;
        }

        return 'eth';
    }

    /**
     * Map free-form network (chain name, hex id, decimal id, legacy labels) to a storage slug.
     */
    private function resolveEvmChainSlugFromNetworkHint(?string $network): ?string
    {
        $hint = strtolower(trim((string) $network));

        if ($hint === '') {
            return null;
        }

        if (in_array($hint, ['eth', 'ethereum', 'mainnet', 'eip155:1'], true)) {
            return 'eth';
        }

        if (in_array($hint, ['arbitrum', 'arb', 'arbitrum one', 'eip155:42161'], true)) {
            return 'arbitrum';
        }

        if (in_array($hint, ['base', 'eip155:8453'], true)) {
            return 'base';
        }

        if (in_array($hint, ['polygon', 'matic', 'eip155:137'], true)) {
            return 'polygon';
        }

        if (in_array($hint, ['bnb', 'bsc', 'binance', 'eip155:56'], true)) {
            return 'bnb';
        }

        $hexMap = [
            '0x1' => 'eth',
            '0xa4b1' => 'arbitrum',
            '0x2105' => 'base',
            '0x89' => 'polygon',
            '0x38' => 'bnb',
        ];

        if (isset($hexMap[$hint])) {
            return $hexMap[$hint];
        }

        if (preg_match('/0x[a-f0-9]+/i', $hint, $hexMatch)) {
            $hexToken = strtolower($hexMatch[0]);
            if (isset($hexMap[$hexToken])) {
                return $hexMap[$hexToken];
            }
        }

        $decimalMap = [
            '1' => 'eth',
            '42161' => 'arbitrum',
            '8453' => 'base',
            '137' => 'polygon',
            '56' => 'bnb',
        ];

        return $decimalMap[$hint] ?? null;
    }

    private function storageNetworkForLinkedWallet(string $walletType): string
    {
        return match ($walletType) {
            'solana' => 'solana',
            'sui' => 'sui',
            default => $walletType,
        };
    }

    private function isEvmWalletType(string $walletType): bool
    {
        return in_array($walletType, ['eth', 'arbitrum', 'base', 'polygon', 'bnb'], true);
    }

    private function unlinkWalletTypeForAddressNormalization(?string $walletTypeRaw): string
    {
        if ($walletTypeRaw === null || trim($walletTypeRaw) === '') {
            return 'eth';
        }

        $t = strtolower(trim($walletTypeRaw));

        if ($t === 'solana') {
            return 'solana';
        }

        if ($t === 'sui') {
            return 'sui';
        }

        if (in_array($t, ['eth', 'arbitrum', 'base', 'polygon', 'bnb'], true)) {
            return $t;
        }

        return 'eth';
    }

    /**
     * @return non-null string to filter one row, or null to delete all rows for this address (any network)
     */
    private function unlinkStorageNetworkFilter(?string $walletTypeRaw): ?string
    {
        if ($walletTypeRaw === null || trim((string) $walletTypeRaw) === '') {
            return null;
        }

        $t = strtolower(trim((string) $walletTypeRaw));

        if ($t === 'solana') {
            return 'solana';
        }

        if ($t === 'sui') {
            return 'sui';
        }

        if (in_array($t, ['eth', 'arbitrum', 'base', 'polygon', 'bnb'], true)) {
            return $t;
        }

        return 'eth';
    }

    private function normalizeLookupWalletAddress(string $address): string
    {
        return strtolower(trim($address));
    }

    private function normalizeWalletAddress(string $address, string $walletType): string
    {
        $address = trim($address);

        if ($walletType === 'solana') {
            return $address;
        }

        if ($walletType === 'sui') {
            return $this->normalizeSuiWalletAddress($address);
        }

        if ($this->isEvmWalletType($walletType)) {
            return str_starts_with(strtolower($address), '0x')
                ? strtolower($address)
                : $address;
        }

        return str_starts_with(strtolower($address), '0x')
            ? strtolower($address)
            : $address;
    }

    private function isValidWalletAddress(string $address, string $walletType): bool
    {
        if ($walletType === 'solana') {
            return (bool) preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
        }

        if ($walletType === 'sui') {
            return $this->looksLikeSuiAddress($address);
        }

        if ($this->isEvmWalletType($walletType)) {
            return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
        }

        return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    private function verifyWalletSignature(string $message, string $signature, string $address, string $walletType): bool
    {
        if ($walletType === 'solana') {
            return $this->verifySolanaSignature($message, $signature, $address);
        }

        if ($walletType === 'sui') {
            return $this->verifySuiPersonalMessageSignature($message, $signature, $address);
        }

        if ($this->isEvmWalletType($walletType)) {
            return $this->verifyEthereumSignature($message, $signature, $address);
        }

        return $this->verifyEthereumSignature($message, $signature, $address);
    }

    private function verifySuiPersonalMessageSignature(string $message, string $signature, string $address): bool
    {
        if ($this->verifySuiEd25519PersonalMessageSignature($message, $signature, $address)) {
            return true;
        }

        $script = base_path('scripts/verify-sui-personal-message.mjs');

        if (! is_file($script)) {
            Log::warning('Sui personal message verification script is missing.', ['path' => $script]);

            return false;
        }

        $node = trim((string) config('services.sui.verify_node_binary', 'node'));
        $messageB64 = base64_encode($message);

        $process = new Process([$node, $script, $messageB64, $signature, $address]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::info('Sui personal message verification failed.', [
                'stderr' => $process->getErrorOutput(),
            ]);

            return false;
        }

        return trim($process->getOutput()) === '1';
    }

    private function verifySuiEd25519PersonalMessageSignature(string $message, string $signature, string $address): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached') || ! function_exists('sodium_crypto_generichash')) {
            Log::warning('Sui Ed25519 verification requires PHP sodium extension.');

            return false;
        }

        $bytes = base64_decode($signature, true);
        if ($bytes === false || strlen($bytes) !== 97) {
            return false;
        }

        $schemeFlag = ord($bytes[0]);
        if ($schemeFlag !== 0) {
            return false;
        }

        $rawSignature = substr($bytes, 1, 64);
        $publicKey = substr($bytes, 65, 32);

        if (strlen($rawSignature) !== SODIUM_CRYPTO_SIGN_BYTES || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        $normalizedAddress = $this->normalizeSuiAddressForVerification($address);
        $derivedAddress = '0x' . bin2hex(sodium_crypto_generichash(chr($schemeFlag) . $publicKey, '', 32));
        if ($normalizedAddress === '' || ! hash_equals($normalizedAddress, $derivedAddress)) {
            return false;
        }

        $digest = sodium_crypto_generichash(
            chr(3) . chr(0) . chr(0) . $this->encodeUleb128(strlen($message)) . $message,
            '',
            32
        );

        return sodium_crypto_sign_verify_detached($rawSignature, $digest, $publicKey);
    }

    private function normalizeSuiAddressForVerification(string $address): string
    {
        $trimmed = strtolower(trim($address));
        if ($trimmed === '') {
            return '';
        }

        $hex = str_starts_with($trimmed, '0x') ? substr($trimmed, 2) : $trimmed;
        if ($hex === '' || ! ctype_xdigit($hex) || strlen($hex) > 64) {
            return '';
        }

        return '0x' . str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    private function encodeUleb128(int $value): string
    {
        $bytes = '';

        do {
            $byte = $value & 0x7f;
            $value >>= 7;
            if ($value !== 0) {
                $byte |= 0x80;
            }
            $bytes .= chr($byte);
        } while ($value !== 0);

        return $bytes;
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

    private function resolveOrCreateZkLoginIdentity(User $user, array $payload): array
    {
        if (!Schema::hasTable('zklogin_identities')) {
            abort(response()->json([
                'message' => 'zkLogin identities storage is not available. Run database migrations first.',
            ], 503));
        }

        $issuer = trim((string) ($payload['iss'] ?? 'https://accounts.google.com'));
        $subject = trim((string) ($payload['sub'] ?? ''));
        $audience = trim((string) ($payload['aud'] ?? ''));

        if ($issuer === '' || $subject === '' || $audience === '') {
            abort(response()->json([
                'message' => 'Google token does not contain the required zkLogin claims.',
            ], 422));
        }

        $existing = DB::table('zklogin_identities')
            ->where('provider', 'google')
            ->where('issuer', $issuer)
            ->where('subject', $subject)
            ->first();

        if ($existing) {
            if ((int) $existing->user_id !== (int) $user->id) {
                DB::table('zklogin_identities')
                    ->where('id', $existing->id)
                    ->update([
                        'user_id' => $user->id,
                        'audience' => $audience,
                        'updated_at' => now(),
                    ]);
            }

            return [
                'id' => (int) $existing->id,
                'salt' => (string) $existing->salt,
                'wallet_address' => $existing->wallet_address ? (string) $existing->wallet_address : null,
            ];
        }

        $id = DB::table('zklogin_identities')->insertGetId([
            'user_id' => $user->id,
            'provider' => 'google',
            'issuer' => $issuer,
            'subject' => $subject,
            'audience' => $audience,
            'salt' => $this->generateZkLoginSalt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $created = DB::table('zklogin_identities')->where('id', $id)->first();

        return [
            'id' => (int) $id,
            'salt' => (string) ($created->salt ?? ''),
            'wallet_address' => $created && $created->wallet_address ? (string) $created->wallet_address : null,
        ];
    }

    private function generateZkLoginSalt(): string
    {
        return $this->hexToDecimalString(bin2hex(random_bytes(16)));
    }

    private function hexToDecimalString(string $hex): string
    {
        $hex = strtolower(trim($hex));
        $hex = ltrim($hex, '0');

        if ($hex === '') {
            return '0';
        }

        $decimal = '0';

        foreach (str_split($hex) as $char) {
            $value = strpos('0123456789abcdef', $char);
            $decimal = $this->decimalMultiplyAndAdd($decimal, 16, $value === false ? 0 : $value);
        }

        return $decimal;
    }

    private function decimalMultiplyAndAdd(string $decimal, int $multiplier, int $addition): string
    {
        $carry = $addition;
        $result = '';

        for ($index = strlen($decimal) - 1; $index >= 0; $index--) {
            $product = ((int) $decimal[$index] * $multiplier) + $carry;
            $result = (string) ($product % 10) . $result;
            $carry = intdiv($product, 10);
        }

        while ($carry > 0) {
            $result = (string) ($carry % 10) . $result;
            $carry = intdiv($carry, 10);
        }

        return ltrim($result, '0') ?: '0';
    }

    private function normalizeSuiWalletAddress(string $address): string
    {
        $address = strtolower(trim($address));

        if ($address === '') {
            return '';
        }

        return str_starts_with($address, '0x') ? $address : "0x{$address}";
    }

    private function looksLikeSuiAddress(string $address): bool
    {
        return (bool) preg_match('/^0x[a-f0-9]{64}$/', strtolower(trim($address)));
    }

    private function verifySolanaSignature(string $message, string $signature, string $address): bool
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $publicKey = $this->base58Decode($address);
        $signatureBytes = base64_decode($signature, true);

        if ($publicKey === null || $signatureBytes === false) {
            return false;
        }

        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signatureBytes, $message, $publicKey);
    }

    private function base58Decode(string $value): ?string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $bytes = [0];

        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];
            $charIndex = strpos($alphabet, $char);

            if ($charIndex === false) {
                return null;
            }

            $carry = $charIndex;

            for ($j = 0, $count = count($bytes); $j < $count; $j++) {
                $carry += $bytes[$j] * 58;
                $bytes[$j] = $carry & 0xff;
                $carry >>= 8;
            }

            while ($carry > 0) {
                $bytes[] = $carry & 0xff;
                $carry >>= 8;
            }
        }

        for ($i = 0; $i < $length && $value[$i] === '1'; $i++) {
            $bytes[] = 0;
        }

        return pack('C*', ...array_reverse($bytes));
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
