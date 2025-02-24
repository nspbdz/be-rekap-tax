<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPayer extends Model
{
    use HasFactory;

    protected $fillable = ['npwp', 'nik', 'tku_id', 'name', 'ktp_photo', 'status_ptkp', 'facility'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'taxpayer_id');
    }

}
