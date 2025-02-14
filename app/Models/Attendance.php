<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = ['taxpayer_id', 'attendance_date', 'status'];

    public function taxpayer()
    {
        return $this->belongsTo(TaxPayer::class, 'taxpayer_id');
    }

}
