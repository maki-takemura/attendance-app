<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
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
            'date' => fake()->date(),
            'clock_in' => fake()->dateTimeBetween('08:00:00', '10:00:00')->format('H:i:s'),
            'clock_out' => fake()->dateTimeBetween('17:00:00', '20:00:00')->format('H:i:s'),
            'comment' => fake()->text(255),
        ];
    }
}
