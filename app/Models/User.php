<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    private static ?array $usersColumnsCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'login',
        'phone',
        'address',
        'pass',
        'name',
        'secondname',
        'fathername',
        'city',
        'country',
        'region',
        'fid',
        'firma',
        'project_id',
        'status',
        'idstatus',
        'idkassa',
        'idsklad',
        'idreestr',
        'domen',
        'bonus',
        'balance',
        'balans',
        'hbd',
        'email',
        'tgroup',
        'usergroup',
        'foto1',
        'foto2',
        'foto3',
        'foto4',
        'foto5',
        'wallet_address',
        'wallet_network',
        'wallet_connected_at',
        'kyc_provider',
        'kyc_status',
        'kyc_applicant_id',
        'kyc_level_name',
        'kyc_verified_at',
        'kyc_passport_file_path',
        'kyc_passport_file_name',
        'kyc_passport_file_mime',
        'kyc_passport_file_size',
        'kyc_passport_uploaded_at',
        'kyc_passport_back_file_path',
        'kyc_passport_back_file_name',
        'kyc_passport_back_file_mime',
        'kyc_passport_back_file_size',
        'kyc_passport_back_uploaded_at',
        'kyc_selfie_file_path',
        'kyc_selfie_file_name',
        'kyc_selfie_file_mime',
        'kyc_selfie_file_size',
        'kyc_selfie_uploaded_at',
        'kyc_kep_signature_file_path',
        'kyc_kep_signature_file_name',
        'kyc_kep_signature_file_mime',
        'kyc_kep_signature_file_size',
        'kyc_kep_signature_uploaded_at',
        'kyc_liveness_file_path',
        'kyc_liveness_file_name',
        'kyc_liveness_file_mime',
        'kyc_liveness_file_size',
        'kyc_liveness_uploaded_at',
        'password',
        'education_rating',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'pass',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'wallet_connected_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'kyc_passport_uploaded_at' => 'datetime',
        'kyc_passport_back_uploaded_at' => 'datetime',
        'kyc_selfie_uploaded_at' => 'datetime',
        'kyc_kep_signature_uploaded_at' => 'datetime',
        'kyc_liveness_uploaded_at' => 'datetime',
        'password' => 'hashed',
        'education_rating' => 'integer',
    ];

    public function getAuthPassword(): string
    {
        return (string) ($this->password ?: $this->pass ?: '');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeForLogin(Builder $query, string $login): Builder
    {
        return $query->where(function (Builder $q) use ($login) {
            $hasCondition = false;

            if (self::hasUsersColumn('login')) {
                $q->where('login', $login);
                $hasCondition = true;
            }

            if (self::hasUsersColumn('email')) {
                if ($hasCondition) {
                    $q->orWhere('email', $login);
                } else {
                    $q->where('email', $login);
                }
                $hasCondition = true;
            }

            if (self::hasUsersColumn('phone')) {
                if ($hasCondition) {
                    $q->orWhere('phone', $login);
                } else {
                    $q->where('phone', $login);
                }
            }
        });
    }

    public function legacyLoginValue(): string
    {
        return (string) ($this->login ?: $this->email ?: $this->phone ?: '');
    }

    public function passwordMatches(string $plainText): bool
    {
        $hash = (string) ($this->password ?: $this->pass ?: '');

        if ($hash === '') {
            return false;
        }

        return $this->supportsLaravelHash($hash) && Hash::check($plainText, $hash)
            || $this->pass === $plainText
            || $this->password === $plainText
            || $this->pass === md5($plainText)
            || $this->pass === md5(md5($plainText));
    }

    public function usesLegacyPasswordHash(): bool
    {
        return !$this->supportsLaravelHash((string) $this->password)
            || !$this->supportsLaravelHash((string) $this->pass);
    }

    public function syncPasswordHash(string $plainText): void
    {
        $hash = Hash::make($plainText);

        $this->forceFill([
            'password' => $hash,
            'pass' => $hash,
        ])->save();
    }

    private function supportsLaravelHash(?string $hash): bool
    {
        if (!$hash) {
            return false;
        }

        $info = password_get_info($hash);

        return ($info['algo'] ?? null) !== null;
    }

    public static function init()
    {
    // Placeholder for initialization logic if needed
    }

    public static function userslist($fid, $filters, $pos, $pos2 = 20)
    {
        $firmas = self::normalizeFirmaScope($fid);
        $currentFirma = $firmas[0] ?? '';
        $search = $filters['search'] ?? '';
        $filterCity = $filters['city'] ?? '';
        $filterStatus = $filters['idstatus'] ?? '';
        $filterPhone = $filters['phone'] ?? '';
        $filterEmail = $filters['email'] ?? '';
        $hasEmailCol = self::hasUsersColumn('email');

        $query = DB::table('users')
            ->whereIn('firma', $firmas);

        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $searchId = ctype_digit(trim($search)) ? (int) trim($search) : null;
            $query->where(function ($q) use ($like, $searchId, $hasEmailCol) {
                $q->whereRaw('LOWER(orgname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(secondname) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(fathername) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(phone) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(city) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name2) LIKE ?', [$like]);

                if ($hasEmailCol) {
                    $q->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                }

                if ($searchId !== null) {
                    $q->orWhere('id', $searchId);
                }
            });
        }
        if ($filterCity !== '') {
            $query->where('city', 'like', '%' . $filterCity . '%');
        }
        if ($filterStatus !== '') {
            $query->where('idstatus', $filterStatus);
        }
        if ($filterPhone !== '') {
            $query->where('phone', 'like', '%' . $filterPhone . '%');
        }
        if ($filterEmail !== '' && $hasEmailCol) {
            $emailLike = '%' . mb_strtolower($filterEmail) . '%';
            $query->whereRaw('LOWER(email) LIKE ?', [$emailLike]);
        }

        $total = $query->count();
        $clients = $query->orderByDesc('top')->orderBy('id')->offset($pos)->limit($pos2)->get();

        // Data for filter dropdowns
        $statuses = DB::table('conf')
            ->where('type', 'tclient')
            ->where('firma', $currentFirma)
            ->orderBy('name')->get();

        return compact('clients', 'total', 'statuses');
    }

    public static function showClient($id, $fid)
    {
        $firmas = self::normalizeFirmaScope($fid);
        $currentFirma = $firmas[0] ?? '';
        $client = $id !== '0'
            ? DB::table('users')->where('id', $id)->whereIn('firma', $firmas)->first()
            : null;

        // Selects needed for form
        $statuses = DB::table('conf')
            ->where('type', 'tclient')
            ->where('firma', $currentFirma)
            ->orderBy('name')->get();

        return compact('client', 'statuses');
    }

    public static function edit($id, $data, bool $setFirmUserForNew = true)
    {
        $data = self::filterUsersColumns($data);

        if ($id === '0' || $id === '') {
            if ($setFirmUserForNew && self::hasUsersColumn('firmuser')) {
                $data['firmuser'] = '1';
            }

            // New client: generate login/pass
            $phone = trim((string) ($data['phone'] ?? ''));
            if (self::hasUsersColumn('login')) {
                $login = trim((string) ($data['login'] ?? ''));
                $email = trim((string) ($data['email'] ?? ''));
                $data['login'] = $login !== '' ? $login : ($email !== '' ? $email : ($phone ?: uniqid('cl_')));
            }
            $passwordHash = Hash::make($phone ?: str_pad((string)rand(1000, 9999), 4));
            $data['pass'] = $passwordHash;
            if (self::hasUsersColumn('password')) {
                $data['password'] = $passwordHash;
            }
            $id = (string)DB::table('users')->insertGetId($data);
            if (self::shouldCreateProjectForNewCounterparty($data)) {
                self::createProjectForCounterparty($id, $data);
            }
        }
        else {
            if (self::hasUsersColumn('login')) {
                $existingLogin = (string) (DB::table('users')->where('id', $id)->value('login') ?? '');
                $incomingLogin = trim((string) ($data['login'] ?? ''));
                $email = trim((string) ($data['email'] ?? ''));
                $phone = trim((string) ($data['phone'] ?? ''));

                $data['login'] = $incomingLogin !== ''
                    ? $incomingLogin
                    : ($existingLogin !== '' ? $existingLogin : ($email !== '' ? $email : ($phone !== '' ? $phone : uniqid('cl_'))));
            }

            DB::table('users')->where('id', $id)->update($data);
        }

        return $id;
    }

    public static function deleteClient($id, $fid)
    {
        // Guard: has documents (check both client1 and client2)
        $hasDoc = DB::table('document')->where('client1', $id)->exists()
            || DB::table('document')->where('client2', $id)->exists()
            || DB::table('z_document')->where('client1', $id)->exists()
            || DB::table('z_document')->where('client2', $id)->exists();

        if ($hasDoc) {
            return false;
        }

        DB::table('users')->where('id', $id)->whereIn('firma', self::normalizeFirmaScope($fid))->delete();
        return true;
    }

    private static function normalizeFirmaScope($fid): array
    {
        $values = is_array($fid) ? $fid : [$fid];

        return collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    public static function saveFirm($id, $data)
    {
        $exists = DB::table('firma')->where('id', $id)->exists();
        if ($exists) {
            DB::table('firma')->where('id', $id)->update($data);
        }
        else {
            DB::table('firma')->insert($data);
        }
    }

    public static function hasUsersColumn(string $column): bool
    {
        return in_array($column, self::usersColumns(), true);
    }

    public static function filterUsersColumns(array $data): array
    {
        $allowed = array_flip(self::usersColumns());

        return array_intersect_key($data, $allowed);
    }

    private static function usersColumns(): array
    {
        if (self::$usersColumnsCache === null) {
            self::$usersColumnsCache = Schema::hasTable('users')
                ? Schema::getColumnListing('users')
                : [];
        }

        return self::$usersColumnsCache;
    }

    /**
     * Запис у project — лише для контрагента без прив’язки до фірми (fid/firma порожній або 0).
     */
    private static function shouldCreateProjectForNewCounterparty(array $data): bool
    {
        $raw = $data['firma'] ?? $data['fid'] ?? '';
        $fid = trim((string) $raw);

        if ($fid === '' || $fid === '0') {
            return true;
        }

        if (is_numeric($fid)) {
            return ((int) $fid) === 0;
        }

        return false;
    }

    private static function createProjectForCounterparty(string $userId, array $data): void
    {
        if (!Schema::hasTable('project')) {
            return;
        }

        $projectColumns = Schema::getColumnListing('project');
        if ($projectColumns === []) {
            return;
        }

        if (in_array('userid', $projectColumns, true)) {
            $exists = DB::table('project')->where('userid', (int) $userId)->exists();
            if ($exists) {
                return;
            }
        }

        $companyName = self::resolveCounterpartyProjectName($data, $userId);

        $payload = [];

        if (in_array('num', $projectColumns, true)) {
            $payload['num'] = (int) $userId;
        }
        if (in_array('name', $projectColumns, true)) {
            $payload['name'] = $companyName;
        }
        if (in_array('phone', $projectColumns, true)) {
            $payload['phone'] = trim((string) ($data['phone'] ?? ''));
        }
        if (in_array('url', $projectColumns, true)) {
            $payload['url'] = '';
        }
        if (in_array('telegram', $projectColumns, true)) {
            $payload['telegram'] = '';
        }
        if (in_array('instagram', $projectColumns, true)) {
            $payload['instagram'] = '';
        }
        if (in_array('twitter', $projectColumns, true)) {
            $payload['twitter'] = '';
        }
        if (in_array('facebook', $projectColumns, true)) {
            $payload['facebook'] = '';
        }
        if (in_array('userid', $projectColumns, true)) {
            $payload['userid'] = (int) $userId;
        }
        if (in_array('foto', $projectColumns, true)) {
            $payload['foto'] = '';
        }
        if (in_array('foto_header', $projectColumns, true)) {
            $payload['foto_header'] = '';
        }
        if (in_array('foto_footer', $projectColumns, true)) {
            $payload['foto_footer'] = '';
        }
        if (in_array('description', $projectColumns, true)) {
            $payload['description'] = '';
        }
        if (in_array('web', $projectColumns, true)) {
            $payload['web'] = 0;
        }
        if (in_array('hit', $projectColumns, true)) {
            $payload['hit'] = 0;
        }
        if (in_array('htmlkeys', $projectColumns, true)) {
            $payload['htmlkeys'] = '';
        }
        if (in_array('created_at', $projectColumns, true)) {
            $payload['created_at'] = now();
        }
        if (in_array('updated_at', $projectColumns, true)) {
            $payload['updated_at'] = now();
        }

        if ($payload !== []) {
            DB::table('project')->insert($payload);
        }
    }

    private static function resolveCounterpartyProjectName(array $data, string $userId): string
    {
        $orgName = trim((string) ($data['orgname'] ?? ''));
        if ($orgName !== '') {
            return $orgName;
        }

        $fullName = trim(implode(' ', array_filter([
            trim((string) ($data['secondname'] ?? '')),
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['fathername'] ?? '')),
        ])));

        if ($fullName !== '') {
            return $fullName;
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            return $email;
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        return 'Counterparty #' . $userId;
    }
}
