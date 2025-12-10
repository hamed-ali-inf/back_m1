#!/bin/bash

echo "================================================================================"
echo "إعداد وإصلاح نظام المعلمين (Teacher System)"
echo "================================================================================"
echo ""

echo "[1/6] تثبيت Laravel Sanctum..."
composer require laravel/sanctum
if [ $? -ne 0 ]; then
    echo "خطأ في تثبيت Sanctum"
    exit 1
fi

echo ""
echo "[2/6] نشر ملفات Sanctum..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

echo ""
echo "[3/6] تشغيل Migrations..."
php artisan migrate
if [ $? -ne 0 ]; then
    echo "خطأ في Migrations"
    exit 1
fi

echo ""
echo "[4/6] إنشاء Storage Link..."
php artisan storage:link --force

echo ""
echo "[5/6] تنظيف Cache..."
php artisan optimize:clear

echo ""
echo "[6/6] التحقق من Routes..."
php artisan route:list --path=api/teacher

echo ""
echo "================================================================================"
echo "تم الانتهاء بنجاح!"
echo "================================================================================"
echo ""
echo "الخطوات التالية:"
echo "1. تأكد من وجود Laravel Sanctum في User Model"
echo "2. اختبر API باستخدام Postman أو أي أداة مشابهة"
echo "3. تحقق من صلاحيات المجلدات storage/ و bootstrap/cache"
echo ""

