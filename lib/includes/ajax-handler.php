<?php
namespace MySer;

defined('ABSPATH') || exit;

/**
 * Класс для обработки AJAX-запросов
 *
 * Регистрирует все обработчики для административных и публичных AJAX-запросов.
 * Включает операции с клиентами, заказами, бекапами и ребутом.
 *
 * @package MySer
 */
class Ajax_Handler
{


    /**
     * Инициализирует все AJAX-обработчики
     *
     * Регистрирует действия для wp_ajax_* и wp_ajax_nopriv_*.
     *
     * @return void
     */
    public static function init()
    {
        $actions = [
            'myser_get_clients',
            'myser_get_client',
            'myser_save_client',
            'myser_delete_client',
            'myser_get_orders',
            'myser_get_order',
            'myser_save_order',
            'myser_delete_order',
            'myser_get_staff',
            'myser_get_staff_member',
            'myser_save_staff',
            'myser_delete_staff',
            'myser_reboot',
            'myser_export_backup',
            'myser_import_backup',
            'myser_list_backups',
            'myser_delete_backup',
            'myser_download_backup',
            'myser_custom_uninstall',
            'myser_delete_backups',
            // Сетки заработка
            'myser_get_salary_grids',
            'myser_save_salary_grid',
            'myser_delete_salary_grid',
            'myser_get_staff_list',
            'myser_get_staff_assignments',
            'myser_save_staff_assignment',
            'myser_delete_staff_assignment',
            // Подразделения
            'myser_get_departments',
            'myser_get_department',
            'myser_save_department',
            'myser_delete_department',
        ];
        foreach ($actions as $action) {
            add_action('wp_ajax_'.$action, [self::class, str_replace('myser_', '', $action)]);
        }

    }//end init()


