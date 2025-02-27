<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxCutter;
use App\Models\TaxDocument;
use App\Models\TaxPayer;
use App\Models\TaxTransaction;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? null;

        $query = TaxPayer::with('project')
            ->select('tax_payers.*')
            ->when($request->filled('nik'), function ($q) use ($request) {
                $q->where('nik', 'like', '%' . $request->nik . '%');
            })
            ->when($request->filled('project_id'), function ($q) use ($request) {
                $q->where('project_id', 'like', '%' . $request->project_id . '%');
            })
            ->groupBy('id')
            ->orderByDesc('created_at');

        return response()->json($query->paginate($request->get('per_page', $per_page)));
    }


    // Menampilkan detail kehadiran
    public function detail(Request $request)
    {
        // return $request;
        try {
            // Validasi agar id tidak kosong
            $request->validate([
                'id' => 'required|integer|exists:tax_transactions,id'
            ]);

            // Ambil data dengan semua relasi
            $data = TaxTransaction::with([
                'taxpayer', 
                'taxCutter', 
                'taxDocument', 
                'project'
            ])->where("taxpayer_id",  $request->id)
            ->first();
            return response()->json([
                'success' => true,
                'message' => 'Tax Transaction retrieved successfully',
                'data' => $data
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tax Transaction not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Tax Transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        // 'npwp' => 'required|string|max:20',
        // 'nik' => 'required|string|max:16',
        // 'tku_id' => 'required|uuid',
        // 'name' => 'required|string|max:100',
        // 'ktp_photo' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Maks 2MB
        // 'status_ptkp' => 'required|string|max:10',
        // 'facility' => 'required|string|max:50',
        // // 'project_id' => 'required|integer',
        // 'tax_period' => 'required|integer',
        // 'tax_year' => 'required|integer|min:1900|max:' . date('Y'),
        // 'tax_object_code' => 'required|string|max:20',
        // 'income' => 'required|numeric',
        // 'deemed' => 'required|numeric',
        // 'rate' => 'required|numeric',
        // 'document_type' => 'required|string|max:50',
        // 'document_number' => 'required|string|max:50',
        // 'document_date' => 'nullable|date',
        // 'tax_cutter_id' => 'required|uuid',
        // 'deduction_date' => 'required|date',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();

        // Simpan KTP Foto sebagai Base64
        $ktpPhoto = base64_encode(file_get_contents($request->file('ktp_photo')->getRealPath()));

        // Buat TaxPayer
        $taxPayer = TaxPayer::create([
            'npwp' => $request->npwp,
            'nik' => $request->nik,
            'tku_id' => $request->tku_id,
            'name' => $request->name,
            'ktp_photo' => $ktpPhoto, // Simpan Base64
            'status_ptkp' => $request->status_ptkp,
            'facility' => $request->facility,
            'project_id' => $request->project_id,
        ]);

        // Buat TaxCutter
        $taxCutter = TaxCutter::create([
            'tku_id' => $request->tax_cutter_id,
        ]);

        // Buat TaxDocument
        $document = TaxDocument::create([
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'document_date' => $request->document_date,
        ]);

        // Buat TaxTransaction
        $taxTransaction = TaxTransaction::create([
            'taxpayer_id' => $taxPayer->id,
            'tax_cutter_id' => $taxCutter->id,
            'tax_document_id' => $document->id,
            'project_id' => $request->project_id,
            'tax_period' => $request->tax_period,
            'tax_year' => $request->tax_year,
            'tax_object_code' => $request->tax_object_code,
            'income' => $request->income,
            'deemed' => $request->deemed,
            'rate' => $request->rate,
            'deduction_date' => $request->deduction_date,
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $taxTransaction,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function update(Request $request)
    {

        $dataTaxPayer = [
            'npwp' => $request->npwp,
            'nik' => $request->nik,
            'tku_id' => $request->tku_id,
            'name' => $request->name,
            'ktp_photo' => $request->ktp_photo,
            'status_ptkp' => $request->status_ptkp,
            'facility' => $request->facility,
            'project_id' => $request->project_id, // Pastikan ada nilai
        ];
        
        $taxPayer = TaxPayer::create($dataTaxPayer);

        $dataTaxCutter = [
            'tku_id' => $request->tax_cutter_id,
        ];

        $taxCutter = TaxCutter::create($dataTaxCutter);

        $dataTaxDocument = [
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'document_date' => $request->document_date,
        ];

        $document = TaxDocument::create($dataTaxDocument);


        $dataTaxDocument = [
            'taxpayer_id'=> $taxPayer->id,
            'tax_cutter_id'=> $taxCutter->id,
            'tax_document_id'=> $document->id,
            'project_id'=> $request->project_id,
            'tax_period'=> $request->tax_period,
            'tax_year'=> $request->tax_year,
            'tax_object_code'=> $request->tax_object_code,
            'income'=> $request->income,
            'deemed'=> $request->deemed,
            'rate'=> $request->rate,
            'deduction_date'=> $request->deduction_date,
        ];

        // if ($request->hasFile('file')) {
        //     if ($worker->file) {
        //         Storage::disk('public')->delete($worker->file);
        //     }
        //     $data['file'] = $request->file('file')->store('files', 'public');
        // }

        // $worker->update($data);
        return response()->json(['message' => 'Worker updated successfully']);
    }


}
