<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxDocument extends Model
{
    use HasFactory;

    public function taxTransactions()
    {
        return $this->hasMany(TaxTransaction::class, 'tax_document_id');
    }

}
