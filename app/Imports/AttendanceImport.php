<?php
namespace App\Imports;

use App\Models\Attendance;
use App\Models\Taxpayer;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Exception;

class AttendanceImport implements ToCollection
{
    public $importedData = [];

    protected $year;
    protected $month;
    protected $projectId;

    public function __construct($year, $month, $projectId)
    {
        $this->year = $year;
        $this->month = $month;
        $this->projectId = $projectId;
    }

    public function collection(Collection $rows)
    {
        \Log::info('Store Excel API called', [
            'year' => $this->year,
            'month' => $this->month,
            'project_id' => $this->projectId,
            'total_rows' => $rows->count() - 1,
        ]);

        $parsedData = [];
        $dataToInsert = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Lewati header

            $nik = trim($row[1] ?? '');
            if (empty($nik)) {
                \Log::warning("Skipping row $index due to missing NIK", ['row' => $row]);
                continue;
            }

            // Cari taxpayer_id berdasarkan NIK
            $taxpayer = Taxpayer::where('nik', $nik)->first();
            if (!$taxpayer) {
                \Log::warning("Skipping row $index: NIK not found in database", ['nik' => $nik]);
                continue;
            }
            $taxpayerId = $taxpayer->id;

            // Cek apakah taxpayer_id sudah ada di Attendance untuk bulan dan tahun yang sama
            $existingAttendance = Attendance::where('taxpayer_id', $taxpayerId)
                ->whereYear('attendance_date', $this->year)
                ->whereMonth('attendance_date', $this->month)
                ->exists();

            if ($existingAttendance) {
                \Log::error("Skipping import: Attendance for taxpayer already exists", [
                    'taxpayer_id' => $taxpayerId,
                    'year' => $this->year,
                    'month' => $this->month,
                ]);
                throw new Exception("Data absensi untuk taxpayer dengan NIK $nik sudah ada untuk bulan {$this->month} tahun {$this->year}.");
            }

            $attendanceDates = array_filter(array_map('trim', explode(',', $row[2] ?? '')));
            $statuses = array_filter(array_map('trim', explode(',', $row[3] ?? '')));

            if (count($attendanceDates) !== count($statuses)) {
                \Log::warning("Skipping row $index due to mismatched attendance dates and statuses", [
                    'taxpayer_id' => $taxpayerId,
                    'attendance_dates' => $attendanceDates,
                    'statuses' => $statuses,
                ]);
                continue;
            }

            foreach ($attendanceDates as $key => $day) {
                $dataToInsert[] = [
                    'taxpayer_id' => $taxpayerId,
                    'project_id' => $this->projectId,
                    'attendance_date' => Carbon::create($this->year, $this->month, (int)$day)->format('Y-m-d'),
                    'status' => $statuses[$key],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $parsedData[] = [
                'nik' => $nik,
                'taxpayer_id' => $taxpayerId,
                'attendance_dates' => $attendanceDates,
                'statuses' => $statuses,
            ];
        }

        $this->importedData = $parsedData;

        if (!empty($dataToInsert)) {
            Attendance::insert($dataToInsert);
            \Log::info('Data successfully inserted into attendances table', ['total_inserted' => count($dataToInsert)]);
        } else {
            \Log::warning('No valid data to insert.');
        }
    }
}
