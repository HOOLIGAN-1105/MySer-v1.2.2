<?php
/**
 * Шаблон страницы "Заказы"
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap myser-admin-wrap">
    <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;">
            <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/orders.svg" class="myser-icon" alt="">
            <?php _e('Заказы', 'myser'); ?>
        </h1>
        <div style="font-size: 0.9em; color: #0073aa; text-align: center; flex: 1;">
            MySer v<?php echo MYSER_VERSION; ?>
        </div>
        <div style="text-align: right; min-width: 150px;">
            <button class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ Ребут плагина</button>
            <span id="myser-reboot-status" style="display: block; margin-top: 4px; font-size: 12px;"></span>
        </div>
    </div>
    <?php
    $myser_add_label  = '+ ' . __('Добавить заказ', 'myser');
    $myser_add_action = 'alert("Форма добавления заказа появится позже");';
    require MYSER_PLUGIN_DIR.'lib/templates/header-actions.php';
    ?>

    <hr class="wp-header-end">
    
    <div class="myser-filter-row">
        <input type="text" id="myser-search" placeholder="<?php _e('Поиск по номеру, клиенту, модели...', 'myser'); ?>" style="flex:1; min-width:200px;">
        <select id="myser-status-filter">
            <option value=""><?php _e('Все статусы', 'myser'); ?></option>
            <?php
            global $wpdb;
            $statuses = $wpdb->get_results("SELECT id, name, color FROM {$wpdb->prefix}myser_statuses ORDER BY sort_order");
            foreach ($statuses as $s) {
                echo '<option value="'.esc_attr($s->id).'">'.esc_html($s->name).'</option>';
            }
            ?>
        </select>
        <label style="font-weight:bold;color:#e326b4;">С даты:</label> <input type="date" id="myser-date-from">
        <label style="font-weight:bold;color:#e326b4;">По дату:</label> <input type="date" id="myser-date-to">
        <button class="button" id="myser-apply-filters"><?php _e('Применить', 'myser'); ?></button>
    </div>
    
    <div class="myser-table-wrap" id="myser-orders-table-wrap">
        <table id="myser-orders-table">
            <thead>
                <tr>
                    <th>№ заказа</th>
                    <th>Дата</th>
                    <th>Клиент</th>
                    <th>Устройство</th>
                    <th>Статус</th>
                    <th>Сумма</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody id="myser-orders-body">
                <tr><td colspan="7">Загрузка...</td></tr>
            </tbody>
        </table>
        <div id="myser-orders-pagination" style="margin-top:10px;"></div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var currentPage = 1;
    var perPage = 20;
    
    function loadOrders(page) {
        page = page || 1;
        var data = {
            action: 'myser_get_orders',
            nonce: myser_ajax.nonce,
            page: page,
            per_page: perPage,
            search: $('#myser-search').val(),
            status_id: $('#myser-status-filter').val(),
            date_from: $('#myser-date-from').val(),
            date_to: $('#myser-date-to').val()
        };
        $('#myser-orders-body').html('<tr><td colspan="7">Загрузка...</td></tr>');
        
        $.post(myser_ajax.ajaxurl, data, function(response) {
            if (response.success) {
                var items = response.data.items || [];
                var total = response.data.total || 0;
                var html = '';
                if (items.length === 0) {
                    html = '<tr><td colspan="7">Нет заказов</td></tr>';
                } else {
                    $.each(items, function(i, order) {
                        html += '<tr>';
                        html += '<td><a href="?page=myser-orders&action=edit&id=' + order.id + '">' + (order.doc_number || '—') + '</a></td>';
                        html += '<td>' + (order.doc_date || '—') + '</td>';
                        html += '<td>' + (order.client_name || '—') + '</td>';
                        html += '<td>' + (order.device_model || '—') + '</td>';
                        html += '<td><span style="background:' + (order.status_color || '#ccc') + ';padding:2px 8px;border-radius:3px;color:#fff;">' + (order.status_name || '—') + '</span></td>';
                        html += '<td>' + (order.grand_total || '0') + '</td>';
                        html += '<td><a href="#" class="button button-small myser-edit-order" data-id="' + order.id + '">Редактировать</a> <a href="#" class="button button-small myser-delete-order" data-id="' + order.id + '">Удалить</a></td>';
                        html += '</tr>';
                    });
                }
                $('#myser-orders-body').html(html);
                
                var totalPages = Math.ceil(total / perPage);
                var pagHtml = '';
                for (var i = 1; i <= totalPages; i++) {
                    pagHtml += '<button class="button" data-page="' + i + '" ' + (i === page ? 'disabled' : '') + '>' + i + '</button>';
                }
                $('#myser-orders-pagination').html(pagHtml);
                $('#myser-orders-pagination button').on('click', function() {
                    loadOrders($(this).data('page'));
                });
            } else {
                $('#myser-orders-body').html('<tr><td colspan="7">Ошибка загрузки: ' + (response.data.message || '') + '</td></tr>');
            }
        }).fail(function() {
            $('#myser-orders-body').html('<tr><td colspan="7">Ошибка соединения</td></tr>');
        });
    }
    
    loadOrders(1);
    
    $('#myser-apply-filters').on('click', function() {
        loadOrders(1);
    });
    
    $('#myser-add-order').on('click', function(e) {
        e.preventDefault();
        alert('Форма добавления заказа появится позже');
    });
    
    $(document).on('click', '.myser-delete-order', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!confirm('Удалить заказ №' + id + '?')) return;
        var row = $(this).closest('tr');
        $.post(myser_ajax.ajaxurl, {
            action: 'myser_delete_order',
            nonce: myser_ajax.nonce,
            order_id: id
        }, function(response) {
            if (response.success) {
                row.fadeOut();
            } else {
                alert('Ошибка: ' + response.data.message);
            }
        });
    });
});
</script>
