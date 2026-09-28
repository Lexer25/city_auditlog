@echo off
chcp 65001 >nul
cd /d "%~dp0"
set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"
echo CONNECT %DB% USER SYSDBA PASSWORD temp; > tmp.sql
echo SET SQL DIALECT 3; >> tmp.sql
echo SET NAMES WIN1251; >> tmp.sql
echo CREATE GENERATOR LOG_TABLES_GEN; >> tmp.sql
echo CREATE GENERATOR GEN_AUDITLOG_ID; >> tmp.sql
echo COMMIT; >> tmp.sql
echo %ISQL% -input tmp.sql
rem del tmp.sql