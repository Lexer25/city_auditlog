@echo off
chcp 65001 >nul
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Требуются права администратора...
    echo Запустите этот файл правой кнопкой - "Запуск от имени администратора"
    pause
    exit /b
)

echo ============================================
echo Установка объектов аудита
echo ============================================
echo.

sc query FirebirdServerDefaultInstance | find "RUNNING" >nul
if %ERRORLEVEL% NEQ 0 (
    echo Служба Firebird не запущена. Запуск...
    net start FirebirdServerDefaultInstance
    timeout /t 3 >nul
    echo.
)

cd /d "%~dp0"

echo --- СОЗДАНИЕ ГЕНЕРАТОРОВ И ТАБЛИЦ ---
call create_generators.bat
call cerate_table_auditlog.bat
call create_table_log_fields.bat
call create_table_log_keys.bat
call create_table_log_tables.bat
call create_table_userconnection.bat
call create_table_blob_fileds.bat

echo.
echo --- СОЗДАНИЕ ТРИГГЕРОВ ---
call create_trigger_BI_AUDITLOG.bat
call create_trigger_log_tables_bi.bat
call create_trigger_logaudit_people_ai.bat
call create_trigger_logaudit_people_au0.bat
call create_trigger_logaudit_people_ad0.bat
call create_trigger_log_people_ai.bat
call create_trigger_log_people_au.bat
call create_trigger_log_ss_accessuser_ai.bat
call create_trigger_log_ss_accessuser_au.bat
call create_trigger_log_ss_accessuser_ad.bat
call create_trigger_logaudit_ss_accessuser_ai0.bat
call create_trigger_logaudit_ss_accessuser_ad0.bat

echo.
echo ============================================
echo ВСЕ ОБЪЕКТЫ СОЗДАНЫ!
echo ============================================
echo.
pause