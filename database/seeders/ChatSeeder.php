<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Course;
use App\Models\Department;
use App\Models\Institute;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $students = Student::with('user')->get();
        $teachers = Teacher::with('user')->get();
        $courses = Course::all();
        $departments = Department::all();
        $institutes = Institute::all();

        if ($users->isEmpty()) {
            $this->command->info('لا توجد مستخدمين. يرجى إنشاء المستخدمين أولاً.');
            return;
        }

        // 1. محادثات طالب ↔ أستاذ (في مادة محددة)
        if ($students->isNotEmpty() && $teachers->isNotEmpty() && $courses->isNotEmpty()) {
            foreach ($students->take(5) as $student) {
                foreach ($teachers->take(2) as $teacher) {
                    $course = $courses->random();
                    
                    $chat = Chat::create([
                        'type' => 'student_teacher',
                        'created_by' => $student->user_id,
                        'context_id' => $course->id,
                        'context_type' => 'course',
                        'is_group' => false,
                        'is_active' => true
                    ]);

                    // إضافة المشاركين
                    $chat->addParticipant($student->user_id, 'member');
                    $chat->addParticipant($teacher->user_id, 'admin');

                    // إضافة رسائل تجريبية
                    $messages = [
                        ['sender' => $student->user_id, 'content' => 'السلام عليكم دكتور، لدي استفسار حول المحاضرة الأخيرة'],
                        ['sender' => $teacher->user_id, 'content' => 'وعليكم السلام، تفضل بالسؤال'],
                        ['sender' => $student->user_id, 'content' => 'لم أفهم الجزء الخاص بالخوارزميات، هل يمكن توضيحه؟'],
                        ['sender' => $teacher->user_id, 'content' => 'بالطبع، سأرسل لك ملف توضيحي إضافي'],
                        ['sender' => $student->user_id, 'content' => 'شكراً جزيلاً دكتور']
                    ];

                    foreach ($messages as $index => $msg) {
                        ChatMessage::create([
                            'chat_id' => $chat->id,
                            'sender_id' => $msg['sender'],
                            'content' => $msg['content'],
                            'type' => 'text',
                            'created_at' => now()->subMinutes(count($messages) - $index)
                        ]);
                    }

                    $chat->updateLastMessage();
                }
            }
        }

        // 2. محادثات طالب ↔ رئيس قسم
        if ($students->isNotEmpty() && $departments->isNotEmpty()) {
            foreach ($students->take(3) as $student) {
                $department = $departments->random();
                
                // افتراض أن رئيس القسم هو أول مستخدم في النظام
                $departmentHead = $users->first();
                
                $chat = Chat::create([
                    'type' => 'student_department',
                    'created_by' => $student->user_id,
                    'context_id' => $department->id,
                    'context_type' => 'department',
                    'is_group' => false,
                    'is_active' => true
                ]);

                $chat->addParticipant($student->user_id, 'member');
                $chat->addParticipant($departmentHead->id, 'admin');

                // رسائل تجريبية
                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'sender_id' => $student->user_id,
                    'content' => 'السلام عليكم، أريد الاستفسار عن إجراءات التحويل بين الأقسام',
                    'type' => 'text',
                    'created_at' => now()->subHours(2)
                ]);

                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'sender_id' => $departmentHead->id,
                    'content' => 'وعليكم السلام، يرجى مراجعة مكتب شؤون الطلاب مع الأوراق المطلوبة',
                    'type' => 'text',
                    'created_at' => now()->subHours(1)
                ]);

                $chat->updateLastMessage();
            }
        }

        // 3. محادثات طالب ↔ رئيس معهد
        if ($students->isNotEmpty() && $institutes->isNotEmpty()) {
            foreach ($students->take(2) as $student) {
                $institute = $institutes->random();
                $instituteHead = $users->skip(1)->first(); // افتراض أن رئيس المعهد هو المستخدم الثاني
                
                $chat = Chat::create([
                    'type' => 'student_institute',
                    'created_by' => $student->user_id,
                    'context_id' => $institute->id,
                    'context_type' => 'institute',
                    'is_group' => false,
                    'is_active' => true
                ]);

                $chat->addParticipant($student->user_id, 'member');
                $chat->addParticipant($instituteHead->id, 'admin');

                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'sender_id' => $student->user_id,
                    'content' => 'السلام عليكم، لدي اقتراح لتطوير المناهج في المعهد',
                    'type' => 'text',
                    'created_at' => now()->subDays(1)
                ]);

                $chat->updateLastMessage();
            }
        }

        // 4. محادثات أستاذ ↔ أستاذ (داخل نفس القسم)
        if ($teachers->count() >= 2 && $departments->isNotEmpty()) {
            $department = $departments->first();
            $teacher1 = $teachers->first();
            $teacher2 = $teachers->skip(1)->first();
            
            $chat = Chat::create([
                'type' => 'teacher_teacher',
                'created_by' => $teacher1->user_id,
                'context_id' => $department->id,
                'context_type' => 'department',
                'is_group' => false,
                'is_active' => true
            ]);

            $chat->addParticipant($teacher1->user_id, 'member');
            $chat->addParticipant($teacher2->user_id, 'member');

            // رسائل تجريبية
            $messages = [
                ['sender' => $teacher1->user_id, 'content' => 'مرحباً، هل يمكننا تنسيق مواعيد الامتحانات؟'],
                ['sender' => $teacher2->user_id, 'content' => 'أهلاً، بالطبع. ما هي المواعيد المقترحة؟'],
                ['sender' => $teacher1->user_id, 'content' => 'أقترح الأسبوع القادم، يومي الثلاثاء والخميس']
            ];

            foreach ($messages as $index => $msg) {
                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'sender_id' => $msg['sender'],
                    'content' => $msg['content'],
                    'type' => 'text',
                    'created_at' => now()->subHours(3 - $index)
                ]);
            }

            $chat->updateLastMessage();
        }

        // 5. مجموعات (لفصل معين، قسم، معهد)
        if ($departments->isNotEmpty()) {
            $department = $departments->first();
            
            // مجموعة قسم
            $groupChat = Chat::create([
                'title' => "مجموعة قسم {$department->name}",
                'type' => 'group_department',
                'created_by' => $users->first()->id,
                'context_id' => $department->id,
                'context_type' => 'department',
                'is_group' => true,
                'is_active' => true
            ]);

            // إضافة مشاركين للمجموعة
            $groupChat->addParticipant($users->first()->id, 'admin');
            foreach ($users->take(5)->skip(1) as $user) {
                $groupChat->addParticipant($user->id, 'member');
            }

            // رسائل المجموعة
            ChatMessage::createSystemMessage($groupChat->id, 'تم إنشاء المجموعة');
            
            ChatMessage::create([
                'chat_id' => $groupChat->id,
                'sender_id' => $users->first()->id,
                'content' => 'مرحباً بكم في مجموعة القسم. هنا يمكننا مناقشة الأمور الأكاديمية والإدارية.',
                'type' => 'text',
                'created_at' => now()->subHours(6)
            ]);

            ChatMessage::create([
                'chat_id' => $groupChat->id,
                'sender_id' => $users->skip(1)->first()->id,
                'content' => 'شكراً لإنشاء هذه المجموعة، ستكون مفيدة جداً للتواصل',
                'type' => 'text',
                'created_at' => now()->subHours(5)
            ]);

            $groupChat->updateLastMessage();
        }

        // 6. مجموعة معهد
        if ($institutes->isNotEmpty()) {
            $institute = $institutes->first();
            
            $instituteGroup = Chat::create([
                'title' => "مجموعة معهد {$institute->name}",
                'type' => 'group_institute',
                'created_by' => $users->first()->id,
                'context_id' => $institute->id,
                'context_type' => 'institute',
                'is_group' => true,
                'is_active' => true
            ]);

            // إضافة مشاركين
            $instituteGroup->addParticipant($users->first()->id, 'admin');
            foreach ($users->take(8)->skip(1) as $user) {
                $instituteGroup->addParticipant($user->id, 'member');
            }

            ChatMessage::createSystemMessage($instituteGroup->id, 'تم إنشاء مجموعة المعهد');
            
            ChatMessage::create([
                'chat_id' => $instituteGroup->id,
                'sender_id' => $users->first()->id,
                'content' => 'أهلاً وسهلاً بجميع أعضاء المعهد في هذه المجموعة',
                'type' => 'text',
                'created_at' => now()->subDays(2)
            ]);

            $instituteGroup->updateLastMessage();
        }

        // 7. محادثة خاصة عامة
        if ($users->count() >= 2) {
            $user1 = $users->first();
            $user2 = $users->skip(1)->first();
            
            $privateChat = Chat::create([
                'type' => 'private',
                'created_by' => $user1->id,
                'is_group' => false,
                'is_active' => true
            ]);

            $privateChat->addParticipant($user1->id, 'member');
            $privateChat->addParticipant($user2->id, 'member');

            ChatMessage::create([
                'chat_id' => $privateChat->id,
                'sender_id' => $user1->id,
                'content' => 'مرحباً، كيف حالك؟',
                'type' => 'text',
                'created_at' => now()->subMinutes(30)
            ]);

            ChatMessage::create([
                'chat_id' => $privateChat->id,
                'sender_id' => $user2->id,
                'content' => 'أهلاً، الحمد لله بخير. وأنت كيف حالك؟',
                'type' => 'text',
                'created_at' => now()->subMinutes(25)
            ]);

            $privateChat->updateLastMessage();
        }

        // تحديث إحصائيات المشاركين
        ChatParticipant::all()->each(function ($participant) {
            $unreadCount = $participant->chat->messages()
                                          ->where('sender_id', '!=', $participant->user_id)
                                          ->where('created_at', '>', $participant->last_read_at ?? now()->subDays(7))
                                          ->count();
            
            $participant->update([
                'unread_count' => $unreadCount,
                'last_seen_at' => now()->subMinutes(rand(1, 60))
            ]);
        });

        $this->command->info('تم إنشاء المحادثات والرسائل التجريبية بنجاح!');
    }
}
