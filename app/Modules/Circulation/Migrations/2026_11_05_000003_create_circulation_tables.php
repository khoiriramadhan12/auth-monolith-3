<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->string('borrow_code')->unique();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->date('borrow_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['borrowed', 'returned', 'overdue'])->default('borrowed');
            $table->decimal('total_fine', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('borrowing_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->constrained('borrowings')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->string('book_title');
            $table->string('book_isbn');
            $table->integer('quantity')->default(1);
            $table->enum('status', ['borrowed', 'returned'])->default('borrowed');
            $table->date('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borrowing_id')->constrained('borrowings')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->integer('days_late')->default(0);
            $table->decimal('fine_amount', 12, 2);
            $table->enum('payment_status', ['unpaid', 'paid', 'waived'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
        Schema::dropIfExists('borrowing_details');
        Schema::dropIfExists('borrowings');
    }
};
