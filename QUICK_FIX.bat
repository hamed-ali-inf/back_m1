@echo off
chcp 65001 >nul
echo ================================================================================
echo حل مشكلة إصدار PHP
echo ================================================================================
echo.
echo المشكلة: PHP 7.4.33 ^(الحالي^) - Laravel 12 يحتاج PHP 8.2+
echo.
echo الحل: يجب ترقية PHP أولاً
echo.
echo ================================================================================
echo الخيارات:
echo ================================================================================
echo.
echo 1. استخدام Laragon ^(موصى به - الأسهل^)
echo    - حمّل من: https://laragon.org/download/
echo    - اختر: Laragon Full
echo    - ثبّت وأعد تشغيل PowerShell
echo.
echo 2. استخدام XAMPP
echo    - حمّل من: https://www.apachefriends.org/download.html
echo    - اختر: PHP 8.2.x
echo    - حدّث PATH يدوياً
echo.
echo 3. تثبيت PHP يدوياً
echo    - حمّل من: https://windows.php.net/download/
echo    - اختر: PHP 8.2.x Thread Safe ZIP
echo    - حدّث PATH يدوياً
echo.
echo ================================================================================
echo بعد ترقية PHP، شغّل:
echo ================================================================================
echo.
echo   composer self-update
echo   composer update
echo   composer require laravel/sanctum
echo   php artisan migrate
echo   php artisan storage:link
echo.
echo ================================================================================
echo للتفاصيل الكاملة، اقرأ: SOLUTION_PHP_VERSION.txt
echo ================================================================================
echo.
pause

