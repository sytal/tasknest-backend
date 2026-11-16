<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('type', 50);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['project_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            $table->string('color', 20);
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        Schema::create('label_task', function (Blueprint $table) {
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->primary(['label_id', 'task_id']);
        });

        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_done')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['task_id', 'position']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'subtasks', 'label_task', 'labels', 'activities', 'comments'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
