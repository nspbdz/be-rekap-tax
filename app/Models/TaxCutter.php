<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCutter extends Model
{
    use HasFactory;

    protected $fillable = [
        'tku_id',
    ];

    public function taxTransactions()
    {
        return $this->hasMany(TaxTransaction::class, 'tax_cutter_id');
    }

}
