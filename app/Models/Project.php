<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes; // Tambahkan SoftDeletes
    protected $dates = ['deleted_at']; // Menambahkan kolom deleted_at untuk soft delete


    protected $fillable = ['project_name', 'project_location'];

    /**
     * Relasi ke Attendance.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'project_id');
    }

    public function taxTransactions()
    {
        return $this->hasMany(TaxTransaction::class, 'project_id');
    }

}
