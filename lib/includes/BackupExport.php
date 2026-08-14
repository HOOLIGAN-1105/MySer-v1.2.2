<?php
/**
 * Экспорт данных: SQL, CSV (ZIP), MDB (Access).
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class BackupExport
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
     * Экспорт всех таблиц в SQL-дамп
     *
     * @param  string|null $filename Имя файла (без пути). Если null, генерируется автоматически.
     * @return string|false Путь к созданному файлу или false в случае ошибки.
     */
    public function export_sql($filename=null)
    {
        global $wpdb;

        if ($filename === null) {
            $filename = $this->core->generate_filename('sql');
        }

        $backup_dir = $this->core->get_backup_dir();
        $filepath   = $backup_dir.$filename;
        $logger     = Logger::get();

        $tables = $this->core->get_tables();
        $output = "-- MySer SQL Dump\n";
        $output .= '-- Generated: '.date('Y-m-d H:i:s')."\n";
        $output .= '-- Tables: '.implode(', ', $tables)."\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $key => $table_name) {
            // Структура
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_N);
            if (!$create_table) {
                $logger->error("Не удалось получить структуру таблицы $table_name");
                continue;
            }

            $output .= $create_table[1].";\n\n";

            // Данные
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            if (empty($rows)) {
                continue;
            }

            $columns = array_keys($rows[0]);
            $output .= "INSERT INTO `$table_name` (`".implode('`, `', $columns)."`) VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $escaped  = array_map(
                    function ($val) use ($wpdb) {
                        if ($val === null) {
                            return 'NULL';
                        }

                        return "'".$wpdb->_real_escape($val)."'";
                    },
                    $row
                );
                $values[] = '('.implode(', ', $escaped).')';
            }

            $output .= implode(",\n", $values).";\n\n";
        }//end foreach

        $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        if (file_put_contents($filepath, $output) === false) {
            $logger->error("Не удалось сохранить SQL-дамп в $filepath");
            return false;
        }

        $logger->info("SQL-дамп создан: $filename");
        return $filepath;

    }//end export_sql()


    /**
     * Экспорт всех таблиц в CSV-файлы, упакованные в ZIP-архив
     *
     * @param  string|null $filename Имя файла (без пути). Если null, генерируется автоматически.
     * @return string|false Путь к созданному ZIP-файлу или false в случае ошибки.
     */
    public function export_csv($filename=null)
    {
        global $wpdb;

        if (!$this->core->check_zip_archive()) {
            return false;
        }

        if ($filename === null) {
            $filename = $this->core->generate_filename('zip');
        }

        $backup_dir = $this->core->get_backup_dir();
        $filepath   = $backup_dir.$filename;
        $logger     = Logger::get();

        $zip = new \ZipArchive();
        if ($zip->open($filepath, \ZipArchive::CREATE) !== true) {
            $logger->error("Не удалось создать ZIP-архив $filepath");
            return false;
        }

        $tables = $this->core->get_tables();
        foreach ($tables as $key => $table_name) {
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            if (empty($rows)) {
                // Создаём пустой CSV с заголовками
                $columns = [];
                $zip->addFromString("$table_name.csv", '');
                continue;
            }

            $columns  = array_keys($rows[0]);
            $csv_data = fopen('php://temp', 'r+');
            fputcsv($csv_data, $columns, ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($csv_data, $row, ',', '"', '\\');
            }

            rewind($csv_data);
            $csv_content = stream_get_contents($csv_data);
            fclose($csv_data);

            $zip->addFromString("$table_name.csv", $csv_content);
        }//end foreach

        // Добавляем инструкцию для Access
        $instructions = $this->get_access_instructions();
        $zip->addFromString('ACCESS_IMPORT_INSTRUCTION.txt', $instructions);

        $zip->close();

        $logger->info("CSV-экспорт создан: $filename");
        return $filepath;

    }//end export_csv()


    /**
     * Генерирует инструкцию по импорту CSV в Microsoft Access
     *
     * @return string
     */
    public function get_access_instructions()
    {
        $tables        = $this->core->get_tables();
        $instructions  = "=== ИНСТРУКЦИЯ ПО ИМПОРТУ CSV В MICROSOFT ACCESS ===\n\n";
        $instructions .= "1. Откройте Microsoft Access.\n";
        $instructions .= "2. Создайте новую базу данных или откройте существующую.\n";
        $instructions .= "3. Для каждой таблицы выполните импорт:\n\n";

        foreach ($tables as $key => $table_name) {
            $instructions .= "   Таблица: $table_name\n";
            $instructions .= "   Файл: $table_name.csv\n";
            $instructions .= "   - В Access: Внешние данные -> Импорт из текстового файла\n";
            $instructions .= "   - Выберите CSV-файл\n";
            $instructions .= "   - Укажите, что файл содержит разделители (запятые)\n";
            $instructions .= "   - Отметьте 'Первая строка содержит заголовки'\n";
            $instructions .= "   - Назначьте таблице имя $table_name\n";
            $instructions .= "   - При необходимости укажите ключевые поля\n\n";
        }

        $instructions .= "Примечание: Для корректного импорта убедитесь, что структура таблиц в Access соответствует структуре CSV-файлов.\n";
        $instructions .= "Рекомендуется создать таблицы в Access с теми же полями, что и в плагине.\n";

        return $instructions;

    }//end get_access_instructions()


    /**
     * Экспорт всех таблиц в Microsoft Access MDB-файл
     * Требуется Windows и расширение com_dotnet
     *
     * @param  string|null $filename Имя файла (без пути). Если null, генерируется автоматически.
     * @return string|false Путь к созданному MDB-файлу или false в случае ошибки.
     */
    public function export_mdb($filename=null)
    {
        global $wpdb;

        $logger     = Logger::get();
        $backup_dir = $this->core->get_backup_dir();

        // Проверка ОС
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $logger->error('Экспорт в MDB доступен только на Windows');
            return false;
        }

        // Проверка расширения com_dotnet
        if (!extension_loaded('com_dotnet')) {
            $logger->error('Расширение com_dotnet не загружено. Невозможно создать MDB.');
            return false;
        }

        if ($filename === null) {
            $filename = $this->core->generate_filename('mdb');
        }

        $filepath = $backup_dir.$filename;

        // Проверка возможности записи в папку
        if (!is_writable($backup_dir)) {
            $logger->error("Папка бекапов недоступна для записи: {$backup_dir}");
            return false;
        }

        try {
            // Пробуем использовать Jet OLEDB, если не получится — ACE
            $providers = [
                'Microsoft.Jet.OLEDB.4.0',
                'Microsoft.ACE.OLEDB.12.0',
            ];
            $conn_str  = '';
            $connected = false;
            foreach ($providers as $provider) {
                try {
                    $test_conn_str = "Provider=$provider;Data Source=$filepath;";
                    // Проверяем, доступен ли провайдер, создав временный объект
                    $test_cat = new \COM('ADOX.Catalog');
                    $test_cat->Create($test_conn_str);
                    $conn_str  = $test_conn_str;
                    $connected = true;
                    break;
                } catch (\Exception $e) {
                    // Провайдер не работает, пробуем следующий
                    continue;
                }
            }

            if (!$connected) {
                $logger->error('Не найден подходящий OLEDB-провайдер (Jet или ACE). Убедитесь, что установлены драйверы Microsoft Access.');
                return false;
            }

            // Подключаемся к созданной БД
            $conn = new \COM('ADODB.Connection');
            $conn->Open($conn_str);

            $tables = $this->core->get_tables();

            foreach ($tables as $table_name) {
                // Получаем структуру таблицы из MySQL
                $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table_name`", ARRAY_A);
                if (empty($columns)) {
                    $logger->warning("Не удалось получить структуру таблицы $table_name, пропускаем");
                    continue;
                }

                // Строим CREATE TABLE для Access
                $create_sql = "CREATE TABLE `$table_name` (";
                $field_defs = [];
                foreach ($columns as $col) {
                    $name  = $col['Field'];
                    $type  = strtoupper($col['Type']);
                    $null  = ($col['Null'] === 'YES') ? 'NULL' : 'NOT NULL';
                    $extra = ($col['Extra'] ?? '');

                    // Преобразование типов MySQL в типы Access
                    if (strpos($type, 'INT') !== false) {
                        $access_type = 'INTEGER';
                    } else if (strpos($type, 'DECIMAL') !== false || strpos($type, 'NUMERIC') !== false || strpos($type, 'FLOAT') !== false || strpos($type, 'DOUBLE') !== false) {
                        $access_type = 'CURRENCY';
                    } else if (strpos($type, 'TEXT') !== false || strpos($type, 'VARCHAR') !== false) {
                        $access_type = 'TEXT';
                    } else if (strpos($type, 'LONGTEXT') !== false || strpos($type, 'MEDIUMTEXT') !== false) {
                        $access_type = 'MEMO';
                    } else if (strpos($type, 'DATE') !== false || strpos($type, 'TIME') !== false || strpos($type, 'DATETIME') !== false || strpos($type, 'TIMESTAMP') !== false) {
                        $access_type = 'DATETIME';
                    } else if (strpos($type, 'BOOL') !== false || strpos($type, 'TINYINT(1)') !== false) {
                        $access_type = 'YESNO';
                    } else {
                        $access_type = 'TEXT';
                    }

                    // Проверка на автоинкремент (primary key)
                    if (strpos($extra, 'auto_increment') !== false) {
                        $field_defs[] = "`$name` COUNTER PRIMARY KEY";
                    } else {
                        $field_defs[] = "`$name` $access_type $null";
                    }
                }//end foreach

                $create_sql .= implode(', ', $field_defs).')';

                // Выполняем CREATE TABLE через ADODB.Connection
                $conn->Execute($create_sql);

                // Копируем данные
                $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
                if (empty($rows)) {
                    continue;
                }

                $columns_names = array_keys($rows[0]);
                $placeholders  = implode(', ', array_fill(0, count($columns_names), '?'));
                $insert_sql    = "INSERT INTO `$table_name` (`".implode('`, `', $columns_names)."`) VALUES ($placeholders)";

                // Подготавливаем команду
                $cmd                   = new \COM('ADODB.Command');
                $cmd->ActiveConnection = $conn;
                $cmd->CommandText      = $insert_sql;

                // Создаём параметры
                $params = [];
                foreach ($columns_names as $col) {
                    $param = $cmd->CreateParameter('@'.$col, 200, 1, 255);
                    // 200 = adVarChar, 1 = adParamInput
                    $cmd->Parameters->Append($param);
                    $params[$col] = $param;
                }

                foreach ($rows as $row) {
                    foreach ($columns_names as $col) {
                        $value = $row[$col];
                        if ($value === null) {
                            $params[$col]->Value = null;
                        } else {
                            $params[$col]->Value = $value;
                        }
                    }

                    $cmd->Execute();
                }
            }//end foreach

            $conn->Close();
            $logger->info("MDB-экспорт создан: $filename");
            return $filepath;
        } catch (\Exception $e) {
            $logger->error('Ошибка при создании MDB: '.$e->getMessage());
            $logger->error('Трассировка: '.$e->getTraceAsString());
            return false;
        }//end try

    }//end export_mdb()


}//end class
