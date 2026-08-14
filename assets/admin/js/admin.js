jQuery(document).ready(
    function ($) {
        console.log('MySer Admin JS loaded');
    }
);

// Функция ребута плагина через AJAX
function myser_reboot_plugin()
{
    if (!confirm('Перезагрузить плагин? Базы данных не затрагиваются.')) {
        return;
    }

    var $btn    = jQuery('#myser-reboot-btn');
    var $status = jQuery('#myser-reboot-status');
    $btn.prop('disabled', true).text('Перезагрузка...');
    $status.html('<span style="color:#0073aa;">Выполняется...</span>');

    jQuery.post(
        myser_ajax.ajaxurl,
        {
            action: 'myser_reboot',
            nonce: myser_ajax.nonce
        },
        function (response) {
            if (response.success) {
                $status.html('<span style="color:#46b450;">Готово! Плагин перезагружен.</span>');
                setTimeout(
                    function () {
                        $status.html('');
                        $btn.prop('disabled', false).html('♻️ Ребут плагина');
                    },
                    3000
                );
            } else {
                $status.html('<span style="color:#dc3232;">Ошибка: ' + (response.data.message || 'Неизвестная ошибка') + '</span>');
                $btn.prop('disabled', false).html('♻️ Ребут плагина');
            }
        }
    ).fail(
        function () {
            $status.html('<span style="color:#dc3232;">Ошибка соединения</span>');
            $btn.prop('disabled', false).html('♻️ Ребут плагина');
        }
    );

}

// Глобальные функции для использования в шаблонах
function myser_show_loading(selector)
{
    jQuery(selector).html('<tr><td colspan="10">Загрузка...</td></tr>');

}

function myser_show_error(message)
{
    alert('Ошибка: ' + message);

}
// ========================================
// Инфо-карточка: общая функция печати
// ========================================
function myser_show_info_card(title, rows, footer) {
    var footerHtml = footer || '';
    var printContent = '<div style="font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 780px; padding: 20px;">';
    printContent += '<h2 style="margin: 0 0 12px; color: #1d2327; font-size: 20px; border-bottom: 2px solid #0073aa; padding-bottom: 8px;">' + title + '</h2>';
    printContent += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px;">';
    for (var i = 0; i < rows.length; i++) {
        printContent += '<div style="display: flex; padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px;">';
        printContent += '<span style="color: #888; min-width: 130px; flex-shrink: 0;">' + rows[i][0] + ':</span>';
        printContent += '<span style="color: #1d2327; font-weight: 500;">' + (rows[i][1] || '—') + '</span></div>';
    }
    printContent += '</div>';
    if (footerHtml) printContent += '<div style="margin-top: 15px; color: #999; font-size: 12px;">' + footerHtml + '</div>';
    printContent += '</div>';

    var w = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title + '</title></head><body style="margin: 0;">');
    w.document.write(printContent);
    w.document.write('<div style="padding: 0 20px 20px;"><button onclick="window.print()" style="padding: 10px 24px; background: #0073aa; color: #fff; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;"><span class="dashicons dashicons-printer" style="vertical-align: middle;"></span> Печать</button></div>');
    w.document.write('</body></html>');
    w.document.close();
}