    private static function verify_nonce()
    {
        $nonce = $_POST['_ajax_nonce'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'myser_nonce')) {
            Logger::get()->warning('Неверный nonce в AJAX', ['action' => ($_POST['action'] ?? 'unknown')]);
            wp_send_json_error(['message' => __('Nonce verification failed', 'myser')]);
        }

    }//end verify_nonce()


    private static function check_permissions()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Недостаточно прав', 'myser')]);
        }

    }//end check_permissions()


    /**
     * Синхронизирует клиента/сотрудника с таблицей subjects.
     * Автоматически генерирует display_name и short_name.
     *
     * @param  string      $type      'client' или 'staff'
     * @param  array       $data      Данные субъекта
     * @param  int|null    $client_id ID клиента (если обновление)
     * @return int|null               subject_id
     */
    private static function sync_subject($type, $data, $client_id=null) {
        global $wpdb;
        $subjects_table = $wpdb->prefix . 'myser_subjects';
        $roles_table    = $wpdb->prefix . 'myser_subject_roles';

        $last_name   = ($data['last_name'] ?? '');
        $first_name  = ($data['first_name'] ?? '');
        $middle_name = ($data['middle_name'] ?? '');
        $email       = ($data['email'] ?? '');
        $phone       = ($data['phone'] ?? '');
        $address     = ($data['address'] ?? '');
        $notes       = ($data['notes'] ?? '');

        // Генерируем display_name
        $display_name = trim($last_name.' '.$first_name.' '.$middle_name);

        // Генерируем short_name: Фамилия И.О.
        $short_name = $last_name;
        if (!empty($first_name)) {
            $short_name .= ' '.mb_substr($first_name, 0, 1).'.';
        }
        if (!empty($middle_name)) {
            $short_name .= mb_substr($middle_name, 0, 1).'.';
        }

        // full_name_without_lastname: Имя Отчество
        $full_name_without_lastname = trim($first_name.' '.$middle_name);

        $subject_data = [
            'subject_type'              => $type,
            'last_name'                 => $last_name,
            'first_name'                => $first_name,
            'middle_name'               => $middle_name,
            'display_name'              => $display_name,
            'short_name'                => $short_name,
            'full_name_without_lastname' => $full_name_without_lastname,
            'email'                     => $email,
            'mobile_phone'              => $phone,
            'registration_address'      => $address,
            'notes'                     => $notes,
        ];

        // Ищем существующий subject_id
        $existing_subject_id = null;
        if ($client_id) {
            $clients_table = $wpdb->prefix . 'myser_clients';
            $existing_subject_id = $wpdb->get_var($wpdb->prepare(
                "SELECT subject_id FROM `$clients_table` WHERE id = %d",
                $client_id
            ));
        }

        if ($existing_subject_id) {
            // Обновляем существующий subject
            $wpdb->update($subjects_table, $subject_data, ['id' => $existing_subject_id]);
            return $existing_subject_id;
        } else {
            // Создаём новый subject
            $wpdb->insert($subjects_table, $subject_data);
            $new_subject_id = $wpdb->insert_id;

            // Добавляем роль
            if ($new_subject_id) {
                // Проверяем, нет ли уже такой роли
                $has_role = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM `$roles_table` WHERE subject_id = %d AND role = %s",
                    $new_subject_id, $type
                ));
                if (!$has_role) {
                    $wpdb->insert($roles_table, [
                        'subject_id'  => $new_subject_id,
                        'role'        => $type,
                        'assigned_at' => current_time('mysql'),
                    ]);
                }
            }

            return $new_subject_id;
        }

    }//end sync_subject()


    // ========== Staff CRUD ==========

    public static function get_staff()
    {
        self::verify_nonce();
        global $wpdb;
        $tables   = Database::get_tables();
        $page     = intval(($_POST['page'] ?? 1));
        $per_page = intval(($_POST['per_page'] ?? 20));
        $search   = sanitize_text_field(($_POST['search'] ?? ''));
        $offset   = (($page - 1) * $per_page);

        $where  = ['1=1'];
        $params = [];
        if (!empty($search)) {
            $where[]  = '(staff_name LIKE %s OR email LIKE %s OR mobile_phone LIKE %s)';
            $like     = '%'.$wpdb->esc_like($search).'%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_clause = implode(' AND ', $where);

        try {
            if (empty($params)) {
                $total = $wpdb->get_var("SELECT COUNT(*) FROM {$tables['staff']} WHERE $where_clause");
            } else {
                $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['staff']} WHERE $where_clause", $params));
            }

            if (empty($params)) {
                $sql = $wpdb->prepare("SELECT * FROM {$tables['staff']} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset);
            } else {
                $sql = $wpdb->prepare(
                    "SELECT * FROM {$tables['staff']} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d",
                    array_merge($params, [$per_page, $offset])
                );
            }

            $staff = $wpdb->get_results($sql);

            // Разрешаем JSON department в названия подразделений
            if ($staff) {
                $all_dept_ids = [];
                foreach ($staff as $s) {
                    if (!empty($s->department)) {
                        $ids = json_decode($s->department, true);
                        if (is_array($ids)) {
                            foreach ($ids as $did) {
                                $all_dept_ids[] = intval($did);
                            }
                        }
                    }
                }
                $dept_map = [];
                if (!empty($all_dept_ids)) {
                    $all_dept_ids = array_unique($all_dept_ids);
                    $placeholders = implode(',', array_fill(0, count($all_dept_ids), '%d'));
                    $dept_table = $wpdb->prefix . 'myser_departments';
                    $dept_rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT id, full_name FROM `{$dept_table}` WHERE id IN ($placeholders)",
                        $all_dept_ids
                    ));
                    foreach ($dept_rows as $dr) {
                        $dept_map[$dr->id] = $dr->full_name;
                    }
                }
                // Подменяем department на строку с названиями
                foreach ($staff as $s) {
                    if (!empty($s->department)) {
                        $decoded = json_decode($s->department, true);
                        if (is_array($decoded) && !empty($decoded)) {
                            // Новый формат: JSON-массив ID
                            $names = [];
                            foreach ($decoded as $did) {
                                $did = intval($did);
                                if (isset($dept_map[$did])) {
                                    $names[] = $dept_map[$did];
                                }
                            }
                            $s->department = !empty($names) ? implode(', ', $names) : '';
                        } elseif (is_array($decoded) && empty($decoded)) {
                            // Пустой массив — нет подразделений
                            $s->department = '';
                        } else {
                            // Старый формат: обычная строка (название подразделения)
                            // Оставляем как есть, если не удалось декодировать
                        }
                    } else {
                        $s->department = '';
                    }
                }
            }

            wp_send_json_success([
                'items'        => $staff,
                'total'        => (int) $total,
                'pages'        => ceil($total / $per_page),
                'current_page' => $page,
            ]);
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка получения сотрудников', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end get_staff()


    public static function get_staff_member()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();
        $id     = intval(($_POST['staff_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid staff ID']);
        }

        $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tables['staff']} WHERE id = %d", $id));
        if ($member) {
            // department хранится как JSON-массив ID подразделений
            if (!empty($member->department)) {
                $decoded = json_decode($member->department, true);
                $member->department_ids = is_array($decoded) ? $decoded : [];
            } else {
                $member->department_ids = [];
            }
            wp_send_json_success($member);
        } else {
            wp_send_json_error(['message' => 'Staff not found']);
        }

    }//end get_staff_member()


    public static function save_staff()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();

        $staff_name = sanitize_text_field(($_POST['staff_name'] ?? ''));

        // Авто-генерация short_name из staff_name
        $staff_short_name = sanitize_text_field(($_POST['staff_short_name'] ?? ''));
        if (empty($staff_short_name) && !empty($staff_name)) {
            $parts = explode(' ', $staff_name);
            $staff_short_name = $parts[0];
            if (isset($parts[1])) {
                $staff_short_name .= ' '.mb_substr($parts[1], 0, 1).'.';
            }
            if (isset($parts[2])) {
                $staff_short_name .= mb_substr($parts[2], 0, 1).'.';
            }
        }

        $data = [
            'staff_name'           => $staff_name,
            'staff_short_name'     => $staff_short_name,
            'use_in_schedule'      => intval(($_POST['use_in_schedule'] ?? 1)),
            'mobile_phone'         => sanitize_text_field(($_POST['mobile_phone'] ?? '')),
            'work_phone'           => sanitize_text_field(($_POST['work_phone'] ?? '')),
            'home_phone'           => sanitize_text_field(($_POST['home_phone'] ?? '')),
            'birth_day'            => sanitize_text_field(($_POST['birth_day'] ?? null)),
            'email'                => sanitize_email(($_POST['email'] ?? '')),
            'work_start_date'      => sanitize_text_field(($_POST['work_start_date'] ?? null)),
            'staff_position'       => sanitize_text_field(($_POST['staff_position'] ?? '')),
            'specialization'       => sanitize_text_field(($_POST['specialization'] ?? '')),
            'department'           => json_encode(array_map('intval', ($_POST['department_ids'] ?? []))),
            'work_status'          => sanitize_text_field(($_POST['status'] ?? $_POST['work_status'] ?? 'works')),
            'branch'               => sanitize_text_field(($_POST['branch'] ?? '')),
            'supervisor_id'        => intval(($_POST['supervisor_id'] ?? 0)) ?: null,
            'tabel_number'         => sanitize_text_field(($_POST['tabel_number'] ?? '')),
            'passport'             => sanitize_textarea_field(($_POST['passport'] ?? '')),
            'registration_address' => sanitize_textarea_field(($_POST['registration_address'] ?? '')),
            'real_address'         => sanitize_textarea_field(($_POST['real_address'] ?? '')),
            'family_status'        => sanitize_text_field(($_POST['family_status'] ?? '')),
            'kids'                 => intval(($_POST['kids'] ?? 0)),
            'car'                  => sanitize_text_field(($_POST['car'] ?? '')),
            'driving_licence'      => sanitize_text_field(($_POST['driving_licence'] ?? '')),
            'notes'                => sanitize_textarea_field(($_POST['notes'] ?? '')),
            'salary'               => floatval(($_POST['salary'] ?? 0)),
            'percent_service'      => floatval(($_POST['percent_service'] ?? 0)),
            'percent_products'     => floatval(($_POST['percent_products'] ?? 0)),
            'extra_data'           => sanitize_text_field(($_POST['extra_data'] ?? '')),
        ];

        $id = intval(($_POST['id'] ?? 0));
        try {
            if ($id > 0) {
                $wpdb->update($tables['staff'], $data, ['id' => $id]);
                // Сохраняем роли, если переданы
                self::handle_staff_roles($id);
                // Синхронизируем subject_roles из roles_table
                self::sync_staff_roles($id);
                Logger::get()->info('Сотрудник обновлён', ['id' => $id]);
                self::update_department_staff_counts();
                wp_send_json_success(['message' => 'Сотрудник обновлён', 'id' => $id]);
            } else {
                $wpdb->insert($tables['staff'], $data);
                $new_id = $wpdb->insert_id;
                // Сохраняем роли, если переданы
                self::handle_staff_roles($new_id);
                // Синхронизируем subject_roles из roles_table
                self::sync_staff_roles($new_id);
                Logger::get()->info('Сотрудник создан', ['id' => $new_id]);
                self::update_department_staff_counts();
                wp_send_json_success(['message' => 'Сотрудник создан', 'id' => $new_id]);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка сохранения сотрудника', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end save_staff()


    public static function delete_staff()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();
        $id     = intval(($_POST['staff_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid staff ID']);
        }

        try {
            $result = $wpdb->delete($tables['staff'], ['id' => $id]);
            if ($result) {
                Logger::get()->info('Сотрудник удалён', ['id' => $id]);
                wp_send_json_success(['message' => 'Сотрудник удалён']);
            } else {
                wp_send_json_error(['message' => 'Не удалось удалить сотрудника']);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка удаления сотрудника', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end delete_staff()


    /**
     * Сохраняет роли сотрудника в myser_subject_roles.
     * Принимает массив ролей из $_POST['roles'][].
     *
     * @param int $staff_id ID сотрудника
     */
    private static function handle_staff_roles($staff_id) {
        global $wpdb;
        $tables      = Database::get_tables();
        $roles_table = $wpdb->prefix . 'myser_subject_roles';

        // Получаем subject_id сотрудника
        $subject_id = $wpdb->get_var($wpdb->prepare(
            "SELECT subject_id FROM {$tables['staff']} WHERE id = %d",
            $staff_id
        ));

        if (!$subject_id) {
            // Создаём subject если нет
            $staff = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tables['staff']} WHERE id = %d",
                $staff_id
            ));
            if (!$staff) return;

            $subjects_table = $wpdb->prefix . 'myser_subjects';
            $parts = explode(' ', ($staff->staff_name ?? ''));
            $last_name   = $parts[0] ?? '';
            $first_name  = $parts[1] ?? '';
            $middle_name = $parts[2] ?? '';
            $display_name = trim($last_name.' '.$first_name.' '.$middle_name);
            $short_name = $last_name;
            if (!empty($first_name)) $short_name .= ' '.mb_substr($first_name, 0, 1).'.';
            if (!empty($middle_name)) $short_name .= mb_substr($middle_name, 0, 1).'.';

            $wpdb->insert($subjects_table, [
                'subject_type' => 'staff',
                'last_name'    => $last_name,
                'first_name'   => $first_name,
                'middle_name'  => $middle_name,
                'display_name' => $display_name,
                'short_name'   => $short_name,
                'email'        => $staff->email ?? '',
                'mobile_phone' => $staff->mobile_phone ?? '',
            ]);
            $subject_id = $wpdb->insert_id;

            // Обновляем subject_id в staff
            $wpdb->update($tables['staff'], ['subject_id' => $subject_id], ['id' => $staff_id]);
        }

        // Если роли переданы — обновляем
        if (isset($_POST['roles']) && is_array($_POST['roles'])) {
            $new_roles = array_map('sanitize_text_field', $_POST['roles']);

            // Получаем текущие роли
            $current_roles = $wpdb->get_col($wpdb->prepare(
                "SELECT role FROM `$roles_table` WHERE subject_id = %d",
                $subject_id
            ));

            // Роли которые нужно добавить
            $to_add = array_diff($new_roles, $current_roles);
            foreach ($to_add as $role) {
                $wpdb->insert($roles_table, [
                    'subject_id'  => $subject_id,
                    'role'        => $role,
                    'is_active'   => 1,
                    'assigned_at' => current_time('mysql'),
                ]);
            }

            // Роли которые нужно убрать (деактивировать)
            $to_remove = array_diff($current_roles, $new_roles);
            foreach ($to_remove as $role) {
                $wpdb->delete($roles_table, [
                    'subject_id' => $subject_id,
                    'role'       => $role,
                ]);
            }

            Logger::get()->info('Роли сотрудника обновлены через handle_staff_roles', [
                'staff_id'   => $staff_id,
                'subject_id' => $subject_id,
                'added'      => $to_add,
                'removed'    => $to_remove,
            ]);
        }

    }//end handle_staff_roles()


    /**
     * Синхронизирует subject_roles в myser_staff из myser_subject_roles.
     * Вызывается после сохранения сотрудника и при изменении ролей.
     *
     * @param int $staff_id ID сотрудника
     */
    private static function sync_staff_roles($staff_id) {
        global $wpdb;
        $tables       = Database::get_tables();
        $roles_table  = $wpdb->prefix . 'myser_subject_roles';

        // Получаем subject_id сотрудника
        $subject_id = $wpdb->get_var($wpdb->prepare(
            "SELECT subject_id FROM {$tables['staff']} WHERE id = %d",
            $staff_id
        ));

        if (!$subject_id) {
            return;
        }

        // Получаем все активные роли через запятую
        $roles = $wpdb->get_var($wpdb->prepare(
            "SELECT GROUP_CONCAT(role ORDER BY role SEPARATOR ', ') FROM `$roles_table` WHERE subject_id = %d AND is_active = 1",
            $subject_id
        ));

        // Обновляем колонку subject_roles в staff
        $wpdb->update(
            $tables['staff'],
            ['subject_roles' => $roles ?: null],
            ['id' => $staff_id]
        );

        Logger::get()->info('Роли сотрудника синхронизированы', [
            'staff_id'   => $staff_id,
            'subject_id' => $subject_id,
            'roles'      => $roles ?: 'none',
        ]);

    }//end sync_staff_roles()


    public static function get_clients()
    {
        self::verify_nonce();
        global $wpdb;
        $tables   = Database::get_tables();
        $page     = intval(($_POST['page'] ?? 1));
        $per_page = intval(($_POST['per_page'] ?? 20));
        $search   = sanitize_text_field(($_POST['search'] ?? ''));
        $offset   = (($page - 1) * $per_page);

        Logger::get()->debug('Запрос клиентов', ['page' => $page, 'search' => $search]);

        $where  = ['1=1'];
        $params = [];
        if (!empty($search)) {
            $where[]  = '(last_name LIKE %s OR first_name LIKE %s OR middle_name LIKE %s OR phone LIKE %s OR email LIKE %s)';
            $like     = '%'.$wpdb->esc_like($search).'%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_clause = implode(' AND ', $where);

        try {
            if (empty($params)) {
                $count_sql = "SELECT COUNT(*) FROM {$tables['clients']} WHERE $where_clause";
                $total     = $wpdb->get_var($count_sql);
            } else {
                $count_sql = $wpdb->prepare("SELECT COUNT(*) FROM {$tables['clients']} WHERE $where_clause", $params);
                $total     = $wpdb->get_var($count_sql);
            }

            if (empty($params)) {
                $sql = "SELECT * FROM {$tables['clients']} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d";
                $sql = $wpdb->prepare($sql, $per_page, $offset);
            } else {
                $sql = $wpdb->prepare(
                    "SELECT * FROM {$tables['clients']} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d",
                    array_merge($params, [$per_page, $offset])
                );
            }

            $clients = $wpdb->get_results($sql);
            Logger::get()->debug('Клиенты получены', ['count' => count($clients), 'total' => $total]);

            wp_send_json_success(
                [
                    'items'        => $clients,
                    'total'        => (int) $total,
                    'pages'        => ceil($total / $per_page),
                    'current_page' => $page,
                ]
            );
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка получения клиентов', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }//end try

    }//end get_clients()


    public static function get_client()
    {
        self::verify_nonce();
        $id = intval(($_POST['client_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => __('Invalid client ID', 'myser')]);
        }

        $client = Database::get_client($id);
        if ($client) {
            wp_send_json_success($client);
        } else {
            wp_send_json_error(['message' => __('Client not found', 'myser')]);
        }

    }//end get_client()


    public static function save_client()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();
        // Тип клиента: из JS приходит 'person'/'company', в БД 'individual'/'company'
        $client_type = sanitize_text_field($_POST['client_type'] ?? 'individual');
        $type_db     = ($client_type === 'person') ? 'individual' : $client_type;

        // Адрес: если передан как строка, используем её, иначе собираем из частей
        $address_raw = sanitize_textarea_field($_POST['address'] ?? '');
        if (empty($address_raw)) {
            $parts = [];
            foreach (['city', 'street', 'house'] as $key) {
                $val = sanitize_text_field($_POST[$key] ?? '');
                if ($val !== '') {
                    $parts[] = $val;
                }
            }
            $address = implode(', ', $parts);
        } else {
            $address = $address_raw;
        }

        // Дополнительные поля — в extra_data (JSON)
        $extra_fields = [
            'company_name'           => sanitize_text_field($_POST['company_name'] ?? ''),
            'legal_form'             => sanitize_text_field($_POST['legal_form'] ?? ''),
            'city'                   => sanitize_text_field($_POST['city'] ?? ''),
            'street'                 => sanitize_text_field($_POST['street'] ?? ''),
            'house'                  => sanitize_text_field($_POST['house'] ?? ''),
            'service_discount_percent' => sanitize_text_field($_POST['service_discount_percent'] ?? ''),
        ];

        $id = intval(($_POST['id'] ?? 0));

        // Если обновляем, сохраняем старый extra_data
        $existing_extra = [];
        if ($id > 0) {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT extra_data FROM {$tables['clients']} WHERE id = %d",
                $id
            ));
            if ($row && $row->extra_data) {
                $decoded = json_decode($row->extra_data, true);
                if (is_array($decoded)) {
                    $existing_extra = $decoded;
                }
            }
        }
        $extra_data_merged = array_merge($existing_extra, $extra_fields);
        $extra_data_json   = !empty($extra_data_merged) ? wp_json_encode($extra_data_merged, JSON_UNESCAPED_UNICODE) : '';

        $data   = [
            'last_name'          => sanitize_text_field(($_POST['last_name'] ?? '')),
            'first_name'         => sanitize_text_field(($_POST['first_name'] ?? '')),
            'middle_name'        => sanitize_text_field(($_POST['middle_name'] ?? '')),
            'phone'              => sanitize_text_field(($_POST['phone'] ?? '')),
            'other_phone'        => sanitize_text_field(($_POST['other_phone'] ?? '')),
            'email'              => sanitize_email(($_POST['email'] ?? '')),
            'address'            => $address,
            'type'               => $type_db,
            'is_problem_client'  => intval(($_POST['is_problem_client'] ?? 0)),
            'notes'              => sanitize_textarea_field(($_POST['notes'] ?? '')),
            'extra_data'         => $extra_data_json,
        ];

        try {
            // Синхронизация с таблицей subjects
            $subject_id = self::sync_subject('client', $data, ($id > 0 ? $id : null));
            if ($subject_id) {
                $data['subject_id'] = $subject_id;
            }

            if ($id > 0) {
                $wpdb->update($tables['clients'], $data, ['id' => $id]);
                // Сохраняем роли — отключено, колонка subject_roles не используется
                // self::handle_client_roles($id);
                Logger::get()->info('Клиент обновлён', ['id' => $id, 'subject_id' => $subject_id]);
                wp_send_json_success(['message' => 'Клиент обновлён', 'id' => $id, 'subject_id' => $subject_id]);
            } else {
                $wpdb->insert($tables['clients'], $data);
                $new_id = $wpdb->insert_id;
                // Сохраняем роли — отключено, колонка subject_roles не используется
                // self::handle_client_roles($new_id);
                Logger::get()->info('Клиент создан', ['id' => $new_id, 'subject_id' => $subject_id]);
                wp_send_json_success(['message' => 'Клиент создан', 'id' => $new_id, 'subject_id' => $subject_id]);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка сохранения клиента', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end save_client()


    public static function delete_client()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();
        $id     = intval(($_POST['client_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => __('Invalid client ID', 'myser')]);
        }

        try {
            $result = $wpdb->delete($tables['clients'], ['id' => $id]);
            if ($result) {
                Logger::get()->info('Клиент удалён', ['id' => $id]);
                wp_send_json_success(['message' => 'Клиент удалён']);
            } else {
                wp_send_json_error(['message' => 'Не удалось удалить клиента']);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка удаления клиента', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end delete_client()


    /**
     * Сохраняет роли клиента в myser_subject_roles и обновляет subject_roles в myser_clients.
     *
     * @param int $client_id ID клиента
     */
    private static function handle_client_roles($client_id) {
        global $wpdb;
        $tables      = Database::get_tables();
        $roles_table = $wpdb->prefix . 'myser_subject_roles';

        $subject_id = $wpdb->get_var($wpdb->prepare(
            "SELECT subject_id FROM {$tables['clients']} WHERE id = %d",
            $client_id
        ));

        if (!$subject_id) return;

        if (isset($_POST['roles']) && is_array($_POST['roles'])) {
            $new_roles = array_map('sanitize_text_field', $_POST['roles']);

            $current_roles = $wpdb->get_col($wpdb->prepare(
                "SELECT role FROM `$roles_table` WHERE subject_id = %d",
                $subject_id
            ));

            // Добавить новые
            $to_add = array_diff($new_roles, $current_roles);
            foreach ($to_add as $role) {
                $wpdb->insert($roles_table, [
                    'subject_id'  => $subject_id,
                    'role'        => $role,
                    'is_active'   => 1,
                    'assigned_at' => current_time('mysql'),
                ]);
            }

            // Удалить лишние
            $to_remove = array_diff($current_roles, $new_roles);
            foreach ($to_remove as $role) {
                $wpdb->delete($roles_table, [
                    'subject_id' => $subject_id,
                    'role'       => $role,
                ]);
            }
        }

        // Синхронизируем subject_roles в clients
        $roles = $wpdb->get_var($wpdb->prepare(
            "SELECT GROUP_CONCAT(role ORDER BY role SEPARATOR ', ') FROM `$roles_table` WHERE subject_id = %d AND is_active = 1",
            $subject_id
        ));

        $wpdb->update(
            $tables['clients'],
            ['subject_roles' => $roles ?: null],
            ['id' => $client_id]
        );

        Logger::get()->info('Роли клиента обновлены', [
            'client_id'  => $client_id,
            'subject_id' => $subject_id,
            'roles'      => $roles ?: 'none',
        ]);

    }//end handle_client_roles()


    public static function get_orders()
    {
        self::verify_nonce();
        global $wpdb;
        $tables   = Database::get_tables();
        $page     = intval(($_POST['page'] ?? 1));
        $per_page = intval(($_POST['per_page'] ?? 20));
        $search   = sanitize_text_field(($_POST['search'] ?? ''));
        $offset   = (($page - 1) * $per_page);

        Logger::get()->debug('Запрос заказов', ['page' => $page, 'search' => $search]);

        $where  = ['1=1'];
        $params = [];
        if (!empty($search)) {
            $where[]  = '(doc_number LIKE %s OR client_complaint LIKE %s)';
            $like     = '%'.$wpdb->esc_like($search).'%';
            $params[] = $like;
            $params[] = $like;
        }

        $where_clause = implode(' AND ', $where);

        try {
            if (empty($params)) {
                $count_sql = "SELECT COUNT(*) FROM {$tables['orders']} WHERE $where_clause";
                $total     = $wpdb->get_var($count_sql);
            } else {
                $count_sql = $wpdb->prepare("SELECT COUNT(*) FROM {$tables['orders']} WHERE $where_clause", $params);
                $total     = $wpdb->get_var($count_sql);
            }

            if (empty($params)) {
                $sql = "SELECT * FROM {$tables['orders']} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d";
                $sql = $wpdb->prepare($sql, $per_page, $offset);
            } else {
                $sql = $wpdb->prepare(
                    "SELECT * FROM {$tables['orders']} WHERE $where_clause ORDER BY id DESC LIMIT %d OFFSET %d",
                    array_merge($params, [$per_page, $offset])
                );
            }

            $orders = $wpdb->get_results($sql);
            Logger::get()->debug('Заказы получены', ['count' => count($orders), 'total' => $total]);

            wp_send_json_success(
                [
                    'items'        => $orders,
                    'total'        => (int) $total,
                    'pages'        => ceil($total / $per_page),
                    'current_page' => $page,
                ]
            );
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка получения заказов', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }//end try

    }//end get_orders()


    public static function get_order()
    {
        self::verify_nonce();
        $id = intval(($_POST['order_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid order ID']);
        }

        $order = Database::get_order($id);
        if ($order) {
            wp_send_json_success($order);
        } else {
            wp_send_json_error(['message' => 'Order not found']);
        }

    }//end get_order()


    public static function save_order()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();

        $doc_number = sanitize_text_field(($_POST['doc_number'] ?? ''));
        if (empty($doc_number)) {
            $doc_number = 'MYS-'.date('Ymd').'-'.rand(1000, 9999);
        }

        $client_id = intval(($_POST['client_id'] ?? 0));

        // Получаем subject_id клиента
        $subject_id = null;
        if ($client_id > 0) {
            $subject_id = $wpdb->get_var($wpdb->prepare(
                "SELECT subject_id FROM {$tables['clients']} WHERE id = %d",
                $client_id
            ));
        }

        $data = [
            'doc_number'          => $doc_number,
            'doc_date'            => current_time('mysql'),
            'client_id'           => $client_id,
            'subject_id'          => $subject_id,
            'device_type'         => sanitize_text_field(($_POST['device_type'] ?? '')),
            'device_manufacturer' => sanitize_text_field(($_POST['device_manufacturer'] ?? '')),
            'device_model'        => sanitize_text_field(($_POST['device_model'] ?? '')),
            'device_serial'       => sanitize_text_field(($_POST['device_serial'] ?? '')),
            'client_complaint'    => sanitize_textarea_field(($_POST['client_complaint'] ?? '')),
            'status_id'           => intval(($_POST['status_id'] ?? 1)),
            'grand_total'         => floatval(($_POST['grand_total'] ?? 0)),
        ];

        $id = intval(($_POST['id'] ?? 0));
        try {
            if ($id > 0) {
                $wpdb->update($tables['orders'], $data, ['id' => $id]);
                Logger::get()->info('Заказ обновлён', ['id' => $id, 'subject_id' => $subject_id]);
                wp_send_json_success(['message' => 'Заказ обновлён', 'id' => $id]);
            } else {
                $wpdb->insert($tables['orders'], $data);
                $new_id = $wpdb->insert_id;
                Logger::get()->info('Заказ создан', ['id' => $new_id, 'doc_number' => $doc_number, 'subject_id' => $subject_id]);
                wp_send_json_success(['message' => 'Заказ создан', 'id' => $new_id]);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка сохранения заказа', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end save_order()


    public static function delete_order()
    {
        self::verify_nonce();
        global $wpdb;
        $tables = Database::get_tables();
        $id     = intval(($_POST['order_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid order ID']);
        }

        try {
            $result = $wpdb->delete($tables['orders'], ['id' => $id]);
            if ($result) {
                Logger::get()->info('Заказ удалён', ['id' => $id]);
                wp_send_json_success(['message' => 'Заказ удалён']);
            } else {
                wp_send_json_error(['message' => 'Не удалось удалить заказ']);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка удаления заказа', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка БД: '.$e->getMessage()]);
        }

    }//end delete_order()


    public static function reboot()
    {
        self::verify_nonce();
        self::check_permissions();
        Logger::get()->info('Запущен ребут плагина через AJAX');
        try {
            include_once MYSER_PLUGIN_DIR.'lib/includes/activator.php';
            Activator::activate();
            Logger::get()->info('Ребут успешно выполнен через AJAX');
            wp_send_json_success(['message' => 'Плагин перезагружен!']);
        } catch (\Exception $e) {
            Logger::get()->critical('Ошибка ребута через AJAX', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка ребута: '.$e->getMessage()]);
        }

    }//end reboot()


    /**
     * Экспорт бекапа
     */
    public static function export_backup()
    {
        self::verify_nonce();
        self::check_permissions();

        $format = sanitize_text_field(($_POST['format'] ?? 'sql'));
        if (!in_array($format, ['sql', 'csv', 'mdb'])) {
            wp_send_json_error(['message' => 'Неверный формат. Доступны: sql, csv, mdb']);
        }

        $backup   = Backup::get();
        $result   = false;
        $filename = '';

        try {
            if ($format === 'sql') {
                $result   = $backup->export_sql();
                $filename = basename($result);
            } else if ($format === 'csv') {
                $result   = $backup->export_csv();
                $filename = basename($result);
            } else if ($format === 'mdb') {
                $result   = $backup->export_mdb();
                $filename = basename($result);
            }

            if ($result) {
                Logger::get()->info('Бекап создан через AJAX', ['format' => $format, 'file' => $filename]);
                wp_send_json_success(
                    [
                        'message'      => 'Бекап создан',
                        'file'         => $filename,
                        'download_url' => admin_url('admin-ajax.php?action=myser_download_backup&file='.urlencode($filename).'&nonce='.wp_create_nonce('myser_download_backup')),
                    ]
                );
            } else {
                wp_send_json_error(['message' => 'Ошибка создания бекапа']);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка экспорта бекапа', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка: '.$e->getMessage()]);
        }//end try

    }//end export_backup()


    /**
     * Импорт бекапа
     */
    public static function import_backup()
    {
        self::verify_nonce();
        self::check_permissions();

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'Файл не загружен или произошла ошибка']);
        }

        $file = $_FILES['backup_file'];
        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (!in_array($ext, ['sql', 'zip', 'mdb'])) {
            wp_send_json_error(['message' => 'Неподдерживаемый формат. Используйте .sql, .zip или .mdb']);
        }

        $backup     = Backup::get();
        $upload_dir = $backup->get_backup_dir();
        $dest       = $upload_dir.basename($file['name']);

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Logger::get()->error('Не удалось переместить загруженный файл', ['file' => $file['name']]);
            wp_send_json_error(['message' => 'Не удалось сохранить файл']);
        }

        try {
            $success = false;
            if ($ext === 'sql') {
                $success = $backup->import_sql($dest);
            } else if ($ext === 'zip') {
                $success = $backup->import_csv($dest);
            } else if ($ext === 'mdb') {
                $success = $backup->import_mdb($dest);
            }

            if ($success) {
                Logger::get()->info('Бекап импортирован', ['file' => $file['name']]);
                wp_send_json_success(['message' => 'Бекап успешно импортирован']);
            } else {
                wp_send_json_error(['message' => 'Ошибка импорта бекапа']);
            }
        } catch (\Exception $e) {
            Logger::get()->error('Ошибка импорта бекапа', ['error' => $e->getMessage()]);
            wp_send_json_error(['message' => 'Ошибка: '.$e->getMessage()]);
        }

    }//end import_backup()


    /**
     * Список бекапов
     */
    public static function list_backups()
    {
        self::verify_nonce();
        self::check_permissions();

        $backup = Backup::get();
        $list   = $backup->list_backups();

        wp_send_json_success(
            [
                'items' => $list,
                'total' => count($list),
            ]
        );

    }//end list_backups()


    /**
     * Удаление бекапа
     */
    public static function delete_backup()
    {
        self::verify_nonce();
        self::check_permissions();

        $filename = sanitize_file_name(($_POST['filename'] ?? ''));
        if (empty($filename)) {
            wp_send_json_error(['message' => 'Имя файла не указано']);
        }

        $backup = Backup::get();
        if ($backup->delete_backup($filename)) {
            Logger::get()->info('Бекап удалён через AJAX', ['file' => $filename]);
            wp_send_json_success(['message' => 'Бекап удалён']);
        } else {
            wp_send_json_error(['message' => 'Не удалось удалить бекап']);
        }

    }//end delete_backup()


    /**
     * Скачивание бекапа (не AJAX)
     */
    public static function download_backup()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }

        if (!wp_verify_nonce(($_GET['nonce'] ?? ''), 'myser_download_backup')) {
            wp_die('Nonce verification failed');
        }

        $filename = sanitize_file_name(($_GET['file'] ?? ''));
        if (empty($filename)) {
            wp_die('Имя файла не указано');
        }

        $backup   = Backup::get();
        $filepath = $backup->get_backup_dir().$filename;

        if (!file_exists($filepath)) {
            wp_die('Файл не найден');
        }

        // Отправляем файл на скачивание
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Content-Length: '.filesize($filepath));
        readfile($filepath);
        exit;

    }//end download_backup()


    /**
     * Удаление нескольких бекапов
     */
    public static function delete_backups()
    {
        self::verify_nonce();
        self::check_permissions();

        $files = isset($_POST['files']) ? $_POST['files'] : [];
        if (!is_array($files) || empty($files)) {
            wp_send_json_error(['message' => 'Не выбрано ни одного файла']);
        }

        $backup = Backup::get();
        $result = $backup->delete_backups($files);

        wp_send_json_success($result);

    }//end delete_backups()


    /**
     * Обработчик кастомного удаления плагина
     */
    public static function custom_uninstall()
    {
        self::verify_nonce();
        self::check_permissions();

        $action_mode   = sanitize_text_field($_POST['action_mode']);
        $create_backup = (int) $_POST['create_backup'];

        // Если выбран режим "Оставить данные" — просто удаляем плагин
        if ($action_mode === 'keep') {
            // Устанавливаем глобальный флаг для uninstall.php
            $GLOBALS['myser_keep_data'] = true;
            // Запускаем удаление плагина через WordPress
            $deleted = delete_plugins(['myser/myser.php']);
            if (is_wp_error($deleted)) {
                wp_send_json_error(['message' => $deleted->get_error_message()]);
            } else {
                wp_send_json_success(['redirect' => admin_url('plugins.php?deleted=true')]);
            }
        }

        // Если "Удалить все данные"
        if ($action_mode === 'delete') {
            // Создаём бекап, если нужно
            if ($create_backup) {
                // Используем существующий класс Backup (если доступен)
                if (class_exists('MySer\Backup')) {
                    $backup = new MySer\Backup();
                    $result = $backup->export_backup('sql');
                    // SQL по умолчанию
                    if (!$result) {
                        wp_send_json_error(['message' => 'Не удалось создать бекап.']);
                    }
                } else {
                    // Если класс не найден, просто продолжаем без бекапа
                    error_log('MySer: Класс Backup не найден для создания бекапа при удалении.');
                }
            }

            // Удаляем таблицы
            include_once MYSER_PLUGIN_DIR.'lib/includes/database.php';
            MySer\Database::drop_tables();

            // Теперь удаляем плагин, данные уже удалены
            // Устанавливаем флаг, чтобы uninstall.php не удалял таблицы повторно
            $GLOBALS['myser_keep_data'] = true;
            $deleted                    = delete_plugins(['myser/myser.php']);
            if (is_wp_error($deleted)) {
                wp_send_json_error(['message' => $deleted->get_error_message()]);
            } else {
                wp_send_json_success(['redirect' => admin_url('plugins.php?deleted=true')]);
            }
        }//end if

        wp_send_json_error(['message' => 'Неизвестный режим']);

    }//end custom_uninstall()




    // ──────────────────────────────────────────────
    //  Сетки заработка сотрудников
    // ──────────────────────────────────────────────

    /**
     * Получить список сеток заработка
     */
    public static function get_salary_grids()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $table = $wpdb->prefix . 'myser_salary_grids';
        $grids = $wpdb->get_results("SELECT * FROM `$table` ORDER BY sort_order ASC, id ASC");
        wp_send_json_success($grids ?: []);
    }

    /**
     * Сохранить/обновить сетку
     */
    public static function save_salary_grid()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $table = $wpdb->prefix . 'myser_salary_grids';

        $id      = intval(($_POST['grid_id'] ?? 0));
        $name    = sanitize_text_field(($_POST['name'] ?? ''));
        $percent = floatval(($_POST['percent'] ?? 0));
        $sort    = intval(($_POST['sort_order'] ?? 0));

        if (empty($name)) {
            wp_send_json_error(['message' => 'Название сетки обязательно']);
        }

        $data = [
            'name'       => $name,
            'percent'    => $percent,
            'sort_order' => $sort,
        ];

        if ($id > 0) {
            $wpdb->update($table, $data, ['id' => $id]);
            wp_send_json_success(['message' => 'Сетка обновлена', 'id' => $id]);
        } else {
            $wpdb->insert($table, $data);
            wp_send_json_success(['message' => 'Сетка создана', 'id' => $wpdb->insert_id]);
        }
    }

    /**
     * Удалить сетку
     */
    public static function delete_salary_grid()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $id = intval(($_POST['grid_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Неверный ID сетки']);
        }

        $grids_table    = $wpdb->prefix . 'myser_salary_grids';
        $staff_table    = $wpdb->prefix . 'myser_staff_salary_grids';

        // Удаляем начисления
        $wpdb->delete($staff_table, ['grid_id' => $id]);
        // Удаляем сетку
        $wpdb->delete($grids_table, ['id' => $id]);

        wp_send_json_success(['message' => 'Сетка удалена']);
    }

    /**
     * Получить список сотрудников (id, staff_name)
     */
    public static function get_staff_list()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $table = $wpdb->prefix . 'myser_staff';
        $staff = $wpdb->get_results("SELECT id, staff_name FROM `$table` ORDER BY staff_name ASC");
        wp_send_json_success($staff ?: []);
    }

    /**
     * Получить все начисления сотрудников
     */
    public static function get_staff_assignments()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $staff_table     = $wpdb->prefix . 'myser_staff';
        $grids_table     = $wpdb->prefix . 'myser_salary_grids';
        $assign_table    = $wpdb->prefix . 'myser_staff_salary_grids';

        $assignments = $wpdb->get_results("
            SELECT
                a.id,
                a.staff_id,
                s.staff_name,
                a.grid_id,
                g.name AS grid_name,
                g.percent AS grid_percent,
                a.condition_type,
                a.condition_value,
                a.custom_percent
            FROM `$assign_table` a
            LEFT JOIN `$staff_table` s ON s.id = a.staff_id
            LEFT JOIN `$grids_table` g ON g.id = a.grid_id
            ORDER BY s.staff_name ASC, g.sort_order ASC
        ");

        wp_send_json_success($assignments ?: []);
    }

    /**
     * Сохранить начисление сотрудника на сетку
     */
    public static function save_staff_assignment()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $table = $wpdb->prefix . 'myser_staff_salary_grids';

        $id              = intval(($_POST['assignment_id'] ?? 0));
        $staff_id        = intval(($_POST['staff_id'] ?? 0));
        $grid_id         = intval(($_POST['grid_id'] ?? 0));
        $condition_type_raw = $_POST['condition_type'] ?? 'custom';
        if (is_array($condition_type_raw)) {
            $condition_type = implode(',', array_map('sanitize_text_field', $condition_type_raw));
        } else {
            $condition_type = sanitize_text_field($condition_type_raw);
        }
        $condition_value = sanitize_text_field(($_POST['condition_value'] ?? ''));
        $custom_percent  = $_POST['custom_percent'] !== '' ? floatval($_POST['custom_percent']) : null;

        if ($staff_id <= 0 || $grid_id <= 0) {
            wp_send_json_error(['message' => 'Сотрудник и сетка обязательны']);
        }

        $data = [
            'staff_id'        => $staff_id,
            'grid_id'         => $grid_id,
            'condition_type'  => $condition_type,
            'condition_value' => $condition_value ?: null,
            'custom_percent'  => $custom_percent,
        ];

        if ($id > 0) {
            $wpdb->update($table, $data, ['id' => $id]);
            wp_send_json_success(['message' => 'Назначение обновлено', 'id' => $id]);
        } else {
            $wpdb->insert($table, $data);
            wp_send_json_success(['message' => 'Назначение создано', 'id' => $wpdb->insert_id]);
        }
    }

    /**
     * Удалить начисление
     */
    public static function delete_staff_assignment()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $id = intval(($_POST['assignment_id'] ?? 0));
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Неверный ID начисления']);
        }
        $table = $wpdb->prefix . 'myser_staff_salary_grids';
        $wpdb->delete($table, ['id' => $id]);
        wp_send_json_success(['message' => 'Назначение удалено']);
    }

    /**
     * Получить список подразделений
     */
    public static function get_departments()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $table = $wpdb->prefix . 'myser_departments';
        $staff_table = $wpdb->prefix . 'myser_staff';
        $results = $wpdb->get_results(
            "SELECT d.*, " .
            "(SELECT COUNT(*) FROM `$staff_table` s WHERE JSON_CONTAINS(s.department, CAST(d.id AS CHAR))) AS staff_count " .
            "FROM `$table` d ORDER BY d.short_name ASC",
            ARRAY_A
        );
        wp_send_json_success($results ?: []);
    }

    /**
     * Получить одно подразделение по ID
     */
    public static function get_department()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $id = intval($_POST['dep_id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Не указан ID подразделения']);
        }
        $table = $wpdb->prefix . 'myser_departments';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE id = %d", $id), ARRAY_A);
        if ($row) {
            wp_send_json_success($row);
        } else {
            wp_send_json_error(['message' => 'Подразделение не найдено']);
        }
    }

    /**
     * Сохранить (добавить/обновить) подразделение
     */
    public static function save_department()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $table = $wpdb->prefix . 'myser_departments';
        $id = intval($_POST['dep_id'] ?? 0);

        $data = [
            'short_name'        => sanitize_text_field($_POST['short_name'] ?? ''),
            'full_name'         => sanitize_text_field($_POST['full_name'] ?? ''),
            'order_prefix'      => sanitize_text_field($_POST['order_prefix'] ?? ''),
            'city'              => sanitize_text_field($_POST['city'] ?? ''),
            'address'           => sanitize_textarea_field($_POST['address'] ?? ''),
            'address_fact'      => sanitize_textarea_field($_POST['address_fact'] ?? ''),
            'work_phone'        => sanitize_text_field($_POST['work_phone'] ?? ''),
            'email'             => sanitize_email($_POST['email'] ?? ''),
            'inn'               => sanitize_text_field($_POST['inn'] ?? ''),
            'kpp'               => sanitize_text_field($_POST['kpp'] ?? ''),
            'ogrn'              => sanitize_text_field($_POST['ogrn'] ?? ''),
            'okpo'              => sanitize_text_field($_POST['okpo'] ?? ''),
            'okvd'              => sanitize_text_field($_POST['okvd'] ?? ''),
            'bank_account'      => sanitize_text_field($_POST['bank_account'] ?? ''),
            'bank_name'         => sanitize_text_field($_POST['bank_name'] ?? ''),
            'bank_bic'          => sanitize_text_field($_POST['bank_bic'] ?? ''),
            'bank_corr'         => sanitize_text_field($_POST['bank_corr'] ?? ''),
            'director'          => sanitize_text_field($_POST['director'] ?? ''),
            'director_full'     => sanitize_text_field($_POST['director_full'] ?? ''),
            'director_position' => sanitize_text_field($_POST['director_position'] ?? ''),
            'director_vlice'    => sanitize_text_field($_POST['director_vlice'] ?? ''),
            'accountant'        => sanitize_text_field($_POST['accountant'] ?? ''),
            'notes'             => sanitize_textarea_field($_POST['notes'] ?? ''),
            'status'            => intval($_POST['status'] ?? 1),
        ];

        if ($id > 0) {
            $wpdb->update($table, $data, ['id' => $id]);
            wp_send_json_success(['message' => 'Подразделение обновлено', 'id' => $id]);
        } else {
            $wpdb->insert($table, $data);
            wp_send_json_success(['message' => 'Подразделение добавлено', 'id' => $wpdb->insert_id]);
        }
    }

    /**
     * Удалить подразделение
     */
    public static function delete_department()
    {
        self::verify_nonce();
        self::check_permissions();
        global $wpdb;
        $id = intval($_POST['dep_id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            wp_send_json_error(['message' => 'Не указан ID подразделения']);
        }
        $table = $wpdb->prefix . 'myser_departments';
        $wpdb->delete($table, ['id' => $id]);
        wp_send_json_success(['message' => 'Подразделение удалено']);
    }

    /**
     * Пересчитывает и сохраняет количество сотрудников для каждого подразделения
     */
    private static function update_department_staff_counts()
    {
        global $wpdb;
        $dept_table = $wpdb->prefix . 'myser_departments';
        $staff_table = $wpdb->prefix . 'myser_staff';

        $departments = $wpdb->get_results("SELECT id FROM `$dept_table`", ARRAY_A);
        foreach ($departments as $dept) {
            $dept_id = $dept['id'];
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `$staff_table` WHERE JSON_CONTAINS(department, %s)",
                (string) $dept_id
            ));
            $wpdb->update($dept_table, ['staff_count' => (int) $count], ['id' => $dept_id]);
        }
    }


}//end class
