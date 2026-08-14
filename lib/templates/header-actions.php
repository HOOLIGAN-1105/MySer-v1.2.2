<?php
/**
 * Панель действий на страницах админки: кнопка добавления + кнопка ребута
 * Ожидает глобальные переменные:
 *   $myser_add_action — JavaScript-код для onclick кнопки добавления (например, "myser_add_client()")
 *   $myser_add_label  — текст кнопки добавления (например, "+ Добавить клиента")
 */
if (!defined('ABSPATH')) {
    exit;
}

// Если переменные не заданы, игнорируем
$action = isset($myser_add_action) ? $myser_add_action : '';
$label  = isset($myser_add_label) ? $myser_add_label : '';
?>
 <div class="myser-reboot-top" style="display: flex; justify-content: flex-start; align-items: center; margin: 10px 0 20px 0;">
     <div style="display: flex; flex-direction: column;">
         <?php if ($action && $label) : ?>
             <button class="button button-primary" onclick="<?php echo esc_attr($action); ?>"><?php echo esc_html($label); ?></button>
         <?php endif; ?>
     </div>
 </div>
