<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\TaxPayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttendanceController extends Controller
{
    // Menampilkan daftar kehadiran
    public function index(Request $request)
    {
        $query = Attendance::with('taxpayer');

        if ($request->has('taxpayer_id')) {
            $query->where('taxpayer_id', $request->taxpayer_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'List of attendances',
            'data' => AttendanceResource::collection($query->paginate(10))
        ], Response::HTTP_OK);
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
    public function show(Attendance $attendance)
    {
        return response()->json([
            'success' => true,
            'message' => 'Attendance details',
            'data' => new AttendanceResource($attendance)
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
