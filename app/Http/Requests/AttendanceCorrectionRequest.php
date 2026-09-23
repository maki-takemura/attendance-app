<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i', 'after:new_clock_in'],
            'new_break_in.*' => [
                'nullable',
                'required_with:new_break_out.*',
                'date_format:H:i',
                'after:new_clock_in',
                'before:new_clock_out',
            ],
            'new_break_out.*' => [
                'nullable',
                'required_with:new_break_in.*',
                'date_format:H:i',
                'after:new_break_in.*',
                'before:new_clock_out',
            ],
            'comment' => ['required'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.after' => '休憩時間が不適切な値です',
            'new_break_in.*.before' => '休憩時間が不適切な値です',
            'new_break_out.*.after' => '休憩時間が不適切な値です',
            'new_break_out.*.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ];
    }
}
