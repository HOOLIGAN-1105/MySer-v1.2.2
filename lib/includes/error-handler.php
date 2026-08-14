<?php
/**
 * Класс для обработки ошибок и исключений в плагине MySer
 *
 * Перехватывает ошибки PHP, фатальные ошибки и исключения,
 * записывает их в лог-файл для отладки.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class Error_Handler
{

    /**
     * @var string Путь к файлу лога ошибок
     */
    private static $log_file;


    /**
     * Инициализирует обработчики ошибок, исключений и завершения
     *
     * @return void
     */
    public static function init()
    {
        self::$log_file = MYSER_PLUGIN_DIR.'myser-error.log';
        set_error_handler([self::class, 'handle_error']);
        register_shutdown_function([self::class, 'handle_shutdown']);
        set_exception_handler([self::class, 'handle_exception']);

    }//end init()


    public static function handle_error($errno, $errstr, $errfile, $errline)
    {
        $message = sprintf(
            '[%s] %s: %s in %s on line %d',
            date('Y-m-d H:i:s'),
            self::error_type($errno),
            $errstr,
            $errfile,
            $errline
        );
        self::log($message);
        return false;

    }//end handle_error()


    public static function handle_shutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = sprintf(
                '[%s] FATAL: %s in %s on line %d',
                date('Y-m-d H:i:s'),
                $error['message'],
                $error['file'],
                $error['line']
            );
            self::log($message);
        }

    }//end handle_shutdown()


    public static function handle_exception($exception)
    {
        $message = sprintf(
            "[%s] EXCEPTION: %s in %s on line %d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        self::log($message);

    }//end handle_exception()


    private static function log($message)
    {
        if (!file_exists(self::$log_file)) {
            touch(self::$log_file);
        }

        file_put_contents(self::$log_file, $message.PHP_EOL, FILE_APPEND);

    }//end log()


    private static function error_type($errno)
    {
        $types = [
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
        ];
        return ($types[$errno] ?? 'UNKNOWN');

    }//end error_type()


}//end class
