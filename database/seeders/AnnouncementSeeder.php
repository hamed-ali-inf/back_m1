<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Announcement;
use App\Models\User;
use App\Models\Institute;
use App\Models\Department;
use App\Models\Course;
use App\Models\Teacher;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->info('لا توجد مستخدمين. يرجى إنشاء المستخدمين أولاً.');
            return;
        }

        $institutes = Institute::all();
        $departments = Department::all();
        $courses = Course::all();
        $teachers = Teacher::all();

        $targetTypes = array_keys(Announcement::TARGET_TYPES);
        $priorities = array_keys(Announcement::PRIORITIES);
        $statuses = array_keys(Announcement::STATUSES);

        // إعلانات عامة
        Announcement::create([
            'sender_id' => $users->random()->id,
            'title' => 'إعلان هام: بداية العام الدراسي الجديد',
            'content' => 'نعلن لجميع الطلاب عن بداية العام الدراسي الجديد يوم الأحد القادم. يرجى من جميع الطلاب الحضور في الموعد المحدد والالتزام بالزي الرسمي.',
            'target_type' => 'general',
            'target_id' => null,
            'status' => 'published',
            'priority' => 'high',
            'published_at' => now()->subDays(3),
            'is_pinned' => true,
            'views_count' => rand(50, 200)
        ]);

        Announcement::create([
            'sender_id' => $users->random()->id,
            'title' => 'إعلان عاجل: تأجيل الامتحانات',
            'content' => 'نظراً للظروف الاستثنائية، تم تأجيل جميع الامتحانات المقررة لهذا الأسبوع إلى الأسبوع القادم. سيتم الإعلان عن المواعيد الجديدة قريباً.',
            'target_type' => 'general',
            'target_id' => null,
            'status' => 'published',
            'priority' => 'urgent',
            'published_at' => now()->subHours(2),
            'is_pinned' => true,
            'views_count' => rand(100, 300)
        ]);

        // إعلانات حسب المعهد
        if ($institutes->isNotEmpty()) {
            foreach ($institutes->take(3) as $institute) {
                Announcement::create([
                    'sender_id' => $users->random()->id,
                    'title' => "إعلان خاص بمعهد {$institute->name}",
                    'content' => "نعلن لجميع طلاب معهد {$institute->name} عن إقامة ورشة عمل تخصصية يوم الخميس القادم في قاعة المؤتمرات.",
                    'target_type' => 'institute',
                    'target_id' => $institute->id,
                    'status' => 'published',
                    'priority' => 'normal',
                    'published_at' => now()->subDays(1),
                    'views_count' => rand(20, 80)
                ]);
            }
        }

        // إعلانات حسب القسم
        if ($departments->isNotEmpty()) {
            foreach ($departments->take(5) as $department) {
                Announcement::create([
                    'sender_id' => $users->random()->id,
                    'title' => "إعلان لطلاب قسم {$department->name}",
                    'content' => "يرجى من جميع طلاب قسم {$department->name} مراجعة مكتب القسم لاستلام الجداول الدراسية المحدثة.",
                    'target_type' => 'department',
                    'target_id' => $department->id,
                    'status' => 'published',
                    'priority' => 'normal',
                    'published_at' => now()->subHours(6),
                    'views_count' => rand(15, 50)
                ]);
            }
        }

        // إعلانات حسب المادة
        if ($courses->isNotEmpty()) {
            foreach ($courses->take(4) as $course) {
                Announcement::create([
                    'sender_id' => $users->random()->id,
                    'title' => "إعلان خاص بمادة {$course->name}",
                    'content' => "تم تغيير موعد محاضرة مادة {$course->name} من يوم الثلاثاء إلى يوم الأربعاء في نفس التوقيت.",
                    'target_type' => 'course',
                    'target_id' => $course->id,
                    'status' => 'published',
                    'priority' => 'high',
                    'published_at' => now()->subHours(4),
                    'views_count' => rand(10, 30)
                ]);
            }
        }

        // إعلانات حسب المستوى
        for ($level = 1; $level <= 3; $level++) {
            Announcement::create([
                'sender_id' => $users->random()->id,
                'title' => "إعلان لطلاب السنة {$level}",
                'content' => "نعلن لجميع طلاب السنة {$level} عن بدء التسجيل في الأنشطة الطلابية للفصل الدراسي الجديد.",
                'target_type' => 'level',
                'target_id' => $level,
                'status' => 'published',
                'priority' => 'normal',
                'published_at' => now()->subDays(2),
                'views_count' => rand(25, 75)
            ]);
        }

        // إعلانات مسودة
        Announcement::create([
            'sender_id' => $users->random()->id,
            'title' => 'مسودة إعلان: ورشة البحث العلمي',
            'content' => 'سيتم تنظيم ورشة عمل حول البحث العلمي وكتابة الأوراق البحثية...',
            'target_type' => 'general',
            'target_id' => null,
            'status' => 'draft',
            'priority' => 'normal'
        ]);

        // إعلانات مؤرشفة
        Announcement::create([
            'sender_id' => $users->random()->id,
            'title' => 'إعلان منتهي: نتائج الفصل السابق',
            'content' => 'تم الإعلان عن نتائج الفصل الدراسي السابق وهي متاحة الآن على الموقع الإلكتروني.',
            'target_type' => 'general',
            'target_id' => null,
            'status' => 'archived',
            'priority' => 'normal',
            'published_at' => now()->subWeeks(2),
            'views_count' => rand(200, 500)
        ]);

        // إعلانات منتهية الصلاحية
        Announcement::create([
            'sender_id' => $users->random()->id,
            'title' => 'إعلان منتهي الصلاحية: التسجيل في الدورات',
            'content' => 'انتهت فترة التسجيل في الدورات التدريبية المجانية.',
            'target_type' => 'general',
            'target_id' => null,
            'status' => 'expired',
            'priority' => 'normal',
            'published_at' => now()->subWeeks(3),
            'expires_at' => now()->subDays(1),
            'views_count' => rand(80, 150)
        ]);

        // إعلانات مع مرفقات
        Announcement::create([
            'sender_id' => $users->random()->id,
            'title' => 'إعلان مع مرفقات: دليل الطالب',
            'content' => 'تم تحديث دليل الطالب للعام الدراسي الجديد. يمكنكم تحميله من المرفقات أدناه.',
            'target_type' => 'general',
            'target_id' => null,
            'status' => 'published',
            'priority' => 'normal',
            'published_at' => now()->subDays(5),
            'attachments' => [
                'دليل_الطالب_2025.pdf',
                'قوانين_الجامعة.pdf'
            ],
            'views_count' => rand(150, 300)
        ]);

        $this->command->info('تم إنشاء الإعلانات التجريبية بنجاح!');
    }
}
