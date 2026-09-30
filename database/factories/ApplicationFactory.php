<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'application_date' => now()->toDateString(),
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => fake()->sentence(),
            'approval_status' => '承認待ち',
        ];
    }
}
