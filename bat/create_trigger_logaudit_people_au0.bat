@echo off
chcp 65001 >nul
echo Создание триггера PEOPLE_AU0...
cd /d "%~dp0"

set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"

echo CONNECT %DB% USER SYSDBA PASSWORD temp;> tmp.sql
echo SET SQL DIALECT 3;>> tmp.sql
echo SET NAMES WIN1251;>> tmp.sql
echo SET TERM ^;>> tmp.sql
echo CREATE TRIGGER PEOPLE_AU0 FOR PEOPLE ACTIVE AFTER INSERT OR UPDATE POSITION 0 AS >> tmp.sql
echo declare variable username varchar(50);>> tmp.sql
echo declare variable conn_id varchar(50);>> tmp.sql
echo begin>> tmp.sql
echo   select us.user_name from userconnection us>> tmp.sql
echo   where us.id_connect = (SELECT CURRENT_CONNECTION FROM RDB$DATABASE) into :username;>> tmp.sql
echo   SELECT CAST(CURRENT_CONNECTION AS VARCHAR(50)) FROM RDB$DATABASE INTO conn_id;>> tmp.sql
echo   insert into auditlog (username, tablename, newrecordid, actiondate)>> tmp.sql
echo   values (coalesce(:username, :conn_id), 'people', new.id_pep, CURRENT_TIMESTAMP);>> tmp.sql
echo end>> tmp.sql
echo ^;>> tmp.sql
echo SET TERM ;>> tmp.sql
echo COMMIT;>> tmp.sql

%ISQL% -input tmp.sql 2>nul
del tmp.sql
echo Готово.
pause