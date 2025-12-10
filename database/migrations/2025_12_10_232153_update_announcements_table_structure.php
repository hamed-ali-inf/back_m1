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
        Schema::table('announcements', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة إذا لم تكن موجودة
            if (!Schema::hasColumn('announcements', 'status')) {
                $table->enum('status', ['draft', 'published', 'archived', 'expired'])->default('draft')->after('target_id');
            }
            
            if (!Schema::hasColumn('announcements', 'priority')) {
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('status');
            }
            
            if (!Schema::hasColumn('announcements', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('priority');
            }
            
            if (!Schema::hasColumn('announcements', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('published_at');
            }
            
            if (!Schema::hasColumn('announcements', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('expires_at');
            }
            
            if (!Schema::hasColumn('announcements', 'attachments')) {
                $table->json('attachments')->nullable()->after('is_pinned');
            }
            
            if (!Schema::hasColumn('announcements', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('attachments');
            }
            
            // تحديث enum للـ target_type إذا لزم الأمر
            $table->enum('target_type', [
                'general',          // عامة (لجميع الجامعة)
                'institute',        // حسب المعهد
                'department',       // حسب القسم
                'level',           // حسب المستوى
                'group',           // حسب الفوج
                'course',          // حسب المادة
                'teacher'          // حسب الأستاذ
            ])->change();
        });
        
        // إضافة الفهارس إذا لم تكن موجودة
        if (!Schema::hasIndex('announcements', ['target_type', 'target_id'])) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->index(['target_type', 'target_id']);
            });
        }
        
        if (!Schema::hasIndex('announcements', ['status', 'published_at'])) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->index(['status', 'published_at']);
            });
        }
        
        if (!Schema::hasIndex('announcements', ['priority', 'created_at'])) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->index(['priority', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'priority', 'published_at', 'expires_at', 
                'is_pinned', 'attachments', 'views_count'
            ]);
            
            $table->dropIndex(['target_type', 'target_id']);
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['priority', 'created_at']);
        });
    }
};
