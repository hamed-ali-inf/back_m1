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
        Schema::table('document_requests', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة إذا لم تكن موجودة
            if (!Schema::hasColumn('document_requests', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('document_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('document_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('document_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('document_requests', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('completed_at');
            }
            
            // تحديث enum للـ document_type إذا لزم الأمر
            $table->enum('document_type', [
                'registration_certificate',    // شهادة التسجيل
                'transcript',                  // بيان الدرجات (كشف النقاط)
                'graduation_certificate',     // شهادة التخرج
                'study_certificate',          // إفادة دراسة
                'other_administrative'        // وثائق إدارية أخرى
            ])->change();
            
            // تحديث enum للـ status إذا لزم الأمر
            $table->enum('status', [
                'pending',      // قيد الانتظار
                'approved',     // موافق عليه
                'rejected',     // مرفوض
                'completed',    // مكتمل (تم تسليمه)
                'cancelled'     // ملغي
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['notes', 'rejection_reason', 'approved_at', 'completed_at']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');
        });
    }
};
