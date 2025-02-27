<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'document_date',
    ];


    public function taxTransactions()
    {
        return $this->hasMany(TaxTransaction::class, 'tax_document_id');
    }

}
