<?php
defined('ABSPATH') || exit;
$settings = get_option('myser_settings', []);
?>
<div class="wrap myser-wrap">
    <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;">
            <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/settings.svg" class="myser-icon" alt="">
            <?php _e('Настройки', 'myser'); ?>
        </h1>
        <div style="font-size: 0.9em; color: #0073aa; text-align: center; flex: 1;">
            MySer v<?php echo MYSER_VERSION; ?>
        </div>
        <div style="text-align: right; min-width: 150px;">
            <button type="button" class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ <?php _e('Ребут плагина', 'myser'); ?></button>
            <span id="myser-reboot-status" style="display: block; margin-top: 4px; font-size: 12px;"></span>
        </div>
    </div>

    <nav class="nav-tab-wrapper myser-tabs">
        <a href="#" class="nav-tab nav-tab-active" data-tab="system">⚙️ Системные</a>
        <a href="#" class="nav-tab" data-tab="department"> Подразделения</a>
        <a href="#" class="nav-tab" data-tab="orders"> Заказы</a>
        <a href="#" class="nav-tab" data-tab="finance"> Финансы</a>
        <a href="#" class="nav-tab" data-tab="appearance"> Оформление</a>
    </nav>

    <form method="post" action="options.php">
        <?php settings_fields('myser_settings_group'); ?>

        <!-- ⚙️ Системные -->
        <div class="tab-content" id="tab-system">
            <table class="form-table">
                <tr>
                    <th><label for="uninstall_behavior"><?php _e('При удалении плагина', 'myser'); ?></label></th>
                    <td>
                        <select id="uninstall_behavior" name="myser_settings[uninstall_behavior]">
                            <option value="keep" <?php selected(($settings['uninstall_behavior'] ?? 'keep'), 'keep'); ?>><?php _e('Оставить данные', 'myser'); ?></option>
                            <option value="delete" <?php selected(($settings['uninstall_behavior'] ?? 'keep'), 'delete'); ?>><?php _e('Удалить все данные', 'myser'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="log_level"><?php _e('Уровень логирования', 'myser'); ?></label></th>
                    <td>
                        <select id="log_level" name="myser_settings[log_level]">
                            <?php
                            $levels = [
                                'debug'    => 'Отладка (всё)',
                                'info'     => 'Инфо + предупреждения + ошибки',
                                'warning'  => 'Предупреждения + ошибки',
                                'error'    => 'Только ошибки',
                                'critical' => 'Только критические',
                                'off'      => 'Выключено',
                            ];
                            foreach ($levels as $val => $label) {
                                echo '<option value="' . $val . '" ' . selected(($settings['log_level'] ?? 'error'), $val, false) . '>' . $label . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="log_retention_days"><?php _e('Хранение логов (дней)', 'myser'); ?></label></th>
                    <td><input type="number" id="log_retention_days" name="myser_settings[log_retention_days]" value="<?php echo esc_attr(($settings['log_retention_days'] ?? 7)); ?>" min="1" max="365"></td>
                </tr>
            </table>
        </div>

                <!--  Подразделения -->
         <div class="tab-content" id="tab-department" style="display:none;">
            <div style="margin-bottom:10px;">
                <button type="button" class="button button-primary" id="add-department-btn">+ Добавить подразделение</button>
            </div>
            <table class="wp-list-table widefat striped" id="departments-table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Префикс заказа</th>
                        <th>Город</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Сотрудников</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7">Загрузка...</td></tr>
                </tbody>
            </table>
         </div>

         <!-- Модалка подразделения -->
         <div id="department-modal" class="myser-modal" style="display:none;">
            <div class="myser-modal-content" style="max-width:600px;">
                <h2 id="department-modal-title">Добавить подразделение</h2>
                <label>Краткое название <span style="color:red;">*</span></label>
                <input type="text" id="dep-short-name" class="widefat" style="margin-bottom:8px;" placeholder="Например: Центральный">
                <label>Полное наименование</label>
                <input type="text" id="dep-full-name" class="widefat" style="margin-bottom:8px;" placeholder="ООО «Сервисный центр»">
                <label>Город</label>
                <input type="text" id="dep-city" class="widefat" style="margin-bottom:8px;">
                <label>Юридический адрес</label>
                <textarea id="dep-address" class="widefat" style="margin-bottom:8px;" rows="2"></textarea>
                <label>Фактический адрес</label>
                <textarea id="dep-address-fact" class="widefat" style="margin-bottom:8px;" rows="2"></textarea>
                <label>Рабочий телефон</label>
                <input type="text" id="dep-work-phone" class="widefat" style="margin-bottom:8px;">
                <label>Email</label>
                <input type="email" id="dep-email" class="widefat" style="margin-bottom:8px;">
                <label>Реквизиты</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:8px;">
                    <input type="text" id="dep-inn" class="widefat" placeholder="ИНН">
                    <input type="text" id="dep-kpp" class="widefat" placeholder="КПП">
                    <input type="text" id="dep-ogrn" class="widefat" placeholder="ОГРН">
                    <input type="text" id="dep-okpo" class="widefat" placeholder="ОКПО">
                    <input type="text" id="dep-okvd" class="widefat" placeholder="ОКВД">
                </div>
                <label>Банковские реквизиты</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; margin-bottom:8px;">
                    <input type="text" id="dep-bank-account" class="widefat" placeholder="Р/с">
                    <input type="text" id="dep-bank-name" class="widefat" placeholder="Банк">
                    <input type="text" id="dep-bank-corr" class="widefat" placeholder="К/с">
                    <input type="text" id="dep-bank-bic" class="widefat" placeholder="БИК">
                </div>
                <label>Руководитель (кратко)</label>
                <input type="text" id="dep-director" class="widefat" style="margin-bottom:8px;" placeholder="Иванов И.И.">
                <label>Руководитель (ФИО полностью)</label>
                <input type="text" id="dep-director-full" class="widefat" style="margin-bottom:8px;" placeholder="Иванов Иван Иванович">
                <label>Должность руководителя</label>
                <input type="text" id="dep-director-position" class="widefat" style="margin-bottom:8px;" placeholder="Генеральный директор">
                <label>Бухгалтер</label>
                <input type="text" id="dep-accountant" class="widefat" style="margin-bottom:8px;">
                <label>Примечания</label>
                <textarea id="dep-notes" class="widefat" style="margin-bottom:8px;" rows="2"></textarea>
                <label>Префикс заказа</label>
                <input type="text" id="dep-order-prefix" class="widefat" style="margin-bottom:8px;" placeholder="Например: МСК" maxlength="10">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                    <div>
                        <label>Тип</label>
                        <select id="dep-type" class="widefat">
                            <option value="head">Головной (центральный)</option>
                            <option value="branch">Филиал</option>
                            <option value="remote">Удалёнка</option>
                        </select>
                    </div>
                    <div>
                        <label>Статус</label>
                        <select id="dep-status" class="widefat">
                            <option value="1">Активно</option>
                            <option value="0">Неактивно</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="dep-id" value="0">
                <div style="margin-top:12px;">
                    <button type="button" class="button button-primary" id="save-department-btn">Сохранить</button>
                    <button type="button" class="button" id="close-department-modal">Отмена</button>
                </div>
            </div>
         </div>

<!--  Заказы -->
        <div class="tab-content" id="tab-orders" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="order_numbering"><?php _e('Вид нумерации', 'myser'); ?></label></th>
                    <td>
                        <select id="order_numbering" name="myser_settings[order_numbering]">
                            <option value="prefix5" <?php selected(($settings['order_numbering'] ?? 'prefix5'), 'prefix5'); ?>>***-00001 (префикс + 5 цифр)</option>
                            <option value="prefix4" <?php selected(($settings['order_numbering'] ?? 'prefix5'), 'prefix4'); ?>>***-0001 (префикс + 4 цифры)</option>
                            <option value="year_prefix5" <?php selected(($settings['order_numbering'] ?? 'prefix5'), 'year_prefix5'); ?>>***-25-00001 (год + 5 цифр)</option>
                            <option value="month_prefix4" <?php selected(($settings['order_numbering'] ?? 'prefix5'), 'month_prefix4'); ?>>***-2508-0001 (годмесяц + 4 цифр)</option>
                            <option value="date_prefix2" <?php selected(($settings['order_numbering'] ?? 'prefix5'), 'date_prefix2'); ?>>***-250801-01 (годмесяцдата + 2 цифр)</option>
                            <option value="plain6" <?php selected(($settings['order_numbering'] ?? 'prefix5'), 'plain6'); ?>>000001 (только 6 цифр)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="order_prefix"><?php _e('Префикс', 'myser'); ?></label></th>
                    <td><input type="text" id="order_prefix" name="myser_settings[order_prefix]" value="<?php echo esc_attr(($settings['order_prefix'] ?? 'MYS')); ?>" class="regular-text" style="width:100px;"></td>
                </tr>
                <tr>
                    <th><label for="first_order_number"><?php _e('Первый наряд', 'myser'); ?></label></th>
                    <td><input type="number" id="first_order_number" name="myser_settings[first_order_number]" value="<?php echo esc_attr(($settings['first_order_number'] ?? 1)); ?>" class="small-text" min="1"></td>
                </tr>
                <tr>
                    <th><label for="items_per_page"><?php _e('Записей на страницу', 'myser'); ?></label></th>
                    <td><input type="number" id="items_per_page" name="myser_settings[items_per_page]" value="<?php echo esc_attr(($settings['items_per_page'] ?? 20)); ?>" min="5" max="100"></td>
                </tr>
            </table>
        </div>

        <!--  Финансы -->
        <div class="tab-content" id="tab-finance" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="currency"><?php _e('Валюта', 'myser'); ?></label></th>
                    <td>
                        <select id="currency" name="myser_settings[currency]">
                            <option value="RUB" <?php selected(($settings['currency'] ?? 'RUB'), 'RUB'); ?>>RUB (₽)</option>
                            <option value="USD" <?php selected(($settings['currency'] ?? 'RUB'), 'USD'); ?>>USD ($)</option>
                            <option value="EUR" <?php selected(($settings['currency'] ?? 'RUB'), 'EUR'); ?>>EUR (€)</option>
                        </select>
                        &nbsp;
                        <label for="tax_rate"><?php _e('Налог (%)', 'myser'); ?></label>
                        <input type="number" id="tax_rate" name="myser_settings[tax_rate]" value="<?php echo esc_attr(($settings['tax_rate'] ?? 0)); ?>" step="0.01" min="0" max="100" style="width:80px;">
                    </td>
                </tr>
            </table>
            <h2 style="margin-top: 30px;"><?php _e('Сетки ставок', 'myser'); ?></h2>
            <p class="description">Настройте проценты для расчета зарплаты сотрудников. Одна и та же должность может оплачиваться по разным сеткам (по выслуге, КУР и т.д.).</p>
            <button type="button" class="button button-primary" id="add-salary-grid-btn" style="margin-bottom: 10px;">Добавить сетку</button>
            <table class="widefat fixed striped" id="salary-grids-table" style="max-width: 600px;">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th style="width: 100px;">Процент</th>
                        <th style="width: 120px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="3">Загрузка...</td></tr>
                </tbody>
            </table>

            <!-- Модалка для сеток -->
            <div id="grid-modal" class="myser-modal" style="display:none;">
                <div class="myser-modal-content" style="max-width:400px;">
                    <h2 id="grid-modal-title">Добавить сетку</h2>
                    <label>Название</label>
                    <input type="text" id="grid-name" class="widefat" style="margin-bottom:8px;">
                    <label>Процент (%)</label>
                    <input type="number" id="grid-percent" class="widefat" step="0.01" min="0" max="100" style="margin-bottom:8px;">
                    <label>Порядок сортировки</label>
                    <input type="number" id="grid-sort" class="widefat" value="0" style="margin-bottom:8px;">
                    <input type="hidden" id="grid-id" value="0">
                    <div style="margin-top:12px;">
                        <button type="button" class="button button-primary" id="save-grid-btn">Сохранить</button>
                        <button type="button" class="button" id="close-grid-modal">Отмена</button>
                    </div>
                </div>
            </div>

            <h2 style="margin-top: 30px;"><?php _e('Начисления сотрудникам', 'myser'); ?></h2>
            <p class="description">Привяжите сетки ставок к сотрудникам с учётом условий (выслуга, КУР или вручную).</p>
            <button type="button" class="button button-primary" id="add-assignment-btn" style="margin-bottom: 10px;">Добавить начисления</button>
            <table class="widefat fixed striped" id="staff-assignments-table" style="max-width: 800px;">
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th>Сетка</th>
                        <th>Условие</th>
                        <th style="width: 80px;">Процент</th>
                        <th style="width: 120px;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5">Загрузка...</td></tr>
                </tbody>
            </table>

            <!-- Модалка для начислений -->
            <div id="assignment-modal" class="myser-modal" style="display:none;">
                <div class="myser-modal-content" style="max-width:450px;">
                    <h2 id="assignment-modal-title">Добавить начисления</h2>
                    <label>Сотрудник</label>
                    <select id="assignment-staff" class="widefat" style="margin-bottom:8px;"><option value="">Выберите сотрудника...</option></select>
                    <label>Сетка</label>
                    <select id="assignment-grid" class="widefat" style="margin-bottom:8px;"><option value="">Выберите сетку...</option></select>
                    <label>Условие <small>(Ctrl+клик для множественного выбора)</small></label>
                    <select id="assignment-condition" class="widefat" multiple size="3" style="margin-bottom:8px;">
                        <option value="custom">Вручную</option>
                        <option value="seniority">По выслуге</option>
                        <option value="kur">КУР</option>
                    </select>
                    <label>Значение условия</label>
                    <input type="text" id="assignment-value" class="widefat" style="margin-bottom:8px;" placeholder="Например: 3 (года), 1.5 (коэф.)">
                    <label>Свой % (переопределение)</label>
                    <input type="number" id="assignment-percent" class="widefat" step="0.01" min="0" max="100" style="margin-bottom:8px;" placeholder="Оставьте пустым для % сетки">
                    <input type="hidden" id="assignment-id" value="0">
                    <div style="margin-top:12px;">
                        <button type="button" class="button button-primary" id="save-assignment-btn">Сохранить</button>
                        <button type="button" class="button" id="close-assignment-modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>

        <!--  Оформление -->
        <div class="tab-content" id="tab-appearance" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="theme_primary"><?php _e('Основной цвет', 'myser'); ?></label></th>
                    <td><input type="color" id="theme_primary" name="myser_settings[theme_primary]" value="<?php echo esc_attr(($settings['theme_primary'] ?? '#0073aa')); ?>"></td>
                </tr>
                <tr>
                    <th><label for="theme_font"><?php _e('Шрифт', 'myser'); ?></label></th>
                    <td>
                        <select id="theme_font" name="myser_settings[theme_font]">
                            <option value="inherit" <?php selected(($settings['theme_font'] ?? 'inherit'), 'inherit'); ?>>По умолчанию</option>
                            <option value="Arial" <?php selected(($settings['theme_font'] ?? 'inherit'), 'Arial'); ?>>Arial</option>
                            <option value="Roboto" <?php selected(($settings['theme_font'] ?? 'inherit'), 'Roboto'); ?>>Roboto</option>
                            <option value="Open Sans" <?php selected(($settings['theme_font'] ?? 'inherit'), 'Open Sans'); ?>>Open Sans</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="table_rows"><?php _e('Количество строк таблиц', 'myser'); ?></label></th>
                    <td><input type="number" id="table_rows" name="myser_settings[table_rows]" value="<?php echo esc_attr(($settings['table_rows'] ?? 20)); ?>" min="5" max="100"></td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Сохранить настройки', 'myser')); ?>
    </form>
</div>

<style>
	/* Toast-уведомления */
	#myser-toast-container {
		position: fixed;
		top: 32px;
		right: 20px;
		z-index: 999999;
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.myser-toast {
		padding: 12px 20px;
		border-radius: 4px;
		color: #fff;
		font-size: 14px;
		box-shadow: 0 2px 8px rgba(0,0,0,0.2);
		animation: myserToastIn 0.3s ease-out;
		max-width: 400px;
		word-wrap: break-word;
	}
	.myser-toast.success { background: #46b450; }
	.myser-toast.error { background: #dc3232; }
	.myser-toast.info { background: #0073aa; }
	@keyframes myserToastIn {
		from { opacity: 0; transform: translateX(50px); }
		to { opacity: 1; transform: translateX(0); }
	}
	@keyframes myserToastOut {
		from { opacity: 1; transform: translateX(0); }
		to { opacity: 0; transform: translateX(50px); }
	}
</style>
<div id="myser-toast-container"></div>

