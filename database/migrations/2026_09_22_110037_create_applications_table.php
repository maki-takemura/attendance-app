<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('attendance_record_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('new_date');
            $table->time('new_clock_in');
            $table->time('new_clock_out');
            $table->text('comment');
            $table->enum('approval_status', ['承認待ち', '承認済み']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
