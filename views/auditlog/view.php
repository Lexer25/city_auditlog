<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<script type="text/javascript">
    $(function() {        
        $("#tablesorter").tablesorter({ headers: { 3:{sorter: false}}, widgets: ['zebra']});
    });

    function toggleDetails(rowId) {
        var detailsRow = $('#details-' + rowId);
        if (detailsRow.is(':visible')) {
            detailsRow.hide();
        } else {
            $('.details-row').hide();
            detailsRow.show();
        }
    }
    
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
    
    function changeLimit() {
        var limit = $('#limit_select').val();
        var url = window.location.pathname + '?limit=' + limit;
        window.location.href = url;
    }
    
    // Функция для показа сообщения об ошибке
    function showTableError() {
        <?php if (isset($table_error) && $table_error === true): ?>
        // Показываем модальное окно с ошибкой, если таблицы не найдены
        showCustomError();
        <?php endif; ?>
    }
    
    // Функция для отображения кастомной ошибки
    function showCustomError() {
        // Создаем overlay
        var overlay = $('<div id="error-overlay"></div>').css({
            'position': 'fixed',
            'top': '0',
            'left': '0',
            'width': '100%',
            'height': '100%',
            'background': 'rgba(0,0,0,0.7)',
            'z-index': '9999',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center'
        });
        
        // Создаем окно ошибки
        var errorBox = $('<div id="error-box"></div>').css({
            'background': '#fff',
            'padding': '40px',
            'border-radius': '10px',
            'max-width': '500px',
            'width': '90%',
            'box-shadow': '0 5px 30px rgba(0,0,0,0.3)',
            'text-align': 'center',
            'position': 'relative'
        });
        
        // Добавляем иконку ошибки
        var errorIcon = $('<div></div>').css({
            'font-size': '60px',
            'color': '#f44336',
            'margin-bottom': '20px'
        }).html('❌');
        
        // Заголовок ошибки
        var errorTitle = $('<h2></h2>').css({
            'color': '#d32f2f',
            'margin-bottom': '15px',
            'font-size': '24px'
        }).text('Таблицы не найдены!');
        
        // Текст ошибки
        var errorMessage = $('<p></p>').css({
            'color': '#333',
            'margin-bottom': '20px',
            'font-size': '16px',
            'line-height': '1.6'
        }).html('Не удалось найти необходимые таблицы в базе данных.<br>Пожалуйста, выполните инициализацию системы.');
        
        // Кнопка запуска скрипта
        var runButton = $('<button></button>').css({
            'background': '#4CAF50',
            'color': 'white',
            'border': 'none',
            'padding': '12px 25px',
            'border-radius': '5px',
            'font-size': '16px',
            'cursor': 'pointer',
            'margin': '10px 5px',
            'transition': 'all 0.3s'
        }).text('Запустить run_all.bat')
        .hover(
            function() { $(this).css('background', '#45a049'); },
            function() { $(this).css('background', '#4CAF50'); }
        )
        .click(function() {
            // Запускаем скрипт через AJAX
            $.ajax({
                url: 'auditlog/run_bat_script',
                type: 'POST',
                data: { script: 'C:/bat/run_all.bat' },
                dataType: 'json',
                beforeSend: function() {
                    runButton.text('Выполняется...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('Скрипт успешно выполнен!', 'success');
                        // Перезагружаем страницу через 2 секунды
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showNotification('Ошибка выполнения скрипта: ' + response.message, 'error');
                        runButton.text('Запустить run_all.bat').prop('disabled', false);
                    }
                },
                error: function() {
                    showNotification('Ошибка связи с сервером', 'error');
                    runButton.text('Запустить run_all.bat').prop('disabled', false);
                }
            });
        });
        
        // Кнопка закрытия
        var closeButton = $('<button></button>').css({
            'background': '#f44336',
            'color': 'white',
            'border': 'none',
            'padding': '12px 25px',
            'border-radius': '5px',
            'font-size': '16px',
            'cursor': 'pointer',
            'margin': '10px 5px',
            'transition': 'all 0.3s'
        }).text('Закрыть')
        .hover(
            function() { $(this).css('background', '#d32f2f'); },
            function() { $(this).css('background', '#f44336'); }
        )
        .click(function() {
            overlay.remove();
        });
        
        // Кнопка "Инструкция"
        var helpButton = $('<button></button>').css({
            'background': '#2196F3',
            'color': 'white',
            'border': 'none',
            'padding': '12px 25px',
            'border-radius': '5px',
            'font-size': '16px',
            'cursor': 'pointer',
            'margin': '10px 5px',
            'transition': 'all 0.3s'
        }).text('Инструкция')
        .hover(
            function() { $(this).css('background', '#1976D2'); },
            function() { $(this).css('background', '#2196F3'); }
        )
        .click(function() {
            showNotification('Запустите файл C:/bat/run_all.bat для создания таблиц', 'info');
        });
        
        // Добавляем кнопки в окно
        var buttonContainer = $('<div></div>').css({
            'margin-top': '20px'
        });
        
        buttonContainer.append(runButton);
        buttonContainer.append(closeButton);
        buttonContainer.append(helpButton);
        
        // Собираем все вместе
        errorBox.append(errorIcon);
        errorBox.append(errorTitle);
        errorBox.append(errorMessage);
        errorBox.append(buttonContainer);
        
        overlay.append(errorBox);
        $('body').append(overlay);
        
        // Закрытие по клику вне окна
        overlay.click(function(e) {
            if (e.target === this) {
                // Не закрываем, чтобы пользователь не пропустил важное сообщение
                // Но можно раскомментировать строку ниже, если нужно
                // overlay.remove();
            }
        });
    }
    
    // Функция для уведомлений
    function showNotification(message, type) {
        var notification = $('<div></div>').css({
            'position': 'fixed',
            'bottom': '20px',
            'right': '20px',
            'padding': '15px 25px',
            'border-radius': '5px',
            'color': 'white',
            'z-index': '10000',
            'font-size': '14px',
            'box-shadow': '0 3px 10px rgba(0,0,0,0.2)',
            'animation': 'slideIn 0.5s ease-out'
        });
        
        if (type === 'success') {
            notification.css('background', '#4CAF50');
        } else if (type === 'error') {
            notification.css('background', '#f44336');
        } else {
            notification.css('background', '#2196F3');
        }
        
        notification.text(message);
        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Добавляем CSS анимацию
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            #error-box {
                animation: fadeIn 0.5s ease-out;
            }
            @keyframes fadeIn {
                from {
                    transform: scale(0.8);
                    opacity: 0;
                }
                to {
                    transform: scale(1);
                    opacity: 1;
                }
            }
        `)
        .appendTo('head');
    
    // Вызываем проверку при загрузке страницы
    $(document).ready(function() {
        showTableError();
    });
</script>

<div class="onecolumn">
    <div class="header">
        <span><?php echo __('Журнал аудита'); ?> <?php echo __('(всего записей: :count)', array(':count' => $total_count)); ?></span>
        
        <button type="button" class="btn" onclick="exportToPDF()" style="float: right; margin-left: 10px;">Экспорт в PDF</button>
        <a href="auditlog" class="btn" style="float: right; margin-left: 10px;">Сбросить</a>
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
                
                <select id="limit_select" style="padding: 6px 10px; margin-left: 10px; border: 1px solid #ccc; border-radius: 4px;" onchange="changeLimit()">
                    <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>Показать 10</option>
                    <option value="20" <?php echo ($limit == 20) ? 'selected' : ''; ?>>Показать 20</option>
                    <option value="30" <?php echo ($limit == 30) ? 'selected' : ''; ?>>Показать 30</option>
                </select>
            </form>
        </div>
        
        <?php if (count($audit_data) > 0) { ?>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_data as $record): ?>
                        <tr class="clickable-row" onclick="toggleDetails(<?php echo $record['LOG_TABLES_ID']; ?>)" style="cursor: pointer;">
                            <td><?php echo isset($record['LOG_TABLES_ID']) ? HTML::chars($record['LOG_TABLES_ID']) : '' ?></td>
                            <td><?php echo isset($record['TABLE_NAME']) ? HTML::chars($record['TABLE_NAME']) : ''; ?></td>
                            <td><?php echo isset($record['OPERATION']) ? HTML::chars($record['OPERATION']) : ''; ?></td>
                            <td><?php echo isset($record['DATE_TIME']) ? HTML::chars($record['DATE_TIME']) : ''; ?></td>
                            <td><?php echo isset($record['KEY_VALUE']) ? HTML::chars($record['KEY_VALUE']) : ''; ?></td>
                            <td><?php echo isset($record['USERNAME']) ? HTML::chars($record['USERNAME']) : ''; ?></td>
                        </tr>
                        <tr id="details-<?php echo $record['LOG_TABLES_ID']; ?>" class="details-row">
                            <td colspan="6">
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">Поле</th>
                                            <th width="35%">Старое значение</th>
                                            <th width="35%">Новое значение</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $details = isset($record['details']) ? $record['details'] : array();
                                        if (!empty($details)) {
                                            foreach ($details as $field) {
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($field['FIELD_NAME']) . '</td>';
                                                echo '<td>' . (htmlspecialchars($field['OLD_VALUE']) ?: '&nbsp;') . '</td>';
                                                echo '<td>' . (htmlspecialchars($field['NEW_VALUE']) ?: '&nbsp;') . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="3" style="text-align: center;">Нет данных об изменениях</td><\/tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        <?php } else { ?>
            <div style="margin: 100px 0; text-align: center;">
                Нет данных для отображения<br /><br />
            </div>
        <?php } ?>
    </div>
</div>

<?php 
echo 'mod version ' . (defined('AUDITLOG_MODULE_VERSION') ? AUDITLOG_MODULE_VERSION : 'unknown');
?>