<div class="wrap">
    <h1 style="margin: 0; display: flex; align-items: center; gap: 10px;">
            <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/clients.svg" class="myser-icon" alt="" style="width: 32px; height: 32px;">
            Клиенты
        </h1>

    <!-- Поиск и кнопка добавления -->
    <div id="clients-controls" style="display:flex; gap:10px; margin-bottom:15px;">
        <input type="text" id="clients-search" placeholder="Поиск по имени, телефону, email..." style="flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;">
        <button class="button" onclick="myser_load_clients(1)"> Найти</button>
        <button class="button button-primary" onclick="myser_open_client_modal()">+ Добавить клиента</button>
    </div>

    <!-- Таблица -->
    <div id="clients-table-wrap">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Фамилия</th>
                    <th>Имя</th>
                    <th>Телефон</th>
                    <th>Доп.телефон</th>
                    <th>Статус</th>
                    <th>Заказы</th>
                    <th>Адекватность</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="clients-tbody"></tbody>
        </table>
        <div class="pagination" style="margin-top:10px;"></div>
    </div>
</div>

<!-- Модальное окно -->
<div id="client-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100000; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:8px; padding:25px; width:650px; max-width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 id="client-modal-title" style="margin:0;">➕ Добавить клиента</h3>
            <span onclick="myser_close_client_modal()" style="cursor:pointer; font-size:24px; line-height:1;">&times;</span>
        </div>
        <input type="hidden" id="client-edit-id">

        <!-- Основное: тип клиента -->
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Тип клиента</label>
            <select id="client-type" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" onchange="myser_toggle_client_fields()">
                <option value="person">Физическое лицо</option>
                <option value="company">Юридическое лицо</option>
            </select>
        </div>

        <!-- Общие поля -->
        <fieldset style="border:1px solid #e0e0e0; border-radius:4px; padding:15px; margin-bottom:15px;">
            <legend style="font-weight:600; padding:0 10px;">Основная информация</legend>
            
            <!-- Физлицо: ФИО -->
            <div id="client-person-name" style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Фамилия</label>
                    <input type="text" id="client-last-name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Имя *</label>
                    <input type="text" id="client-first-name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Отчество</label>
                    <input type="text" id="client-middle-name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
            </div>

            <!-- Юрлицо: реквизиты -->
            <div id="client-company-name" style="display:none; grid-template-columns:1fr 1fr; gap:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Название компании *</label>
                    <input type="text" id="client-company" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Форма собственности</label>
                    <select id="client-legal-form" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">Не указано</option>
                        <option value="ooo">ООО</option>
                        <option value="ip">ИП</option>
                        <option value="zao">ЗАО</option>
                        <option value="oao">ОАО</option>
                        <option value="other">Другое</option>
                    </select>
                </div>
            </div>

            <!-- Контакты -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Телефон</label>
                    <input type="text" id="client-phone" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="+7 (999) 123-45-67">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Доп. телефон</label>
                    <input type="text" id="client-other-phone" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="+7 (999) 123-45-67">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Email</label>
                    <input type="email" id="client-email" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="client@example.com">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">&nbsp;</label>
                    <label style="display:flex; align-items:center; gap:8px; padding-top:6px;">
                        <input type="checkbox" id="client-problem" value="1">
                        <span>⚠️ Проблемный клиент</span>
                    </label>
                </div>
            </div>

            <!-- Адрес -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-top:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Город</label>
                    <input type="text" id="client-city" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Улица</label>
                    <input type="text" id="client-street" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Дом</label>
                    <input type="text" id="client-house" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
            </div>

            <!-- Статус и скидка -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Статус</label>
                    <select id="client-status" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                        <option value="active">Активен</option>
                        <option value="blocked">Заблокирован</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:5px; font-weight:600;">Скидка на услуги (%)</label>
                    <input type="number" id="client-discount" min="0" max="100" value="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                </div>
            </div>

            <!-- Заметки -->
            <div style="margin-top:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:600;">Заметки</label>
                <textarea id="client-notes" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; min-height:60px;" placeholder="Дополнительная информация..."></textarea>
            </div>
        </fieldset>

        <!-- Кнопки -->
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button class="button" onclick="myser_close_client_modal()">Отмена</button>
            <button class="button button-primary" onclick="myser_save_client_from_modal()">Сохранить</button>
        </div>
    </div>
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
	gap: 10px;
}
.myser-toast {
	color: #fff;
	padding: 12px 24px;
	border-radius: 4px;
	font-size: 14px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.2);
	animation: myserToastIn 0.3s ease-out;
	min-width: 250px;
	text-align: center;
}
.myser-toast.success { background: #46b450; }
.myser-toast.error { background: #dc3232; }
@keyframes myserToastIn {
	from { opacity: 0; transform: translateX(100px); }
	to { opacity: 1; transform: translateX(0); }
}
@keyframes myserToastOut {
	from { opacity: 1; transform: translateX(0); }
	to { opacity: 0; transform: translateX(100px); }
}
</style>
<script>
let clients_current_page = 1;
let clients_total_pages = 1;

// Toast-уведомление
function showMyserToast(message, type) {
	type = type || 'success';
	var container = document.getElementById('myser-toast-container');
	if (!container) {
		container = document.createElement('div');
		container.id = 'myser-toast-container';
		document.body.appendChild(container);
	}
	var toast = document.createElement('div');
	toast.className = 'myser-toast ' + type;
	toast.textContent = message;
	container.appendChild(toast);
	setTimeout(function() {
		toast.style.animation = 'myserToastOut 0.3s ease-out forwards';
		setTimeout(function() { toast.remove(); }, 300);
	}, 3000);
}

// Переключение полей физлицо/юрлицо
function myser_toggle_client_fields() {
    const type = document.getElementById('client-type').value;
    document.getElementById('client-person-name').style.display = type === 'person' ? 'grid' : 'none';
    document.getElementById('client-company-name').style.display = type === 'company' ? 'grid' : 'none';
}

// Загрузка списка
function myser_load_clients(page = 1) {
    clients_current_page = page;
    const search = document.getElementById('clients-search').value;
    jQuery.ajax({
        url: myser_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'myser_get_clients',
            nonce: myser_ajax.nonce,
            page: page,
            per_page: 20,
            search: search
        },
        dataType: 'json',
        cache: false
    }).done(function(response) {
        if (response.success) {
            let html = '';
            if (response.data.items.length === 0) {
                html = '<tr><td colspan="9">Нет клиентов</td></tr>';
            } else {
                response.data.items.forEach(function(c) {
                    const statusLabel = (c.order_count || 0) >= 11 ? ' Постоянный' : (c.order_count || 0) >= 6 ? ' Регулярный' : (c.order_count || 0) >= 1 ? ' Новый' : '—';
                    const adequacyLabel = c.is_problem_client == 1 ? '⚠️ Проблемный' : '✅ Адекватный';
                    html += `<tr>
                        <td>${c.id}</td>
                        <td>${c.last_name || '—'}</td>
                        <td>${c.first_name || '—'}</td>
                        <td>${c.phone || '—'}</td>
                        <td>${c.other_phone || '—'}</td>
                        <td>${statusLabel}</td>
                        <td>${c.order_count || 0}</td>
                        <td>${adequacyLabel}</td>
                        <td>
                            <button class="button button-small" onclick="myser_open_client_modal(${c.id})">✏️</button>
                            <button class="button button-small" onclick="myser_delete_client(${c.id})" style="color:red;">❌</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById('clients-tbody').innerHTML = html;

            clients_total_pages = response.data.pages || 1;
            let pagination_html = `<span>Страница ${clients_current_page} из ${clients_total_pages}</span>`;
            for (let i = 1; i <= Math.min(clients_total_pages, 10); i++) {
                pagination_html += `<button class="button button-small" onclick="myser_load_clients(${i})" ${i === clients_current_page ? 'disabled' : ''}>${i}</button>`;
            }
            document.querySelector('#clients-table-wrap .pagination').innerHTML = pagination_html;
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    });
}

// ========== Модальное окно ==========
function myser_open_client_modal(id = null) {
    document.getElementById('clients-search').value = '';
    document.getElementById('client-modal-overlay').style.display = 'flex';
    if (id) {
        document.getElementById('client-modal-title').textContent = '✏️ Редактировать клиента';
        document.getElementById('client-edit-id').value = id;
        jQuery.ajax({
            url: myser_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'myser_get_client',
                nonce: myser_ajax.nonce,
                client_id: id
            },
            dataType: 'json',
            cache: false
        }).done(function(response) {
            if (response.success) {
                const c = response.data;
                document.getElementById('client-type').value = c.client_type || 'person';
                document.getElementById('client-last-name').value = c.last_name || '';
                document.getElementById('client-first-name').value = c.first_name || '';
                document.getElementById('client-middle-name').value = c.middle_name || '';
                document.getElementById('client-company').value = c.company_name || '';
                document.getElementById('client-legal-form').value = c.legal_form || '';
                document.getElementById('client-phone').value = c.phone || '';
                document.getElementById('client-other-phone').value = c.other_phone || '';
                document.getElementById('client-email').value = c.email || '';
                document.getElementById('client-city').value = c.city || '';
                document.getElementById('client-street').value = c.street || '';
                document.getElementById('client-house').value = c.house || '';
                document.getElementById('client-problem').checked = c.is_problem_client == 1;
                document.getElementById('client-discount').value = c.service_discount_percent || 0;
                document.getElementById('client-notes').value = c.notes || '';
                myser_toggle_client_fields();
            }
        });
    } else {
        document.getElementById('client-modal-title').textContent = '+ Добавить клиента';
        document.getElementById('client-edit-id').value = '';
        document.getElementById('client-type').value = 'person';
        document.getElementById('client-last-name').value = '';
        document.getElementById('client-first-name').value = '';
        document.getElementById('client-middle-name').value = '';
        document.getElementById('client-company').value = '';
        document.getElementById('client-legal-form').value = '';
        document.getElementById('client-phone').value = '';
        document.getElementById('client-other-phone').value = '';
        document.getElementById('client-email').value = '';
        document.getElementById('client-city').value = '';
        document.getElementById('client-street').value = '';
        document.getElementById('client-house').value = '';
        document.getElementById('client-problem').checked = false;
        document.getElementById('client-discount').value = 0;
        document.getElementById('client-notes').value = '';
        myser_toggle_client_fields();
    }
}

function myser_close_client_modal() {
    document.getElementById('client-modal-overlay').style.display = 'none';
}

document.getElementById('client-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) myser_close_client_modal();
});

function myser_save_client_from_modal() {
    const id = document.getElementById('client-edit-id').value;
    const client_type = document.getElementById('client-type').value;
    const first_name = document.getElementById('client-first-name').value.trim();
    const last_name = document.getElementById('client-last-name').value.trim();
    const middle_name = document.getElementById('client-middle-name').value.trim();
    const company_name = document.getElementById('client-company').value.trim();

    if (client_type === 'person' && !first_name) {
        alert('Имя обязательно для физического лица');
        return;
    }
    if (client_type === 'company' && !company_name) {
        alert('Название компании обязательно');
        return;
    }

    const data = {
        action: 'myser_save_client',
        nonce: myser_ajax.nonce,
        client_type: client_type,
        first_name: first_name,
        last_name: last_name,
        middle_name: middle_name,
        company_name: company_name,
        legal_form: document.getElementById('client-legal-form').value,
        phone: document.getElementById('client-phone').value.trim(),
        other_phone: document.getElementById('client-other-phone').value.trim(),
        email: document.getElementById('client-email').value.trim(),
        city: document.getElementById('client-city').value.trim(),
        street: document.getElementById('client-street').value.trim(),
        house: document.getElementById('client-house').value.trim(),
        is_problem_client: document.getElementById('client-problem').checked ? 1 : 0,
        service_discount_percent: document.getElementById('client-discount').value,
        notes: document.getElementById('client-notes').value.trim()
    };

    if (id) data.id = id;

    jQuery.ajax({
        url: myser_ajax.ajaxurl,
        type: 'POST',
        data: data,
        dataType: 'json'
    }).done(function(response) {
        console.log('myser_save_client response:', response);
        if (response.success) {
            myser_close_client_modal();
            document.getElementById('clients-search').value = '';
            myser_load_clients(1);
            // Показываем уведомление об успехе
            var msg = response.data?.message || 'Клиент сохранён';
            showMyserToast(msg, 'success');
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    }).fail(function(jqXHR, textStatus) {
        console.error('myser_save_client AJAX error:', textStatus, jqXHR.responseText);
        alert('Ошибка соединения: ' + textStatus);
    });
}

// Удаление
function myser_delete_client(id) {
    if (!confirm('Удалить клиента?')) return;
    jQuery.ajax({
        url: myser_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'myser_delete_client',
            nonce: myser_ajax.nonce,
            client_id: id
        },
        dataType: 'json',
        cache: false
    }).done(function(response) {
        if (response.success) {
            myser_load_clients(clients_current_page);
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    });
}

// Загрузка при открытии
document.addEventListener('DOMContentLoaded', function() {
    myser_load_clients(1);
    document.getElementById('clients-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            myser_load_clients(1);
        }
    });
});
</script>
