<?php
namespace MySer;

defined('ABSPATH') || exit;

/**
 * Класс администрирования плагина MySer
 *
 * Регистрирует страницы меню, обрабатывает настройки, логи, бекапы.
 * Содержит методы для отображения страниц и обработки AJAX/POST-запросов.
 *
 * @package MySer
 */
class Admin_Menu
{


    /**
     * Инициализирует админ-меню и регистрирует хуки
     *
     * @return void
     */
    public static function init()
    {
        add_action('admin_menu', [self::class, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
        add_action('admin_post_myser_clear_logs', [self::class, 'clear_logs']);
        add_action('admin_post_myser_download_log', [self::class, 'download_log']);
        add_action('admin_post_myser_save_log_settings', [self::class, 'save_log_settings']);

        // Регистрируем настройки
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_head', [self::class, 'output_theme_css']);

    }//end init()


    /**
     * Регистрирует настройки плагина для сохранения в базе данных
     *
     * @return void
     */
    public static function register_settings()
    {
        register_setting(
            'myser_settings_group',
            'myser_settings',
            [
                'sanitize_callback' => [
                    self::class,
                    'sanitize_settings',
                ],
            ]
        );

    }//end register_settings()


    /**
     * Санитизирует и валидирует входные данные настроек
     *
     * @param  array $input Входной массив с настройками
     * @return array Очищенный массив настроек
     */
    public static function sanitize_settings($input)
    {
        $output                    = [];
        $output['company_name']    = sanitize_text_field(($input['company_name'] ?? ''));
        $output['company_phone']   = sanitize_text_field(($input['company_phone'] ?? ''));
        $output['company_email']   = sanitize_email(($input['company_email'] ?? ''));
        $output['company_address'] = sanitize_textarea_field(($input['company_address'] ?? ''));
        $output['order_prefix']    = sanitize_text_field(($input['order_prefix'] ?? 'MYS'));
        $output['items_per_page']  = intval(($input['items_per_page'] ?? 20));
        $output['currency']        = sanitize_text_field(($input['currency'] ?? 'RUB'));
        $output['tax_rate']        = floatval(($input['tax_rate'] ?? 0));
        $output['enable_notifications'] = isset($input['enable_notifications']) ? 1 : 0;
        $output['uninstall_behavior']   = sanitize_text_field(($input['uninstall_behavior'] ?? 'keep'));
        $output['log_level']            = sanitize_text_field(($input['log_level'] ?? 'error'));
        $output['log_retention_days']   = intval(($input['log_retention_days'] ?? 7));
        // Сохраняем логотип
        $output['logo_url'] = esc_url_raw(($input['logo_url'] ?? ''));
        $output['theme_primary']       = sanitize_hex_color(($input['theme_primary'] ?? '#0073aa'));
        $output['theme_font']          = sanitize_text_field(($input['theme_font'] ?? 'inherit'));
        $output['table_rows']          = intval(($input['table_rows'] ?? 20));
        $output['department_head']     = sanitize_text_field(($input['department_head'] ?? ''));
        $output['order_numbering']     = sanitize_text_field(($input['order_numbering'] ?? 'sequential'));
        return $output;

    }//end sanitize_settings()


    /**
     * Добавляет главное меню и подменю плагина
     *
     * @return void
     */
    public static function add_menu_pages()
    {
        add_menu_page(
            __('MySer', 'myser'),
            __('MySer', 'myser'),
            'manage_options',
            'myser-dashboard',
            [
                self::class,
                'render_dashboard',
            ],
            'dashicons-admin-tools',
            30
        );

        $pages = [
            'myser-dashboard' => __('Дашборд', 'myser'),
            'myser-orders'    => __('Заказы', 'myser'),
            'myser-clients'   => __('Клиенты', 'myser'),
            'myser-services'  => __('Услуги', 'myser'),
            'myser-stock'     => __('Склад', 'myser'),
            'myser-staff'     => __('Сотрудники', 'myser'),
            'myser-settings'  => __('⚙️ Настройки', 'myser'),
            'myser-logs'      => __('Логи', 'myser'),
            'myser-backups'   => __('Бекапы', 'myser'),
        ];

        foreach ($pages as $slug => $title) {
            add_submenu_page(
                'myser-dashboard',
                $title,
                $title,
                'manage_options',
                $slug,
                [
                    self::class,
                    'render_'.str_replace('myser-', '', $slug),
                ]
            );
        }

        // Дашборд теперь виден в подменю

    }//end add_menu_pages()


    /**
     * Подключает стили и скрипты для страниц админки
     *
     * @param  string $hook Текущая страница админки
     * @return void
     */
    public static function enqueue_scripts($hook)
    {
        if (strpos($hook, 'myser-') === false) {
            return;
        }

        // Подключаем медиа-загрузчик и JS для страниц с настройками
        if (strpos($hook, 'myser-settings') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('myser-settings', MYSER_PLUGIN_URL.'assets/admin/js/settings.js', ['myser-admin'], MYSER_VERSION, true);
        }

        wp_enqueue_script('jquery');
        wp_enqueue_style('myser-admin', MYSER_PLUGIN_URL.'assets/admin/css/admin.css', [], MYSER_VERSION);
        wp_enqueue_script('myser-admin', MYSER_PLUGIN_URL.'assets/admin/js/admin.js', ['jquery'], MYSER_VERSION, true);
        wp_localize_script(
            'myser-admin',
            'myser_ajax',
            [
                'ajaxurl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('myser_nonce'),
                'plugin_url' => MYSER_PLUGIN_URL,
            ]
        );

    }//end enqueue_scripts()


    /**
     * Отображает страницу дашборда
     *
     * @return void
     */
    public static function render_dashboard()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/dashboard.php';

    }//end render_dashboard()


    /**
     * Отображает страницу заказов
     *
     * @return void
     */
    public static function render_orders()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/orders.php';

    }//end render_orders()


