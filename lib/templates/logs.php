<?php
defined('ABSPATH') || exit;

$settings = get_option('myser_settings', []);
$logger   = \MySer\Logger::get();
$date     = isset($_GET['log_date']) ? sanitize_text_field($_GET['log_date']) : date('Y-m-d');
    $lines    = isset($_GET['lines']) ? intval($_GET['lines']) : 10;
$logs     = $logger->get_logs($date, $lines);
$dates    = $logger->get_log_dates();
$nonce    = wp_create_nonce('myser_nonce');
?>
<div class="wrap">
    <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;">
            <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/logs.svg" class="myser-icon" alt=""> 
            Логи MySer
        </h1>
        <div style="font-size: 0.9em; color: #0073aa; text-align: center; flex: 1;">
            MySer v<?php echo MYSER_VERSION; ?>
        </div>
        <div style="text-align: right; min-width: 150px;">
            <button class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ Ребут плагина</button>
            <span id="myser-reboot-status" style="display: block; margin-top: 4px; font-size: 12px;"></span>
        </div>
    </div>
    
    <!-- Настройки логирования -->
    <div class="myser-settings-section" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
        <h2>Настройки логирования</h2>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('myser_save_log_settings', 'myser_log_nonce'); ?>
            <input type="hidden" name="action" value="myser_save_log_settings">
            <table class="form-table" style="margin-bottom: 15px;">
                <tr>
                    <th style="width: 200px;"><label for="log_level">Уровень логирования</label></th>
                    <td>
                        <select id="log_level" name="log_level">
                            <option value="off" <?php selected(($settings['log_level'] ?? 'error'), 'off'); ?>>Выключено</option>
                            <option value="error" <?php selected(($settings['log_level'] ?? 'error'), 'error'); ?>>Только ошибки</option>
                            <option value="warning" <?php selected(($settings['log_level'] ?? 'error'), 'warning'); ?>>Предупреждения + ошибки</option>
                            <option value="info" <?php selected(($settings['log_level'] ?? 'error'), 'info'); ?>>Инфо + предупреждения + ошибки</option>
                            <option value="debug" <?php selected(($settings['log_level'] ?? 'error'), 'debug'); ?>>Отладка (всё)</option>
                        </select>
                        <p class="description">Выберите уровень детализации логов. Для продакшена рекомендуется "error" или "warning".</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="log_retention_days">Хранение логов (дней)</label></th>
                    <td>
                        <input type="number" id="log_retention_days" name="log_retention_days"
                               value="<?php echo esc_attr(($settings['log_retention_days'] ?? 7)); ?>"
                               min="1" max="90">
                        <p class="description">Логи старше указанного количества дней будут автоматически удаляться.</p>
                        <p><small>Логи хранятся в: <code>wp-content/uploads/myser-logs/</code></small></p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Сохранить настройки логирования', 'primary', 'submit', false); ?>
        </form>
    </div>
    
    <!-- Просмотр логов -->
    <div class="myser-filter-row">
        <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; width: 100%;">
            <input type="hidden" name="page" value="myser-logs">
            <label>Дата: 
                <select name="log_date">
                    <?php foreach ($dates as $d) : ?>
                        <option value="<?php echo esc_attr($d); ?>" <?php selected($date, $d); ?>>
                            <?php echo esc_html($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
             <label>Строк:
                 <select name="lines">
                     <?php foreach ([10, 25, 50, 150, 300] as $l) : ?>
                         <option value="<?php echo $l; ?>" <?php selected($lines, $l); ?>><?php echo $l; ?></option>
                     <?php endforeach; ?>
                 </select>
             </label>
            <button type="submit" class="button">Показать</button>
            <a href="<?php echo admin_url('admin-post.php?action=myser_download_log&log_date='.urlencode($date).'&nonce='.$nonce); ?>" 
               class="button">Скачать лог</a>
            <button type="button" class="button button-danger" onclick="if(confirm('Вы уверены, что хотите очистить логи за эту дату?')){window.location.href='<?php echo admin_url('admin-post.php?action=myser_clear_logs&log_date='.urlencode($date).'&nonce='.$nonce); ?>';}">
                Очистить логи
            </button>
        </form>
    </div>
    
    <!-- Отображение логов -->
    <div class="myser-logs-container" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; overflow-x: auto; max-height: 600px; overflow-y: auto;">
        <?php if (empty($logs)) : ?>
            <p style="color: #888;">Нет логов за выбранную дату.</p>
        <?php else : ?>
            <pre style="margin: 0; color: #d4d4d4; white-space: pre-wrap; word-wrap: break-word;">
            <?php
            foreach ($logs as $log) {
                // Подсветка уровней
                $line = htmlspecialchars($log);
                if (strpos($line, '[ERROR]') !== false) {
                    $line = '<span style="color: #f44747;">'.$line.'</span>';
                } else if (strpos($line, '[WARNING]') !== false) {
                    $line = '<span style="color: #f4a847;">'.$line.'</span>';
                } else if (strpos($line, '[INFO]') !== false) {
                    $line = '<span style="color: #47a8f4;">'.$line.'</span>';
                } else if (strpos($line, '[DEBUG]') !== false) {
                    $line = '<span style="color: #888;">'.$line.'</span>';
                }

                echo $line."\n";
            }
            ?>
            </pre>
        <?php endif; ?>
    </div>
    
    <p><small>Всего строк: <?php echo count($logs); ?></small></p>
</div>

<style>
.myser-settings-section select,
.myser-settings-section input[type="number"] {
    max-width: 100%;
}
.myser-settings-section .description {
    margin-top: 4px;
    font-size: 12px;
    color: #666;
}
.button-danger {
    background: #dc3232 !important;
    border-color: #dc3232 !important;
    color: #fff !important;
}
.button-danger:hover {
    background: #c62828 !important;
    border-color: #c62828 !important;
}
</style>
