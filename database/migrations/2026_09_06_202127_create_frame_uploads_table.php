<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks what the queue is doing with each file dropped on the frame page.
     *
     * Video is transcoded before it reaches the panel, which takes minutes on
     * this hardware, so the browser needs somewhere to read the progress from.
     */
    public function up(): void
    {
        Schema::create('frame_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('kind', 16);
            $table->string('status', 16)->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('message')->nullable();
            $table->string('stored_path')->nullable();
            $table->string('frame_name')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frame_uploads');
    }
};
