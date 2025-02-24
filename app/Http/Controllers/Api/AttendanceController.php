<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\TaxPayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AttendanceImport;

class AttendanceController extends Controller
{

    public function storeByExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'project_id' => 'required|integer',
        ]);

       // Ambil nilai dari request
    $year = $request->input('year');
    $month = $request->input('month');
    $projectId = $request->input('project_id');

    // Buat instance dengan parameter yang diperlukan
    $import = new AttendanceImport($year, $month, $projectId);
    Excel::import($import, $request->file('file'));

    // Mengembalikan data hasil parsing ke Postman
    return response()->json([
        'message' => 'File processed successfully',
        'imported_data' => $import->importedData
    ], 200);
    
    //    $data= Excel::import(new AttendanceImport($request->year, $request->month, $request->project_id), $request->file('file'));
    //    return response()->json($data);
    
    //     return response()->json(['message' => 'Attendance saved successfully'], 201);
    }

    // Menampilkan daftar kehadiran
    // public function index(Request $request)
    // {
    //     $query = Attendance::with(['taxpayer', 'project'])
    //     ->selectRaw('taxpayer_id, project_id, MIN(id) as id')
    //     ->groupBy('taxpayer_id', 'project_id');



    //     if ($request->has('taxpayer_id')) {
    //         $query->where('taxpayer_id', $request->taxpayer_id);
    //     }

    //     // $attendances = $query->get()->map(function ($attendance) {
    //     //     return Attendance::with('taxpayer')->find($attendance->id);
    //     // });

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'List of attendances',
    //         // 'data' => AttendanceResource::collection($query->paginate(10)),
    //         'data' => $query->get()
    //         // 'data' => AttendanceResource::collection($query),
            
    //     ], Response::HTTP_OK);
    // }

    public function index(Request $request)
    {
        $per_page = $request->per_page ?? null;

        $query = Attendance::with(['taxpayer', 'project'])
            ->select('attendances.*') // Memilih semua kolom dari attendances
            ->whereHas('taxpayer', function ($q) use ($request) {
                if ($request->filled('nik')) {
                    $q->where('nik', 'like', '%' . $request->nik . '%');
                }
            })
            ->whereHas('project', function ($q) use ($request) {
                if ($request->filled('project_name')) {
                    $q->where('project_name', 'like', '%' . $request->project_name . '%');
                }
            })
            ->groupBy('taxpayer_id') // Hindari duplikasi taxpayer
            ->orderByDesc('created_at'); // Urutkan berdasarkan tanggal terbaru

        return response()->json($query->paginate($request->get('per_page', $per_page)));
    }


    // Menyimpan data kehadiran baru
    public function store(Request $request)
    {
        $data = $request->validate([
            // 'taxpayer_id' => 'required|exists:tax_payers,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:1,2,3',
        ]);

        $taxpayer = TaxPayer::where('nik', '=' , $request->nik)->first(); // Mencari taxpayer pertama

        if (!$taxpayer) {
            return response()->json([
                'success' => false,
                'message' => 'Taxpayer not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data['taxpayer_id']=$taxpayer->id;

        $attendance = Attendance::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Attendance created successfully',
            'data' => new AttendanceResource($attendance)
        ], Response::HTTP_CREATED);
    }

    // Menampilkan detail kehadiran
    public function show(Request $request)
    {
        $data = Attendance::with('taxpayer', 'project')->find($request->id);
        return response()->json([
            'success' => true,
            'message' => 'Attendance details',
            'data' => $data
        ], Response::HTTP_OK);
    }

    // Memperbarui data kehadiran
    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'taxpayer_id' => 'exists:tax_payers,id',
            'attendance_date' => 'date',
            'status' => 'in:1,2,3',
        ]);
        return $data;
        $attendance->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => new AttendanceResource($attendance)
        ], Response::HTTP_OK);
    }

    // Menghapus data kehadiran
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance deleted successfully'
        ], Response::HTTP_OK);
    }
}
