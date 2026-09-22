CREATE TRIGGER PEOPLE_AU0 FOR PEOPLE ACTIVE AFTER INSERT OR UPDATE POSITION 0 AS >> tmp.sql
declare variable username varchar(50);>> tmp.sql
declare variable conn_id varchar(50);>> tmp.sql
begin>> tmp.sql
  select us.user_name from userconnection us>> tmp.sql
  where us.id_connect = (SELECT CURRENT_CONNECTION FROM RDB$DATABASE) into :username;>> tmp.sql
  SELECT CAST(CURRENT_CONNECTION AS VARCHAR(50)) FROM RDB$DATABASE INTO conn_id;>> tmp.sql
  insert into auditlog (username, tablename, newrecordid, actiondate)>> tmp.sql
  values (coalesce(:username, :conn_id), 'people', new.id_pep, CURRENT_TIMESTAMP);>> tmp.sql
end>>