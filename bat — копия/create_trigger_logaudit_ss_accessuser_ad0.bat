@echo off
chcp 65001 >nul
cd /d "%~dp0"
set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"
echo CONNECT %DB% USER SYSDBA PASSWORD temp; > tmp.sql
echo CREATE TRIGGER SS_ACCESSUSER_AUDIT_AD FOR SS_ACCESSUSER ACTIVE AFTER DELETE POSITION 0 AS BEGIN INSERT INTO AUDITLOG (USERNAME, TABLENAME, NEWRECORDID, ACTIONDATE) VALUES (USER, 'SS_ACCESSUSER', NULL, CURRENT_TIMESTAMP); END; >> tmp.sql
echo COMMIT; >> tmp.sql
%ISQL% -input tmp.sql
del tmp.sql