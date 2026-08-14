<?php
/**
 * Миграции схемы БД — единый метод без версионных переходов.
 *
 * При активации плагина создаёт/добавляет все таблицы и колонки,
 * независимо от текущего состояния БД (IF NOT EXISTS / SHOW COLUMNS).
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class Migrator
{

    /**
     * Выполнить все миграции (идемпотентно).
     */
    public static function run()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $charset_collate = $wpdb->get_charset_collate();
        $charset = Database::get_charset();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ----------------------------------------------------------------
        // 1. Основные таблицы
        // ----------------------------------------------------------------

        // Клиенты
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}myser_clients (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            last_name VARCHAR(100) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            middle_name VARCHAR(100) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            other_phone VARCHAR(50) DEFAULT NULL COMMENT 'Доп. телефон',
            email VARCHAR(255) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            type VARCHAR(50) DEFAULT 'individual',
            order_count INT UNSIGNED DEFAULT 0 COMMENT 'Счётчик заказов',
            is_problem_client TINYINT(1) DEFAULT 0 COMMENT '0=адекватный, 1=проблемный',
            notes TEXT DEFAULT NULL,
            subject_id INT UNSIGNED DEFAULT NULL,
            extra_data LONGTEXT DEFAULT NULL COMMENT 'JSON с личными и юр. данными',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // Заказы
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}myser_orders (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            doc_number VARCHAR(100) DEFAULT NULL,
            doc_date DATETIME DEFAULT NULL,
            client_id INT UNSIGNED NOT NULL,
            device_type VARCHAR(100) DEFAULT NULL,
            device_manufacturer VARCHAR(100) DEFAULT NULL,
            device_model VARCHAR(100) DEFAULT NULL,
            device_serial VARCHAR(100) DEFAULT NULL,
            client_complaint TEXT DEFAULT NULL,
            status_id INT UNSIGNED DEFAULT 1,
            grand_total DECIMAL(12,2) DEFAULT 0.00,
            subject_id INT UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_client (client_id),
            INDEX idx_status (status_id),
            INDEX idx_doc_number (doc_number)
        ) $charset_collate;";
        dbDelta($sql);

        // Сотрудники
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}myser_staff (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_name VARCHAR(255) NOT NULL COMMENT 'Полное ФИО',
            staff_short_name VARCHAR(100) DEFAULT NULL COMMENT 'Краткое имя',
            use_in_schedule TINYINT(1) DEFAULT 1,
            mobile_phone VARCHAR(50) DEFAULT NULL,
            work_phone VARCHAR(50) DEFAULT NULL,
            home_phone VARCHAR(50) DEFAULT NULL,
            birth_day DATE DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            work_start_date DATE DEFAULT NULL,
            staff_position VARCHAR(100) DEFAULT NULL,
            specialization VARCHAR(255) DEFAULT NULL,
            department VARCHAR(100) DEFAULT NULL,
            work_status VARCHAR(50) DEFAULT NULL,
            company VARCHAR(100) DEFAULT NULL,
            branch VARCHAR(100) DEFAULT NULL,
            supervisor_id INT UNSIGNED DEFAULT NULL,
            tabel_number VARCHAR(50) DEFAULT NULL,
            passport TEXT DEFAULT NULL,
            registration_address TEXT DEFAULT NULL,
            real_address TEXT DEFAULT NULL,
            family_status VARCHAR(50) DEFAULT NULL,
            kids TINYINT UNSIGNED DEFAULT 0,
            car VARCHAR(100) DEFAULT NULL,
            driving_licence VARCHAR(100) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            salary DECIMAL(12,2) DEFAULT 0.00,
            percent_service DECIMAL(5,2) DEFAULT 0.00,
            percent_products DECIMAL(5,2) DEFAULT 0.00,
            subject_id INT UNSIGNED DEFAULT NULL,
            subject_roles VARCHAR(500) DEFAULT NULL COMMENT 'Роли через запятую',
            extra_data LONGTEXT DEFAULT NULL COMMENT 'JSON с личными и юр. данными',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_staff_name (staff_name(100)),
            INDEX idx_email (email),
            INDEX idx_supervisor (supervisor_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ----------------------------------------------------------------
        // 2. Защитные ALTER — добавляем колонки, если таблицы уже были
        // ----------------------------------------------------------------
        $alter_columns = [
            'myser_staff' => [
                'subject_id'    => "ADD COLUMN `subject_id` INT UNSIGNED DEFAULT NULL",
                'subject_roles' => "ADD COLUMN `subject_roles` VARCHAR(500) DEFAULT NULL COMMENT 'Роли через запятую'",
                'extra_data'    => "ADD COLUMN `extra_data` LONGTEXT DEFAULT NULL COMMENT 'JSON с личными и юр. данными'",
                'work_status'   => "ADD COLUMN `work_status` VARCHAR(50) DEFAULT NULL",
            ],
            'myser_clients' => [
                'subject_id'         => "ADD COLUMN `subject_id` INT UNSIGNED DEFAULT NULL",
                'extra_data'         => "ADD COLUMN `extra_data` LONGTEXT DEFAULT NULL COMMENT 'JSON с личными и юр. данными'",
                'other_phone'        => "ADD COLUMN `other_phone` VARCHAR(50) DEFAULT NULL COMMENT 'Доп. телефон'",
                'is_problem_client'  => "ADD COLUMN `is_problem_client` TINYINT(1) DEFAULT 0 COMMENT '0=адекватный, 1=проблемный'",
                'order_count'        => "ADD COLUMN `order_count` INT UNSIGNED DEFAULT 0 COMMENT 'Счётчик заказов'",
            ],
            'myser_orders' => [
                'subject_id' => "ADD COLUMN `subject_id` INT UNSIGNED DEFAULT NULL",
            ],
        ];
        foreach ($alter_columns as $table => $columns) {
            $full_table = $prefix . $table;
            foreach ($columns as $col_name => $alter_sql) {
                $col_exists = $wpdb->get_results($wpdb->prepare(
                    "SHOW COLUMNS FROM `$full_table` LIKE %s",
                    $col_name
                ));
                if (empty($col_exists)) {
                    $wpdb->query("ALTER TABLE `$full_table` $alter_sql");
                }
            }
        }

        // ----------------------------------------------------------------
        // 3. Субъекты
        // ----------------------------------------------------------------
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}myser_subjects (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `subject_type` VARCHAR(50) NOT NULL,
            `last_name` VARCHAR(100) NOT NULL,
            `first_name` VARCHAR(100) NOT NULL,
            `middle_name` VARCHAR(100) DEFAULT NULL,
            `display_name` VARCHAR(255) DEFAULT NULL,
            `short_name` VARCHAR(100) DEFAULT NULL,
            `full_name_without_lastname` VARCHAR(255) DEFAULT NULL,
            `company_name` VARCHAR(255) DEFAULT NULL,
            `number_int` INT(11) DEFAULT NULL,
            `number_float` VARCHAR(50) DEFAULT NULL,
            `birth_date` DATE DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `mobile_phone` VARCHAR(30) DEFAULT NULL,
            `work_phone` VARCHAR(30) DEFAULT NULL,
            `home_phone` VARCHAR(30) DEFAULT NULL,
            `registration_address` TEXT DEFAULT NULL,
            `real_address` TEXT DEFAULT NULL,
            `passport` TEXT DEFAULT NULL,
            `tax_id` VARCHAR(50) DEFAULT NULL,
            `snils` VARCHAR(50) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `subject_type` (`subject_type`),
            KEY `last_name` (`last_name`),
            KEY `email` (`email`),
            KEY `mobile_phone` (`mobile_phone`)
        ) $charset;";
        $wpdb->query($sql);

        // ----------------------------------------------------------------
        // 4. Роли субъектов
        // ----------------------------------------------------------------
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}myser_subject_roles (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `subject_id` INT(11) UNSIGNED NOT NULL,
            `role` VARCHAR(50) NOT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `subject_role` (`subject_id`, `role`),
            KEY `role` (`role`),
            KEY `is_active` (`is_active`)
        ) $charset;";
        $wpdb->query($sql);

        // ----------------------------------------------------------------
        // 5. Справочники
        // ----------------------------------------------------------------

        // Статусы работы
        $table = $prefix . 'myser_work_status';
        $wpdb->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            `sort_order` INT(11) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) $charset");
        $statuses = ['Работает', 'На больничном', 'Уволен', 'Отпуск', 'Командировка'];
        foreach ($statuses as $i => $status) {
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `$table` (name, sort_order) VALUES (%s, %d)", $status, $i));
        }

        // Роли (справочник)
        $table = $prefix . 'myser_roles';
        $wpdb->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            `sort_order` INT(11) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) $charset");
        $roles = ['Администратор', 'Менеджер', 'Техник', 'Сотрудник', 'Мастер', 'Тех.инженер', 'IT-инженер', 'Бухгалтерия', 'Склад', 'Руководитель'];
        foreach ($roles as $i => $role) {
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `$table` (name, sort_order) VALUES (%s, %d)", $role, $i));
        }

        // ----------------------------------------------------------------
        // 6. Подразделения
        // ----------------------------------------------------------------
        $sql = "CREATE TABLE IF NOT EXISTS {$prefix}myser_departments (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `short_name` VARCHAR(100) NOT NULL COMMENT 'Краткое название (Центральный, Тверской)',
            `full_name` VARCHAR(500) DEFAULT NULL COMMENT 'Полное наименование',
            `order_prefix` VARCHAR(10) DEFAULT NULL COMMENT 'Префикс номера заказа',
            `city` VARCHAR(100) DEFAULT NULL,
            `address` TEXT DEFAULT NULL COMMENT 'Юридический адрес',
            `address_fact` TEXT DEFAULT NULL COMMENT 'Фактический адрес',
            `work_phone` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `staff_count` INT(11) NOT NULL DEFAULT 0,
            `inn` VARCHAR(20) DEFAULT NULL,
            `kpp` VARCHAR(20) DEFAULT NULL,
            `ogrn` VARCHAR(30) DEFAULT NULL,
            `okpo` VARCHAR(30) DEFAULT NULL,
            `okvd` VARCHAR(100) DEFAULT NULL,
            `bank_account` VARCHAR(50) DEFAULT NULL,
            `bank_name` VARCHAR(255) DEFAULT NULL,
            `bank_corr` VARCHAR(50) DEFAULT NULL,
            `bank_bic` VARCHAR(20) DEFAULT NULL,
            `accountant` VARCHAR(255) DEFAULT NULL,
            `director` VARCHAR(255) DEFAULT NULL COMMENT 'Руководитель (кратко)',
            `director_full` VARCHAR(500) DEFAULT NULL COMMENT 'ФИО руководителя полностью',
            `director_position` VARCHAR(255) DEFAULT NULL,
            `director_vlice` VARCHAR(255) DEFAULT NULL COMMENT 'Действует на основании (в лице)',
            `director_dobased` VARCHAR(255) DEFAULT NULL COMMENT 'На основании документа',
            `notes` TEXT DEFAULT NULL,
            `status` TINYINT(1) DEFAULT 1 COMMENT '1=активно, 0=неактивно',
            `is_default` TINYINT(1) DEFAULT 0 COMMENT '1=головное подразделение по умолчанию',
            `logo_url` VARCHAR(500) DEFAULT NULL,
            `stamp_url` VARCHAR(500) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) $charset;";
        dbDelta($sql);

        // Доп. колонка для departments (если таблица уже существовала без неё)
        $dep_table = $prefix . 'myser_departments';
        $col = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `$dep_table` LIKE %s", 'staff_count'));
        if (empty($col)) {
            $wpdb->query("ALTER TABLE `$dep_table` ADD COLUMN `staff_count` INT(11) NOT NULL DEFAULT 0 AFTER `email`");
        }

        // ----------------------------------------------------------------
        // 7. Сетки заработка
        // ----------------------------------------------------------------
        $table = $prefix . 'myser_salary_grids';
        $wpdb->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `sort_order` INT(11) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) $charset");

        $table = $prefix . 'myser_staff_salary_grids';
        $wpdb->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `staff_id` INT(11) UNSIGNED NOT NULL,
            `grid_id` INT(11) UNSIGNED NOT NULL,
            `condition_type` VARCHAR(50) DEFAULT 'custom',
            `condition_value` VARCHAR(100) DEFAULT NULL,
            `custom_percent` DECIMAL(5,2) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `staff_id` (`staff_id`),
            KEY `grid_id` (`grid_id`)
        ) $charset");

        // Записываем версию схемы
        Database::update_db_version(MYSER_VERSION);
    }
}
