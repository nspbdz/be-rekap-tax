<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'taxpayer_id' => $this->taxpayer_id,
            'attendance_date' => $this->attendance_date,
            'status' => $this->status,
        ];
    }
}
