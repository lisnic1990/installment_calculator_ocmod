<?php
require_once('config.php');
require_once(DIR_SYSTEM . 'startup.php');

// Registry
$registry = new Registry();

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Language
$language = new Language('ru-ru');
$language->load('extension/module/installment_calculator');
$registry->set('language', $language);

echo "<h1>Тест локализации</h1>";
echo "<hr>";

// 1. Проверка языков в БД
echo "<h2>1. Установленные языки:</h2>";
$q = $db->query("SELECT * FROM " . DB_PREFIX . "language ORDER BY sort_order");
if ($q->num_rows) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Название</th><th>Код</th><th>Директория</th><th>Статус</th></tr>";
    foreach ($q->rows as $row) {
        $status = $row['status'] ? '<span style="color:green;">✓ Активен</span>' : '<span style="color:red;">✗ Неактивен</span>';
        echo "<tr>";
        echo "<td>" . $row['language_id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['code'] . "</td>";
        echo "<td>" . $row['directory'] . "</td>";
        echo "<td>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Языки не найдены!</p>";
}

// 2. Проверка файлов
echo "<h2>2. Файлы локализации:</h2>";
$languages = ['ru-ru', 'ro-ro'];
$files = [];

foreach ($languages as $lang) {
    $catalog_file = "catalog/language/$lang/extension/module/installment_calculator.php";
    $admin_file = "admin/language/$lang/extension/module/installment_calculator.php";
    
    echo "<h3>Язык: $lang</h3>";
    echo "<ul>";
    if (file_exists($catalog_file)) {
        echo "<li style='color:green;'>✓ $catalog_file</li>";
    } else {
        echo "<li style='color:red;'>✗ $catalog_file <strong>НЕ НАЙДЕН</strong></li>";
    }
    
    if (file_exists($admin_file)) {
        echo "<li style='color:green;'>✓ $admin_file</li>";
    } else {
        echo "<li style='color:red;'>✗ $admin_file <strong>НЕ НАЙДЕН</strong></li>";
    }
    echo "</ul>";
}

// 3. Загрузка языковых переменных
echo "<h2>3. Загрузка языковых переменных (ru-ru):</h2>";

$language_ru = new Language('ru-ru');
$language_ru->load('extension/module/installment_calculator');

$test_keys = [
    'text_installment_period',
    'text_months',
    'text_buy_installment',
    'heading_popup',
    'text_success',
    'error_name'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Ключ</th><th>Значение</th></tr>";
foreach ($test_keys as $key) {
    $value = $language_ru->get($key);
    $color = ($value && $value != $key) ? 'green' : 'red';
    echo "<tr>";
    echo "<td>$key</td>";
    echo "<td style='color:$color;'>" . htmlspecialchars($value) . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Загрузка румынских переменных
echo "<h2>4. Загрузка языковых переменных (ro-ro):</h2>";

if (file_exists('catalog/language/ro-ro/extension/module/installment_calculator.php')) {
    $language_ro = new Language('ro-ro');
    $language_ro->load('extension/module/installment_calculator');
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Ключ</th><th>Значение</th></tr>";
    foreach ($test_keys as $key) {
        $value = $language_ro->get($key);
        $color = ($value && $value != $key) ? 'green' : 'red';
        echo "<tr>";
        echo "<td>$key</td>";
        echo "<td style='color:$color;'>" . htmlspecialchars($value) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Файл ro-ro не найден!</p>";
}

// 5. Проверка модификации
echo "<h2>5. Проверка модификации контроллера:</h2>";
$mod_file = DIR_STORAGE . 'modification/catalog/controller/product/product.php';

if (file_exists($mod_file)) {
    $content = file_get_contents($mod_file);
    
    if (strpos($content, "load->controller('extension/module/installment_calculator')") !== false) {
        echo "<p style='color:green;'>✓ Вызов контроллера найден (ПРАВИЛЬНО)</p>";
        
        // Показываем фрагмент
        preg_match("/load->controller\('extension\/module\/installment_calculator.*?\n.*?\n/s", $content, $matches);
        if (!empty($matches[0])) {
            echo "<pre style='background:#e8f5e9;padding:10px;'>" . htmlspecialchars($matches[0]) . "</pre>";
        }
    } elseif (strpos($content, "load->view('extension/module/installment_calculator") !== false) {
        echo "<p style='color:red;'>✗ Найден прямой вызов view (НЕПРАВИЛЬНО)</p>";
        echo "<p><strong>Решение:</strong> Загрузите новый install.xml v1.0.9 и обновите модификаторы</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Модификация не найдена</p>";
    }
} else {
    echo "<p style='color:red;'>✗ Файл модификации не найден</p>";
}

echo "<hr>";
echo "<p><strong>После проверки УДАЛИТЕ этот файл!</strong></p>";
?>