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
        Schema::create('tracker_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->nullable();
            $table->string('device_id')->nullable()->index();
            $table->string('user')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('accuracy', 10, 3)->nullable();
            $table->decimal('speed', 10, 3)->nullable();
            $table->decimal('bearing', 8, 2)->nullable();
            $table->unsignedTinyInteger('battery')->nullable();
            $table->timestamp('tracked_at')->nullable()->index();
            $table->json('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_messages');
    }
};
