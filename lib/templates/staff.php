<?php
defined('ABSPATH') || exit;
?>
<div class="wrap">
    <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;">
            <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/staff.svg" class="myser-icon" alt=""> 
            Сотрудники
        </h1>
        <div style="font-size: 0.9em; color: #0073aa; text-align: center; flex: 1;">
            MySer v<?php echo MYSER_VERSION; ?>
        </div>
        <div style="text-align: right; min-width: 150px;">
            <button class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ Ребут плагина</button>
            <span id="myser-reboot-status" style="display: block; margin-top: 4px; font-size: 12px;"></span>
        </div>
    </div>
    <div style="margin-bottom: 15px;">
        <button class="button button-primary" onclick="myser_open_staff_modal()">+ Добавить сотрудника</button>
    </div>
    
    <div class="myser-filter-row">
        <input type="text" id="staff-search" placeholder="Поиск по имени, должности, отделу..." style="flex: 1; min-width: 200px;">
        <button class="button" onclick="myser_load_staff()">Поиск</button>
    </div>
    
    <div class="myser-table-wrap" id="staff-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ФИО</th>
                    <th>Должность</th>
                    <th>Специализация</th>
                    <th>Подразделение</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Работает с...</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="staff-tbody">
                <tr><td colspan="9">Загрузка...</td></tr>
            </tbody>
        </table>
        <div class="pagination" style="margin-top: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
            <span id="staff-pagination-info">Страница 1</span>
        </div>
    </div>
</div>

<!-- Модальное окно -->
<div id="staff-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; justify-content:center; align-items:center;">
    <div id="staff-modal" style="background:#fff; border-radius:8px; padding:25px; width:650px; max-width:95%; max-height:90vh; overflow-y:auto; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 id="staff-modal-title" style="margin:0;">➕ Добавить сотрудника</h2>
            <span onclick="myser_close_staff_modal()" style="cursor:pointer; font-size:24px; line-height:1;">&times;</span>
        </div>
        
        <input type="hidden" id="staff-edit-id" value="">
        
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Фамилия</label>
                <input type="text" id="staff-last-name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Иванов">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Имя *</label>
                <input type="text" id="staff-first-name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Иван">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Отчество</label>
                <input type="text" id="staff-middle-name" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Иванович">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Должность</label>
                <input type="text" id="staff-position" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Мастер по ремонту">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Специализация</label>
                <input type="text" id="staff-specialization" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Ремонт телевизоров">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Подразделение</label>
                <select id="staff-subdivision" multiple style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; min-height:100px;" size="4">
                    <option value="">— Загрузка... —</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Мобильный телефон</label>
                <input type="text" id="staff-mobile-phone-main" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="+7 (999) 123-45-67">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Работает с...</label>
                <input type="date" id="staff-work-date" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Статус</label>
                <select id="staff-status" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <option value="works"> Работает</option>
                    <option value="sick"> На больничном</option>
                    <option value="fired"> Уволен</option>
                    <option value="vacation">️ Отпуск</option>
                    <option value="business_trip"> Командировка</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:8px; font-weight:600;">Роли</label>
            <div style="display:flex; flex-wrap:wrap; gap:10px;">
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Админ"> Админ
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Руководитель"> Руководитель
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Мастер"> Мастер
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Тех.инженер"> Тех.инженер
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Тех.отдел"> Тех.отдел
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="IT-инженер"> IT-инженер
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Менеджер"> Менеджер
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Бухгалтерия"> Бухгалтерия
                </label>
                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;">
                    <input type="checkbox" name="staff-roles[]" value="Склад"> Склад
                </label>
            </div>
        </div>
        
        <div style="display:flex; gap:10px; justify-content:space-between; align-items:center;">
            <div style="display:flex; gap:10px;">
                <button class="button" onclick="myser_open_staff_personal_modal()"> Личные данные</button>
                <button class="button" onclick="myser_open_staff_legal_modal()">⚖️ Юридическая информация</button>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="button" onclick="myser_close_staff_modal()">Отмена</button>
                <button class="button button-primary" onclick="myser_save_staff_from_modal()">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Суб-модалка: Личные данные сотрудника -->
