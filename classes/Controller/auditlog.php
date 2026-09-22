<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Auditlog extends Controller_Template {

    public function before()
    {
        parent::before();
        // Проверка авторизации
        $this->user = Auth::instance()->get_user();
        if (!$this->user) {
            HTTP::redirect('auth/login');
        }
    }

    public function action_index($filter = null)
    {
        $auditlog = new Model_AuditLog();
        
        // Проверяем существование таблиц и получаем список отсутствующих
        $tables_check = $this->checkTablesExist();
        
        if (!$tables_check['all_exist']) {
            // Таблицы не существуют - показываем страницу с ошибкой
            $table_error = true;
            $missing_tables = $tables_check['missing_tables'];
            $this->template->content = View::factory('auditlog/error')
                ->bind('table_error', $table_error)
                ->bind('missing_tables', $missing_tables);
            return;
        }
        
        $all_data = $auditlog->getAuditLog();
        
        // Проверяем, что данные получены корректно (только если это не массив, т.е. ошибка)
        if (!is_array($all_data)) {
            // Ошибка при получении данных
            $table_error = true;
            $missing_tables = array('Ошибка получения данных из базы');
            $this->template->content = View::factory('auditlog/error')
                ->bind('table_error', $table_error)
                ->bind('missing_tables', $missing_tables);
            return;
        }
        
        // Получаем детали для каждой записи (только если есть данные)
        if (!empty($all_data)) {
            foreach ($all_data as $key => $record) {
                $details = $auditlog->getAuditLogDetails($record['LOG_TABLES_ID']);
                
                // Проверяем, что детали - массив
                if (is_array($details)) {
                    // Раскодируем детали
                    foreach ($details as &$detail) {
                        foreach ($detail as $k => &$v) {
                            if ($v !== null && !empty($v) && is_string($v)) {
                                $decoded = @iconv('Windows-1251', 'UTF-8//IGNORE', $v);
                                if ($decoded !== false && $decoded != $v) {
                                    $v = $decoded;
                                }
                            }
                        }
                    }
                    $all_data[$key]['details'] = $details;
                } else {
                    $all_data[$key]['details'] = array();
                }
            }
        }
        
        $total_count = count($all_data);
        
        // Получаем лимит (по умолчанию 10)
        $limit = $this->request->query('limit');
        if (!in_array($limit, array(10, 20, 30))) {
            $limit = 10;
        }
        
        // Обрезаем данные
        $audit_data = array_slice($all_data, 0, $limit);
        
        $this->template->content = View::factory('auditlog/view')
            ->bind('audit_data', $audit_data)
            ->bind('total_count', $total_count)
            ->bind('limit', $limit);
    }
    
    /**
     * Проверка существования таблиц аудита
     * @return array ['all_exist' => bool, 'missing_tables' => array]
     */
   /**
 * Проверка существования таблиц аудита
 * @return array ['all_exist' => bool, 'missing_tables' => array]
 */
private function checkTablesExist() {
    try {
        // Проверяем основные таблицы (в UPPER CASE для Firebird)
        $tables = array('LOG_TABLES', 'LOG_FIELDS', 'LOG_KEYS', 'AUDITLOG');
        $db = Database::instance('fb');
        $missing = array();
        
        foreach ($tables as $table) {
            try {
                // Для Firebird используем правильный запрос
                // Вариант 1: Проверка через RDB$RELATIONS
                $sql = "SELECT 1 FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = :table";
                $result = DB::query(Database::SELECT, $sql)
                    ->param(':table', $table) // Firebird хранит в UPPER CASE
                    ->execute($db);
                
                if (count($result) == 0) {
                    // Попробуем найти таблицу в любом регистре
                    $sql2 = "SELECT 1 FROM RDB\$RELATIONS WHERE UPPER(RDB\$RELATION_NAME) = UPPER(:table)";
                    $result2 = DB::query(Database::SELECT, $sql2)
                        ->param(':table', $table)
                        ->execute($db);
                    
                    if (count($result2) == 0) {
                        $missing[] = $table;
                    }
                }
            } catch (Exception $e) {
                // Если ошибка, проверяем другим способом
                try {
                    // Вариант 2: Попытка выполнить простой SELECT
                    $test_sql = "SELECT FIRST 1 1 FROM " . $table;
                    $test_result = DB::query(Database::SELECT, $test_sql)
                        ->execute($db);
                    
                    // Если дошли сюда, таблица существует
                } catch (Exception $e2) {
                    $missing[] = $table . ' (не существует или нет доступа)';
                }
            }
        }
        
        // Также проверим, есть ли данные в таблице LOG_TABLES
        if (empty($missing)) {
            try {
                $check_data = DB::query(Database::SELECT, "SELECT FIRST 1 1 FROM LOG_TABLES")
                    ->execute(Database::instance('fb'));
                
                // Таблица существует, но может быть пустой - это нормально
            } catch (Exception $e) {
                // Если ошибка, значит таблица пустая или нет доступа
                // Это не критично, просто предупреждение
            }
        }
        
        return array(
            'all_exist' => empty($missing),
            'missing_tables' => $missing
        );
    } catch (Exception $e) {
        // Логируем ошибку для отладки
        error_log('Error checking tables: ' . $e->getMessage());
        
        // Если не можем проверить, предполагаем что таблицы есть
        // чтобы не блокировать работу модуля
        return array(
            'all_exist' => true,
            'missing_tables' => array()
        );
    }
}

    public function action_details()
    {
        $this->auto_render = false;
        $log_tables_id = $this->request->param('id');
        
        if (!$log_tables_id) {
            echo json_encode(array('error' => 'Missing log_tables_id'));
            return;
        }
        
        $auditlog = new Model_AuditLog();
        $details = $auditlog->getAuditLogDetails($log_tables_id);
        
        // Проверяем, что детали получены корректно
        if (!is_array($details)) {
            echo json_encode(array('error' => 'Failed to get details'));
            return;
        }
        
        // Раскодируем русские символы из WIN1251 в UTF-8
        foreach ($details as &$record) {
            foreach ($record as $key => &$value) {
                if ($value !== null && !empty($value) && is_string($value)) {
                    $decoded = @iconv('Windows-1251', 'UTF-8//IGNORE', $value);
                    if ($decoded !== false && $decoded != $value) {
                        $value = $decoded;
                    }
                }
            }
        }
        
        echo json_encode($details);
    }
    
    /**
     * Запуск bat-скрипта для создания таблиц
     */
    public function action_run_bat_script()
    {
        $this->auto_render = false;
        
        // Проверяем, что запрос AJAX
        if (!$this->request->is_ajax() || $this->request->method() !== 'POST') {
            echo json_encode(array('success' => false, 'message' => 'Invalid request'));
            return;
        }
        
        $script_path = $this->request->post('script');
        
        // Безопасность: проверяем, что путь соответствует ожидаемому
        if ($script_path !== 'C:/bat/run_all.bat') {
            echo json_encode(array('success' => false, 'message' => 'Invalid script path'));
            return;
        }
        
        try {
            // Проверяем существование файла
            if (!file_exists($script_path)) {
                echo json_encode(array(
                    'success' => false, 
                    'message' => 'Файл не найден: ' . $script_path
                ));
                return;
            }
            
            // Запускаем bat-файл
            // Для Windows:
            $command = 'start /B ' . escapeshellcmd($script_path);
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Скрипт выполнен успешно'
                ));
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Скрипт завершился с ошибкой. Код: ' . $return_var
                ));
            }
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ));
        }
    }

    public function action_export_pdf()
    {
        $auditlog = new Model_AuditLog();
        $audit_data = $auditlog->getAuditLog();
        
        // Проверяем, что данные получены корректно
        if (!is_array($audit_data)) {
            throw new HTTP_Exception_500('Failed to get audit data');
        }
        
        // Получаем детали для каждой записи (только если есть данные)
        if (!empty($audit_data)) {
            foreach ($audit_data as $key => $record) {
                $details = $auditlog->getAuditLogDetails($record['LOG_TABLES_ID']);
                
                if (is_array($details)) {
                    // Раскодируем детали
                    foreach ($details as &$detail) {
                        foreach ($detail as $k => &$v) {
                            if ($v !== null && !empty($v) && is_string($v)) {
                                $decoded = @iconv('Windows-1251', 'UTF-8//IGNORE', $v);
                                if ($decoded !== false && $decoded != $v) {
                                    $v = $decoded;
                                }
                            }
                        }
                    }
                    $audit_data[$key]['details'] = $details;
                } else {
                    $audit_data[$key]['details'] = array();
                }
            }
        }
        
        // Устанавливаем заголовки для скачивания как .html
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.html"');
        
        // Выводим HTML
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Журнал аудита</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
                h2 { text-align: center; }
                .info { margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #000; padding: 6px; text-align: left; vertical-align: top; }
                th { background-color: #f0f0f0; }
                .details-table { width: 100%; border-collapse: collapse; margin: 5px 0; }
                .details-table td, .details-table th { border: 1px solid #ccc; }
                .details-table th { background-color: #e0e0e0; }
                .record-separator { margin-top: 20px; }
            </style>
        </head>
        <body>
            <h2>Журнал аудита</h2>
            <div class="info">
                <strong>Дата выгрузки:</strong> <?php echo date('d.m.Y H:i:s'); ?><br>
                <strong>Всего записей:</strong> <?php echo count($audit_data); ?>
            </div>
            
            <?php foreach ($audit_data as $record): ?>
            <table>
                <thead>
                    <tr style="background-color: #d3d3d3;">
                        <th width="10%">ID лога</th>
                        <th width="15%">Таблица</th>
                        <th width="10%">Операция</th>
                        <th width="20%">Дата и время</th>
                        <th width="15%">ID записи</th>
                        <th width="30%">Пользователь</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($record['LOG_TABLES_ID']); ?></td>
                        <td><?php echo htmlspecialchars($record['TABLE_NAME']); ?></td>
                        <td><?php echo htmlspecialchars($record['OPERATION']); ?></td>
                        <td><?php echo htmlspecialchars($record['DATE_TIME']); ?></td>
                        <td><?php echo htmlspecialchars($record['KEY_VALUE']); ?></td>
                        <td><?php echo htmlspecialchars($record['USERNAME']); ?></td>
                    </tr>
                    
                    <?php if (!empty($record['details'])): ?>
                    <tr>
                        <td colspan="6" style="padding: 10px; background-color: #f9f9f9;">
                            <table class="details-table" style="width: 95%; margin: 0 auto; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th width="30%">Поле</th>
                                        <th width="35%">Старое значение</th>
                                        <th width="35%">Новое значение</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($record['details'] as $field): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($field['FIELD_NAME']); ?></td>
                                        <td><?php echo htmlspecialchars($field['OLD_VALUE']) ?: '&nbsp;'; ?></td>
                                        <td><?php echo htmlspecialchars($field['NEW_VALUE']) ?: '&nbsp;'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
            
            <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                Сгенерировано автоматически
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}