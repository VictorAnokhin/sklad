<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Migrated from: users table + auth.php
 *
 * @property int    $id
 * @property string $login
 * @property string $pass        md5 hash → migrated to bcrypt on first login
 * @property string $phone
 * @property string $name        base64-encoded
 * @property string $secondname  base64-encoded
 * @property string $fathername  base64-encoded
 * @property string $orgname     base64-encoded
 * @property string $kod1        EDRPOU / tax code
 * @property string $name2       alias
 * @property string $city
 * @property string $region
 * @property string $poshta      Nova Poshta office
 * @property int    $idstatus    access level: 1=client, 2=worker, 3=manager, 4=admin
 * @property string $fid         firma id
 * @property string $idkassa     default cash register
 * @property string $idsklad     default warehouse
 * @property string $idreestr    default register
 * @property string $domen
 * @property float  $bonus
 * @property float  $balans
 * @property int    $top         rating 0-5
 * @property string $hbd         birthday base64-encoded
 */
class User extends Authenticatable
{
    protected $table    = 'users';
    public    $timestamps = false;

    protected $fillable = [
        'login', 'pass', 'phone', 'phone1', 'name', 'secondname', 'fathername',
        'orgname', 'kod1', 'name2', 'city', 'region', 'poshta',
        'idstatus', 'fid', 'idkassa', 'idsklad', 'idreestr',
        'domen', 'bonus', 'balans', 'top', 'hbd',
    ];

    protected $hidden = ['pass', 'remember_token'];

    // Laravel Auth uses 'password'; our column is 'pass'
    public function getAuthPassword(): string { return $this->pass; }
    public function getAuthIdentifierName(): string { return 'id'; }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim(
            convert_from_base($this->secondname) . ' '
          . convert_from_base($this->name)        . ' '
          . convert_from_base($this->fathername)
        );
    }

    public function getOrgDisplayAttribute(): string
    {
        $org = convert_from_base($this->orgname);
        return $org !== '' ? $org . ', ' . $this->kod1 : '';
    }

    public function getFormattedPhoneAttribute(): string
    {
        return formatPhone((string)$this->phone);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function documents()      { return $this->hasMany(Document::class,  'client1'); }
    public function zDocuments()     { return $this->hasMany(ZDocument::class, 'client1'); }
    public function zBodies()        { return $this->hasManyThrough(ZBody::class, Document::class, 'client1', 'docid'); }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForFirm($q, string $fid) { return $q->where('firma', $fid); }
    public function scopeActive($q)                { return $q->where('top', '>', 0); }
}
