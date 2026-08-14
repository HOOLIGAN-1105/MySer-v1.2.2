<?php
/**
 * Класс для управления бекапами (экспорт/импорт данных)
 *
 * Поддерживает форматы: SQL, CSV (ZIP), MDB (Access).
 * Использует паттерн Singleton. Хранит бекапы в папке wp-content/uploads/myser-backups/.
 *
 * Фасад: делегирует вызовы специализированным классам BackupCore, BackupExport, BackupImport, BackupManager.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class Backup
{

    /**
     * @var self|null Экземпляр класса (Singleton)
     */
    private static $instance = null;

    /**
     * @var BackupCore
     */
    private $core;

    /**
     * @var BackupExport
     */
    private $export;

    /**
     * @var BackupImport
     */
    private $import;

    /**
     * @var BackupManager
     */
    private $manager;


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
     * Инициализирует все подклассы
     */
    private function __construct()
    {
        $this->core = BackupCore::get();

        // Явное создание экземпляров
        if (class_exists('MySer\\BackupExport')) {
            $this->export = new \MySer\BackupExport();
        } else {
            $this->export = null;
        }

        if (class_exists('MySer\\BackupImport')) {
            $this->import = new \MySer\BackupImport();
        } else {
            $this->import = null;
        }

        if (class_exists('MySer\\BackupManager')) {
            $this->manager = new \MySer\BackupManager();
        } else {
            $this->manager = null;
        }

    }//end __construct()


    // ─── Делегирование в BackupCore ───────────────────────────────────────────

    public function get_backup_dir()       { return $this->core->get_backup_dir(); }
    public function get_tables()           { return $this->core->get_tables(); }
    public function generate_filename($format) { return $this->core->generate_filename($format); }
    public function delete_all_data()      { return $this->core->delete_all_data(); }

    // ─── Делегирование в BackupExport ─────────────────────────────────────────

    public function export_sql($filename=null)          { return $this->export->export_sql($filename); }
    public function export_csv($filename=null)          { return $this->export->export_csv($filename); }
    public function export_mdb($filename=null)          { return $this->export->export_mdb($filename); }
    public function get_access_instructions()            { return $this->export->get_access_instructions(); }

    // ─── Делегирование в BackupImport ─────────────────────────────────────────

    public function import_sql($filepath)  { return $this->import->import_sql($filepath); }
    public function import_csv($filepath)  { return $this->import->import_csv($filepath); }
    public function import_mdb($filepath)  { return $this->import->import_mdb($filepath); }

    // ─── Делегирование в BackupManager ────────────────────────────────────────

    public function list_backups()                  { return $this->manager->list_backups(); }
    public function delete_backup($filename)        { return $this->manager->delete_backup($filename); }
    public function delete_backups($filenames)      { return $this->manager->delete_backups($filenames); }


}//end class
