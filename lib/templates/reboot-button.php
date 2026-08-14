<?php
/**
 * Шаблон кнопки "Ребут плагина"
 * Используется на всех страницах админки MySer
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="myser-reboot-top">
    <div>
        <button class="button button-secondary" id="myser-reboot-btn" onclick="myser_reboot_plugin()">♻️ Ребут плагина</button>
        <span id="myser-reboot-status" style="margin-left:10px;"></span>
    </div>
</div>
