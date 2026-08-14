jQuery(document).ready(function($) {
	// Toast-уведомление
	function myserShowToast(message, type) {
		type = type || 'success';
		var $container = $('#myser-toast-container');
		if (!$container.length) {
			$container = $('<div id="myser-toast-container"></div>').appendTo('body');
		}
		var $toast = $('<div class="myser-toast ' + type + '">' + message + '</div>');
		$container.append($toast);
		setTimeout(function() {
			$toast.css('animation', 'myserToastOut 0.3s ease-out forwards');
			setTimeout(function() { $toast.remove(); }, 300);
		}, 3000);
	}

	// Переключение вкладок с сохранением
    function activateTab(tab) {
        $('.myser-tabs .nav-tab').removeClass('nav-tab-active');
        $('.myser-tabs .nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
        $('.tab-content').hide();
        $('#tab-' + tab).show();
        $('#department-modal').hide();
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('myserSettingsActiveTab', tab);
        }
    }

    $('.myser-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        activateTab($(this).data('tab'));
    });

    // Синхронизация префикса: dep-order-prefix ↔ order_prefix
    $('#dep-order-prefix').on('input', function() {
        $('#order_prefix').val($(this).val());
    });
    $('#order_prefix').on('input', function() {
        $('#dep-order-prefix').val($(this).val());
    });

    // Восстановление вкладки после перезагрузки
    var savedTab = localStorage.getItem('myserSettingsActiveTab');
    if (savedTab) {
        activateTab(savedTab);
    }
    // Показать уведомление при успешном сохранении
    // 1. Проверяем sessionStorage (надёжно работает для всех вкладок)
    if (sessionStorage.getItem('myserSettingsSaved') === '1') {
        sessionStorage.removeItem('myserSettingsSaved');
        myserShowToast('Настройки сохранены', 'success');
    }
    // 2. Дополнительная проверка URL-параметра (для совместимости)
    else if (window.location.search.indexOf('settings-updated') !== -1) {
        myserShowToast('Настройки сохранены', 'success');
        if (window.history && window.history.replaceState) {
            var cleanUrl = window.location.href.replace(/[?&]settings-updated=[^&]*/, '').replace(/[?&]$/, '');
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    // Устанавливаем флаг при отправке формы (перед перезагрузкой страницы)
    $('form[action="options.php"]').on('submit', function() {
        sessionStorage.setItem('myserSettingsSaved', '1');
    });

    var mediaUploader;
    $('#upload_logo_button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: 'Выберите логотип компании',
            button: { text: 'Выбрать' },
            multiple: false,
            library: { type: 'image' }
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo_url').val(attachment.url);
            $('#logo_preview').html('<img src="' + attachment.url + '" style="max-width:150px; max-height:150px;">');
            $('#remove_logo_button').show();
        });
        mediaUploader.open();
    });

    $('#remove_logo_button').on('click', function(e) {
        e.preventDefault();
        $('#logo_url').val('');
        $('#logo_preview').html('');
        $(this).hide();
    });

    // === Сетки ставок ===
    function loadGrids() {
        $.post(ajaxurl, {
            action: 'myser_get_salary_grids',
            nonce: myser_ajax.nonce
        }, function(res) {
            if (res.success) {
                var rows = '';
                $.each(res.data, function(i, grid) {
                    rows += '<tr>';
                    rows += '<td>' + grid.name + '</td>';
                    rows += '<td>' + grid.percent + '%</td>';
                    rows += '<td>';
                    rows += '<button type="button" class="button button-small edit-grid" data-id="' + grid.id + '" data-name="' + grid.name + '" data-percent="' + grid.percent + '" data-sort="' + grid.sort_order + '">✏️</button> ';
                    rows += '<button type="button" class="button button-small delete-grid" style="color:red;" data-id="' + grid.id + '">✕</button>';
                    rows += '</td></tr>';
                });
                if (!rows) rows = '<tr><td colspan="3">Нет сеток</td></tr>';
                $('#salary-grids-table tbody').html(rows);
            }
        });
    }

    function loadAssignments() {
        $.post(ajaxurl, {
            action: 'myser_get_staff_assignments',
            nonce: myser_ajax.nonce
        }, function(res) {
            if (res.success) {
                var rows = '';
                $.each(res.data, function(i, a) {
                    var condLabels = { 'custom': 'Вручную', 'seniority': 'По выслуге', 'kur': 'КУР' };
                    var condTypes = (a.condition_type || 'custom').split(',');
                    var cond = condTypes.map(function(ct) { return condLabels[ct.trim()] || ct.trim(); }).join(', ');
                    if (a.condition_value) cond += ': ' + a.condition_value;
                    var pct = a.custom_percent !== null ? a.custom_percent : a.grid_percent;
                    rows += '<tr>';
                    rows += '<td>' + (a.staff_name || 'ID:' + a.staff_id) + '</td>';
                    rows += '<td>' + a.grid_name + '</td>';
                    rows += '<td>' + cond + '</td>';
                    rows += '<td>' + pct + '%</td>';
                    rows += '<td>';
                    rows += '<button type="button" class="button button-small edit-assignment" data-id="' + a.id + '" data-staff="' + a.staff_id + '" data-grid="' + a.grid_id + '" data-cond="' + a.condition_type + '" data-value="' + (a.condition_value || '') + '" data-percent="' + (a.custom_percent || '') + '">✏️</button> ';
                    rows += '<button type="button" class="button button-small delete-assignment" style="color:red;" data-id="' + a.id + '">✕</button>';
                    rows += '</td></tr>';
                });
                if (!rows) rows = '<tr><td colspan="5">Нет начислений</td></tr>';
                $('#staff-assignments-table tbody').html(rows);
            }
        });
    }

    loadGrids();
    loadAssignments();

    // Сетка: модалка
    $('#add-salary-grid-btn').on('click', function() {
        $('#grid-id').val('0');
        $('#grid-name').val('');
        $('#grid-percent').val('');
        $('#grid-sort').val('0');
        $('#grid-modal-title').text('Добавить сетку');
        $('#grid-modal').show();
    });

    $(document).on('click', '.edit-grid', function() {
        $('#grid-id').val($(this).data('id'));
        $('#grid-name').val($(this).data('name'));
        $('#grid-percent').val($(this).data('percent'));
        $('#grid-sort').val($(this).data('sort'));
        $('#grid-modal-title').text('Редактировать сетку');
        $('#grid-modal').show();
    });

    $('#close-grid-modal').on('click', function() { $('#grid-modal').hide(); });

    $('#save-grid-btn').on('click', function() {
        var id = $('#grid-id').val();
        $.post(ajaxurl, {
            action: 'myser_save_salary_grid',
            nonce: myser_ajax.nonce,
            grid_id: id,
            name: $('#grid-name').val(),
            percent: $('#grid-percent').val(),
            sort_order: $('#grid-sort').val()
        }, function(res) {
            if (res.success) {
            			$('#grid-modal').hide();
            			loadGrids();
            			myserShowToast(id == '0' ? 'Сетка добавлена' : 'Сетка обновлена');
            		} else {
            			myserShowToast(res.data.message || 'Ошибка сохранения', 'error');
            		}
        });
    });

    $(document).on('click', '.delete-grid', function() {
        if (!confirm('Удалить сетку?')) return;
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'myser_delete_salary_grid',
            nonce: myser_ajax.nonce,
            grid_id: id
        }, function(res) {
            if (res.success) { loadGrids(); myserShowToast('Сетка удалена'); } else myserShowToast(res.data.message || 'Ошибка', 'error');
        });
    });

    // Назначения: модалка
    $('#add-assignment-btn').on('click', function() {
        $('#assignment-id').val('0');
        $('#assignment-staff').val('');
        $('#assignment-grid').val('');
        $('#assignment-condition').val([]);
        $('#assignment-value').val('');
        $('#assignment-percent').val('');
        $('#assignment-modal-title').text('Добавить начисления');
        // Загружаем списки сотрудников в select
        $.post(ajaxurl, { action: 'myser_get_staff_list', nonce: myser_ajax.nonce }, function(res) {
        	if (res.success) {
        		var opts = '<option value="">Выберите сотрудника...</option>';
        		$.each(res.data, function(i, s) { opts += '<option value="' + s.id + '">' + s.staff_name + '</option>'; });
        		$('#assignment-staff').html(opts);
        	}
        });
        $.post(ajaxurl, { action: 'myser_get_salary_grids', nonce: myser_ajax.nonce }, function(res) {
            if (res.success) {
                var opts = '<option value="">Выберите сетку...</option>';
                $.each(res.data, function(i, g) { opts += '<option value="' + g.id + '">' + g.name + ' (' + g.percent + '%)</option>'; });
                $('#assignment-grid').html(opts);
            }
        });
        $('#assignment-modal').show();
    });

    // При выборе из datalist записываем staff_id в скрытое поле
    $('#assignment-staff-search').on('input', function() {
        var val = $(this).val();
        var opt = $('#staff-datalist option[value="' + val + '"]');
        $('#assignment-staff').val(opt.length ? opt.data('id') : '');
    });

    $(document).on('click', '.edit-assignment', function() {
        $('#assignment-id').val($(this).data('id'));
        var staffId = $(this).data('staff');
        $('#assignment-staff').val(staffId);
        $('#assignment-grid').val($(this).data('grid'));
        var condVal = $(this).data('cond') || 'custom';
        $('#assignment-condition').val(condVal.split(','));
        $('#assignment-value').val($(this).data('value'));
        $('#assignment-percent').val($(this).data('percent'));
        $('#assignment-modal-title').text('Редактировать начисление');
        // Загружаем списки
        $.post(ajaxurl, { action: 'myser_get_staff_list', nonce: myser_ajax.nonce }, function(res) {
        	if (res.success) {
        		var opts = '<option value="">Выберите сотрудника...</option>';
        		$.each(res.data, function(i, s) {
        			opts += '<option value="' + s.id + '"' + (s.id == staffId ? ' selected' : '') + '>' + s.staff_name + '</option>';
        		});
        		$('#assignment-staff').html(opts);
        	}
        });
        $.post(ajaxurl, { action: 'myser_get_salary_grids', nonce: myser_ajax.nonce }, function(res) {
            if (res.success) {
                var opts = '<option value="">Выберите сетку...</option>';
                $.each(res.data, function(i, g) { opts += '<option value="' + g.id + '">' + g.name + ' (' + g.percent + '%)</option>'; });
                $('#assignment-grid').html(opts);
                $('#assignment-grid').val($(this).data('grid'));
            }
        });
        $('#assignment-modal').show();
    });

    $('#close-assignment-modal').on('click', function() { $('#assignment-modal').hide(); });

    $('#save-assignment-btn').on('click', function() {
        var id = $('#assignment-id').val();
        $.post(ajaxurl, {
            action: 'myser_save_staff_assignment',
            nonce: myser_ajax.nonce,
            assignment_id: id,
            staff_id: $('#assignment-staff').val(),
            grid_id: $('#assignment-grid').val(),
            condition_type: ($('#assignment-condition').val() || ['custom']).join(','),
            condition_value: $('#assignment-value').val(),
            custom_percent: $('#assignment-percent').val()
        }, function(res) {
            if (res.success) { $('#assignment-modal').hide(); loadAssignments(); myserShowToast(id == '0' ? 'Назначение добавлено' : 'Назначение обновлено'); } else { myserShowToast(res.data.message || 'Ошибка сохранения', 'error'); }
        });
    });

    $(document).on('click', '.delete-assignment', function() {
        if (!confirm('Удалить начисление?')) return;
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'myser_delete_staff_assignment',
            nonce: myser_ajax.nonce,
            assignment_id: id
        }, function(res) {
            if (res.success) { loadAssignments(); myserShowToast('Назначение удалено'); } else myserShowToast(res.data.message || 'Ошибка', 'error');
        });
    });

    // === Подразделения ===
    function loadDepartments() {
        $.post(ajaxurl, {
            action: 'myser_get_departments',
            nonce: myser_ajax.nonce
        }, function(res) {
            if (res.success) {
                var rows = '';
                $.each(res.data, function(i, d) {
                    rows += '<tr>';
                    rows += '<tr data-department-id="' + d.id + '" class="myser-clickable-row" style="cursor:pointer;" onclick="myser_open_department_info(' + d.id + ')">';
                    rows += '<td>' + d.short_name + '</td>';
                    rows += '<td>' + (d.order_prefix || '—') + '</td>';
                    rows += '<td>' + (d.city || '—') + '</td>';
                    rows += '<td>' + (d.work_phone || '—') + '</td>';
                    rows += '<td>' + (d.email || '—') + '</td>';
                    rows += '<td>' + (d.staff_count || '0') + '</td>';
                    rows += '<td>' + (d.status == 1 ? 'Активно' : 'Неактивно') + '</td>';
                    rows += '<td>';
                    rows += '<button type="button" class="button button-small edit-department" data-id="' + d.id + '">✏️</button> ';
                    rows += '<button type="button" class="button button-small delete-department" style="color:red;" data-id="' + d.id + '">✕</button>';
                    rows += '</td></tr>';
                });
                if (!rows) rows = '<tr><td colspan="8">Нет подразделений</td></tr>';
                $('#departments-table tbody').html(rows);
            }
        });
    }

    loadDepartments();

    // Подразделение: модалка
    $('#add-department-btn').on('click', function() {
        $('#dep-id').val('0');
        $('#dep-short-name, #dep-full-name, #dep-order-prefix, #dep-city, #dep-address, #dep-address-fact, #dep-work-phone, #dep-email').val('');
        $('#dep-inn, #dep-kpp, #dep-ogrn, #dep-okpo, #dep-okvd').val('');
        $('#dep-bank-account, #dep-bank-name, #dep-bank-corr, #dep-bank-bic').val('');
        $('#dep-director, #dep-director-full, #dep-director-position, #dep-accountant, #dep-notes').val('');
        $('#dep-type').val('branch');
        $('#dep-status').val('1');
        $('#department-modal-title').text('➕ Добавить подразделение');
        $('#department-modal').show();
    });

    $(document).on('click', '.edit-department', function() {
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'myser_get_department',
            nonce: myser_ajax.nonce,
            dep_id: id
        }, function(res) {
            if (res.success) {
                var d = res.data;
                $('#dep-id').val(d.id);
                $('#dep-short-name').val(d.short_name);
                $('#dep-full-name').val(d.full_name);
                $('#dep-city').val(d.city);
                $('#dep-address').val(d.address);
                $('#dep-work-phone').val(d.work_phone);
                $('#dep-email').val(d.email);
                $('#dep-inn').val(d.inn);
                $('#dep-kpp').val(d.kpp);
                $('#dep-ogrn').val(d.ogrn);
                $('#dep-okpo').val(d.okpo);
                $('#dep-okvd').val(d.okvd);
                $('#dep-bank-account').val(d.bank_account);
                $('#dep-bank-name').val(d.bank_name);
                $('#dep-bank-bic').val(d.bank_bic);
                $('#dep-bank-corr').val(d.bank_corr);
                $('#dep-director').val(d.director);
                $('#dep-director-full').val(d.director_full);
                $('#dep-director-position').val(d.director_position);
                $('#dep-director-vlice').val(d.director_vlice);
                $('#dep-accountant').val(d.accountant);
                $('#dep-notes').val(d.notes);
                $('#dep-status').val(d.status);
                $('#dep-order-prefix').val(d.order_prefix);
                $('#order_prefix').val(d.order_prefix);
                $('#department-modal-title').text('✏️ Редактировать подразделение');
                $('#department-modal').show();
            }
        });
    });

    $('#close-department-modal').on('click', function() { $('#department-modal').hide(); });

    $('#save-department-btn').on('click', function() {
        var id = $('#dep-id').val();
        $.post(ajaxurl, {
            action: 'myser_save_department',
            nonce: myser_ajax.nonce,
            dep_id: id,
            short_name: $('#dep-short-name').val(),
            full_name: $('#dep-full-name').val(),
            city: $('#dep-city').val(),
            address: $('#dep-address').val(),
            work_phone: $('#dep-work-phone').val(),
            email: $('#dep-email').val(),
            inn: $('#dep-inn').val(),
            kpp: $('#dep-kpp').val(),
            ogrn: $('#dep-ogrn').val(),
            okpo: $('#dep-okpo').val(),
            okvd: $('#dep-okvd').val(),
            bank_account: $('#dep-bank-account').val(),
            bank_name: $('#dep-bank-name').val(),
            bank_bic: $('#dep-bank-bic').val(),
            bank_corr: $('#dep-bank-corr').val(),
            director: $('#dep-director').val(),
            director_full: $('#dep-director-full').val(),
            director_position: $('#dep-director-position').val(),
            director_vlice: $('#dep-director-vlice').val(),
            accountant: $('#dep-accountant').val(),
            notes: $('#dep-notes').val(),
            status: $('#dep-status').val(),
            order_prefix: $('#dep-order-prefix').val()
        }, function(res) {
            if (res.success) {
                $('#department-modal').hide();
                loadDepartments();
                myserShowToast(id == '0' ? 'Подразделение добавлено' : 'Подразделение обновлено');
            } else {
                myserShowToast(res.data.message || 'Ошибка сохранения', 'error');
            }
        });
    });

    $(document).on('click', '.delete-department', function() {
        if (!confirm('Удалить подразделение?')) return;
        var id = $(this).data('id');
        $.post(ajaxurl, {
            action: 'myser_delete_department',
            nonce: myser_ajax.nonce,
            dep_id: id
        }, function(res) {
            if (res.success) { loadDepartments(); myserShowToast('Подразделение удалено'); } else myserShowToast(res.data.message || 'Ошибка', 'error');
        });
    });

});

    // Инфо-карточка подразделения
    window.myser_open_department_info = function(id) {
        jQuery.post(ajaxurl, {
            action: 'myser_get_department',
            nonce: myser_ajax.nonce,
            dep_id: id
        }, function(res) {
            if (res.success) {
                var d = res.data;
                window.myser_info_card_data = [
                    ['ID', d.id],
                    ['Краткое название', d.short_name],
                    ['Полное название', d.full_name],
                    ['Тип', d.type === 'branch' ? 'Филиал' : (d.type === 'main' ? 'Головное' : (d.type || '—'))],
                    ['Город', d.city || '—'],
                    ['Префикс заказов', d.order_prefix || '—'],
                    ['Статус', d.status == 1 ? 'Активно' : 'Неактивно'],
                    ['Телефон', d.work_phone || '—'],
                    ['Email', d.email || '—'],
                    ['Сотрудников', d.staff_count || '0'],
                    ['Юр. адрес', d.address || '—'],
                    ['Факт. адрес', d.address_fact || '—'],
                    ['ИНН', d.inn || '—'],
                    ['КПП', d.kpp || '—'],
                    ['ОГРН', d.ogrn || '—'],
                    ['ОКПО', d.okpo || '—'],
                    ['ОКВЭД', d.okvd || '—'],
                    ['Расчётный счёт', d.bank_account || '—'],
                    ['Банк', d.bank_name || '—'],
                    ['БИК', d.bank_bic || '—'],
                    ['Корр. счёт', d.bank_corr || '—'],
                    ['Руководитель', d.director || '—'],
                    ['Должность рук.', d.director_position || '—'],
                    ['Бухгалтер', d.accountant || '—'],
                    ['Примечания', d.notes || '—']
                ];
                myser_show_info_modal('Подразделение: ' + (d.short_name || d.full_name), window.myser_info_card_data, 'MySer v' + (d.version || '1.2.2'));
            } else {
                myserShowToast(res.data.message || 'Ошибка загрузки данных', 'error');
            }
        });
    };
