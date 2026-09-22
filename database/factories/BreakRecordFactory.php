<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakRecord>
 */
class BreakRecordFactory extends Factory
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
            'break_in' => fake()->dateTimeBetween('12:00:00', '12:29:59')->format('H:i:s'),
            'break_out' => fake()->dateTimeBetween('12:30:00', '13:00:00')->format('H:i:s'),
        ];
    }
}
