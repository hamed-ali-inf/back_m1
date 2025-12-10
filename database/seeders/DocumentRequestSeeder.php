<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentRequest;
use App\Models\Student;
use App\Models\User;

class DocumentRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // التأكد من وجود طلاب ومستخدمين
        $students = Student::all();
        $users = User::all();

        if ($students->isEmpty() || $users->isEmpty()) {
            $this->command->info('لا توجد بيانات طلاب أو مستخدمين. يرجى إنشاء البيانات الأساسية أولاً.');
            return;
        }

        $documentTypes = array_keys(DocumentRequest::DOCUMENT_TYPES);
        $statuses = array_keys(DocumentRequest::STATUSES);

        // إنشاء طلبات وثائق تجريبية
        foreach ($students->take(10) as $student) {
            // طلب معلق
            DocumentRequest::create([
                'student_id' => $student->id,
                'document_type' => $documentTypes[array_rand($documentTypes)],
                'status' => 'pending',
                'notes' => 'طلب وثيقة عاجل للتقديم في منحة دراسية'
            ]);

            // طلب موافق عليه
            DocumentRequest::create([
                'student_id' => $student->id,
                'document_type' => $documentTypes[array_rand($documentTypes)],
                'status' => 'approved',
                'notes' => 'طلب وثيقة للتقديم في وظيفة',
                'approved_at' => now()->subDays(2),
                'approved_by' => $users->random()->id
            ]);

            // طلب مكتمل
            if (rand(0, 1)) {
                DocumentRequest::create([
                    'student_id' => $student->id,
                    'document_type' => $documentTypes[array_rand($documentTypes)],
                    'status' => 'completed',
                    'notes' => 'طلب وثيقة للسفر',
                    'approved_at' => now()->subDays(5),
                    'completed_at' => now()->subDays(1),
                    'approved_by' => $users->random()->id
                ]);
            }

            // طلب مرفوض
            if (rand(0, 2) == 0) {
                DocumentRequest::create([
                    'student_id' => $student->id,
                    'document_type' => $documentTypes[array_rand($documentTypes)],
                    'status' => 'rejected',
                    'notes' => 'طلب وثيقة للتحويل',
                    'rejection_reason' => 'الطالب لم يكمل المتطلبات الأكاديمية المطلوبة'
                ]);
            }
        }

        $this->command->info('تم إنشاء طلبات الوثائق التجريبية بنجاح!');
    }
}