function myser_show_info_modal(title, rows, footer) {
    var html = '<div id="myser-info-modal-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100000; display:flex; justify-content:center; align-items:center;">';
    html += '<div style="background:#fff; border-radius:10px; padding:0; width:750px; max-width:95%; max-height:85vh; overflow-y:auto; box-shadow:0 8px 30px rgba(0,0,0,0.25);">';
    html += '<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 20px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:1;">';
    html += '<h3 style="margin:0; font-size:17px; color:#1d2327;"> ' + title + '</h3>';
    html += '<div style="display:flex; gap:8px;">';
    html += '<button onclick="jQuery(\'#myser-info-modal-overlay\').remove()" class="button" style="padding:6px 12px; font-size:16px; line-height:1;" title="Закрыть"><span class="dashicons dashicons-exit"></span></button>';
    html += '<button onclick="myser_show_info_card(\'' + title.replace(/'/g, "\\'") + '\', myser_info_card_data, \'' + (footer || '').replace(/'/g, "\\'") + '\')" class="button button-primary" style="padding:6px 12px; font-size:16px; line-height:1;" title="Печать"><span class="dashicons dashicons-printer"></span></button>';
    html += '</div></div>';
    html += '<div style="padding:15px 20px;">';
    html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2px 24px;">';
    for (var i = 0; i < rows.length; i++) {
        html += '<div style="display: flex; padding: 7px 0; border-bottom: 1px solid #f5f5f5; font-size: 13px; align-items: baseline;">';
        html += '<span style="color: #999; min-width: 115px; flex-shrink: 0;">' + rows[i][0] + ':</span>';
        html += '<span style="color: #1d2327; font-weight: 500;">' + (rows[i][1] || '—') + '</span></div>';
    }
    html += '</div>';
    if (footer) html += '<div style="margin-top:10px; color:#999; font-size:11px;">' + footer + '</div>';
    html += '</div>';
    html += '</div></div>';
    jQuery('body').append(html);
    jQuery('#myser-info-modal-overlay').on('click', function(e) { if (e.target === this) jQuery(this).remove(); });
}

// ========================================
// Инфо-карточка сотрудника
// ========================================
window.myser_open_staff_info = function(id) {
    jQuery.post(ajaxurl, {
        action: 'myser_get_staff_member',
        nonce: myser_ajax.nonce,
        staff_id: id
    }, function(res) {
        if (res.success) {
            var s = res.data;
            var statusLabels = { 'works': 'Работает', 'fired': 'Уволен', 'vacation': 'В отпуске', 'sick': 'На больничном' };
            window.myser_info_card_data = [
                ['ID', s.id],
                ['ФИО', s.staff_name],
                ['Кратко', s.staff_short_name || '—'],
                ['Должность', s.staff_position || '—'],
                ['Специализация', s.specialization || '—'],
                ['Подразделение', (s.department_ids && s.department_ids.length ? s.department_ids.join(', ') : '—')],
                ['Статус', statusLabels[s.work_status] || s.work_status || '—'],
                ['Телефон моб.', s.mobile_phone || '—'],
                ['Телефон раб.', s.work_phone || '—'],
                ['Email', s.email || '—'],
                ['Дата рождения', s.birth_day || '—'],
                ['Дата приёма', s.work_start_date || '—'],
                ['Табельный №', s.tabel_number || '—'],
                ['Оклад', s.salary ? s.salary + ' ₽' : '—'],
                ['% от услуг', s.percent_service ? s.percent_service + '%' : '—'],
                ['% от товаров', s.percent_products ? s.percent_products + '%' : '—'],
                ['Семейное положение', s.family_status || '—'],
                ['Дети', s.kids || '0'],
                ['Автомобиль', s.car || '—'],
                ['Вод. удостоверение', s.driving_licence || '—'],
                ['Паспорт', s.passport || '—'],
                ['Адрес регистрации', s.registration_address || '—'],
                ['Факт. адрес', s.real_address || '—'],
                ['Примечания', s.notes || '—']
            ];
            myser_show_info_modal('Сотрудник: ' + (s.staff_short_name || s.staff_name), window.myser_info_card_data, 'MySer');
        } else {
            alert('Ошибка: ' + (res.data.message || 'Неизвестная ошибка'));
        }
    });
};

// ========================================
// Инфо-карточка клиента
// ========================================
window.myser_open_client_info = function(id) {
    jQuery.post(ajaxurl, {
        action: 'myser_get_client',
        nonce: myser_ajax.nonce,
        client_id: id
    }, function(res) {
        if (res.success) {
            var c = res.data;
            var typeLabel = c.type === 'company' ? 'Юрлицо' : 'Физлицо';
            var extra = {};
            if (c.extra_data) {
                try { extra = JSON.parse(c.extra_data); } catch(e) {}
            }
            window.myser_info_card_data = [
                ['ID', c.id],
                ['Тип', typeLabel],
                ['Фамилия', c.last_name || '—'],
                ['Имя', c.first_name || '—'],
                ['Отчество', c.middle_name || '—'],
                ['Телефон', c.phone || '—'],
                ['Доп. телефон', c.other_phone || '—'],
                ['Email', c.email || '—'],
                ['Адрес', c.address || '—'],
                ['Статус', c.status || '—'],
                ['Скидка', extra.service_discount_percent ? extra.service_discount_percent + '%' : '0%'],
                ['Заказов', c.order_count || '0'],
                ['Проблемный', c.is_problem_client == 1 ? 'Да' : 'Нет'],
                ['Компания', extra.company_name || '—'],
                ['Юр. форма', extra.legal_form || '—'],
                ['Заметки', c.notes || '—']
            ];
            var title = (c.last_name || '') + ' ' + (c.first_name || '');
            if (extra.company_name) title += ' (' + extra.company_name + ')';
            myser_show_info_modal('Клиент: ' + (title.trim() || 'Без имени'), window.myser_info_card_data, 'MySer');
        } else {
            alert('Ошибка: ' + (res.data.message || 'Неизвестная ошибка'));
        }
    });
};
