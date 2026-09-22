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
echo Удаление объектов аудита
echo ============================================
echo.

cd /d "%~dp0"

set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"

echo --- УДАЛЕНИЕ ТРИГГЕРОВ ---
(
echo CONNECT %DB% USER SYSDBA PASSWORD temp;
echo SET SQL DIALECT 3;
echo SET NAMES WIN1251;
echo SET TERM !!;
echo DROP TRIGGER TR_BI_AUDITLOG; !!
echo DROP TRIGGER TR_LOG_TABLES_BI; !!
echo DROP TRIGGER LOGAUDIT_PEOPLE_AI; !!
echo DROP TRIGGER PEOPLE_AU0; !!
echo DROP TRIGGER LOG_PEOPLE_AI; !!
echo DROP TRIGGER LOG_PEOPLE_AU; !!
echo DROP TRIGGER LOG_SS_ACCESSUSER_AI; !!
echo DROP TRIGGER LOG_SS_ACCESSUSER_AU; !!
echo DROP TRIGGER LOG_SS_ACCESSUSER_AD; !!
echo DROP TRIGGER SS_ACCESSUSER_AUDIT_AI; !!
echo DROP TRIGGER SS_ACCESSUSER_AUDIT_AD; !!
echo SET TERM ;;
echo COMMIT;
) > uninstall.sql

%ISQL% -input uninstall.sql 2>nul
del uninstall.sql
echo Триггеры удалены.
echo.

echo --- УДАЛЕНИЕ ТАБЛИЦ ---
(
echo CONNECT %DB% USER SYSDBA PASSWORD temp;
echo SET SQL DIALECT 3;
echo SET NAMES WIN1251;
echo DROP TABLE LOG_BLOB_FIELDS;
echo DROP TABLE LOG_FIELDS;
echo DROP TABLE AUDITLOG;
echo DROP TABLE USERCONNECTION;
echo DROP TABLE LOG_KEYS;
echo DROP TABLE LOG_TABLES;
echo COMMIT;
) > uninstall.sql

%ISQL% -input uninstall.sql 2>nul
del uninstall.sql
echo Таблицы удалены.
echo.

echo --- УДАЛЕНИЕ ГЕНЕРАТОРОВ ---
(
echo CONNECT %DB% USER SYSDBA PASSWORD temp;
echo SET SQL DIALECT 3;
echo SET NAMES WIN1251;
echo DROP GENERATOR LOG_TABLES_GEN;
echo DROP GENERATOR GEN_AUDITLOG_ID;
echo COMMIT;
) > uninstall.sql

%ISQL% -input uninstall.sql 2>nul
del uninstall.sql
echo Генераторы удалены.
echo.

echo ============================================
echo ВСЕ ОБЪЕКТЫ УДАЛЕНЫ!
echo ============================================
echo.
pause