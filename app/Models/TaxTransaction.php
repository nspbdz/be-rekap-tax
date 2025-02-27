<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxTransaction extends Model
{
    use HasFactory;

    protected $table = 'tax_transactions';

    protected $fillable = [
        'taxpayer_id',
        'tax_cutter_id',
        'tax_document_id',
        'project_id',
        'tax_period',
        'tax_year',
        'tax_object_code',
        'income',
        'deemed',
        'rate',
        'deduction_date',
    ];

    /**
     * Relasi ke taxpayer (pembayar pajak)
     */
    public function taxpayer()
    {
        return $this->belongsTo(TaxPayer::class, 'taxpayer_id');
    }

    /**
     * Relasi ke tax cutter (pemotong pajak)
     */
    public function taxCutter()
    {
        return $this->belongsTo(TaxCutter::class, 'tax_cutter_id');
    }

    /**
     * Relasi ke tax document (dokumen pajak, opsional)
     */
    public function taxDocument()
    {
        return $this->belongsTo(TaxDocument::class, 'tax_document_id');
    }

    /**
     * Relasi ke project (proyek, opsional)
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
