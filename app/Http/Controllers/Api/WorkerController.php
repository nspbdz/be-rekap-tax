<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxPayer;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class WorkerController extends Controller
{
    public function index(Request $request)
    {
        $per_page = $request->per_page ?? null;

        $query = TaxPayer::with('project')
            ->select('tax_payers.*') // Memilih semua kolom dari attendances
            ->when($request->filled('nik') || $request->filled('project_location'), function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    if ($request->filled('nik')) {
                        $query->where('nik', 'like', '%' . $request->nik . '%');
                    }
                    if ($request->filled('project_id')) {
                        $query->orWhere('project_id', 'like', '%' . $request->project_id . '%');
                    }
                });
            })
            ->groupBy('id') // Hindari duplikasi taxpayer
            ->orderByDesc('created_at'); // Urutkan berdasarkan tanggal terbaru

        return response()->json($query->paginate($request->get('per_page', $per_page)));
    }
}
