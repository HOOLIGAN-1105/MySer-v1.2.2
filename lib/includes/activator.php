<?php
namespace MySer;

class Activator {
    public static function activate() {
        // Запускаем миграции (они создадут все таблицы)
        if (class_exists('\MySer\Migrator')) {
            \MySer\Migrator::run();
        }

        self::add_default_settings();
    }

    // create_tables() удалён — всё перенесено в Migrator

    public static function add_default_settings() {
        if (!get_option('myser_settings')) {
            add_option('myser_settings', ['items_per_page' => 20]);
        }
    }
}
