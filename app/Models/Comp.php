<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Comp — product / component catalog (comp table)
 * Migrated from: comp/index.php, run-comp.php, delete-comp.php, toggle-sklad.php
 */
class Comp extends Model
{
    protected $table    = 'comp';
    public    $timestamps = false;

    protected $fillable = [
        'cod', 'hit', 'constanta', 'firma', 'firma_share', 'top',
        'nickname', 'idtype', 'idcaption', 'idglava', 'namedoc',
        'name', 'name_ua', 'name_en',
        'slogan', 'description', 'description_ua', 'description_en',
        'count', 'pay1', 'pay', 'profitpay', 'hand',
        'htmldescr', 'htmlkeys', 'htmlkeyspop',
        'param1','param2','param3','param4','param5','param6',
        'paramfix1','paramfix2','paramfix3','paramfix4',
        'garant', 'flag', 'sklad', 'dt',
        'nfoto','nfoto1','nfoto2','nfoto3','nfoto4',
        'nfoto5','nfoto6','nfoto7','nfoto8','nfoto9',
        'nfile', 'nvideo1', 'nvideo2',
    ];

    // ── Accessors (base64 decode) ─────────────────────────────────────────────

    public function getNameDecodedAttribute(): string        { return convert_from_base($this->name); }
    public function getNameUaDecodedAttribute(): string      { return convert_from_base($this->name_ua); }
    public function getNicknameDecodedAttribute(): string    { return convert_from_base($this->nickname); }
    public function getDescriptionDecodedAttribute(): string { return convert_from_base($this->description); }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function prices()      { return $this->hasMany(Price::class, 'pnum'); }
    public function lineItems()   { return $this->hasMany(ZBody::class, 'pnum'); }
    public function descript()    { return $this->hasOne(CompDescript::class, 'pnum'); }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForFirm(Builder $q, string $fid): Builder
    {
        return $q->where(function ($s) use ($fid) {
            $s->where('firma', $fid)->orWhere('constanta', '1');
        });
    }

    public function scopeInSection(Builder $q, string $idcaption): Builder
    {
        return $q->where('idcaption', $idcaption);
    }

    // ── Stock toggle (replaces toggle-sklad.php) ──────────────────────────────

    public function toggleSklad(string $idagent): string
    {
        $price = Price::where('cod', $this->cod)->where('idagent', $idagent)->first();
        if (!$price) return '0';

        $new = $price->sklad === '1' ? '0' : '1';
        $price->update(['sklad' => $new]);

        $active = Price::where('cod', $this->cod)->where('sklad', '1')->count();
        $this->update(['sklad' => $active > 0 ? '1' : '0', 'dt' => now()]);

        return $new;
    }
}
