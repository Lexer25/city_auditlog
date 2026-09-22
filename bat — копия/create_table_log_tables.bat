@echo off
chcp 65001 >nul
cd /d "%~dp0"
set ISQL="C:\Program Files (x86)\Firebird\Firebird_1_5_6\bin\isql.exe"
set DB="localhost:C:\Program Files (x86)\Cardsoft\DuoSE\Access\ShieldPro_rest.GDB"
echo CONNECT %DB% USER SYSDBA PASSWORD temp; > tmp.sql
echo SET SQL DIALECT 3; >> tmp.sql
echo SET NAMES WIN1251; >> tmp.sql
echo CREATE TABLE LOG_TABLES ( ID INTEGER NOT NULL, TABLE_NAME VARCHAR(67) NOT NULL, OPERATION VARCHAR(1) NOT NULL, DATE_TIME TIMESTAMP NOT NULL, USER_NAME VARCHAR(67) NOT NULL ); >> tmp.sql
echo ALTER TABLE LOG_TABLES ADD PRIMARY KEY (ID); >> tmp.sql
echo COMMIT; >> tmp.sql
%ISQL% -input tmp.sql
del tmp.sql