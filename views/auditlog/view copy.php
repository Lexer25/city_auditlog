<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<script type="text/javascript">
    $(function() {        
        $("#tablesorter").tablesorter({ headers: { 3:{sorter: false}}, widgets: ['zebra']});
    });

    function showDetails(log_tables_id) {
        $.ajax({
            url: 'auditlog/details/' + log_tables_id,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    alert('Ошибка: ' + data.error);
                    return;
                }
                
                let html = '<table style="width: 100%; border-collapse: collapse;">';
                html += '<thead><tr style="background: #f0f0f0;">';
                html += '<th style="border: 1px solid #ccc; padding: 8px;">Поле</th>';
                html += '<th style="border: 1px solid #ccc; padding: 8px;">Старое значение</th>';
                html += '<th style="border: 1px solid #ccc; padding: 8px;">Новое значение</th>';
                html += '</tr></thead><tbody>';
                
                if (data.length > 0) {
                    let firstRecord = data[0];
                    html += '<tr><td colspan="3" style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">';
                    html += 'Таблица: ' + (firstRecord.TABLE_NAME || '') + 
                           ' | Операция: ' + (firstRecord.OPERATION || '') + 
                           ' | Дата: ' + (firstRecord.DATE_TIME || '') + 
                           ' | ID: ' + (firstRecord.KEY_VALUE || '') + 
                           ' | Пользователь: ' + (firstRecord.USERNAME || '');
                    html += '</td></tr>';
                    
                    $.each(data, function(index, record) {
                        html += '<tr>';
                        html += '<td style="border: 1px solid #ccc; padding: 8px;">' + (record.FIELD_NAME || '') + '</td>';
                        html += '<td style="border: 1px solid #ccc; padding: 8px;">' + (record.OLD_VALUE || '&nbsp;') + '</td>';
                        html += '<td style="border: 1px solid #ccc; padding: 8px;">' + (record.NEW_VALUE || '&nbsp;') + '</td>';
                        html += '</tr>';
                    });
                } else {
                    html += '<tr><td colspan="3" style="border: 1px solid #ccc; padding: 8px;">Нет данных</td></tr>';
                }
                
                html += '</tbody></table>';
                
                $('#details-modal-content').html(html);
                $('#details-modal').show();
            },
            error: function() {
                alert('Ошибка при загрузке данных');
            }
        });
    }

    function closeDetails() {
        $('#details-modal').hide();
    }
    
    // Функция для экспорта в PDF с сохранением фильтров
    function exportToPDF() {
        var filters = {
            table_filter: $('select[name="table_filter"]').val(),
            operation_filter: $('select[name="operation_filter"]').val(),
            username_filter: $('input[name="username_filter"]').val(),
            key_filter: $('input[name="key_filter"]').val()
        };
        
        var queryString = $.param(filters);
        window.location.href = 'auditlog/export_pdf?' + queryString;
    }
</script>

