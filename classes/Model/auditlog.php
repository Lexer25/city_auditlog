<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_AuditLog extends Model{
    public function getAuditLog(){
//         $sql = 'select lgt.table_name, lgt.operation, lgt.date_time, lgf.field_name, lgf.old_value, lgf.new_value,
// lgk.key_value, au.username
// from log_tables lgt
// join LOG_FIELDS lgf on lgf.log_tables_id = lgt.id
// join LOG_KEYS lgk on lgk.log_tables_id = lgt.id
// join AUDITLOG au on au.newrecordid = lgk.key_value
// order by lgt.date_time';
$sql = 'select DISTINCT lgt.id as log_tables_id, lgt.table_name, lgt.operation, lgt.date_time, p.surname, --lgf.field_name, lgf.old_value, lgf.new_value,
lgk.key_value, au.username
from log_tables lgt
join LOG_FIELDS lgf on lgf.log_tables_id = lgt.id
join LOG_KEYS lgk on lgk.log_tables_id = lgt.id
join AUDITLOG au on au.newrecordid = lgk.key_value
left join people p on p.id_pep = lgk.key_value
order by lgt.date_time';
            try {
			$query = DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->as_array();
			return $query;
					
		} catch (Exception $e) {
			//echo Debug::vars('64',$e);exit;
			return 3;
		}	
    }

    public function getAuditLogDetails($log_tables_id){
        $sql = 'select lgt.table_name, lgt.operation, lgt.date_time, lgf.field_name, lgf.old_value, lgf.new_value
from log_tables lgt
join LOG_FIELDS lgf on lgf.log_tables_id = lgt.id
where lgt.id = :log_tables_id
order by lgf.field_name';
            try {
			$query = DB::query(Database::SELECT, $sql)
				->param(':log_tables_id', $log_tables_id)
				->execute(Database::instance('fb'))
				->as_array();
			
			// Получаем key_value и username отдельно
			$sql_meta = 'select first 1 lgk.key_value, au.username
from LOG_KEYS lgk
join AUDITLOG au on au.newrecordid = lgk.key_value
where lgk.log_tables_id = :log_tables_id';
			$meta = DB::query(Database::SELECT, $sql_meta)
				->param(':log_tables_id', $log_tables_id)
				->execute(Database::instance('fb'))
				->as_array();
			
			// Добавляем метаданные к каждой записи
			if (!empty($meta)) {
				foreach ($query as &$record) {
					$record['KEY_VALUE'] = $meta[0]['KEY_VALUE'];
					$record['USERNAME'] = $meta[0]['USERNAME'];
				}
			}
			
			return $query;
					
		} catch (Exception $e) {
			//echo Debug::vars('64',$e);exit;
			return 3;
		}	
    }
	public function CheckTables(){

	}
}