<?php
/**
 * Класс для логирования событий плагина MySer
 *
 * Реализует паттерн Singleton. Поддерживает уровни логирования (debug, info, warning, error, critical, off),
 * автоматическую ротацию логов, очистку старых файлов и получение списка логов.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class Logger
{
    /**
 * Уровень DEBUG — подробная отладочная информация
*/
    const LEVEL_DEBUG = 0;
    /**
 * Уровень INFO — информационные сообщения
*/
    const LEVEL_INFO = 1;
    /**
 * Уровень WARNING — предупреждения
*/
    const LEVEL_WARNING = 2;
    /**
 * Уровень ERROR — ошибки
*/
    const LEVEL_ERROR = 3;
    /**
 * Уровень CRITICAL — критичекие ошибки
*/
    const LEVEL_CRITICAL = 4;
    /**
 * Уровень OFF — логирование отключено
*/
    const LEVEL_OFF = 5;

    /**
     * @var self|null Экземпляр класса (Singleton)
     */
    private static $instance;

    /**
     * @var string Путь к директории с логами
     */
    private $log_dir;

    /**
     * @var integer Текущий уровень логирования
     */
    private $current_level;

    /**
     * @var integer Максимальное количество дней хранения логов
     */
    private $max_days;

    /**
     * @var boolean Флаг инициализации (для предотвращения повторной очистки)
     */
    private $initialized = false;


    private function __construct()
    {
        $upload_dir    = wp_upload_dir();
        $this->log_dir = $upload_dir['basedir'].'/myser-logs/';
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }

        $settings            = get_option('myser_settings', []);
        $level_map           = [
            'off'     => self::LEVEL_OFF,
            'error'   => self::LEVEL_ERROR,
            'warning' => self::LEVEL_WARNING,
            'info'    => self::LEVEL_INFO,
            'debug'   => self::LEVEL_DEBUG,
        ];
        $this->current_level = ($level_map[($settings['log_level'] ?? 'error')] ?? self::LEVEL_ERROR);
        $this->max_days      = intval(($settings['log_retention_days'] ?? 7));

        if (!$this->initialized) {
            $this->clean_old_logs();
            $this->initialized = true;
        }

    }//end __construct()


    public static function get()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;

    }//end get()


    public function debug($message, $context=[])
    {
        if ($this->current_level <= self::LEVEL_DEBUG) {
            $this->write('DEBUG', $message, $context);
        }

    }//end debug()


    public function info($message, $context=[])
    {
        if ($this->current_level <= self::LEVEL_INFO) {
            $this->write('INFO', $message, $context);
        }

    }//end info()


    public function warning($message, $context=[])
    {
        if ($this->current_level <= self::LEVEL_WARNING) {
            $this->write('WARNING', $message, $context);
        }

    }//end warning()


    public function error($message, $context=[])
    {
        if ($this->current_level <= self::LEVEL_ERROR) {
            $this->write('ERROR', $message, $context);
        }

    }//end error()


    public function critical($message, $context=[])
    {
        if ($this->current_level <= self::LEVEL_CRITICAL) {
            $this->write('CRITICAL', $message, $context);
        }

    }//end critical()


    private function write($level, $message, $context)
    {
        $timestamp   = current_time('mysql');
        $context_str = $context ? ' '.json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line        = "[$timestamp] [$level] $message$context_str\n";
        $file        = $this->log_dir.'myser-'.date('Y-m-d').'.log';
        file_put_contents($file, $line, (FILE_APPEND | LOCK_EX));

    }//end write()


    private function clean_old_logs()
    {
        $files = glob($this->log_dir.'myser-*.log');
        $now   = time();
        foreach ($files as $f) {
            if (filemtime($f) < ($now - $this->max_days * DAY_IN_SECONDS)) {
                @unlink($f);
            }
        }

    }//end clean_old_logs()


    public function get_logs($date=null, $lines=100)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $file = $this->log_dir.'myser-'.$date.'.log';
        if (!file_exists($file)) {
            return [];
        }

        $content     = file_get_contents($file);
        $lines_array = explode("\n", trim($content));
        return array_slice($lines_array, -$lines);

    }//end get_logs()


    public function get_log_dates()
    {
        $files = glob($this->log_dir.'myser-*.log');
        $dates = [];
        foreach ($files as $f) {
            $dates[] = basename($f, '.log');
        }

        return $dates;

    }//end get_log_dates()


    public function clear_logs()
    {
        $files = glob($this->log_dir.'myser-*.log');
        foreach ($files as $f) {
            @unlink($f);
        }

    }//end clear_logs()


    public function get_log_dir()
    {
        return $this->log_dir;

    }//end get_log_dir()


}//end class