<div class="onecolumn">
    <div class="header">
        <span><?php echo __('Журнал аудита'); ?> <?php echo __('(всего записей: :count)', array(':count' => isset($total_count) ? $total_count : count($audit_data))); ?></span>
    </div>
    <br class="clear"/>
    <div class="content">
        
        <!-- Строка поиска и фильтров -->
        <div id="search" style="text-align: center; margin: 10px 0 15px;">
            <form action="auditlog" method="get" style="display: inline-block; margin: 0 auto;">
                <select name="table_filter" style="padding: 6px 10px; margin-right: 5px;">
                    <option value=""><?php echo __('Все таблицы'); ?></option>
                    <option value="PEOPLE" <?php echo (isset($_GET['table_filter']) && $_GET['table_filter'] == 'PEOPLE') ? 'selected' : ''; ?>>PEOPLE</option>
                    <option value="GUEST" <?php echo (isset($_GET['table_filter']) && $_GET['table_filter'] == 'GUEST') ? 'selected' : ''; ?>>GUEST</option>
                </select>
                
                <select name="operation_filter" style="padding: 6px 10px; margin-right: 5px;">
                    <option value=""><?php echo __('Все операции'); ?></option>
                    <option value="I" <?php echo (isset($_GET['operation_filter']) && $_GET['operation_filter'] == 'I') ? 'selected' : ''; ?>>INSERT</option>
                    <option value="U" <?php echo (isset($_GET['operation_filter']) && $_GET['operation_filter'] == 'U') ? 'selected' : ''; ?>>UPDATE</option>
                </select>
                
                <input
                    type="text"
                    name="username_filter"
                    placeholder="Пользователь"
                    value="<?php echo isset($_GET['username_filter']) ? HTML::chars($_GET['username_filter']) : ''; ?>"
                    style="padding: 6px 10px; width: 150px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                />
                
                <input
                    type="text"
                    name="key_filter"
                    placeholder="ID записи"
                    value="<?php echo isset($_GET['key_filter']) ? HTML::chars($_GET['key_filter']) : ''; ?>"
                    style="padding: 6px 10px; width: 100px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"
                />
                
                <input type="submit" class="btn" value="Найти"/>
                <button type="button" class="btn" onclick="exportToPDF()">Экспорт в PDF</button>
                <a href="auditlog" class="btn">Очистить</a>
            </form>
        </div>
        
        <?php 
        include Kohana::find_file('views', 'paginatoion_controller_template'); 
        if (count($audit_data) > 0) { 
        ?>
            <form id="form_data" name="form_data" action="" method="post">
                <table class="data tablesorter-blue" width="100%" cellpadding="0" cellspacing="0" id="tablesorter">
                    <thead>
                        <tr>
                            <th>ID лога</th>
                            <th>Таблица</th>
                            <th>Операция</th>
                            <th>Дата и время</th>
                            <th>ID записи</th>
                            <th>Пользователь</th>
                            <th>Детали</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($audit_data as $record) 
                        { 
                        ?>
                        <tr>
                            <td><?php echo isset($record['LOG_TABLES_ID']) ? HTML::chars($record['LOG_TABLES_ID']) : '' ?></td>
                            <td><?php echo isset($record['TABLE_NAME']) ? HTML::chars($record['TABLE_NAME']) : ''; ?></td>
                            <td><?php echo isset($record['OPERATION']) ? HTML::chars($record['OPERATION']) : ''; ?></td>
                            <td><?php echo isset($record['DATE_TIME']) ? HTML::chars($record['DATE_TIME']) : ''; ?></td>
                            <td><?php echo isset($record['KEY_VALUE']) ? HTML::chars($record['KEY_VALUE']) : ''; ?></td>
                            <td><?php echo isset($record['USERNAME']) ? HTML::chars($record['USERNAME']) : ''; ?></td>
                            <td>
                                <?php if (isset($record['LOG_TABLES_ID'])): ?>
                                    <button type="button" class="btn" onclick="showDetails(<?php echo $record['LOG_TABLES_ID']; ?>)">Детали</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </form>
        <?php 
            echo $pagination; 
        } else { 
        ?>
            <div style="margin: 100px 0; text-align: center;">
                Нет данных для отображения<br /><br />
            </div>
        <?php } ?>
    </div>
</div>

<!-- Модальное окно для отображения деталей -->
<div id="details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: relative; width: 80%; max-width: 900px; margin: 50px auto; background: white; padding: 20px; border-radius: 5px; max-height: 80vh; overflow-y: auto;">
        <button onclick="closeDetails()" style="position: absolute; top: 10px; right: 10px; background: #f44336; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Закрыть</button>
        <h3>Детали записи</h3>
        <div id="details-modal-content"></div>
    </div>
</div>

<?php 
echo 'mod version ' . (defined('AUDITLOG_MODULE_VERSION') ? AUDITLOG_MODULE_VERSION : 'unknown');
?>