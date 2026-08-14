<?php
namespace MySer;

defined('ABSPATH') || exit;

/**
 * Helper functions for the plugin
 */


/**
 * Возвращает список статусов заказов
 *
 * @return array|object|null
 */
function get_order_statuses()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT * FROM {$tables['statuses']} ORDER BY sort_order");

}//end get_order_statuses()


/**
 * Возвращает список клиентов для выпадающего списка
 *
 * @return array|object|null
 */
function get_clients_list()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, full_name FROM {$tables['clients']} WHERE status = 'active' ORDER BY full_name");

}//end get_clients_list()


/**
 * Возвращает список сотрудников для выпадающего списка
 *
 * @return array|object|null
 */
function get_staff_list()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, name FROM {$tables['staff']} WHERE is_active = 1 ORDER BY name");

}//end get_staff_list()


/**
 * Возвращает список отделов
 *
 * @return array|object|null
 */
function get_departments()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, name FROM {$tables['departments']} ORDER BY name");

}//end get_departments()


/**
 * Возвращает список типов устройств
 *
 * @return array|object|null
 */
function get_device_types()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, name FROM {$tables['device_types']} ORDER BY name");

}//end get_device_types()


/**
 * Возвращает список производителей
 *
 * @return array|object|null
 */
function get_manufacturers()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, name FROM {$tables['manufacturers']} ORDER BY name");

}//end get_manufacturers()


/**
 * Возвращает список услуг
 *
 * @return array|object|null
 */
function get_services()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, name, default_price FROM {$tables['services']} WHERE is_active = 1 ORDER BY name");

}//end get_services()


/**
 * Возвращает список товаров
 *
 * @return array|object|null
 */
function get_stock()
{
    global $wpdb;
    $tables = Database::get_tables();
    return $wpdb->get_results("SELECT id, name, sku, price FROM {$tables['stock']} WHERE is_active = 1 ORDER BY name");

}//end get_stock()


/**
 * Форматирует сумму в валюту
 *
 * @param  float $amount Сумма
 * @return string
 */
function format_currency($amount)
{
    return number_format($amount, 2, '.', ' ').' ₽';

}//end format_currency()


/**
 * Генерирует уникальный номер заказа
 *
 * @return string
 */
function generate_order_number()
{
    return 'ORD-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -4));

}//end generate_order_number()


/**
 * Проверяет, является ли дата праздничной
 *
 * @param  string $date Дата в формате Y-m-d
 * @return boolean
 */
function is_holiday($date)
{
    global $wpdb;
    $tables = Database::get_tables();
    $count  = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['holidays']} WHERE date = %s",
            $date
        )
    );
    return $count > 0;

}//end is_holiday()


/**
 * Возвращает список праздничных дней
 *
 * @param  integer|null $year Год
 * @return array|object|null
 */
function get_holidays($year=null)
{
    global $wpdb;
    $tables = Database::get_tables();
    $where  = '';
    if ($year) {
        $where = $wpdb->prepare('WHERE YEAR(date) = %d', $year);
    }

    return $wpdb->get_results("SELECT * FROM {$tables['holidays']} $where ORDER BY date");

}//end get_holidays()


/**
 * Рассчитывает общую сумму заказа
 *
 * @param  integer $order_id ID заказа
 * @return float
 */
function calculate_order_total($order_id)
{
    global $wpdb;
    $tables = Database::get_tables();

    $services_total = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(price - (price * discount_percent / 100)) FROM {$tables['order_services']} WHERE order_id = %d",
            $order_id
        )
    );

    $stock_total = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(quantity * (price - (price * discount_percent / 100))) FROM {$tables['order_stock']} WHERE order_id = %d",
            $order_id
        )
    );

    return (floatval($services_total) + floatval($stock_total));

}//end calculate_order_total()


/**
 * Возвращает сумму оплат по заказу
 *
 * @param  integer $order_id ID заказа
 * @return float
 */
function get_order_payments_total($order_id)
{
    global $wpdb;
    $tables = Database::get_tables();
    return floatval(
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$tables['order_payments']} WHERE order_id = %d",
                $order_id
            )
        )
    );

}//end get_order_payments_total()


/**
 * Возвращает остаток задолженности по заказу
 *
 * @param  integer $order_id ID заказа
 * @return float
 */
function get_order_balance($order_id)
{
    $total    = calculate_order_total($order_id);
    $payments = get_order_payments_total($order_id);
    return ($total - $payments);

}//end get_order_balance()
