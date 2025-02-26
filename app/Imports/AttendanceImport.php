<?php
namespace App\Imports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class AttendanceImport implements ToCollection
{
    public $importedData = []; // Tambahkan variabel ini

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
        'total_rows' => $rows->count() - 1, // Mengabaikan header
    ]);

    $parsedData = [];
    $dataToInsert = [];

    foreach ($rows as $index => $row) {
        if ($index === 0) continue; // Lewati header

        $taxpayerId = $row[0]; // Ambil taxpayer_id dari file Excel
        $attendanceDates = explode(',', $row[2]); // Kolom Attendance Date
        $statuses = explode(',', $row[3]); // Kolom Status
        $parsedData[] = [
            'taxpayer_id' => $taxpayerId,
            'attendance_dates' => $attendanceDates,
            'statuses' => $statuses,
        ];

        foreach ($attendanceDates as $key => $day) {
            if (isset($statuses[$key])) {
                $dataToInsert[] = [
                    'taxpayer_id' => $taxpayerId,
                    'project_id' => $this->projectId,
                    'attendance_date' => Carbon::create($this->year, $this->month, (int)$day)->format('Y-m-d'),
                    'status' => $statuses[$key],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    $this->importedData = $parsedData; // Simpan hasil parsing

    \Log::info('Parsed Data', ['data' => $parsedData]);

    Attendance::insert($dataToInsert);

    \Log::info('Data successfully inserted into attendances table', ['total_inserted' => count($dataToInsert)]);
}

}
