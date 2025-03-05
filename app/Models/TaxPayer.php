<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxPayer extends Model
{
    use HasFactory;

    protected $fillable = ['nik', 'tku_id', 'name', 'ktp_photo', 'status_ptkp', 'facility', 'project_id'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'taxpayer_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function taxTransactions()
    {
        return $this->hasMany(TaxTransaction::class, 'taxpayer_id');
    }

}
