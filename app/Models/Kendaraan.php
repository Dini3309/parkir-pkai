<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    protected $table = 'tb_kendaraan';

    protected $primaryKey = 'id_kendaraan';

    public $timestamps = false;

    protected $fillable = [
        'plat_nomer',
        'jenis_kendaraan',
        'warna_kendaraan',
        'pemilik',
        'id_user',
    ];
}
