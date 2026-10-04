<?php

namespace Tests\Feature\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報が_u_iと同じ形式で出力されている(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 1, 9, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2026年10月1日(木)');
        $response->assertSee('09:00');

        Carbon::setTestNow();
    }
}
