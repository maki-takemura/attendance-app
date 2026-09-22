<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
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
            'user_id' => User::factory(),
            'attendance_record_id' => AttendanceRecord::factory(),
            'new_date' => fake()->date(),
            'new_clock_in' => fake()->dateTimeBetween('08:00:00', '10:00:00')->format('H:i:s'),
            'new_clock_out' => fake()->dateTimeBetween('17:00:00', '20:00:00')->format('H:i:s'),
            'comment' => fake()->text(),
            'approval_status' => '承認待ち',
        ];
    }
}
