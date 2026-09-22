@echo off
chcp 65001 >nul
cd /d "%~dp0"
set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"
echo CONNECT %DB% USER SYSDBA PASSWORD temp; > tmp.sql
echo CREATE TRIGGER LOGAUDIT_PEOPLE_AI FOR PEOPLE ACTIVE AFTER INSERT OR UPDATE POSITION 0 AS BEGIN INSERT INTO AUDITLOG (USERNAME, TABLENAME, NEWRECORDID, ACTIONDATE) VALUES (USER, 'PEOPLE', NEW.ID_PEP, CURRENT_TIMESTAMP); END; >> tmp.sql
echo COMMIT; >> tmp.sql
%ISQL% -input tmp.sql
del tmp.sql