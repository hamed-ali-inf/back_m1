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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable(); // عنوان المحادثة (للمجموعات)
            
            // نوع المحادثة
            $table->enum('type', [
                'student_teacher',      // طالب ↔ أستاذ (في مادة محددة)
                'student_department',   // طالب ↔ رئيس قسم
                'student_institute',    // طالب ↔ رئيس معهد
                'teacher_teacher',      // أستاذ ↔ أستاذ (داخل نفس القسم)
                'group_class',          // مجموعة (لفصل معين)
                'group_department',     // مجموعة (قسم)
                'group_institute',      // مجموعة (معهد)
                'private'               // محادثة خاصة عامة
            ]);
            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // منشئ المحادثة
            
            // معرف السياق (مادة، قسم، معهد، إلخ)
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('context_type')->nullable(); // نوع السياق (course, department, institute)
            
            $table->boolean('is_group')->default(false); // هل هي محادثة جماعية
            $table->boolean('is_active')->default(true); // هل المحادثة نشطة
            
            $table->timestamp('last_message_at')->nullable(); // آخر رسالة
            $table->unsignedInteger('messages_count')->default(0); // عدد الرسائل
            
            $table->json('settings')->nullable(); // إعدادات المحادثة
            
            $table->timestamps();
            
            // فهارس للبحث السريع
            $table->index(['type', 'is_active']);
            $table->index(['context_type', 'context_id']);
            $table->index(['created_by', 'created_at']);
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
