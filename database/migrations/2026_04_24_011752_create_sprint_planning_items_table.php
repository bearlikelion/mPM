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
        Schema::create('sprint_planning_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sprint_planning_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('selected_story_points')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['sprint_planning_meeting_id', 'task_id']);
            $table->index(['sprint_planning_meeting_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sprint_planning_items');
    }
};
