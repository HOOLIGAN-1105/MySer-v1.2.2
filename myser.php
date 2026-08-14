<?php
/**
 * Plugin Name: MySer - Сервисный центр
 * Plugin URI: https://myser.ru
 * Description: Плагин для управления сервисным центром: заказы, клиенты, ремонт, онлайн-запись.
 * Version: 1.2.2
 * Author: HOOLIGAN-1105 (Rachin Sergey)
 * Author URI: https://myser.ru
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: myser
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.5
 * Tested up to: 6.7
 */

if (! defined('ABSPATH')) {
    exit;
}

define('MYSER_VERSION', '1.2.2');
define('MYSER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MYSER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Подключаем обработчик ошибок
require_once MYSER_PLUGIN_DIR.'lib/includes/error-handler.php';
MySer\Error_Handler::init();

// Подключаем логгер
require_once MYSER_PLUGIN_DIR.'lib/includes/logger.php';

// Автозагрузка классов
spl_autoload_register(
    function ($class) {
        $prefix   = 'MySer\\';
        $base_dir = MYSER_PLUGIN_DIR.'lib/includes/';
        if (strpos($class, $prefix) === 0) {
            $relative_class = substr($class, strlen($prefix));
            $file           = $base_dir.str_replace('\\', '/', $relative_class).'.php';
            if (file_exists($file)) {
                include_once $file;
            }
        }
    }
);

/**
 * Основной класс плагина MySer
 *
 * Реализует паттерн Singleton. Отвечает за активацию, деактивацию,
 * инициализацию плагина, загрузку текстового домена, регистрацию хуков
 * и обработку ребута через admin-post.
 *
 * @package MySer
 */
class MySer_Plugin
{

    /**
     * @var self|null Экземпляр класса (Singleton)
     */
    private static $instance = null;


    /**
     * Возвращает единственный экземпляр класса
     *
     * @return self
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;

    }//end get_instance()


    /**
     * Приватный конструктор (Singleton)
     * Регистрирует хуки активации, деактивации и инициализации
     */
    private function __construct()
    {
        register_activation_hook(__FILE__, [ $this, 'activate' ]);
        register_deactivation_hook(__FILE__, [ $this, 'deactivate' ]);
        add_action('plugins_loaded', [ $this, 'init' ]);
        add_action('admin_post_myser_reboot_plugin', [ $this, 'handle_reboot' ]);

    }//end __construct()


    /**
     * Выполняется при активации плагина
     * Запускает установку таблиц и начальных данных
     *
     * @return void
     */
    public function activate()
    {
        include_once MYSER_PLUGIN_DIR.'lib/includes/activator.php';
        MySer\Activator::activate();
        MySer\Logger::get()->info('Плагин активирован', [ 'version' => MYSER_VERSION ]);

    }//end activate()


    /**
     * Выполняется при деактивации плагина
     * Логирует событие (без удаления данных)
     *
     * @return void
     */
    public function deactivate()
    {
        MySer\Logger::get()->info('Плагин деактивирован');

    }//end deactivate()


    /**
     * Инициализирует плагин после загрузки всех ядер WordPress
     * Подключает текстовый домен, загружает необходимые файлы,
     * инициализирует админ-меню и AJAX-обработчик,
     * устанавливает настройки по умолчанию
     *
     * @return void
     */
    public function init()
    {
        load_plugin_textdomain('myser', false, dirname(plugin_basename(__FILE__)).'/languages');

        include_once MYSER_PLUGIN_DIR.'lib/admin/menu.php';
        include_once MYSER_PLUGIN_DIR.'lib/includes/ajax-handler.php';
        include_once MYSER_PLUGIN_DIR.'lib/includes/database.php';

        MySer\Admin_Menu::init();
        MySer\Ajax_Handler::init();

        // Настройки по умолчанию
        $defaults = [
            'company_name'         => 'Мой сервисный центр',
            'company_phone'        => '+7 (999) 123-45-67',
            'company_email'        => 'info@myser.ru',
            'company_address'      => 'г. Москва, ул. Ленина, д. 1',
            'order_prefix'         => 'MYS',
            'items_per_page'       => 20,
            'currency'             => 'RUB',
            'tax_rate'             => 0,
            'enable_notifications' => 1,
            'uninstall_behavior'   => 'keep',
            'log_level'            => 'error',
            'log_retention_days'   => 7,
        ];
        $current  = get_option('myser_settings', []);
        update_option('myser_settings', array_merge($defaults, $current));

        MySer\Logger::get()->debug('Плагин инициализирован');

    }//end init()


    /**
     * Обрабатывает ребут плагина через admin-post
     * Выполняет повторную активацию, сбрасывает кэш и пересоздает таблицы
     *
     * @return void
     */
    public function handle_reboot()
    {
        if (! current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }

        MySer\Logger::get()->info('Запущен ребут через admin-post');
        try {
            include_once MYSER_PLUGIN_DIR.'lib/includes/activator.php';
            MySer\Activator::activate();
            MySer\Logger::get()->info('Ребут успешно выполнен через admin-post');
            wp_redirect(add_query_arg('rebooted', '1', wp_get_referer()));
            exit;
        } catch (\Exception $e) {
            MySer\Logger::get()->critical('Ошибка ребута через admin-post', [ 'error' => $e->getMessage() ]);
            wp_die('Ошибка ребута: '.$e->getMessage());
        }

    }//end handle_reboot()


}//end class

MySer_Plugin::get_instance();
