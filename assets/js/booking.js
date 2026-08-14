jQuery(document).ready(function($) { let currentStep = 1; let totalSteps = 4; let selectedData = { filial_id: null, staff_id: null, date: null, time: null }; let selectedSlotElement = null;
function showStep(step) {
    $('.myser-booking-step').hide();
    $('#step-' + step).show();
    $('#myser-prev').toggle(step > 1);
    $('#myser-next').toggle(step < totalSteps);
    $('#myser-submit').toggle(step === totalSteps);
    currentStep = step;
    $('#myser-message').hide();
    updateButtons();
}

function updateButtons() {
    if (currentStep === 1) {
        $('#myser-next').prop('disabled', !selectedData.filial_id);
    } else if (currentStep === 2) {
        $('#myser-next').prop('disabled', !selectedData.staff_id);
    } else if (currentStep === 3) {
        $('#myser-next').prop('disabled', !selectedData.date || !selectedData.time);
    } else if (currentStep === 4) {
        let name = $('#client_name').val().trim();
        let phone = $('#client_phone').val().trim();
        $('#myser-submit').prop('disabled', !name || !phone);
    }
}

function showMessage(text, type) {
    let msg = $('#myser-message');
    msg.text(text).removeClass('myser-message-success myser-message-error myser-message-info');
    if (type === 'success') msg.addClass('myser-message-success');
    else if (type === 'error') msg.addClass('myser-message-error');
    else msg.addClass('myser-message-info');
    msg.show();
}

function showLoading(show) {
    $('#myser-loading').toggle(show);
    $('#myser-next, #myser-submit, #myser-prev').prop('disabled', show);
}

// Load filials
function loadFilials() {
    showLoading(true);
    $.post(myser_ajax.ajax_url, {
        action: 'myser_get_filials',
        nonce: myser_ajax.nonce
    }, function(response) {
        showLoading(false);
        if (response.success && response.data) {
            let select = $('#myser-filial');
            select.find('option:not(:first)').remove();
            $.each(response.data, function(i, filial) {
                select.append($('<option>', {
                    value: filial.id,
                    text: filial.name + (filial.prefix ? ' (' + filial.prefix + ')' : '')
                }));
            });
        } else {
            showMessage('Не удалось загрузить филиалы', 'error');
        }
    }).fail(function() {
        showLoading(false);
        showMessage('Ошибка загрузки филиалов', 'error');
    });
}

// Load masters
function loadMasters(filialId) {
    showLoading(true);
    $.post(myser_ajax.ajax_url, {
        action: 'myser_get_masters',
        filial_id: filialId,
        nonce: myser_ajax.nonce
    }, function(response) {
        showLoading(false);
        if (response.success && response.data) {
            let select = $('#myser-master');
            select.find('option:not(:first)').remove();
            $.each(response.data, function(i, master) {
                select.append($('<option>', {
                    value: master.id,
                    text: master.name + (master.department_name ? ' (' + master.department_name + ')' : '')
                }));
            });
        } else {
            showMessage('Не удалось загрузить мастеров', 'error');
        }
    }).fail(function() {
        showLoading(false);
        showMessage('Ошибка загрузки мастеров', 'error');
    });
}

// Load slots
function loadSlots(staffId, date) {
    showLoading(true);
    $('#myser-slots').html('');
    $.post(myser_ajax.ajax_url, {
        action: 'myser_get_available_slots',
        staff_id: staffId,
        date: date,
        duration: 30,
        nonce: myser_ajax.nonce
    }, function(response) {
        showLoading(false);
        if (response.success && response.data) {
            let container = $('#myser-slots');
            let slots = response.data.slots || [];
            if (slots.length === 0) {
                container.html('<p>Нет доступных слотов на эту дату</p>');
                return;
            }
            $.each(slots, function(i, slot) {
                let div = $('<div>', {
                    class: 'myser-slot',
                    text: slot.time,
                    'data-time': slot.time
                });
                if (slot.available) {
                    div.addClass('myser-slot-available');
                } else if (slot.is_break) {
                    div.addClass('myser-slot-break');
                    div.text(slot.time + ' ☕');
                } else if (slot.booked) {
                    div.addClass('myser-slot-unavailable');
                    div.text(slot.time + ' ❌');
                } else {
                    div.addClass('myser-slot-unavailable');
                }
                if (slot.available) {
                    div.on('click', function() {
                        if (selectedSlotElement) {
                            selectedSlotElement.removeClass('myser-slot-selected');
                        }
                        $(this).addClass('myser-slot-selected');
                        selectedSlotElement = $(this);
                        selectedData.time = $(this).data('time');
                        $('#myser-next').prop('disabled', false);
                    });
                }
                container.append(div);
            });
        } else {
            showMessage('Не удалось загрузить слоты', 'error');
        }
    }).fail(function() {
        showLoading(false);
        showMessage('Ошибка загрузки слотов', 'error');
    });
}

// Event: filial change
$('#myser-filial').on('change', function() {
    let val = $(this).val();
    if (val) {
        selectedData.filial_id = parseInt(val);
        $('#filial-info').show().html('<p>✅ Филиал выбран. Перейдите к следующему шагу.</p>');
        $('#myser-next').prop('disabled', false);
        // Load masters for this filial
        loadMasters(selectedData.filial_id);
        $('#step-2').show();
    } else {
        selectedData.filial_id = null;
        $('#filial-info').hide();
        $('#myser-next').prop('disabled', true);
    }
});

// Event: master change
$('#myser-master').on('change', function() {
    let val = $(this).val();
    if (val) {
        selectedData.staff_id = parseInt(val);
        $('#myser-next').prop('disabled', false);
    } else {
        selectedData.staff_id = null;
        $('#myser-next').prop('disabled', true);
    }
});

// Event: date change
$('#myser-date').on('change', function() {
    let val = $(this).val();
    if (val) {
        selectedData.date = val;
        selectedData.time = null;
        if (selectedSlotElement) {
            selectedSlotElement.removeClass('myser-slot-selected');
            selectedSlotElement = null;
        }
        loadSlots(selectedData.staff_id, val);
    } else {
        selectedData.date = null;
        $('#myser-slots').html('');
    }
});

// Event: form inputs for step 4
$('#client_name, #client_phone').on('input', function() {
    let name = $('#client_name').val().trim();
    let phone = $('#client_phone').val().trim();
    $('#myser-submit').prop('disabled', !name || !phone);
});

// Next button
$('#myser-next').on('click', function() {
    if (currentStep === 1 && !selectedData.filial_id) {
        showMessage('Пожалуйста, выберите филиал', 'error');
        return;
    }
    if (currentStep === 2 && !selectedData.staff_id) {
        showMessage('Пожалуйста, выберите мастера', 'error');
        return;
    }
    if (currentStep === 3 && (!selectedData.date || !selectedData.time)) {
        showMessage('Пожалуйста, выберите дату и время', 'error');
        return;
    }
    if (currentStep === 2) {
        // Set default date
        if (!$('#myser-date').val()) {
            let today = new Date().toISOString().split('T')[0];
            $('#myser-date').val(today);
            selectedData.date = today;
            loadSlots(selectedData.staff_id, today);
        }
    }
    showStep(currentStep + 1);
    updateButtons();
});

// Prev button
$('#myser-prev').on('click', function() {
    showStep(currentStep - 1);
    updateButtons();
});

// Submit
$('#myser-submit').on('click', function() {
    let name = $('#client_name').val().trim();
    let phone = $('#client_phone').val().trim();
    if (!name || !phone) {
        showMessage('Заполните имя и телефон', 'error');
        return;
    }

    let data = {
        action: 'myser_save_appointment',
        filial_id: selectedData.filial_id,
        staff_id: selectedData.staff_id,
        appointment_date: selectedData.date,
        appointment_time: selectedData.time,
        client_name: name,
        client_phone: phone,
        client_email: $('#client_email').val().trim(),
        device_type: $('#device_type').val().trim(),
        device_model: $('#device_model').val().trim(),
        client_complaint: $('#client_complaint').val().trim(),
        notes: $('#notes').val().trim(),
        nonce: myser_ajax.nonce
    };

    showLoading(true);
    $('#myser-submit').prop('disabled', true);

    $.post(myser_ajax.ajax_url, data, function(response) {
        showLoading(false);
        if (response.success) {
            let msg = '✅ Запись успешно создана!';
            if (response.data && response.data.order_id) {
                msg += ' Номер заказа: #' + response.data.order_id;
            }
            showMessage(msg, 'success');
            $('#myser-submit').prop('disabled', true);
            $('#myser-summary').html('<h4> Итог:</h4><p><strong>Филиал:</strong> ' + 
                $('#myser-filial option:selected').text() + 
                '</p><p><strong>Мастер:</strong> ' + $('#myser-master option:selected').text() + 
                '</p><p><strong>Дата:</strong> ' + selectedData.date + 
                '</p><p><strong>Время:</strong> ' + selectedData.time + 
                '</p><p><strong>Клиент:</strong> ' + name + 
                '</p><p><strong>Телефон:</strong> ' + phone + 
                '</p>');
            $('#myser-summary').show();
        } else {
            let msg = '❌ Ошибка: ' + (response.data && response.data.message ? response.data.message : 'Не удалось создать запись');
            showMessage(msg, 'error');
            $('#myser-submit').prop('disabled', false);
        }
    }).fail(function() {
        showLoading(false);
        showMessage('Ошибка сети. Попробуйте снова.', 'error');
        $('#myser-submit').prop('disabled', false);
    });
});

// Init
loadFilials();
showStep(1);
updateButtons();

// Set default date
let today = new Date().toISOString().split('T')[0];
$('#myser-date').attr('min', today);
$('#myser-date').attr('max', new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0]);
})