    /**
     * Отображает страницу клиентов
     *
     * @return void
     */
    public static function render_clients()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/clients.php';

    }//end render_clients()


    /**
     * Отображает страницу услуг
     *
     * @return void
     */
    public static function render_services()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/services.php';

    }//end render_services()


    /**
     * Отображает страницу товаров
     *
     * @return void
     */
    public static function render_stock()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/stock.php';

    }//end render_stock()


    /**
     * Отображает страницу сотрудников
     *
     * @return void
     */
    public static function render_staff()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/staff.php';

    }//end render_staff()


    /**
     * Отображает страницу настроек
     *
     * @return void
     */
    public static function render_settings()
    {
        include MYSER_PLUGIN_DIR.'lib/templates/settings.php';

    }//end render_settings()


    /**
     * Отображает страницу логов
     *
     * @return void
     */
    public static function render_logs()
    {
        $logger   = Logger::get();
        $settings = get_option('myser_settings', []);
        $date     = isset($_GET['log_date']) ? sanitize_text_field($_GET['log_date']) : date('Y-m-d');
        $lines    = isset($_GET['lines']) ? intval($_GET['lines']) : 100;
        $logs     = $logger->get_logs($date, $lines);
        $dates    = $logger->get_log_dates();
        $nonce    = wp_create_nonce('myser_log_action');

        include MYSER_PLUGIN_DIR.'lib/templates/logs.php';

    }//end render_logs()


    /**
     * Сохраняет настройки логирования
     */
    public static function save_log_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав.', 'myser'));
        }

        check_admin_referer('myser_save_log_settings', 'myser_log_nonce');

        $settings              = get_option('myser_settings', []);
        $settings['log_level'] = sanitize_text_field(($_POST['log_level'] ?? 'error'));
        $settings['log_retention_days'] = intval(($_POST['log_retention_days'] ?? 7));

        update_option('myser_settings', $settings);

        wp_redirect(add_query_arg('page', 'myser-logs', admin_url('admin.php')));
        exit;

    }//end save_log_settings()


    /**
     * Очищает все логи (обработчик POST-запроса)
     *
     * @return void
     */
    public static function clear_logs()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав', 'myser'));
        }

        Logger::get()->clear_logs();
        wp_redirect(admin_url('admin.php?page=myser-logs&cleared=1'));
        exit;

    }//end clear_logs()


    /**
     * Скачивает лог-файл (обработчик GET-запроса)
     *
     * @return void
     */
    public static function download_log()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав', 'myser'));
        }

        $date       = isset($_GET['log_date']) ? sanitize_text_field($_GET['log_date']) : date('Y-m-d');
        $upload_dir = wp_upload_dir();
        $file       = $upload_dir['basedir'].'/myser-logs/myser-'.$date.'.log';
        if (!file_exists($file)) {
            wp_die(__('Лог не найден', 'myser'));
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="myser-'.$date.'.log"');
        readfile($file);
        exit;

    }//end download_log()


    /**
     * Страница управления бекапами
     */


    /**
     * Отображает страницу управления бекапами
     *
     * @return void
     */
    public static function render_backups()
    {
        $backup            = Backup::get();
        $backups           = $backup->list_backups();
        $com_dotnet_loaded = extension_loaded('com_dotnet');
        ?>
        <div class="wrap">
            <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h1 style="margin: 0;">
                    <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/backup.svg" class="myser-icon" alt="">
                    <?php _e('Управление бекапами', 'myser'); ?>
                </h1>
                <div style="font-size: 0.9em; color: #0073aa; text-align: center; flex: 1;">
                    MySer v<?php echo MYSER_VERSION; ?>
                </div>
                <div style="text-align: right; min-width: 150px;">
                    <button class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ Ребут плагина</button>
                    <span id="myser-reboot-status" style="display: block; margin-top: 4px; font-size: 12px;"></span>
                </div>
            </div>
            <p style="margin-top: -5px; margin-bottom: 15px;"><?php _e('Экспортируйте или импортируйте данные плагина. Бекапы хранятся в папке', 'myser'); ?> <code>wp-content/uploads/myser-backups/</code>.</p>

            <div class="myser-backup-actions">
                <button type="button" class="button button-primary" id="myser_export_backup_btn"><?php _e('Создать бекап', 'myser'); ?></button>
                <button type="button" class="button" id="myser_import_backup_btn"><?php _e('Импортировать бекап', 'myser'); ?></button>
                <button type="button" class="button" id="myser_refresh_backups_btn"><?php _e('Обновить список', 'myser'); ?></button>
            </div>

            <div id="myser_backup_list_container">
                <div class="myser-backup-header">
                    <h3><?php _e('Список бекапов', 'myser'); ?></h3>
                    <button type="button" id="myser-delete-selected" class="button button-primary"><?php _e('Удалить выбранные', 'myser'); ?></button>
                </div>
                <div id="myser_backup_list">
                    <?php if (empty($backups)) : ?>
                        <p>Бекапов не найдено</p>
                    <?php else : ?>
                        <form id="backup-list-form">
                            <input type="hidden" name="action" value="myser_delete_backups">
                             <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('myser_nonce'); ?>">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="select-all-backups"></th>
                                        <th><?php _e('Имя файла', 'myser'); ?></th>
                                        <th><?php _e('Тип', 'myser'); ?></th>
                                        <th><?php _e('Размер', 'myser'); ?></th>
                                        <th><?php _e('Дата изменения', 'myser'); ?></th>
                                        <th style="width: 150px;"><?php _e('Действия', 'myser'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $file) : ?>
                                        <tr>
                                            <td><input type="checkbox" name="backup_files[]" value="<?php echo esc_attr($file['name']); ?>"></td>
                                            <td><?php echo esc_html($file['name']); ?></td>
                                            <td><?php echo esc_html($file['type']); ?></td>
                                            <td><?php echo esc_html($file['size_formatted']); ?></td>
                                            <td><?php echo esc_html($file['modified']); ?></td>
                                            <td>
                                                <a href="<?php echo admin_url('admin-ajax.php?action=myser_download_backup&file='.urlencode($file['name']).'&nonce='.wp_create_nonce('myser_download_backup')); ?>" class="button button-small">Скачать</a>
                                                <button type="button" class="button button-small button-link-delete myser-delete-single" data-filename="<?php echo esc_attr($file['name']); ?>">Удалить</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Модалка для выбора формата экспорта (скрытая) -->
            <div id="myser_export_modal" style="display:none;">
                <div class="myser-modal-overlay">
                    <div class="myser-modal-box">
                        <h3>Выберите формат бекапа</h3>
                        <p>Выберите формат, в котором будет создан бекап:</p>
                        <select id="myser_export_format">
                            <option value="sql">SQL (дамп)</option>
                            <option value="csv">CSV (ZIP-архив)</option>
                            <?php if ($com_dotnet_loaded) : ?>
                            <option value="mdb">Access (MDB)</option>
                            <?php endif; ?>
                        </select>
                        <?php if (!$com_dotnet_loaded) : ?>
                        <p class="description">Формат MDB недоступен, так как расширение PHP <code>com_dotnet</code> не загружено. Для работы с MDB включите <code>extension=com_dotnet</code> в php.ini.</p>
                        <?php endif; ?>
                        <br><br>
                        <button type="button" class="button button-primary" id="myser_export_confirm">Создать</button>
                        <button type="button" class="button" id="myser_export_cancel">Отмена</button>
                    </div>
                </div>
            </div>

            <!-- Модалка для импорта (скрытая) -->
            <div id="myser_import_modal" style="display:none;">
                <div class="myser-modal-overlay">
                    <div class="myser-modal-box">
                        <h3>Импорт бекапа</h3>
                        <p>Выберите файл бекапа (.sql, .zip
                        <?php
                        if ($com_dotnet_loaded) :
                            ?>
                             или .mdb
                            <?php
                        endif;
                        ?>
                                                           ):</p>
                        <input type="file" id="myser_import_file" accept=".sql,.zip
                        <?php
                        if ($com_dotnet_loaded) :
                            ?>
                            ,.mdb
                            <?php
                        endif;
                        ?>
                                                                                   ">
                        <?php if (!$com_dotnet_loaded) : ?>
                        <p class="description">Импорт MDB недоступен, так как расширение PHP <code>com_dotnet</code> не загружено.</p>
                        <?php endif; ?>
                        <br><br>
                        <button type="button" class="button button-primary" id="myser_import_confirm">Импортировать</button>
                        <button type="button" class="button" id="myser_import_cancel">Отмена</button>
                    </div>
                </div>
            </div>

            <style>
                .myser-modal-overlay {
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
                    background: rgba(0,0,0,0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 10000;
                }
                .myser-modal-box {
                    background: #fff;
                    padding: 30px;
                    border-radius: 8px;
                    max-width: 500px;
                    width: 90%;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                }
                .myser-modal-box h3 {
                    margin-top: 0;
                }
                .myser-backup-actions .button {
                    margin-right: 10px;
                }
                #myser_backup_list table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                #myser_backup_list th, #myser_backup_list td {
                    padding: 8px 12px;
                    border-bottom: 1px solid #ddd;
                    text-align: left;
                }
                #myser_backup_list th {
                    background: #f1f1f1;
                }
                .myser-backup-actions {
                    margin: 15px 0;
                }
                .myser-backup-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 20px 0 10px 0;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #ccc;
                }
                .myser-backup-header h3 {
                    margin: 0;
                }
            </style>
            <script>
            jQuery(document).ready(function($) {
                // Выделить все чекбоксы
                $('#select-all-backups').on('change', function() {
                    $('input[name="backup_files[]"]').prop('checked', this.checked);
                });

                // Обработка удаления выбранных
                $('#myser-delete-selected').on('click', function() {
                    var selected = $('input[name="backup_files[]"]:checked');
                    if (selected.length === 0) {
                        alert('Выберите хотя бы один бекап для удаления');
                        return;
                    }
                    if (!confirm('Удалить выбранные бекапы (' + selected.length + ')?')) {
                        return;
                    }

                    var files = [];
                    selected.each(function() {
                        files.push($(this).val());
                    });

                    var nonce = $('input[name="nonce"]').val();

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'myser_delete_backups',
                            nonce: nonce,
                            files: files
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $('#myser-delete-selected').prop('disabled', true).text('Удаление...');
                        },
                        success: function(response) {
                            if (response.success) {
                                var result = response.data;
                                var msg = 'Удалено файлов: ' + result.success;
                                if (result.errors.length > 0) {
                                    msg += '\nОшибки: ' + result.errors.join(', ');
                                }
                                alert(msg);
                                location.reload();
                            } else {
                                alert('Ошибка: ' + (response.data.message || 'Неизвестная ошибка'));
                                $('#myser-delete-selected').prop('disabled', false).text('Удалить выбранные');
                            }
                        },
                        error: function() {
                            alert('Ошибка соединения с сервером');
                            $('#myser-delete-selected').prop('disabled', false).text('Удалить выбранные');
                        }
                    });
                });

                // Одиночное удаление бекапа
                $(document).on('click', '.myser-delete-single', function() {
                    var filename = $(this).data('filename');
                    if (!filename) return;
                    if (!confirm('Удалить бекап "' + filename + '"?')) return;

                    var nonce = '<?php echo wp_create_nonce('myser_nonce'); ?>';
                    var $btn = $(this);
                    $btn.prop('disabled', true).text('Удаление...');

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'myser_delete_backup',
                            nonce: nonce,
                            filename: filename
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                alert('Бекап удалён');
                                location.reload();
                            } else {
                                alert('Ошибка: ' + (response.data.message || 'Неизвестная ошибка'));
                                $btn.prop('disabled', false).text('Удалить');
                            }
                        },
                        error: function() {
                            alert('Ошибка соединения с сервером');
                            $btn.prop('disabled', false).text('Удалить');
                        }
                    });
                });

                // Кнопка "Обновить список"
                $('#myser_refresh_backups_btn').on('click', function() {
                    location.reload();
                });

                // Кнопка "Создать бекап"
                $('#myser_export_backup_btn').on('click', function() {
                    $('#myser_export_modal').show();
                });

                $('#myser_export_cancel').on('click', function() {
                    $('#myser_export_modal').hide();
                });

                $('#myser_export_confirm').on('click', function() {
                    var format = $('#myser_export_format').val();
                    var nonce = '<?php echo wp_create_nonce('myser_nonce'); ?>';
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'myser_export_backup',
                            nonce: nonce,
                            format: format
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $('#myser_export_confirm').prop('disabled', true).text('Создание...');
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Бекап создан: ' + response.data.file);
                                location.reload();
                            } else {
                                alert('Ошибка: ' + (response.data.message || 'Неизвестная ошибка'));
                            }
                            $('#myser_export_modal').hide();
                            $('#myser_export_confirm').prop('disabled', false).text('Создать');
                        },
                        error: function() {
                            alert('Ошибка соединения с сервером');
                            $('#myser_export_modal').hide();
                            $('#myser_export_confirm').prop('disabled', false).text('Создать');
                        }
                    });
                });

                // Кнопка "Импортировать бекап"
                $('#myser_import_backup_btn').on('click', function() {
                    $('#myser_import_modal').show();
                });

                $('#myser_import_cancel').on('click', function() {
                    $('#myser_import_modal').hide();
                });

                $('#myser_import_confirm').on('click', function() {
                    var fileInput = document.getElementById('myser_import_file');
                    if (!fileInput.files || fileInput.files.length === 0) {
                        alert('Выберите файл для импорта');
                        return;
                    }
                    var file = fileInput.files[0];
                    var formData = new FormData();
                    formData.append('action', 'myser_import_backup');
                    formData.append('nonce', '<?php echo wp_create_nonce('myser_nonce'); ?>');
                    formData.append('backup_file', file);

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        beforeSend: function() {
                            $('#myser_import_confirm').prop('disabled', true).text('Импорт...');
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Бекап успешно импортирован');
                                location.reload();
                            } else {
                                alert('Ошибка: ' + (response.data.message || 'Неизвестная ошибка'));
                            }
                            $('#myser_import_modal').hide();
                            $('#myser_import_confirm').prop('disabled', false).text('Импортировать');
                        },
                        error: function() {
                            alert('Ошибка соединения с сервером');
                            $('#myser_import_modal').hide();
                            $('#myser_import_confirm').prop('disabled', false).text('Импортировать');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php

    }//end render_backups()



    /**
     * Выводит CSS с кастомным основным цветом из настроек
     */
    public static function output_theme_css()
    {
        $settings = get_option('myser_settings', []);
        $color    = $settings['theme_primary'] ?? '#0073aa';
        echo '<style id="myser-theme-css">
            .nav-tab-active, .nav-tab-active:hover, .nav-tab-active:focus { background: '.esc_attr($color).' !important; color: #fff !important; }
            .nav-tab-active, .nav-tab-active:focus { box-shadow: none !important; }
            .button-primary { background: '.esc_attr($color).' !important; border-color: '.esc_attr($color).' !important; }
            .button-primary:hover { background: '.esc_attr(self::adjust_brightness($color, -15)).' !important; border-color: '.esc_attr(self::adjust_brightness($color, -15)).' !important; }
        </style>';
    }

    /**
     * Затемняет или осветляет hex-цвет
     */
    private static function adjust_brightness($hex, $steps)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
        return '#'.str_pad(dechex($r), 2, '0', STR_PAD_LEFT).str_pad(dechex($g), 2, '0', STR_PAD_LEFT).str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

}//end class
