<?php
// Автоматическая установка модуля Калькулятор рассрочки
// После установки УДАЛИТЕ этот файл!

define('VERSION', '3.0.3.7');

// Подключаемся к ocStore
require_once('config.php');
require_once(DIR_SYSTEM . 'startup.php');

// Инициализация
$registry = new Registry();
$config = new Config();
$registry->set('config', $config);

$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

echo "<h1>Установка модуля Калькулятор рассрочки</h1>";

// 1. Создаем событие
echo "<p>1. Создание события... ";
$db->query("DELETE FROM `" . DB_PREFIX . "event` WHERE `code` = 'installment_calculator'");
$db->query("INSERT INTO `" . DB_PREFIX . "event` SET 
    `code` = 'installment_calculator',
    `trigger` = 'catalog/controller/product/product/before',
    `action` = 'extension/module/installment_calculator/injectToProduct',
    `status` = 1,
    `sort_order` = 1
");
echo "<strong style='color:green;'>OK</strong></p>";

// 2. Даем права
echo "<p>2. Установка прав доступа... ";
$user_group_query = $db->query("SELECT DISTINCT `user_group_id` FROM `" . DB_PREFIX . "user_group` LIMIT 1");
if ($user_group_query->num_rows) {
    $user_group_id = $user_group_query->row['user_group_id'];
    
    // Получаем текущие права
    $permission_query = $db->query("SELECT * FROM `" . DB_PREFIX . "user_group` WHERE `user_group_id` = '" . (int)$user_group_id . "'");
    
    if ($permission_query->num_rows) {
        $permission = json_decode($permission_query->row['permission'], true);
        
        // Добавляем права для модуля
        $permission['access'][] = 'extension/module/installment_calculator';
        $permission['modify'][] = 'extension/module/installment_calculator';
        
        // Убираем дубли
        $permission['access'] = array_unique($permission['access']);
        $permission['modify'] = array_unique($permission['modify']);
        
        // Сохраняем
        $db->query("UPDATE `" . DB_PREFIX . "user_group` SET `permission` = '" . $db->escape(json_encode($permission)) . "' WHERE `user_group_id` = '" . (int)$user_group_id . "'");
    }
}
echo "<strong style='color:green;'>OK</strong></p>";

// 3. Настройки по умолчанию
echo "<p>3. Установка настроек по умолчанию... ";
$db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_installment_calculator'");

$settings = [
    'module_installment_calculator_status' => 1,
    'module_installment_calculator_months' => '4,6,10,12',
    'module_installment_calculator_email' => ''
];

foreach ($settings as $key => $value) {
    $db->query("INSERT INTO `" . DB_PREFIX . "setting` SET 
        `store_id` = 0,
        `code` = 'module_installment_calculator',
        `key` = '" . $db->escape($key) . "',
        `value` = '" . $db->escape($value) . "',
        `serialized` = 0
    ");
}
echo "<strong style='color:green;'>OK</strong></p>";

// 4. Проверка файлов
echo "<p>4. Проверка файлов:</p>";
echo "<ul>";

$files = [
    'catalog/controller/extension/module/installment_calculator.php',
    'catalog/language/ru-ru/extension/module/installment_calculator.php',
    'catalog/view/theme/default/template/extension/module/installment_calculator.twig',
    'catalog/view/theme/default/template/extension/module/installment_popup.twig',
    'catalog/view/javascript/installment_calculator.js',
    'admin/controller/extension/module/installment_calculator.php',
    'admin/language/ru-ru/extension/module/installment_calculator.php',
    'admin/view/template/extension/module/installment_calculator.twig'
];

$missing = [];
foreach ($files as $file) {
    if (file_exists(DIR_APPLICATION . '../' . $file)) {
        echo "<li style='color:green;'>✓ $file</li>";
    } else {
        echo "<li style='color:red;'>✗ $file <strong>НЕ НАЙДЕН</strong></li>";
        $missing[] = $file;
    }
}
echo "</ul>";

if (empty($missing)) {
    echo "<h2 style='color:green;'>✓ Установка завершена успешно!</h2>";
    echo "<p><strong>Следующие шаги:</strong></p>";
    echo "<ol>";
    echo "<li>Загрузите OCMOD через Админка → Расширения → Установка расширений</li>";
    echo "<li>Обновите модификаторы: Расширения → Модификаторы → Обновить</li>";
    echo "<li>Очистите кэш в админке</li>";
    echo "<li><strong style='color:red;'>УДАЛИТЕ ЭТОТ ФАЙЛ (install_installment.php)!</strong></li>";
    echo "</ol>";
} else {
    echo "<h2 style='color:red;'>✗ Не все файлы найдены</h2>";
    echo "<p>Загрузите недостающие файлы и запустите установщик снова.</p>";
}
?>