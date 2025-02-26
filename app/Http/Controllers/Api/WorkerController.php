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

}
