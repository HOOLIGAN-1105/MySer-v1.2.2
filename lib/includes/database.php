<?php
/**
 * Класс для работы с базой данных плагина MySer
 *
 * Предоставляет методы для получения имён таблиц, выполнения запросов,
 * получения записей клиентов и заказов, а также удаления таблиц.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class Database
{


    /**
     * Возвращает массив имён таблиц с префиксом WordPress
     *
     * @return array Ассоциативный массив с ключами: clients, orders, statuses, services, stock, staff, order_stock, order_services
     */
    public static function get_tables()
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        return [
            'clients'        => $prefix.'myser_clients',
            'orders'         => $prefix.'myser_orders',
            'statuses'       => $prefix.'myser_statuses',
            'services'       => $prefix.'myser_services',
            'items'          => $prefix.'myser_items',
            'staff'          => $prefix.'myser_staff',
            'order_stock'    => $prefix.'myser_order_stock',
            'order_services' => $prefix.'myser_order_services',
            'departments'    => $prefix.'myser_departments',
        ];

    }//end get_tables()


    public static function get_charset()
    {
        global $wpdb;
        return $wpdb->get_charset_collate();

    }//end get_charset()


    public static function query($sql, $params=[])
    {
        global $wpdb;
        if (empty($params)) {
            return $wpdb->query($sql);
        } else {
            return $wpdb->query($wpdb->prepare($sql, ...$params));
        }

    }//end query()


    public static function get_results($sql, $params=[])
    {
        global $wpdb;
        if (empty($params)) {
            return $wpdb->get_results($sql);
        } else {
            return $wpdb->get_results($wpdb->prepare($sql, ...$params));
        }

    }//end get_results()


    public static function get_client($id)
    {
        global $wpdb;
        $tables = self::get_tables();
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['clients']} WHERE id = %d",
                $id
            )
        );

    }//end get_client()


    public static function get_order($id)
    {
        global $wpdb;
        $tables = self::get_tables();
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$tables['orders']} WHERE id = %d",
                $id
            )
        );

    }//end get_order()


    public static function drop_tables()
    {
        global $wpdb;
        $tables = self::get_tables();
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

    }//end drop_tables()

    /**
     * Получить текущую версию схемы БД
     */
    public static function get_db_version() {
        return get_option('myser_db_version', '1.0.0');
    }

    /**
     * Обновить версию схемы БД
     */
    public static function update_db_version($version) {
        update_option('myser_db_version', $version);
    }

    /**
     * Проверить, нужно ли обновить схему
     */
    public static function needs_upgrade() {
        $current = self::get_db_version();
        $target = defined('MYSER_VERSION') ? MYSER_VERSION : '1.0.0';
        return version_compare($current, $target, '<');
    }


}//end class
