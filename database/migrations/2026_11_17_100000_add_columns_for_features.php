<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('designation');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('description');
            $table->date('due_date')->nullable()->after('status');
            $table->string('color', 20)->nullable()->after('due_date');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->date('start_date')->nullable();
            $table->decimal('estimate_hours', 6, 2)->nullable();
            $table->index(['assignee_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assignee_id', 'status']);
            $table->dropIndex(['due_date']);
            $table->dropColumn(['start_date', 'estimate_hours']);
        });
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn(['status', 'due_date', 'color']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_active'));
    }
};
