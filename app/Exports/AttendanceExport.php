<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceExport implements FromCollection, WithHeadings
{
    protected $year;
    protected $month;
    protected $projectId;

    public function __construct($year, $month, $projectId)
    {
        $this->year = $year;
        $this->month = $month;
        $this->projectId = $projectId;
    }

    public function collection()
    {
        return Attendance::whereYear('attendance_date', $this->year)
            ->whereMonth('attendance_date', $this->month)
            ->where('project_id', $this->projectId)
            ->get([
                'taxpayer_id',
                'project_id',
                'attendance_date',
                'status',
                'created_at'
            ]);
    }

    public function headings(): array
    {
        return ["Taxpayer ID", "Project ID", "Attendance Date", "Status", "Created At"];
    }
}
