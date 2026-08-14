<?php
/**
 * Импорт бекапов: SQL, CSV (ZIP), MDB (Access).
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class BackupImport
{

    /**
     * @var BackupCore Ядро
     */
    private $core;


    public function __construct()
    {
        $this->core = BackupCore::get();

    }//end __construct()


    /**
     * Импорт данных из SQL-дампа
     *
     * @param  string $filepath Полный путь к SQL-файлу
     * @return boolean
     */
    public function import_sql($filepath)
    {
        global $wpdb;

        $logger = Logger::get();

        if (!file_exists($filepath)) {
            $logger->error("Файл не найден: $filepath");
            return false;
        }

        $sql = file_get_contents($filepath);
        if ($sql === false) {
            $logger->error("Не удалось прочитать файл: $filepath");
            return false;
        }

        // Разбиваем дамп на отдельные запросы (учитываем многострочные конструкции)
        $queries = $this->_split_sql_queries($sql);

        $success = true;
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) {
                continue;
            }

            $result = $wpdb->query($query);
            if ($result === false) {
                $logger->error('Ошибка выполнения запроса: '.$wpdb->last_error);
                $logger->error('Запрос: '.substr($query, 0, 200).'...');
                $success = false;
            }
        }

        if ($success) {
            $logger->info('Импорт из SQL-дампа успешно завершён: '.basename($filepath));
        } else {
            $logger->error('Импорт из SQL-дампа завершён с ошибками: '.basename($filepath));
        }

        return $success;

    }//end import_sql()


    /**
     * Импорт данных из ZIP-архива с CSV-файлами
     *
     * @param  string $filepath Полный путь к ZIP-файлу
     * @return boolean
     */
    public function import_csv($filepath)
    {
        global $wpdb;

        $logger = Logger::get();

        if (!$this->core->check_zip_archive()) {
            return false;
        }

        if (!file_exists($filepath)) {
            $logger->error("Файл не найден: $filepath");
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($filepath) !== true) {
            $logger->error("Не удалось открыть ZIP-архив: $filepath");
            return false;
        }

        $tables  = $this->core->get_tables();
        $success = true;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat     = $zip->statIndex($i);
            $filename = $stat['name'];

            // Пропускаем файл инструкции
            if ($filename === 'ACCESS_IMPORT_INSTRUCTION.txt') {
                continue;
            }

            // Определяем имя таблицы по имени файла (без расширения .csv)
            $table_name = pathinfo($filename, PATHINFO_FILENAME);
            if (!in_array($table_name, $tables)) {
                $logger->warning("Таблица $table_name не найдена в списке, пропускаем файл $filename");
                continue;
            }

            $csv_content = $zip->getFromName($filename);
            if ($csv_content === false) {
                $logger->error("Не удалось прочитать $filename из архива");
                $success = false;
                continue;
            }

            // Парсим CSV
            $lines = explode("\n", trim($csv_content));
            if (empty($lines)) {
                $logger->warning("Пустой CSV-файл: $filename");
                continue;
            }

            // Заголовки
            $headers = str_getcsv(array_shift($lines));
            if (empty($headers)) {
                $logger->error("Не удалось определить заголовки в $filename");
                $success = false;
                continue;
            }

            // Очищаем таблицу перед импортом
            $wpdb->query("TRUNCATE TABLE `$table_name`");

            // Вставляем данные
            $placeholders = implode(', ', array_fill(0, count($headers), '%s'));
            $insert_query = "INSERT INTO `$table_name` (`".implode('`, `', $headers)."`) VALUES ($placeholders)";

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }

                $row = str_getcsv($line);
                if (count($row) !== count($headers)) {
                    $logger->warning("Количество колонок не совпадает в $filename, строка пропущена");
                    continue;
                }

                $prepared = $wpdb->prepare($insert_query, $row);
                $result   = $wpdb->query($prepared);
                if ($result === false) {
                    $logger->error("Ошибка вставки в $table_name: ".$wpdb->last_error);
                    $success = false;
                }
            }
        }//end for

        $zip->close();

        if ($success) {
            $logger->info('Импорт из CSV-архива успешно завершён: '.basename($filepath));
        } else {
            $logger->error('Импорт из CSV-архива завершён с ошибками: '.basename($filepath));
        }

        return $success;

    }//end import_csv()


    /**
     * Импорт данных из MDB-файла (Microsoft Access)
     * Требуется Windows и расширение com_dotnet
     *
     * @param  string $filepath Полный путь к MDB-файлу
     * @return boolean
     */
    public function import_mdb($filepath)
    {
        global $wpdb;

        $logger = Logger::get();

        // Проверка ОС
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $logger->error('Импорт из MDB доступен только на Windows');
            return false;
        }

        if (!extension_loaded('com_dotnet')) {
            $logger->error('Расширение com_dotnet не загружено. Невозможно открыть MDB.');
            return false;
        }

        if (!file_exists($filepath)) {
            $logger->error("Файл не найден: $filepath");
            return false;
        }

        try {
            $conn_str = "Provider=Microsoft.Jet.OLEDB.4.0;Data Source=$filepath;";
            $conn     = new \COM('ADODB.Connection');
            $conn->Open($conn_str);

            $tables  = $this->core->get_tables();
            $success = true;

            foreach ($tables as $table_name) {
                // Проверяем, существует ли таблица в MDB
                $rs = $conn->Execute("SELECT COUNT(*) FROM `$table_name`");
                if ($rs === false) {
                    $logger->warning("Таблица $table_name не найдена в MDB, пропускаем");
                    continue;
                }

                $rs->Close();

                // Получаем данные из MDB
                $rs = $conn->Execute("SELECT * FROM `$table_name`");
                if ($rs->EOF) {
                    $rs->Close();
                    continue;
                }

                // Очищаем таблицу в WordPress
                $wpdb->query("TRUNCATE TABLE `$table_name`");

                // Получаем список полей
                $fields = [];
                for ($i = 0; $i < $rs->Fields->Count; $i++) {
                    $fields[] = $rs->Fields($i)->Name;
                }

                $placeholders = implode(', ', array_fill(0, count($fields), '%s'));
                $insert_sql   = "INSERT INTO `$table_name` (`".implode('`, `', $fields)."`) VALUES ($placeholders)";

                while (!$rs->EOF) {
                    $row = [];
                    foreach ($fields as $field) {
                        $row[] = $rs->Fields($field)->Value;
                    }

                    $prepared = $wpdb->prepare($insert_sql, $row);
                    $result   = $wpdb->query($prepared);
                    if ($result === false) {
                        $logger->error("Ошибка вставки в $table_name: ".$wpdb->last_error);
                        $success = false;
                    }

                    $rs->MoveNext();
                }

                $rs->Close();
            }//end foreach

            $conn->Close();

            if ($success) {
                $logger->info('Импорт из MDB успешно завершён: '.basename($filepath));
            } else {
                $logger->error('Импорт из MDB завершён с ошибками: '.basename($filepath));
            }

            return $success;
        } catch (\Exception $e) {
            $logger->error('Ошибка при импорте MDB: '.$e->getMessage());
            return false;
        }//end try

    }//end import_mdb()


    /**
     * Разбивает SQL-дамп на отдельные запросы
     *
     * @param  string $sql
     * @return array
     */
    private function _split_sql_queries($sql)
    {
        // Удаляем комментарии
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Разбиваем по ; с учётом кавычек
        $queries     = [];
        $buffer      = '';
        $len         = strlen($sql);
        $in_string   = false;
        $string_char = '';

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            if ($char === "'" || $char === '"') {
                if (!$in_string) {
                    $in_string   = true;
                    $string_char = $char;
                } else if ($string_char === $char) {
                    $in_string = false;
                }
            }

            $buffer .= $char;

            if ($char === ';' && !$in_string) {
                $queries[] = trim($buffer);
                $buffer    = '';
            }
        }

        if (!empty($buffer)) {
            $queries[] = trim($buffer);
        }

        return $queries;

    }//end _split_sql_queries()


}//end class
