<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->integer('max_borrow_days')->default(7);
            $table->integer('max_borrow_books')->default(3);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('member_code')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->date('membership_expiry')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('member_role_pivot', function (Blueprint $table) {
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('member_roles')->onDelete('cascade');
            $table->primary(['member_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_role_pivot');
        Schema::dropIfExists('members');
        Schema::dropIfExists('member_roles');
    }
};
