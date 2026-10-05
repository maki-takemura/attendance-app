<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer',
            'date' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|date_format:Y-m',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.integer' => 'ユーザーIDは整数で指定してください。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'month.date_format' => '年月は YYYY-MM 形式で指定してください。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'per_page.integer' => '1ページあたりの件数は整数で指定してください。',
            'per_page.max' => '1ページあたりの件数は100以下で指定してください。',
        ];
    }
}
