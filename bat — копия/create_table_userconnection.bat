@echo off
chcp 65001 >nul
cd /d "%~dp0"
set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"
echo CONNECT %DB% USER SYSDBA PASSWORD temp; > tmp.sql
echo SET SQL DIALECT 3; >> tmp.sql
echo SET NAMES WIN1251; >> tmp.sql
echo CREATE TABLE USERCONNECTION ( ID_CONNECT INTEGER NOT NULL, USER_NAME VARCHAR(50) NOT NULL, CONNECT_TIME TIMESTAMP NOT NULL, IS_ACTIVE SMALLINT NOT NULL ); >> tmp.sql
echo COMMIT; >> tmp.sql
%ISQL% -input tmp.sql
del tmp.sql