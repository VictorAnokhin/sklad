<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'fathername',
        'fid',
        'idstatus',
        'idkassa',
        'idsklad',
        'idreestr',
        'domen',
        'bonus',
        'balans',
        'hbd',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
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
        'password' => 'hashed',
    ];

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
                $q->where('orgname', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('secondname', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('name2', 'like', $like);
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
            ->where('type', 'idstatus')->where('firma', $fid)->orderBy('name')->get();

        return compact('clients', 'total', 'statuses');
    }

    public static function showClient($id, $fid)
    {
        $client = $id !== '0' ?DB::table('users')->where('id', $id)->first() : null;

        // Selects needed for form
        $statuses = DB::table('conf')
            ->where('type', 'idstatus')->where('firma', $fid)->orderBy('name')->get();

        return compact('client', 'statuses');
    }

    public static function edit($id, $data)
    {
        if ($id === '0' || $id === '') {
            // New client: generate login/pass
            $phone = $data['phone'] ?? '';
            $data['login'] = $phone ?: uniqid('cl_');
            $data['pass'] = Hash::make($phone ?: str_pad((string)rand(1000, 9999), 4));
            $id = (string)DB::table('users')->insertGetId($data);
        }
        else {
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
        $exists = DB::table('firm')->where('id', $id)->exists();
        if ($exists) {
            DB::table('firm')->where('id', $id)->update($data);
        }
        else {
            DB::table('firm')->insert($data);
        }
    }
}