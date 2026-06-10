<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('event')->index();
            $table->timestamp('occurred_at');
            $table->json('meta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_audits');
    }
};
