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
use App\Exports\AttendanceExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use DB;

class AttendanceController extends Controller
{

    public function updateAttendance(Request $request)
    {
        // return $request;

        // Validasi data yang diterima
        // $validated = $request->validate([
        //     'data' => 'required|array',
        //     'data.*.taxpayer_id' => 'required|string',
        //     'data.*.attendance_date' => 'required|date',
        //     'data.*.status' => 'required|string|in:Hadir,Izin',
        // ]);

        try {
            DB::beginTransaction();

            foreach ($request['data'] as $record) {
                Attendance::updateOrCreate(
                    [
                        'taxpayer_id' => $record['taxpayer_id'],
                        'attendance_date' => $record['attendance_date'],
                    ],
                    [
                        'status' => $record['status'],
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => 'Data kehadiran berhasil diperbarui'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui data', 'error' => $e->getMessage()], 500);
        }
    }


    public function getAttendance(Request $request)
    {
        // return $request;
        // return $request->nik;
        // Validasi input
        $request->validate([
            'month' => 'required|digits:2',
            'year' => 'required|digits:4',
            // 'project_id' => 'required|integer',
            // 'id' => 'nullable|string', // Tambahkan validasi untuk NIK (opsional)
        ]);
    
        // Query kehadiran
        $attendances = Attendance::whereYear('attendance_date', $request->year)
            ->whereMonth('attendance_date', $request->month)
            // ->where('project_id', $request->project_id)
            ->when($request->id, function ($query) use ($request) {
                return $query->whereHas('taxpayer', function ($taxpayerQuery) use ($request) {
                    $taxpayerQuery->where('id', $request->id);
                });
            })
            ->with(['taxpayer', 'project'])
            ->get();
    
        if ($attendances->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    
        // Format response
        $formattedData = $attendances->groupBy('taxpayer_id')->map(function ($records) {
            return [
                'id' => $records->first()->taxpayer_id,
                'nik' => $records->first()->taxpayer->nik,
                'nama' => $records->first()->taxpayer->name,
                'kehadiran' => $records->map(function ($record) {
                    return [
                        'tanggal' => $record->attendance_date,
                        'status' => $record->status
                    ];
                })->values()
            ];
        })->values();
    
        return response()->json([
            'success' => true,
            'data' => $formattedData
        ]);
    }
    

    public function exportAttendance(Request $request): BinaryFileResponse
    {

        $projectId = $request->query('pr');
        $attendanceMonth = $request->query('attd');
        [$year, $month] = explode('-', $attendanceMonth);

        $fileName = "attendance_{$year}_{$month}_project_{$projectId}.xlsx";
        
        return Excel::download(new AttendanceExport($year, $month, $projectId), $fileName);
    }


    public function storeByExcel(Request $request)
    {
        // $request->validate([
        //     'file' => 'required|file|mimes:xlsx,csv',
        //     'year' => 'required|integer',
        //     'month' => 'required|integer|min:1|max:12',
        //     'project_id' => 'required|integer',
        // ]);

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
                if ($request->filled('project_id')) {
                    $q->where('id', '=', $request->project_id );
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
        'attendance_date' => 'required|date',
        'status' => 'required|in:1,2,3',
    ]);

    $taxpayer = TaxPayer::where('nik', $request->nik)->first();

    if (!$taxpayer) {
        return response()->json([
            'success' => false,
            'message' => 'Taxpayer not found'
        ], Response::HTTP_NOT_FOUND);
    }

    // Ambil tahun dan bulan dari attendance_date
    $year = date('Y', strtotime($request->attendance_date));
    $month = date('m', strtotime($request->attendance_date));

    // Cek apakah sudah ada entri dengan taxpayer_id, bulan, dan tahun yang sama
    $existingAttendance = Attendance::where('taxpayer_id', $taxpayer->id)
        ->whereYear('attendance_date', $year)
        ->whereMonth('attendance_date', $month)
        ->exists();

        return $existingAttendance;

    if ($existingAttendance) {
        return response()->json([
            'success' => false,
            'message' => 'Attendance for this taxpayer in the same month and year already exists'
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $data['taxpayer_id'] = $taxpayer->id;

    // $attendance = Attendance::create($data);

    return response()->json([
        'success' => true,
        'message' => 'Attendance created successfully',
        // 'data' => new AttendanceResource($attendance)
    ], Response::HTTP_CREATED);
}


    // Menampilkan detail kehadiran
    public function show(Request $request)
    {
        $data = Attendance::with('taxpayer', 'project')->where('taxpayer_id', '=', $request->id)->first();
        return response()->json([
            'success' => true,
            'message' => 'Attendance details',
            'data' => $data
        ], Response::HTTP_OK);
    }

    public function summaryAttendanceByMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|digits:2',
            'year' => 'required|digits:4',
        ]);
        
            $attendances = Attendance::whereYear('attendance_date', $request->year)
            ->whereMonth('attendance_date', $request->month)
            ->when($request->id, function ($query) use ($request) {
                return $query->whereHas('taxpayer', function ($taxpayerQuery) use ($request) {
                    $taxpayerQuery->where('id', $request->id);
                });
            })
            ->where('status', 1) // Ambil hanya yang statusnya 1 (hadir)
            ->count(); // Hitung jumlahnya
        
        // return response()->json(['attendance_count' => $attendances]);
        return response()->json([
            'success' => true,
            'message' => 'Attendance details',
            'data' => $attendances
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