<div id="staff-personal-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100001; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:8px; padding:25px; width:550px; max-width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;"> Личные данные</h3>
            <span onclick="myser_close_staff_personal_modal()" style="cursor:pointer; font-size:24px; line-height:1;">&times;</span>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; align-items:end;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Семейное положение</label>
                <select id="staff-marital-status" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">Не указано</option>
                    <option value="single">Холост/Не замужем</option>
                    <option value="married">Женат/Замужем</option>
                    <option value="divorced">Разведён(а)</option>
                    <option value="widowed">Вдовец/Вдова</option>
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Количество детей</label>
                <input type="number" id="staff-children-count" min="0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="0">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Дата рождения</label>
                <input type="date" id="staff-birth-date" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Email</label>
                <input type="email" id="staff-personal-email" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="personal@example.com">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-top:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Мобильный телефон</label>
                <input type="text" id="staff-mobile-phone" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="+7 (999) 123-45-67">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Рабочий телефон</label>
                <input type="text" id="staff-work-phone" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="+7 (495) 123-45-67">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Доп.телефон</label>
                <input type="text" id="staff-home-phone" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="+7 (495) 765-43-21">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Адрес регистрации</label>
                <input type="text" id="staff-reg-address" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Адрес по прописке">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Фактический адрес</label>
                <input type="text" id="staff-real-address" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Адрес проживания">
            </div>
        </div>
        
        <div style="margin-top:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Заметки (видны только определённым ролям)</label>
            <textarea id="staff-personal-notes" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; min-height:60px;" placeholder="Приватные заметки..."></textarea>
        </div>
        
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
            <button class="button" onclick="myser_close_staff_personal_modal()">Отмена</button>
            <button class="button button-primary" onclick="myser_save_staff_personal_data()">Сохранить</button>
        </div>
    </div>
</div>

<!-- Суб-модалка: Юридическая информация сотрудника -->
<div id="staff-legal-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100001; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:8px; padding:25px; width:550px; max-width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;">⚖️ Юридическая информация</h3>
            <span onclick="myser_close_staff_legal_modal()" style="cursor:pointer; font-size:24px; line-height:1;">&times;</span>
        </div>
        
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Тип занятости</label>
            <select id="staff-employment-role" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                <option value="">Не указано</option>
                <option value="individual">Физлицо</option>
                <option value="self_employed">Самозанятый</option>
                <option value="entrepreneur">ИП</option>
                <option value="employee">Наёмный сотрудник</option>
            </select>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Автомобиль</label>
                <input type="text" id="staff-vehicle" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Марка, модель, госномер">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Водительское удостоверение</label>
                <input type="text" id="staff-driver-license" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Серия и номер ВУ">
            </div>
        </div>
        
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:600;">Паспортные данные</label>
            <textarea id="staff-passport" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; min-height:60px;" placeholder="Серия, номер, кем выдан, дата выдачи"></textarea>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">ИНН</label>
                <input type="text" id="staff-tax-id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="123456789012">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">СНИЛС</label>
                <input type="text" id="staff-snils" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="123-456-789 01">
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">ПДМС (полис)</label>
                <input type="text" id="staff-pdms" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Номер полиса">
            </div>
            <div>
                <label style="display:block; margin-bottom:5px; font-weight:600;">Табельный номер</label>
                <input type="text" id="staff-employee-id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" placeholder="Номер приказа/табеля">
            </div>
        </div>
        
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
            <button class="button" onclick="myser_close_staff_legal_modal()">Отмена</button>
            <button class="button button-primary" onclick="myser_save_staff_legal_data()">Сохранить</button>
        </div>
    </div>
</div>

<script>
let staff_current_page = 1;
let staff_total_pages = 1;

