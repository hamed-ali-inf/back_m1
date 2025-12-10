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
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // دور المشارك في المحادثة
            $table->enum('role', [
                'admin',        // مدير المحادثة
                'moderator',    // مشرف
                'member'        // عضو عادي
            ])->default('member');
            
            $table->timestamp('joined_at')->useCurrent(); // تاريخ الانضمام
            $table->timestamp('last_seen_at')->nullable(); // آخر ظهور
            $table->timestamp('last_read_at')->nullable(); // آخر قراءة
            
            $table->boolean('is_muted')->default(false); // كتم الإشعارات
            $table->boolean('is_pinned')->default(false); // تثبيت المحادثة
            $table->boolean('is_archived')->default(false); // أرشفة المحادثة
            
            $table->unsignedInteger('unread_count')->default(0); // عدد الرسائل غير المقروءة
            
            $table->timestamps();
            
            // فهارس وقيود
            $table->unique(['chat_id', 'user_id']); // مشارك واحد لكل محادثة
            $table->index(['user_id', 'is_archived']);
            $table->index(['chat_id', 'role']);
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
