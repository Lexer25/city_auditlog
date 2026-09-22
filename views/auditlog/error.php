<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<?php if (isset($table_error) && $table_error === true): ?>
<style>
    .error-page-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: #f5f5f5;
        margin: 0;
        padding: 20px;
        font-family: Arial, sans-serif;
    }
    .error-content {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        max-width: 600px;
        width: 100%;
    }
    .error-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    .error-title {
        color: #d32f2f;
        margin-bottom: 15px;
        font-size: 24px;
    }
    .error-message {
        color: #333;
        margin-bottom: 20px;
        font-size: 16px;
        line-height: 1.8;
    }
    .missing-tables-list {
        text-align: left;
        margin: 15px 30px;
        padding: 10px 20px;
        background: #fff3f3;
        border: 1px solid #ffcdd2;
        border-radius: 5px;
        color: #c62828;
        font-weight: bold;
        list-style: none;
    }
    .missing-tables-list li {
        margin: 5px 0;
        padding: 5px;
        border-bottom: 1px solid #ffcdd2;
    }
    .missing-tables-list li:last-child {
        border-bottom: none;
    }
    .error-steps {
        text-align: left;
        margin: 20px 30px;
        color: #555;
        font-size: 14px;
    }
    .error-steps li {
        margin: 8px 0;
    }
    .btn-group {
        margin-top: 20px;
    }
    .btn-group button {
        margin: 5px 10px;
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-close {
        background: #f44336;
        color: white;
    }
    .btn-close:hover {
        background: #d32f2f;
    }
    .table-name {
        font-family: monospace;
        background: #fff;
        padding: 2px 8px;
        border-radius: 3px;
        border: 1px solid #ddd;
    }
</style>

<div class="error-page-container">
    <div class="error-content">
        <div class="error-icon">❌</div>
        <h2 class="error-title">Таблицы не найдены!</h2>
        <div class="error-message">
            <p><strong>В базе данных отсутствуют необходимые таблицы для работы модуля аудита.</strong></p>
            
            <?php if (isset($missing_tables) && !empty($missing_tables)): ?>
            <div style="margin: 15px 0;">
                <p style="color: #666; font-size: 14px; margin-bottom: 10px;">
                    <strong>Отсутствуют следующие таблицы:</strong>
                </p>
                <ul class="missing-tables-list">
                    <?php foreach ($missing_tables as $table): ?>
                    <li>📋 <span class="table-name"><?php echo htmlspecialchars($table); ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <p style="color: #666; font-size: 14px; margin-top: 15px;">
                Для исправления проблемы выполните следующие действия:
            </p>
            <ol class="error-steps">
                <li>Запустите файл <strong>C:/bat/run_all.bat</strong></li>
                <li>Дождитесь завершения выполнения скрипта</li>
                <li>Обновите страницу</li>
            </ol>
        </div>
        <div class="btn-group">
            <button class="btn-close" onclick="this.closest('.error-page-container').style.display='none'">✖ Закрыть</button>
        </div>
    </div>
</div>
<?php endif; ?>