<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'approval_status' => $this->approval_status,
            'date' => $this->attendanceRecord->date->format('Y-m-d'),
            'comment' => $this->comment,
            'application_date' => $this->application_date->format('Y-m-d'),
        ];
    }
}
