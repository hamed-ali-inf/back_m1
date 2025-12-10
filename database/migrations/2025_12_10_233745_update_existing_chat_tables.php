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
        // تحديث جدول chat_messages
        Schema::table('chat_messages', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة إذا لم تكن موجودة
            if (!Schema::hasColumn('chat_messages', 'chat_id')) {
                $table->foreignId('chat_id')->after('id')->constrained()->onDelete('cascade');
            }
            
            // تحديث العمود message إلى content إذا كان موجوداً
            if (Schema::hasColumn('chat_messages', 'message') && !Schema::hasColumn('chat_messages', 'content')) {
                $table->renameColumn('message', 'content');
            }
            
            if (!Schema::hasColumn('chat_messages', 'type')) {
                $table->enum('type', ['text', 'file', 'image', 'voice', 'system'])->default('text')->after('content');
            }
            
            if (!Schema::hasColumn('chat_messages', 'file_name')) {
                $table->string('file_name')->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('chat_messages', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_name');
            }
            
            if (!Schema::hasColumn('chat_messages', 'file_type')) {
                $table->string('file_type')->nullable()->after('file_path');
            }
            
            if (!Schema::hasColumn('chat_messages', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_type');
            }
            
            if (!Schema::hasColumn('chat_messages', 'reply_to_id')) {
                $table->foreignId('reply_to_id')->nullable()->constrained('chat_messages')->onDelete('set null')->after('file_size');
            }
            
            if (!Schema::hasColumn('chat_messages', 'is_edited')) {
                $table->boolean('is_edited')->default(false)->after('reply_to_id');
            }
            
            if (!Schema::hasColumn('chat_messages', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false)->after('is_edited');
            }
            
            if (!Schema::hasColumn('chat_messages', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('is_deleted');
            }
            
            if (!Schema::hasColumn('chat_messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('edited_at');
            }
            
            if (!Schema::hasColumn('chat_messages', 'read_count')) {
                $table->unsignedInteger('read_count')->default(0)->after('deleted_at');
            }
            
            if (!Schema::hasColumn('chat_messages', 'read_by')) {
                $table->json('read_by')->nullable()->after('read_count');
            }
            
            // حذف الأعمدة غير المطلوبة
            if (Schema::hasColumn('chat_messages', 'receiver_id')) {
                $table->dropForeign(['receiver_id']);
                $table->dropColumn('receiver_id');
            }
            
            if (Schema::hasColumn('chat_messages', 'read_status')) {
                $table->dropColumn('read_status');
            }
        });
        
        // إضافة الفهارس
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasIndex('chat_messages', ['chat_id', 'created_at'])) {
                $table->index(['chat_id', 'created_at']);
            }
            
            if (!Schema::hasIndex('chat_messages', ['sender_id', 'created_at'])) {
                $table->index(['sender_id', 'created_at']);
            }
            
            if (!Schema::hasIndex('chat_messages', ['type', 'created_at'])) {
                $table->index(['type', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn([
                'chat_id', 'type', 'file_name', 'file_path', 'file_type', 'file_size',
                'reply_to_id', 'is_edited', 'is_deleted', 'edited_at', 'deleted_at',
                'read_count', 'read_by'
            ]);
            
            $table->dropIndex(['chat_id', 'created_at']);
            $table->dropIndex(['sender_id', 'created_at']);
            $table->dropIndex(['type', 'created_at']);
        });
    }
};
