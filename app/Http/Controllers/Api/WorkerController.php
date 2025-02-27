<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxPayer;
use App\Models\TaxTransaction;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


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

}
