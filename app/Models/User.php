<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'admin_status' => 'boolean',
    ];

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * 当日の勤怠状況を取得する。
     */
    public function getAttendanceStatusAttribute(): string
    {
        $attendanceRecord = $this->attendanceRecords()
            ->whereDate('date', now()->toDateString())
            ->with('breakRecords')
            ->first();

        if (is_null($attendanceRecord)) {
            return '勤務外';
        }

        if (! is_null($attendanceRecord->clock_out)) {
            return '退勤済';
        }

        $hasActiveBreak = $attendanceRecord->breakRecords
            ->contains(fn ($breakRecord) => is_null($breakRecord->break_out));

        if ($hasActiveBreak) {
            return '休憩中';
        }

        return '出勤中';
    }
}
