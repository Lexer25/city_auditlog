@echo off
chcp 65001 >nul
echo Создание триггера LOG_SS_ACCESSUSER_AD...
cd /d "%~dp0"

set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"

echo CONNECT %DB% USER SYSDBA PASSWORD temp;> tmp.sql
echo SET SQL DIALECT 3;>> tmp.sql
echo SET NAMES WIN1251;>> tmp.sql
echo SET TERM ^;>> tmp.sql
echo CREATE TRIGGER LOG_SS_ACCESSUSER_AD FOR SS_ACCESSUSER ACTIVE AFTER DELETE POSITION 32767 AS DECLARE VARIABLE TID INTEGER; BEGIN TID = GEN_ID(LOG_TABLES_GEN,1); INSERT INTO LOG_TABLES (ID, TABLE_NAME, OPERATION, DATE_TIME, USER_NAME) VALUES (:TID, 'SS_ACCESSUSER', 'D', 'NOW', USER); INSERT INTO LOG_KEYS (LOG_TABLES_ID, KEY_FIELD, KEY_VALUE) VALUES (:TID, 'ID_ACCESSUSER', OLD.ID_ACCESSUSER); INSERT INTO LOG_KEYS (LOG_TABLES_ID, KEY_FIELD, KEY_VALUE) VALUES (:TID, 'ID_DB', OLD.ID_DB); END >> tmp.sql
echo ^;>> tmp.sql
echo SET TERM ;>> tmp.sql
echo COMMIT;>> tmp.sql

%ISQL% -input tmp.sql
del tmp.sql
echo Готово.
pause