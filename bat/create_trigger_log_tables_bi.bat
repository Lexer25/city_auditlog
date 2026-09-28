@echo off
chcp 65001 >nul
echo Создание триггера TR_LOG_TABLES_BI...
cd /d "%~dp0"

set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"

echo CONNECT %DB% USER SYSDBA PASSWORD temp;> tmp.sql
echo SET SQL DIALECT 3;>> tmp.sql
echo SET NAMES WIN1251;>> tmp.sql
echo SET TERM ^;>> tmp.sql
echo CREATE TRIGGER TR_LOG_TABLES_BI FOR LOG_TABLES ACTIVE BEFORE INSERT POSITION 0 AS BEGIN IF (NEW.ID IS NULL) THEN NEW.ID = GEN_ID(LOG_TABLES_GEN, 1); END >> tmp.sql
echo ^;>> tmp.sql
echo SET TERM ;>> tmp.sql
echo COMMIT;>> tmp.sql

%ISQL% -input tmp.sql
del tmp.sql
echo Готово.
pause