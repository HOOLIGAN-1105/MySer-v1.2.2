# Инструкция для разработчиков MySer

> Версия 1.2.1 | Техническая документация

---

## Порядок внесения изменений

1. Править файлы в workspace: `C:\Users\HOOLIGAN\.nanobot\workspace\myser\`
2. Копировать на сервер: `C:\OSPanel\home\MySer\wp-content\plugins\myser\`
   ```powershell
   Copy-Item -Path "C:\Users\HOOLIGAN\.nanobot\workspace\myser\" -Destination "C:\OSPanel\home\MySer\wp-content\plugins\myser\" -Recurse -Force
   ```
3. Сбросить OPcache (кнопка «Ребут плагина» в админке или перезапуск PHP)

**Важно:** Команда `Copy-Item ...\* ...\ -Recurse -Force` не копирует файлы в корне (`myser.php`, `uninstall.php`). Используйте копирование папки целиком.

---

## Структура проекта

```
myser/
├── assets/                  # Публичные ассеты
│   ├── admin/
│   │   ├── css/admin.css    # Стили админ-панели
│   │   ├── css/style.css    # Дополнительные стили
│   │   ├── js/admin.js      # Скрипты админ-панели
│   │   └── images/icons/    # SVG-иконки меню
│   ├── css/booking.css      # Стили формы онлайн-записи
│   └── js/booking.js        # Скрипты формы онлайн-записи
├── languages/myser.pot      # Шаблон перевода
├── lib/
│   ├── admin/
│   │   └── menu.php         # Регистрация меню и страниц
│   ├── includes/            # Ядро плагина
│   │   ├── activator.php    # Активация и создание таблиц
│   │   ├── ajax-handler.php # Обработка AJAX-запросов
│   │   ├── backup.php       # Фасад для бекапов
│   │   ├── BackupCore.php   # Ядро бекапов
│   │   ├── BackupExport.php # Экспорт данных
│   │   ├── BackupImport.php # Импорт данных
│   │   ├── BackupManager.php  # Управление файлами бекапов
│   │   ├── core.php         # Вспомогательные функции
│   │   ├── database.php     # Работа с БД
│   │   ├── error-handler.php# Обработка ошибок
│   │   ├── functions.php    # Общие функции
│   │   ├── logger.php       # Система логирования
│   │   └── migrator.php     # Миграции БД
│   └── templates/           # HTML-шаблоны страниц
│       ├── clients.php
│       ├── dashboard.php
│       ├── header-actions.php
│       ├── logs.php
│       ├── orders.php
│       ├── reboot-button.php
│       ├── services.php
│       ├── settings.php     # Страница настроек (содержит встроенный JS)
│       ├── staff.php
│       └── stock.php
├── myser.php                # Главный файл плагина
├── uninstall.php            # Деинсталляция
├── README.md
├── USER_GUIDE.md
└── INSTRUCTION.md
```

---

## Классы и автозагрузка

Плагин использует PSR-4-like автозагрузку через `spl_autoload_register`:
- Namespace: `MySer\`
- Базовая директория: `lib/includes/`

```php
spl_autoload_register(function ($class) {
    $prefix   = 'MySer\\';
    $base_dir = MYSER_PLUGIN_DIR.'lib/includes/';
    if (strpos($class, $prefix) === 0) {
        $relative_class = substr($class, strlen($prefix));
        $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
        if (file_exists($file)) include_once $file;
    }
});
```

---

## AJAX-обработчики

Все обработчики находятся в `lib/includes/ajax-handler.php` (класс `Ajax_Handler`).

### Регистрация

```php
$actions = [
    'myser_get_departments',
    'myser_get_department',
    'myser_save_department',
    'myser_delete_department',
    // ...
];
foreach ($actions as $action) {
    add_action('wp_ajax_'.$action, [self::class, str_replace('myser_', '', $action)]);
}
```

**Известная проблема:** `wp_ajax_nopriv_*` не регистрируются, хотя заявлены в PHPDoc.

### Проверка nonce

```php
private static function verify_nonce() {
    $nonce = $_POST['_ajax_nonce'] ?? $_POST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'myser_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }
}
```

Nonce создаётся в шаблоне: `$nonce = wp_create_nonce('myser_nonce');`  
Передаётся в JS через `_ajax_nonce: '<?php echo $nonce; ?>'`.

---

## Страница настроек (settings.php)

### Структура

- Форма отправляется на `options.php` (стандартный механизм WordPress Settings API)
- Группа настроек: `myser_settings_group`
- Санитизация через `Admin_Menu::sanitize_settings()`
- Toast-уведомления через `sessionStorage` (ключ `myserSettingsSaved`)

### JavaScript

Весь JS-код (строки 354-770) встроен в PHP-файл. **Требуется вынос** в `assets/admin/js/settings.js`.

### Toast-уведомления

- При отправке формы устанавливается `sessionStorage.setItem('myserSettingsSaved', '1')`
- При загрузке страницы проверяется флаг и показывается toast
- Флаг удаляется после показа

---

## База данных

### Таблицы

| Таблица | Назначение |
|---------|------------|
| `{prefix}myser_departments` | Подразделения |
| `{prefix}myser_staff` | Сотрудники |
| `{prefix}myser_salary_grids` | Зарплатные сетки |
| `{prefix}myser_staff_salary_grids` | Начисления сотрудников |
| `{prefix}myser_clients` | Клиенты |
| `{prefix}myser_orders` | Заказы |
| `{prefix}myser_services` | Услуги |
| `{prefix}myser_stock` | Склад |

Создание таблиц: `lib/includes/activator.php` → `MySer\Activator::activate()`

---

## Логирование

```php
MySer\Logger::get()->info('Сообщение', ['key' => 'value']);
MySer\Logger::get()->warning('Предупреждение');
MySer\Logger::get()->error('Ошибка', ['error' => $e->getMessage()]);
MySer\Logger::get()->critical('Критическая ошибка');
```

Лог-файл: `wp-content/uploads/myser-logs/` (точный путь зависит от конфигурации)

---

## План доработок

### Приоритетные

- [ ] Вынести JS из `settings.php` в отдельный файл
- [ ] Добавить `wp_ajax_nopriv_*` для публичных действий
- [ ] Обернуть все строки в функции перевода (`__()`, `_e()`)
- [x] Удалить дублирующийся файл `backup-manager.php` (исправлено в v1.2.1)
- [x] Унифицировать структуру `assets/` и `lib/admin/` — дублирующиеся CSS/JS удалены (v1.2.2)

### Желаемые

- [ ] Заменить самописную `verify_nonce()` на `check_ajax_referer()`
- [ ] Добавить кастомные capabilities для ролей
- [ ] Реализовать REST API эндпоинты
- [ ] Добавить unit-тесты

---

## Сборка релиза

```powershell
# Создать архив для распространения
Compress-Archive -Path "C:\Users\HOOLIGAN\.nanobot\workspace\myser\*" -DestinationPath "C:\Users\HOOLIGAN\.nanobot\workspace\myser-1.2.1.zip" -Force
```

**Важно:** Перед сборкой убедиться, что удалены:
- `settings.php.bak`
- `myser-error.log`
- Любые другие временные файлы
