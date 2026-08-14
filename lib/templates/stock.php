<?php
defined('ABSPATH') || exit;
?>
<div class="wrap">
    <div class="myser-page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h1 style="margin: 0;">
            <img src="<?php echo MYSER_PLUGIN_URL; ?>assets/admin/images/icons/stock.svg" class="myser-icon" alt="">
            <?php _e('Склад', 'myser'); ?>
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
    $myser_add_label  = '+ ' . __('Добавить товар', 'myser');
    $myser_add_action = 'alert("Добавление товара в разработке");';
    require MYSER_PLUGIN_DIR.'lib/templates/header-actions.php';
    ?>
    <p>Страница управления товарами (в разработке).</p>
</div>
