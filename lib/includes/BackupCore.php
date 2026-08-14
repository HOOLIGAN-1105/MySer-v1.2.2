<?php
/**
 * Ядро системы бекапов: Singleton, инициализация папки, общие утилиты.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class BackupCore
{

    /**
     * @var self|null Экземпляр класса (Singleton)
     */
    private static $instance = null;

    /**
     * @var Logger Экземпляр логгера
     */
    private $logger;

    /**
     * @var string Путь к папке с бекапами
     */
    private $backup_dir;


    /**
     * Возвращает единственный экземпляр класса (Singleton)
     *
     * @return self
     */
    public static function get()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;

    }//end get()


    /**
     * Приватный конструктор (Singleton)
     * Инициализирует папку для бекапов и подключает логгер
     */
    private function __construct()
    {
        $this->logger     = Logger::get();
        $upload_dir       = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'].'/myser-backups/';

        if (!file_exists($this->backup_dir)) {
            $created = wp_mkdir_p($this->backup_dir);
            if (!$created) {
                $this->logger->error("Не удалось создать директорию для бекапов: {$this->backup_dir}");
                add_action(
                    'admin_notices',
                    function () {
                        echo '<div class="notice notice-error is-dismissible"><p><strong>MySer:</strong> Не удалось создать директорию для бекапов. Проверьте права на запись в папку загрузок WordPress.</p></div>';
                    }
                );
            }
        }

        if (is_dir($this->backup_dir) && !is_writable($this->backup_dir)) {
            $this->logger->error("Директория бекапов недоступна для записи: {$this->backup_dir}");
            add_action(
                'admin_notices',
                function () {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>MySer:</strong> Директория бекапов недоступна для записи. Проверьте права на папку '.esc_html($this->backup_dir).'.</p></div>';
                }
            );
        } else {
            if (!file_exists($this->backup_dir.'/.htaccess')) {
                file_put_contents($this->backup_dir.'/.htaccess', 'Deny from all');
            }

            if (!file_exists($this->backup_dir.'/index.php')) {
                file_put_contents($this->backup_dir.'/index.php', '<?php // silence');
            }
        }

    }//end __construct()


    /**
     * Возвращает путь к папке бекапов
     *
     * @return string
     */
    public function get_backup_dir()
    {
        return $this->backup_dir;

    }//end get_backup_dir()


    public function get_tables()
    {
        return Database::get_tables();

    }//end get_tables()


    public function generate_filename($format)
    {
        return 'myser_backup_'.date('Y-m-d_H-i-s').'.'.$format;

    }//end generate_filename()


    /**
     * Проверяет доступность ZipArchive и выводит админ-уведомление при ошибке
     *
     * @return boolean
     */
    public function check_zip_archive()
    {
        if (class_exists('ZipArchive')) {
            return true;
        }

        $this->logger->error('ZipArchive не доступен. Невозможно создать ZIP-архив.');
        add_action(
            'admin_notices',
            function () {
                echo '<div class="notice notice-error is-dismissible"><p><strong>MySer:</strong> Для работы с ZIP-архивами требуется расширение PHP ZipArchive. Установите или включите его.</p></div>';
            }
        );
        return false;

    }//end check_zip_archive()


    /**
     * Удаляет все данные плагина (таблицы)
     * Используется при деинсталляции
     */
    public function delete_all_data()
    {
        global $wpdb;

        $tables = $this->get_tables();
        foreach ($tables as $table_name) {
            $wpdb->query("DROP TABLE IF EXISTS `$table_name`");
        }

        // Удаляем опции
        delete_option('myser_settings');
        delete_option('myser_version');

        // Удаляем папку с логами и бекапами
        $upload_dir = wp_upload_dir();
        $log_dir    = $upload_dir['basedir'].'/myser-logs';
        $this->logger->info('Удалены все данные плагина');

    }//end delete_all_data()


}//end class
