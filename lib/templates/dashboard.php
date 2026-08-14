<?php
defined('ABSPATH') || exit;
global $wpdb;
$settings     = get_option('myser_settings', []);
$company_name = ($settings['company_name'] ?? 'Мой сервисный центр');

// Счётчики для виджетов дашборда
$count_orders  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}myser_orders");
$count_clients = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}myser_clients");
$count_staff   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}myser_staff");
?>
<div class="wrap">
    <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0; display: flex; align-items: center; gap: 10px;">
            <?php if (!empty($settings['logo_url'])) : ?>
                <img src="<?php echo esc_url($settings['logo_url']); ?>" style="height: 32px; vertical-align: middle;" alt="Логотип">
            <?php else : ?>
                <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/dashboard.svg" class="myser-icon" alt="">
            <?php endif; ?>
            <?php echo esc_html($company_name); ?>
        </h1>
        <div style="font-size: 0.9em; color: #0073aa; text-align: center; flex: 1;">
            MySer v<?php echo MYSER_VERSION; ?>
        </div>
        <div style="text-align: right; min-width: 150px;">
            <button class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ Ребут плагина</button>
            <span id="myser-reboot-status" style="display: block; margin-top: 4px; font-size: 12px;"></span>
        </div>
    </div>
    
    <div class="dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
        <div class="widget" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa;">
            <h3><img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/orders.svg" class="myser-icon" alt=""> Заказы</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $count_orders; ?></p>
            <a href="<?php echo admin_url('admin.php?page=myser-orders'); ?>">Перейти →</a>
        </div>
        <div class="widget" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #46b450;">
            <h3><img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/clients.svg" class="myser-icon" alt=""> Клиенты</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $count_clients; ?></p>
            <a href="<?php echo admin_url('admin.php?page=myser-clients'); ?>">Перейти →</a>
        </div>
        <div class="widget" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #ffba00;">
            <h3><img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/services.svg" class="myser-icon" alt=""> Услуги</h3>
            <p style="font-size: 24px; font-weight: bold;">0 <small style="color:#888;">(скоро)</small></p>
            <a href="<?php echo admin_url('admin.php?page=myser-services'); ?>">Перейти →</a>
        </div>
        <div class="widget" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3232;">
            <h3><img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/stock.svg" class="myser-icon" alt=""> Склад</h3>
            <p style="font-size: 24px; font-weight: bold;">0 <small style="color:#888;">(скоро)</small></p>
            <a href="<?php echo admin_url('admin.php?page=myser-stock'); ?>">Перейти →</a>
        </div>
        <div class="widget" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1;">
            <h3><img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/staff.svg" class="myser-icon" alt=""> Сотрудники</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $count_staff; ?></p>
            <a href="<?php echo admin_url('admin.php?page=myser-staff'); ?>">Перейти →</a>
        </div>
    </div>
</div>

