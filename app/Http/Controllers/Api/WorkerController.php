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
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class WorkerController extends Controller
{

    public function checkNikExists(Request $request)
    {
        try {
            $request->validate([
                'nik' => 'required|string',
            ]);

            $nikExists = TaxPayer::where('nik', $request->nik)->exists();

            if ($nikExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'NIK sudah terdaftar',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'NIK tersedia',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan, silakan coba lagi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
{
    $per_page = $request->per_page ?? null;

    $query = TaxTransaction::with('taxpayer.project', 'project') // Memanggil taxpayer dan project
        ->select('tax_transactions.*')
        ->when($request->filled('nik'), function ($q) use ($request) {
            $q->whereHas('taxpayer', function ($query) use ($request) {
                $query->where('nik', 'like', '%' . $request->nik . '%');
            });
        })
        ->when($request->filled('project_id'), function ($q) use ($request) {
            $q->whereHas('taxpayer.project', function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->project_id . '%');
            });
        })
        ->groupBy('id')
        ->orderByDesc('created_at');

    return response()->json($query->paginate($request->get('per_page', $per_page)));
}


    // public function index(Request $request)
    // {
    //     $per_page = $request->per_page ?? null;

    //     $query = TaxPayer::with('project')
    //         ->select('tax_payers.*')
    //         ->when($request->filled('nik'), function ($q) use ($request) {
    //             $q->where('nik', 'like', '%' . $request->nik . '%');
    //         })
    //         ->when($request->filled('project_id'), function ($q) use ($request) {
    //             $q->where('project_id', 'like', '%' . $request->project_id . '%');
    //         })
    //         ->groupBy('id')
    //         ->orderByDesc('created_at');

    //     return response()->json($query->paginate($request->get('per_page', $per_page)));
    // }


    // Menampilkan detail kehadiran
    public function detail(Request $request)
{
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
        ])->where("id", $request->id)
        ->first();

        //  if ($data && $data->taxpayer && $data->taxpayer->ktp_photo) {
        //     $data->taxpayer->ktp_photo_urls = "data:image/jpeg;base64," . $data->taxpayer->ktp_photo;
        // }

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
       'nik' => 'required|string|max:16',
        'tku_id' => 'required|string|max:105',
        'name' => 'required|string|max:100',
        'ktp_photo' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Maks 2MB
        'status_ptkp' => 'required|string|max:10',
        'facility' => 'required|string|max:50',
        'project_id' => 'required|integer',
        'tax_period' => 'required|integer',
        'tax_year' => 'required|integer|min:1900|max:' . date('Y'),
        'tax_object_code' => 'required|string|max:20',
        'income' => 'required|numeric',
        'deemed' => 'required|numeric',
        'rate' => 'required|numeric',
        'document_type' => 'required|string|max:50',
        'document_number' => 'required|string|max:50|unique:tax_documents,document_number',
        'document_date' => 'nullable|date',
        'tax_cutter_id' => 'required|string|max:105',
        'deduction_date' => 'required|date',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        DB::beginTransaction();

        // Simpan KTP Foto sebagai Base64
        // $ktpPhoto = base64_encode(file_get_contents($request->file('ktp_photo')->getRealPath()));

        $file = $request->file('ktp_photo');
        $mimeType = $file->getMimeType();
        $ktpPhoto = "data:$mimeType;base64," . base64_encode(file_get_contents($file->getRealPath()));
       
        // Buat TaxPayer
        $taxPayer = TaxPayer::create([
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
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function update(Request $request)
{
    $validator = Validator::make($request->all(), [
        'nik' => 'required|string|max:16',
        'tku_id' => 'required|string|max:105',
        'name' => 'required|string|max:100',
        'status_ptkp' => 'required|string|max:10',
        'facility' => 'required|string|max:50',
        'project_id' => 'required|integer',
        'tax_period' => 'required|integer',
        'tax_year' => 'required|integer|min:1900|max:' . date('Y'),
        'tax_object_code' => 'required|string|max:20',
        'income' => 'required|numeric',
        'deemed' => 'required|numeric',
        'rate' => 'required|numeric',
        'document_type' => 'required|string|max:50',
        'document_number' => 'required|string|max:50',
        'document_date' => 'nullable|date',
        'tax_cutter_id' => 'required|string|max:105',
        'deduction_date' => 'required|date',
        'ktp_photo' => 'nullable|file|mimes:jpg,png,jpeg|max:2048', // File KTP (Opsional)
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $taxPayer = TaxPayer::where('nik', $request->nik)->first();

    if (!$taxPayer) {
        return response()->json(['error' => 'Data tidak ditemukan'], 404);
    }

    // Jika ada file baru diunggah, maka update
    if ($request->hasFile('file')) {
        // Hapus file lama jika ada
        if ($taxPayer->ktp_photo) {
            Storage::disk('public')->delete($taxPayer->ktp_photo);
        }
        // Simpan file baru
        // $ktpPath = $request->file('file')->store('file', 'public');
        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $ktpPath = "data:$mimeType;base64," . base64_encode(file_get_contents($file->getRealPath()));
        
    } else {
        // Gunakan file lama jika tidak ada file baru
        $ktpPath = $taxPayer->ktp_photo;
    }

    // Update Data TaxPayer
    $taxPayer->update([
        'nik' => $request->nik,
        'tku_id' => $request->tku_id,
        'name' => $request->name,
        'ktp_photo' => $ktpPath,
        'status_ptkp' => $request->status_ptkp,
        'facility' => $request->facility,
        'project_id' => $request->project_id,
    ]);

    // Update atau Buat TaxCutter
    $taxCutter = TaxCutter::updateOrCreate(
        ['tku_id' => $request->tax_cutter_id],
        ['tku_id' => $request->tax_cutter_id]
    );

    // Update atau Buat TaxDocument
    $document = TaxDocument::updateOrCreate(
        ['document_number' => $request->document_number],
        [
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'document_date' => $request->document_date,
        ]
    );

    // Update atau Buat TaxTransaction
    TaxTransaction::updateOrCreate(
        ['taxpayer_id' => $taxPayer->id],
        [
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
        ]
    );

    return response()->json(['message' => 'Worker updated successfully', 'ktp_photo' => asset('storage/' . $ktpPath)]);
}

    public function destroy($id)
    {
        $data = TaxTransaction::find($id);
        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully'
        ]);
    }


}
