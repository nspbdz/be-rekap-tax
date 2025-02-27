<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCutter extends Model
{
    use HasFactory;

    public function taxTransactions()
    {
        return $this->hasMany(TaxTransaction::class, 'tax_cutter_id');
    }

}
