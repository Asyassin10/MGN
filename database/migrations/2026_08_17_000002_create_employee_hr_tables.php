<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('telephone');
            $table->string('address')->nullable()->after('email');
            $table->decimal('salary', 12, 2)->default(0)->after('address');
            $table->unsignedTinyInteger('salary_payment_day')->default(1)->after('salary');
            $table->date('hire_date')->nullable()->after('salary_payment_day');
            $table->json('work_days')->nullable()->after('hire_date');
            $table->string('status')->default('active')->after('work_days');
            $table->text('notes')->nullable()->after('status');
        });

        Schema::create('employee_work_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('status')->default('worked');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
        });

        Schema::create('employee_absences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('absence_date');
            $table->string('status')->default('unjustified');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'absence_date']);
        });

        Schema::create('employee_salary_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7);
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('paid');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_payments');
        Schema::dropIfExists('employee_absences');
        Schema::dropIfExists('employee_work_days');
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn(['email', 'address', 'salary', 'salary_payment_day', 'hire_date', 'work_days', 'status', 'notes']));
    }
};
