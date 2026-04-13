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
        'pass',
        'name',
        'secondname',
        'fathername',
        'fid',
        'firma',
        'idfirma',
        'status',
        'idstatus',
        'idkassa',
        'idsklad',
        'idreestr',
        'domen',
        'bonus',
        'balans',
        'hbd',
        'email',
        'wallet_address',
        'wallet_network',
        'wallet_connected_at',
        'password',
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
        'password' => 'hashed',
    ];

    public function getAuthPassword(): string
    {
        return (string) ($this->password ?: $this->pass ?: '');
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
        $search = $filters['search'] ?? '';
        $filterCity = $filters['city'] ?? '';
        $filterStatus = $filters['idstatus'] ?? '';
        $filterPhone = $filters['phone'] ?? '';

        $query = DB::table('users')
            ->where('firma', $fid)
            ->where('top', '>', 0);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('orgname', 'like', '%' . $like . '%')
                    ->orWhere('name', 'like', '%' . $like . '%')
                    ->orWhere('secondname', 'like', '%' . $like . '%')
                    ->orWhere('phone', 'like', '%' . $like . '%')
                    ->orWhere('city', 'like', '%' . $like . '%')
                    ->orWhere('name2', 'like', '%' . $like . '%');
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

        $total = $query->count();
        $clients = $query->orderByDesc('top')->orderBy('id')->offset($pos)->limit($pos2)->get();

        // Data for filter dropdowns
        $statuses = DB::table('conf')
            ->where('type', 'tclient')
            ->where('firma', $fid)
            ->orderBy('name')->get();

        return compact('clients', 'total', 'statuses');
    }

    public static function showClient($id, $fid)
    {
        $client = $id !== '0' ?DB::table('users')->where('id', $id)->first() : null;

        // Selects needed for form
        $statuses = DB::table('conf')
            ->where('type', 'tclient')
            ->where('firma', $fid)
            ->orderBy('name')->get();

        return compact('client', 'statuses');
    }

    public static function edit($id, $data)
    {
        $data = self::filterUsersColumns($data);

        if ($id === '0' || $id === '') {
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
        // Guard: has documents
        $hasDoc = DB::table('document')->where('client1', $id)->exists()
            || DB::table('z_document')->where('client1', $id)->exists();

        if ($hasDoc) {
            return false;
        }

        DB::table('users')->where('id', $id)->where('firma', $fid)->delete();
        return true;
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
}
