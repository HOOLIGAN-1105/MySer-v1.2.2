<?php
/**
 * Ядро плагина MySer
 *
 * Реализует паттерн Singleton. Отвечает за инициализацию плагина,
 * загрузку текстовых доменов, подключение стилей и скриптов,
 * а также обработку AJAX-запросов для онлайн-бронирования.
 *
 * @package MySer
 */

namespace MySer;

defined('ABSPATH') || exit;

class Core
{

    /**
     * @var self|null Экземпляр класса (Singleton)
     */
    private static $instance = null;

    /**
     * @var string Префикс для опций и хуков
     */
    private $prefix = 'myser_';


    /**
     * Возвращает единственный экземпляр класса
     *
     * @return self
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;

    }//end get_instance()


    /**
     * Приватный конструктор (Singleton)
     * Инициализирует хуки WordPress
     */
    private function __construct()
    {
        $this->init_hooks();

    }//end __construct()


    /**
     * Возвращает префикс плагина
     *
     * @return string
     */
    public function get_prefix()
    {
        return $this->prefix;

    }//end get_prefix()


    /**
     * Инициализирует все хуки и фильтры WordPress
     *
     * @return void
     */
    private function init_hooks()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_myser_get_filials', [self::class, 'ajax_get_filials']);
        add_action('wp_ajax_nopriv_myser_get_filials', [self::class, 'ajax_get_filials']);
        add_action('wp_ajax_myser_get_masters', [self::class, 'ajax_get_masters']);
        add_action('wp_ajax_nopriv_myser_get_masters', [self::class, 'ajax_get_masters']);
        add_action('wp_ajax_myser_get_available_slots', [self::class, 'ajax_get_available_slots']);
        add_action('wp_ajax_nopriv_myser_get_available_slots', [self::class, 'ajax_get_available_slots']);
        add_action('wp_ajax_myser_save_appointment', [self::class, 'ajax_save_appointment']);
        add_action('wp_ajax_nopriv_myser_save_appointment', [self::class, 'ajax_save_appointment']);

    }//end init_hooks()


    /**
     * Загружает текстовый домен для интернационализации
     *
     * @return void
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('myser', false, dirname(plugin_basename(__FILE__)).'/../languages/');

    }//end load_textdomain()


    /**
     * Подключает стили и скрипты для фронтенда (только на страницах с шорткодом)
     *
     * @return void
     */
    public function enqueue_frontend_assets()
    {
        // Only enqueue on pages with our shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'myser_booking')) {
            wp_enqueue_style('myser-frontend', MYSER_PLUGIN_URL.'assets/css/booking.css', [], MYSER_VERSION);
            wp_enqueue_script('myser-frontend', MYSER_PLUGIN_URL.'assets/js/booking.js', ['jquery'], MYSER_VERSION, true);
            wp_localize_script(
                'myser-frontend',
                'myser_ajax',
                [
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('myser_nonce'),
                ]
            );
        }

    }//end enqueue_frontend_assets()


    /**
     * Подключает стили и скрипты для админки (только на страницах MySer)
     *
     * @param  string $hook Текущая страница админки
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'myser') === false) {
            return;
        }

        wp_enqueue_style('myser-admin', MYSER_PLUGIN_URL.'assets/admin/css/style.css', [], MYSER_VERSION);
        wp_enqueue_script('myser-admin', MYSER_PLUGIN_URL.'assets/admin/js/admin.js', ['jquery'], MYSER_VERSION, true);
        wp_localize_script(
            'myser-admin',
            'myser_ajax',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('myser_nonce'),
            ]
        );

    }//end enqueue_admin_assets()


    /**
     * AJAX-обработчик: возвращает список филиалов
     *
     * @return void
     */
    public static function ajax_get_filials()
    {
        global $wpdb;
        $tables  = Database::get_tables();
        $results = $wpdb->get_results(
            "
        SELECT id, name, prefix, address, phone, email
        FROM {$tables['filials']}
        WHERE is_active = 1
        ORDER BY name
    "
        );
        wp_send_json_success($results);

    }//end ajax_get_filials()


    /**
     * AJAX-обработчик: возвращает список мастеров
     *
     * @return void
     */
    public static function ajax_get_masters()
    {
        global $wpdb;
        $tables    = Database::get_tables();
        $filial_id = intval(($_POST['filial_id'] ?? 0));

        $sql     = "
            SELECT s.id, s.name, s.phone, s.email, d.name as department_name
            FROM {$tables['staff']} s
            LEFT JOIN {$tables['departments']} d ON s.department_id = d.id
            WHERE s.is_active = 1
        ";
        $results = $wpdb->get_results($sql);
        wp_send_json_success($results);

    }//end ajax_get_masters()


    /**
     * AJAX-обработчик: возвращает доступные слоты для записи
     *
     * @return void
     */
    public static function ajax_get_available_slots()
    {
        global $wpdb;
        $tables   = Database::get_tables();
        $staff_id = intval(($_POST['staff_id'] ?? 0));
        $date     = sanitize_text_field(($_POST['date'] ?? date('Y-m-d')));
        $duration = intval(($_POST['duration'] ?? 30));

        if ($staff_id <= 0) {
            wp_send_json_error(['message' => 'Staff ID required']);
        }

        // Get working hours
        $default_start = $wpdb->get_var("SELECT setting_value FROM {$tables['booking_settings']} WHERE setting_key = 'working_hours_start'");
        $default_end   = $wpdb->get_var("SELECT setting_value FROM {$tables['booking_settings']} WHERE setting_key = 'working_hours_end'");

        if (!$default_start || !$default_end) {
            $default_start = '09:00';
            $default_end   = '21:00';
        }

        $start_time = $default_start;
        $end_time   = $default_end;

        // Get booked appointments
        $booked = $wpdb->get_results(
            $wpdb->prepare(
                "
        SELECT appointment_time, status FROM {$tables['appointments']}
        WHERE staff_id = %d AND appointment_date = %s
        AND status IN ('pending', 'confirmed')
    ",
                $staff_id,
                $date
            )
        );

        $booked_times = [];
        foreach ($booked as $b) {
            $booked_times[] = $b->appointment_time;
        }

        // Generate slots
        $slots            = [];
        $current          = strtotime($start_time);
        $end              = strtotime($end_time);
        $duration_seconds = ($duration * 60);

        $break_start = $wpdb->get_var("SELECT setting_value FROM {$tables['booking_settings']} WHERE setting_key = 'break_start'");
        $break_end   = $wpdb->get_var("SELECT setting_value FROM {$tables['booking_settings']} WHERE setting_key = 'break_end'");

        while (($current + $duration_seconds) <= $end) {
            $time_slot = date('H:i', $current);
            $is_booked = in_array($time_slot, $booked_times);

            $is_break = false;
            if ($break_start && $break_end) {
                $break_start_ts = strtotime($break_start);
                $break_end_ts   = strtotime($break_end);
                if ($current >= $break_start_ts && $current < $break_end_ts) {
                    $is_break = true;
                }
            }

            $slots[]  = [
                'time'      => $time_slot,
                'available' => !$is_booked && !$is_break,
                'is_break'  => $is_break,
                'booked'    => $is_booked,
            ];
            $current += $duration_seconds;
        }//end while

        wp_send_json_success(
            [
                'date'       => $date,
                'staff_id'   => $staff_id,
                'slots'      => $slots,
                'start_time' => $start_time,
                'end_time'   => $end_time,
            ]
        );

    }//end ajax_get_available_slots()


    public static function ajax_save_appointment()
    {
        global $wpdb;
        $tables = Database::get_tables();
        $data   = $_POST;

        $required = [
            'filial_id',
            'staff_id',
            'appointment_date',
            'appointment_time',
            'client_name',
            'client_phone',
        ];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                wp_send_json_error(['message' => "Field '$field' is required"]);
            }
        }

        // Check if slot is still available
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "
        SELECT COUNT(*) FROM {$tables['appointments']}
        WHERE staff_id = %d AND appointment_date = %s AND appointment_time = %s
        AND status IN ('pending', 'confirmed')
    ",
                $data['staff_id'],
                $data['appointment_date'],
                $data['appointment_time']
            )
        );

        if ($existing > 0) {
            wp_send_json_error(['message' => 'This time slot is no longer available']);
        }

        // Create client
        $client_id = 0;
        $client    = $wpdb->get_row(
            $wpdb->prepare(
                "
        SELECT id FROM {$tables['clients']}
        WHERE phone = %s
    ",
                $data['client_phone']
            )
        );

        if ($client) {
            $client_id = $client->id;
        } else {
            $wpdb->insert(
                $tables['clients'],
                [
                    'full_name' => sanitize_text_field($data['client_name']),
                    'phone'     => sanitize_text_field($data['client_phone']),
                    'email'     => sanitize_email(($data['client_email'] ?? '')),
                    'status'    => 'active',
                ]
            );
            $client_id = $wpdb->insert_id;
        }

        // Create order
        $doc_number = 'ORD-'.date('Ymd').'-'.rand(1000, 9999);
        while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['orders']} WHERE doc_number = %s", $doc_number)) > 0) {
            $doc_number = 'ORD-'.date('Ymd').'-'.rand(1000, 9999);
        }

        $wpdb->insert(
            $tables['orders'],
            [
                'doc_number'          => $doc_number,
                'doc_date'            => current_time('mysql'),
                'client_id'           => $client_id,
                'device_type'         => sanitize_text_field(($data['device_type'] ?? '')),
                'device_manufacturer' => sanitize_text_field(($data['device_manufacturer'] ?? '')),
                'device_model'        => sanitize_text_field(($data['device_model'] ?? '')),
                'client_complaint'    => sanitize_textarea_field(($data['client_complaint'] ?? '')),
                'status_id'           => 7,
            // "Онлайн" status
            ]
        );
        $order_id = $wpdb->insert_id;

        // Create appointment
        $wpdb->insert(
            $tables['appointments'],
            [
                'order_id'         => $order_id,
                'filial_id'        => intval($data['filial_id']),
                'staff_id'         => intval($data['staff_id']),
                'appointment_date' => sanitize_text_field($data['appointment_date']),
                'appointment_time' => sanitize_text_field($data['appointment_time']),
                'status'           => 'pending',
                'notes'            => sanitize_textarea_field(($data['notes'] ?? '')),
            ]
        );
        $appointment_id = $wpdb->insert_id;

        wp_send_json_success(
            [
                'message'        => 'Appointment created successfully',
                'appointment_id' => $appointment_id,
                'order_id'       => $order_id,
            ]
        );

    }//end ajax_save_appointment()


}//end class