function myser_load_staff(page = 1) {
    staff_current_page = page;
    const search = document.getElementById('staff-search').value;
    jQuery.post(myser_ajax.ajaxurl, {
        action: 'myser_get_staff',
        nonce: myser_ajax.nonce,
        page: page,
        per_page: 20,
        search: search
    }, function(response) {
        if (response.success) {
            let html = '';
            if (response.data.items.length === 0) {
                html = '<tr><td colspan="9">Нет сотрудников</td></tr>';
            } else {
                response.data.items.forEach(function(staff) {
                    const status = staff.work_status || staff.status || 'works';
                    const statusMap = {
                        works: { bg: '#d4edda', text: 'Работает' },
                        sick: { bg: '#fff3cd', text: 'На больничном' },
                        fired: { bg: '#f8d7da', text: 'Уволен' },
                        vacation: { bg: '#cce5ff', text: 'Отпуск' },
                        business_trip: { bg: '#e2d9f3', text: 'Командировка' }
                    };
                    const style = statusMap[status] || { bg: '#e2e3e5', text: status };
                    const bgColor = style.bg;
                    const statusText = style.text;
                    html += `<tr style="background-color:${bgColor}; color:#000000; cursor:pointer;" onclick="myser_open_staff_modal(${staff.id})">
                        <td>${staff.id}</td>
                        <td>${staff.staff_name || ''}</td>
                        <td>${staff.staff_position || ''}</td>
                        <td>${staff.specialization || ''}</td>
                        <td>${staff.department || ''}</td>
                        <td>${staff.subject_roles || ''}</td>
                        <td>${statusText}</td>
                        <td>${staff.work_start_date || ''}</td>
                        <td>
                            <button class="button button-small" onclick="event.stopPropagation(); myser_open_staff_modal(${staff.id})" style="color:inherit;">✏️</button>
                             <button class="button button-small" onclick="event.stopPropagation(); myser_delete_staff(${staff.id})" style="color:red;">❌</button>
                        </td>
                    </tr>`;
                });
            }
            document.getElementById('staff-tbody').innerHTML = html;
            
            staff_total_pages = response.data.pages || 1;
            let pagination_html = `<span>Страница ${staff_current_page} из ${staff_total_pages}</span>`;
            for (let i = 1; i <= Math.min(staff_total_pages, 10); i++) {
                pagination_html += `<button class="button button-small" onclick="myser_load_staff(${i})" ${i === staff_current_page ? 'disabled' : ''}>${i}</button>`;
            }
            document.querySelector('#staff-table-wrap .pagination').innerHTML = pagination_html;
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    });
}

// ========== Модальное окно ==========

// Загрузка списка подразделений в <select multiple>
function loadDepartmentsToSelect(selectedIds) {
    selectedIds = selectedIds || [];
    jQuery.post(myser_ajax.ajaxurl, {
        action: 'myser_get_departments',
        nonce: myser_ajax.nonce
    }, function(response) {
        const select = document.getElementById('staff-subdivision');
        select.innerHTML = '';
        if (response.success && response.data) {
            response.data.forEach(function(dep) {
                const option = document.createElement('option');
                option.value = dep.id;
                option.textContent = dep.full_name || dep.short_name;
                if (selectedIds.indexOf(String(dep.id)) !== -1) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">— Нет подразделений —</option>';
        }
    });
}

function myser_open_staff_modal(id = null) {
    const overlay = document.getElementById('staff-modal-overlay');
    overlay.style.display = 'flex';
    
    if (id) {
        // Редактирование
        document.getElementById('staff-modal-title').textContent = '✏️ Редактировать сотрудника';
        document.getElementById('staff-edit-id').value = id;
        
        jQuery.post(myser_ajax.ajaxurl, {
            action: 'myser_get_staff_member',
            nonce: myser_ajax.nonce,
            staff_id: id
        }, function(response) {
            if (response.success) {
                const s = response.data;
                // Разбиваем staff_name на части
                const nameParts = (s.staff_name || '').split(' ');
                document.getElementById('staff-last-name').value = nameParts[0] || '';
                document.getElementById('staff-first-name').value = nameParts[1] || '';
                document.getElementById('staff-middle-name').value = nameParts[2] || '';
                document.getElementById('staff-position').value = s.staff_position || '';
                document.getElementById('staff-specialization').value = s.specialization || '';
                document.getElementById('staff-work-date').value = s.work_start_date || '';
                document.getElementById('staff-status').value = s.work_status || s.status || 'works';

                // Загружаем подразделения и отмечаем выбранные
                const selectedDepartments = (s.department_ids || []).map(String);
                loadDepartmentsToSelect(selectedDepartments);

                // Отмечаем чекбоксы ролей
                const currentRoles = (s.subject_roles || '').split(',').map(r => r.trim());
                document.querySelectorAll('input[name="staff-roles[]"]').forEach(cb => {
                    cb.checked = currentRoles.includes(cb.value);
                });
            }
        });
    } else {
        // Добавление
        document.getElementById('staff-modal-title').textContent = '➕ Добавить сотрудника';
        document.getElementById('staff-edit-id').value = '';
        document.getElementById('staff-last-name').value = '';
        document.getElementById('staff-first-name').value = '';
        document.getElementById('staff-middle-name').value = '';
        document.getElementById('staff-position').value = '';
        document.getElementById('staff-specialization').value = '';
        document.getElementById('staff-work-date').value = '';
        document.getElementById('staff-status').value = 'works';
        document.getElementById('staff-mobile-phone-main').value = '';
        document.querySelectorAll('input[name="staff-roles[]"]').forEach(cb => { cb.checked = false; });
        loadDepartmentsToSelect([]);
    }
}

function myser_close_staff_modal() {
    document.getElementById('staff-modal-overlay').style.display = 'none';
}

function myser_save_staff_from_modal() {
    const id = document.getElementById('staff-edit-id').value;
    const last_name = document.getElementById('staff-last-name').value.trim();
    const first_name = document.getElementById('staff-first-name').value.trim();
    const middle_name = document.getElementById('staff-middle-name').value.trim();

    if (!first_name) {
        alert('Имя обязательно для заполнения');
        return;
    }

    const staff_name = [last_name, first_name, middle_name].filter(Boolean).join(' ');
    
    // Собираем выбранные роли
    const roles = [];
    document.querySelectorAll('input[name="staff-roles[]"]:checked').forEach(cb => {
        roles.push(cb.value);
    });
    
    // Собираем выбранные подразделения
    const deptSelect = document.getElementById('staff-subdivision');
    const department_ids = [];
    for (let i = 0; i < deptSelect.options.length; i++) {
        if (deptSelect.options[i].selected) {
            department_ids.push(deptSelect.options[i].value);
        }
    }
    
    const data = {
        action: 'myser_save_staff',
        nonce: myser_ajax.nonce,
        staff_name: staff_name,
        staff_position: document.getElementById('staff-position').value.trim(),
        specialization: document.getElementById('staff-specialization').value.trim(),
        'department_ids[]': department_ids,
        work_start_date: document.getElementById('staff-work-date').value,
        roles: roles
    };

    if (id) data.id = id;

    jQuery.post(myser_ajax.ajaxurl, data, function(response) {
        if (response.success) {
            myser_close_staff_modal();
            myser_load_staff(staff_current_page);
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    });
}

// Закрытие по клику на оверлей
document.getElementById('staff-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) myser_close_staff_modal();
});

// ========== Удаление ==========

function myser_delete_staff(id) {
    if (!confirm('Удалить сотрудника?')) return;
    jQuery.post(myser_ajax.ajaxurl, {
        action: 'myser_delete_staff',
        nonce: myser_ajax.nonce,
        staff_id: id
    }, function(response) {
        if (response.success) {
            myser_load_staff(staff_current_page);
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    });
}

// ========== Суб-модалки: Личные данные ==========
let staffPersonalData = {};

function myser_open_staff_personal_modal() {
    // Синхронизация из основной модалки в суб-модалку
    document.getElementById('staff-mobile-phone').value = document.getElementById('staff-mobile-phone-main').value;
    document.getElementById('staff-personal-modal-overlay').style.display = 'flex';
}

function myser_close_staff_personal_modal() {
    document.getElementById('staff-personal-modal-overlay').style.display = 'none';
}

function myser_save_staff_personal_data() {
    staffPersonalData.marital_status = document.getElementById('staff-marital-status').value;
    staffPersonalData.children_count = document.getElementById('staff-children-count').value;
    staffPersonalData.birth_date = document.getElementById('staff-birth-date').value;
    staffPersonalData.personal_email = document.getElementById('staff-personal-email').value.trim();
    staffPersonalData.mobile_phone = document.getElementById('staff-mobile-phone').value.trim();
    staffPersonalData.work_phone = document.getElementById('staff-work-phone').value.trim();
    staffPersonalData.home_phone = document.getElementById('staff-home-phone').value.trim();
    staffPersonalData.registration_address = document.getElementById('staff-reg-address').value.trim();
    staffPersonalData.real_address = document.getElementById('staff-real-address').value.trim();
    staffPersonalData.personal_notes = document.getElementById('staff-personal-notes').value.trim();
    // Обратная синхронизация в основную модалку
    document.getElementById('staff-mobile-phone-main').value = staffPersonalData.mobile_phone;
    myser_close_staff_personal_modal();
}

// ========== Суб-модалки: Юридическая информация ==========
let staffLegalData = {};

function myser_open_staff_legal_modal() {
    document.getElementById('staff-legal-modal-overlay').style.display = 'flex';
}

function myser_close_staff_legal_modal() {
    document.getElementById('staff-legal-modal-overlay').style.display = 'none';
}

function myser_save_staff_legal_data() {
    staffLegalData.employment_role = document.getElementById('staff-employment-role').value;
    staffLegalData.vehicle = document.getElementById('staff-vehicle').value.trim();
    staffLegalData.driver_license = document.getElementById('staff-driver-license').value.trim();
    staffLegalData.passport = document.getElementById('staff-passport').value.trim();
    staffLegalData.tax_id = document.getElementById('staff-tax-id').value.trim();
    staffLegalData.snils = document.getElementById('staff-snils').value.trim();
    staffLegalData.pdms = document.getElementById('staff-pdms').value.trim();
    staffLegalData.employee_id = document.getElementById('staff-employee-id').value.trim();
    myser_close_staff_legal_modal();
}

// Обновляем myser_save_staff_from_modal — добавляем extra_data
const originalSaveStaff = myser_save_staff_from_modal;
myser_save_staff_from_modal = function() {
    const id = document.getElementById('staff-edit-id').value;
    const last_name = document.getElementById('staff-last-name').value.trim();
    const first_name = document.getElementById('staff-first-name').value.trim();
    const middle_name = document.getElementById('staff-middle-name').value.trim();

    if (!first_name) {
        alert('Имя обязательно для заполнения');
        return;
    }

    const staff_name = [last_name, first_name, middle_name].filter(Boolean).join(' ');
    
    const roles = [];
    document.querySelectorAll('input[name="staff-roles[]"]:checked').forEach(cb => {
        roles.push(cb.value);
    });
    
    const extraData = {
        personal: staffPersonalData,
        legal: staffLegalData
    };
    
    const data = {
        action: 'myser_save_staff',
        nonce: myser_ajax.nonce,
        staff_name: staff_name,
        staff_position: document.getElementById('staff-position').value.trim(),
        specialization: document.getElementById('staff-specialization').value.trim(),
        'department_ids[]': (function() {
            const deptSelect = document.getElementById('staff-subdivision');
            const ids = [];
            for (let i = 0; i < deptSelect.options.length; i++) {
                if (deptSelect.options[i].selected) {
                    ids.push(deptSelect.options[i].value);
                }
            }
            return ids;
        })(),
        work_start_date: document.getElementById('staff-work-date').value,
        status: document.getElementById('staff-status').value,
        roles: roles,
        extra_data: JSON.stringify(extraData)
    };
    
    if (id) data.id = id;
    
    jQuery.post(myser_ajax.ajaxurl, data, function(response) {
        if (response.success) {
            myser_close_staff_modal();
            myser_load_staff(staff_current_page);
        } else {
            alert('Ошибка: ' + (response.data?.message || 'Неизвестная ошибка'));
        }
    });
};

// Загрузка extra_data при редактировании
const originalOpenStaffModal = myser_open_staff_modal;
myser_open_staff_modal = function(id = null) {
    originalOpenStaffModal(id);
    if (id) {
        jQuery.post(myser_ajax.ajaxurl, {
            action: 'myser_get_staff_member',
            nonce: myser_ajax.nonce,
            staff_id: id
        }, function(response) {
            if (response.success && response.data.extra_data) {
                try {
                    const extra = JSON.parse(response.data.extra_data);
                    if (extra.personal) {
                        staffPersonalData = extra.personal;
                        document.getElementById('staff-marital-status').value = extra.personal.marital_status || '';
                        document.getElementById('staff-children-count').value = extra.personal.children_count || '';
                        document.getElementById('staff-birth-date').value = extra.personal.birth_date || '';
                        document.getElementById('staff-personal-email').value = extra.personal.personal_email || '';
                        document.getElementById('staff-mobile-phone').value = extra.personal.mobile_phone || '';
                        document.getElementById('staff-mobile-phone-main').value = extra.personal.mobile_phone || '';
                        document.getElementById('staff-work-phone').value = extra.personal.work_phone || '';
                        document.getElementById('staff-home-phone').value = extra.personal.home_phone || '';
                        document.getElementById('staff-reg-address').value = extra.personal.registration_address || '';
                        document.getElementById('staff-real-address').value = extra.personal.real_address || '';
                        document.getElementById('staff-personal-notes').value = extra.personal.personal_notes || '';
                    }
                    if (extra.legal) {
                        staffLegalData = extra.legal;
                        document.getElementById('staff-employment-role').value = extra.legal.employment_role || '';
                        document.getElementById('staff-vehicle').value = extra.legal.vehicle || '';
                        document.getElementById('staff-driver-license').value = extra.legal.driver_license || '';
                        document.getElementById('staff-passport').value = extra.legal.passport || '';
                        document.getElementById('staff-tax-id').value = extra.legal.tax_id || '';
                        document.getElementById('staff-snils').value = extra.legal.snils || '';
                        document.getElementById('staff-pdms').value = extra.legal.pdms || '';
                        document.getElementById('staff-employee-id').value = extra.legal.employee_id || '';
                    }
                } catch(e) {}
            }
        });
    } else {
        staffPersonalData = {};
        staffLegalData = {};
    }
};

// Закрытие по клику на оверлей для суб-модалок
document.getElementById('staff-personal-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) myser_close_staff_personal_modal();
});
document.getElementById('staff-legal-modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) myser_close_staff_legal_modal();
});

// Загрузка при открытии страницы
document.addEventListener('DOMContentLoaded', function() {
    myser_load_staff(1);
});
</script>
