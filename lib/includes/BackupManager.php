<?php
/**
 * Управление файлами бекапов: список, удаление.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class BackupManager
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
     * Получает список бекапов в папке
     *
     * @return array Массив с информацией о файлах (имя, размер, дата, тип)
     */
    public function list_backups()
    {
        $backup_dir = $this->core->get_backup_dir();
        $files      = scandir($backup_dir);
        $result     = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess' || $file === 'index.php') {
                continue;
            }

            $filepath = $backup_dir.$file;
            if (!is_file($filepath)) {
                continue;
            }

            $ext  = pathinfo($file, PATHINFO_EXTENSION);
            $type = '';
            if ($ext === 'sql') {
                $type = 'SQL';
            } else if ($ext === 'zip') {
                $type = 'CSV (ZIP)';
            } else if ($ext === 'mdb') {
                $type = 'Access (MDB)';
            } else {
                $type = 'Неизвестный';
            }

            $result[] = [
                'name'           => $file,
                'size'           => filesize($filepath),
                'size_formatted' => size_format(filesize($filepath), 2),
                'modified'       => date('Y-m-d H:i:s', filemtime($filepath)),
                'type'           => $type,
                'extension'      => $ext,
            ];
        }//end foreach

        // Сортировка по дате (сначала новые)
        usort(
            $result,
            function ($a, $b) {
                return (strtotime($b['modified']) - strtotime($a['modified']));
            }
        );

        return $result;

    }//end list_backups()


    /**
     * Удаляет файл бекапа
     *
     * @param  string $filename Имя файла
     * @return boolean
     */
    public function delete_backup($filename)
    {
        $logger    = Logger::get();
        $filepath  = $this->core->get_backup_dir().$filename;

        // Временное логирование для отладки
        $logger->debug("delete_backup вызван", ['filename' => $filename, 'filepath' => $filepath]);

        if (!file_exists($filepath)) {
            $logger->error("Файл не найден для удаления: $filename");
            return false;
        }

        if (unlink($filepath)) {
            $logger->info("Бекап удалён: $filename");
            return true;
        } else {
            $logger->error("Не удалось удалить бекап: $filename");
            return false;
        }

    }//end delete_backup()


    /**
     * Удаляет несколько файлов бекапов
     *
     * @param  array $filenames Массив имён файлов
     * @return array Массив с ключами 'success' (количество успешно удалённых) и 'errors' (список ошибок)
     */
    public function delete_backups($filenames)
    {
        $success_count = 0;
        $errors        = [];
        foreach ($filenames as $filename) {
            // Санитизация имени файла (запрещаем пути)
            $filename = basename($filename);
            if (empty($filename)) {
                $errors[] = 'Пустое имя файла';
                continue;
            }

            $result = $this->delete_backup($filename);
            if ($result) {
                $success_count++;
            } else {
                $errors[] = "Не удалось удалить $filename";
            }
        }

        return [
            'success' => $success_count,
            'errors'  => $errors,
        ];

    }//end delete_backups()


}//end class
