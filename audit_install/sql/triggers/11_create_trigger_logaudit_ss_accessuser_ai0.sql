CREATE TRIGGER SS_ACCESSUSER_AUDIT_AI FOR SS_ACCESSUSER >> trigger.sql
ACTIVE AFTER INSERT POSITION 0 >> trigger.sql
AS >> trigger.sql
declare variable username varchar(50); >> trigger.sql
declare variable conn_id varchar(50); >> trigger.sql
BEGIN >> trigger.sql
  select first 1 us.user_name from userconnection us where us.id_connect = (SELECT FIRST 1 CURRENT_CONNECTION FROM RDB$DATABASE) into :username; >> trigger.sql
  select FIRST 1 CAST(CURRENT_CONNECTION AS VARCHAR(50)) FROM RDB$DATABASE INTO conn_id; >> trigger.sql
  insert into auditlog (username, tablename, newrecordid, actiondate) values (coalesce(:username, :conn_id), 'ss_accessuser', new.id_accessuser, CURRENT_TIMESTAMP); >> trigger.sql
END !!
SET TERM ; >> trigger.sql

