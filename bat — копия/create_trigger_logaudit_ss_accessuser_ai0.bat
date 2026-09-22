@echo off
chcp 65001 >nul
echo Создание триггера SS_ACCESSUSER_AUDIT_AI...
cd /d "%~dp0"

set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"

echo CONNECT %DB% USER SYSDBA PASSWORD temp; > trigger.sql
echo SET SQL DIALECT 3; >> trigger.sql
echo SET NAMES WIN1251; >> trigger.sql
echo SET TERM !!; >> trigger.sql
echo CREATE TRIGGER SS_ACCESSUSER_AUDIT_AI FOR SS_ACCESSUSER >> trigger.sql
echo ACTIVE AFTER INSERT POSITION 0 >> trigger.sql
echo AS >> trigger.sql
echo declare variable username varchar(50); >> trigger.sql
echo declare variable conn_id varchar(50); >> trigger.sql
echo BEGIN >> trigger.sql
echo   select first 1 us.user_name from userconnection us where us.id_connect = (SELECT FIRST 1 CURRENT_CONNECTION FROM RDB$DATABASE) into :username; >> trigger.sql
echo   select FIRST 1 CAST(CURRENT_CONNECTION AS VARCHAR(50)) FROM RDB$DATABASE INTO conn_id; >> trigger.sql
echo   insert into auditlog (username, tablename, newrecordid, actiondate) values (coalesce(:username, :conn_id), 'ss_accessuser', new.id_accessuser, CURRENT_TIMESTAMP); >> trigger.sql
echo END !! >> trigger.sql
echo SET TERM ; >> trigger.sql
echo COMMIT; >> trigger.sql

%ISQL% -input trigger.sql
del trigger.sql
echo Готово.
pause