@echo off
chcp 65001 >nul

:: Проверка прав администратора
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo Требуются права администратора...
    echo Запустите этот файл правой кнопкой - "Запуск от имени администратора"
    pause
    exit /b
)

:: ============================================
:: НАСТРОЙКИ ПОДКЛЮЧЕНИЯ (изменяйте здесь!)
:: ============================================
set ISQL_PATH="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB_HOST=localhost
set DB_PATH="C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"
set DB_USER=SYSDBA
set DB_PASSWORD=temp
:: ============================================

set DB_CONNECTION=%DB_HOST%:%DB_PATH%
set SQL_DIR=sql

echo ============================================
echo Установка объектов аудита
echo ============================================
echo.
echo Подключение к БД: %DB_PATH%
echo Пользователь: %DB_USER%
echo.

:: Проверка службы Firebird
sc query FirebirdServerDefaultInstance | find "RUNNING" >nul
if %ERRORLEVEL% NEQ 0 (
    echo Служба Firebird не запущена. Запуск...
    net start FirebirdServerDefaultInstance
    timeout /t 3 >nul
    echo.
)
echo Служба Firebird запущена, работает.
cd /d "%~dp0"

:: Проверка наличия папки sql
if not exist "%SQL_DIR%" (
    echo 47 ОШИБКА: Папка %SQL_DIR% не найдена!
    pause
    exit /b
)

:: Функция выполнения SQL-файла
call :RunSQL "Таблицы" "%SQL_DIR%\01_generators.sql"
REM call :RunSQL "Таблица AUDITLOG" "%SQL_DIR%\02_table_auditlog.sql"
REM call :RunSQL "Таблица LOG_FIELDS" "%SQL_DIR%\03_table_log_fields.sql"
REM call :RunSQL "Таблица LOG_KEYS" "%SQL_DIR%\04_table_log_keys.sql"
REM call :RunSQL "Таблица LOG_TABLES" "%SQL_DIR%\05_table_log_tables.sql"
REM call :RunSQL "Таблица USERCONNECTION" "%SQL_DIR%\06_table_userconnection.sql"
REM call :RunSQL "Таблица LOG_BLOB_FIELDS" "%SQL_DIR%\07_table_blob_fileds.sql"

REM echo.
REM echo --- СОЗДАНИЕ ТРИГГЕРОВ ---
REM call :RunSQL "Триггер TR_BI_AUDITLOG" "%SQL_DIR%\triggers\01_create_trigger_BI_AUDITLOG.sql"
REM call :RunSQL "Триггер TR_LOG_TABLES_BI" "%SQL_DIR%\triggers\02_create_trigger_log_tables_bi.sql"

REM call :RunSQL "Триггер LOGAUDIT_PEOPLE_AI" "%SQL_DIR%\triggers\03_create_trigger_logaudit_people_ai.sql"

REM call :RunSQL "Триггер PEOPLE_AU0" "%SQL_DIR%\triggers\04_create_trigger_logaudit_people_au0.sql"
REM call :RunSQL "Триггер PEOPLE_AD0" "%SQL_DIR%\triggers\05_create_trigger_logaudit_people_ad0.sql"

REM call :RunSQL "Триггер LOG_PEOPLE_AI" "%SQL_DIR%\triggers\06_create_trigger_log_people_ai.sql"
REM call :RunSQL "Триггер LOG_PEOPLE_AU" "%SQL_DIR%\triggers\07_create_trigger_log_people_au.sql"

REM call :RunSQL "Триггер LOG_SS_ACCESSUSER_AI" "%SQL_DIR%\triggers\08_create_trigger_log_ss_accessuser_ai.sql"
REM call :RunSQL "Триггер LOG_SS_ACCESSUSER_AU" "%SQL_DIR%\triggers\09_create_trigger_log_ss_accessuser_au.sql"
REM call :RunSQL "Триггер LOG_SS_ACCESSUSER_AD" "%SQL_DIR%\triggers\10_create_trigger_log_ss_accessuser_ad.sql"

REM call :RunSQL "Триггер SS_ACCESSUSER_AUDIT_AI" "%SQL_DIR%\triggers\11_create_trigger_logaudit_ss_accessuser_ai0.sql"
REM call :RunSQL "Триггер SS_ACCESSUSER_AUDIT_AD" "%SQL_DIR%\triggers\12_create_trigger_logaudit_ss_accessuser_ad0.sql"

echo.
echo ============================================
echo ВСЕ ОБЪЕКТЫ СОЗДАНЫ!
echo ============================================
echo.
pause
exit /b

:: ============================================
:: ФУНКЦИЯ ВЫПОЛНЕНИЯ SQL-ФАЙЛА
:: ============================================
:RunSQL
set SQL_NAME=%~1
set SQL_FILE=%~2

if not exist "%SQL_FILE%" (
    echo [ОШИБКА] Файл %SQL_FILE% не найден!
    exit /b 1
)

echo Выполняется: %SQL_NAME%...
(
    echo CONNECT %DB_CONNECTION% USER %DB_USER% PASSWORD %DB_PASSWORD%;
    echo SET SQL DIALECT 3;
    echo SET NAMES WIN1251;
    echo SET TERM ^^;
    type "%SQL_FILE%"
    echo ^^;
    echo SET TERM ;
    echo COMMIT;
) > tmp_run.sql

%ISQL_PATH% -input tmp_run.sql 2>nul

echo [115] %ERRORLEVEL%
if %ERRORLEVEL% EQU 0 (
    echo [OK] %SQL_NAME% - успешно
) else (
    echo [ОШИБКА] %SQL_NAME% - ошибка выполнения
    type tmp_run.sql
)
del tmp_run.sql 2>nul
exit /b 0