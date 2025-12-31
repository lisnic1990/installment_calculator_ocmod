<?php
require_once('config.php');
require_once(DIR_SYSTEM . 'startup.php');

$registry = new Registry();
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { border-bottom: 2px solid #333; padding-bottom: 5px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
table { border-collapse: collapse; margin: 10px 0; }
table td, table th { border: 1px solid #ddd; padding: 8px; text-align: left; }
.box { padding: 15px; margin: 10px 0; border-radius: 5px; }
.box-success { background: #e8f5e9; border-left: 4px solid green; }
.box-error { background: #ffebee; border-left: 4px solid red; }
.box-info { background: #e3f2fd; border-left: 4px solid blue; }
</style>";

echo "<h1>🔍 Диагностика модуля рассрочки</h1>";
echo "<p><small>Время проверки: " . date('Y-m-d H:i:s') . "</small></p>";

// 0. Конфигурация
echo "<h2>0️⃣ Конфигурация системы</h2>";
echo "<table>";
echo "<tr><th>Параметр</th><th>Значение</th></tr>";
echo "<tr><td>DIR_STORAGE</td><td><strong>" . DIR_STORAGE . "</strong></td></tr>";
echo "<tr><td>DIR_MODIFICATION</td><td><strong>" . DIR_STORAGE . "modification/</strong></td></tr>";
echo "<tr><td>Тема (config)</td><td><strong>" . (defined('HTTP_CATALOG') ? 'unishop2_free' : 'проверьте в админке') . "</strong></td></tr>";
echo "</table>";

// 1. Проверка настроек модуля
echo "<h2>1️⃣ Настройки модуля в БД</h2>";
$q = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_installment_calculator' ORDER BY `key`");
if ($q->num_rows) {
    echo "<table>";
    echo "<tr><th>Параметр</th><th>Значение</th></tr>";
    foreach ($q->rows as $row) {
        $key = str_replace('module_installment_calculator_', '', $row['key']);
        echo "<tr><td>" . $key . "</td><td><strong>" . htmlspecialchars($row['value']) . "</strong></td></tr>";
    }
    echo "</table>";
    
    // Проверка статуса
    $status_q = $db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = 'module_installment_calculator_status'");
    if ($status_q->num_rows && $status_q->row['value'] == '1') {
        echo "<p class='success'>✓ Модуль включен</p>";
    } else {
        echo "<p class='error'>✗ Модуль выключен - включите в админке!</p>";
    }
} else {
    echo "<div class='box box-error'>";
    echo "<p class='error'><strong>❌ Настройки не найдены!</strong></p>";
    echo "<p>Модуль не установлен. Выполните:</p>";
    echo "<pre>Админка → Расширения → Модули → Installment Calculator → Установить</pre>";
    echo "</div>";
}

// 2. Проверка файлов
echo "<h2>2️⃣ Файлы модуля</h2>";
$files = [
    'catalog/controller/extension/module/installment_calculator.php',
    'catalog/language/ru-ru/extension/module/installment_calculator.php',
    'catalog/view/theme/default/template/extension/module/installment_calculator.twig',
    'catalog/view/theme/default/template/extension/module/installment_popup.twig',
    'catalog/view/theme/unishop2_free/template/extension/module/installment_calculator.twig',
    'catalog/view/theme/unishop2_free/template/extension/module/installment_popup.twig',
    'catalog/view/javascript/installment_calculator.js',
    'admin/controller/extension/module/installment_calculator.php',
    'admin/language/ru-ru/extension/module/installment_calculator.php',
    'admin/view/template/extension/module/installment_calculator.twig'
];

$missing = [];
echo "<ul>";
foreach ($files as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<li class='success'>✓ $file <small>(" . number_format($size) . " bytes)</small></li>";
    } else {
        echo "<li class='error'>✗ $file <strong>НЕ НАЙДЕН!</strong></li>";
        $missing[] = $file;
    }
}
echo "</ul>";

// 3. Проверка OCMOD в БД
echo "<h2>3️⃣ OCMOD в базе данных</h2>";
$q = $db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE code = 'installment_calculator'");
if ($q->num_rows) {
    echo "<table>";
    echo "<tr><th>Название</th><th>Код</th><th>Статус</th><th>Дата добавления</th></tr>";
    $row = $q->row;
    $status = $row['status'] ? '<span class="success">✓ Включен</span>' : '<span class="error">✗ Выключен</span>';
    echo "<tr>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['code'] . "</td>";
    echo "<td>" . $status . "</td>";
    echo "<td>" . $row['date_added'] . "</td>";
    echo "</tr></table>";
    
    if ($row['status']) {
        echo "<p class='success'>✓ OCMOD загружен и активен</p>";
    } else {
        echo "<p class='error'>✗ OCMOD выключен - включите в Модификаторах</p>";
    }
} else {
    echo "<div class='box box-error'>";
    echo "<p class='error'><strong>❌ OCMOD не загружен!</strong></p>";
    echo "<p>Загрузите install.xml:</p>";
    echo "<pre>Админка → Расширения → Установка расширений → Загрузить install.xml</pre>";
    echo "</div>";
}

// 4. Проверка применения модификаций
echo "<h2>4️⃣ Применение модификаций</h2>";
$mod_file = DIR_STORAGE . 'modification/catalog/controller/product/product.php';
echo "<p><strong>Проверяем:</strong> <code>" . $mod_file . "</code></p>";

if (file_exists($mod_file)) {
    $size = filesize($mod_file);
    echo "<p class='success'>✓ Файл существует (" . number_format($size) . " bytes)</p>";
    
    $content = file_get_contents($mod_file);
    if (strpos($content, 'Installment Calculator') !== false) {
        echo "<p class='success'>✓✓ <strong>Модификация ПРИМЕНЕНА!</strong></p>";
        
        // Показываем фрагмент
        if (preg_match('/\/\/ Installment Calculator[^\n]*\n[^\n]*\n[^\n]*\n[^\n]*\n[^\n]*/s', $content, $matches)) {
            echo "<div class='box box-success'>";
            echo "<strong>Найденный код:</strong>";
            echo "<pre style='background:#fff;padding:10px;overflow-x:auto;'>" . htmlspecialchars($matches[0]) . "...</pre>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>✗ Модификация НЕ применена в файле</p>";
        echo "<div class='box box-error'>";
        echo "<p><strong>Решение:</strong></p>";
        echo "<pre>Админка → Расширения → Модификаторы → Обновить (кнопка справа вверху)</pre>";
        echo "</div>";
    }
} else {
    echo "<p class='error'>✗ Файл модификации не найден</p>";
    echo "<div class='box box-error'>";
    echo "<p><strong>Причины:</strong></p>";
    echo "<ul>";
    echo "<li>Модификаторы не обновлены</li>";
    echo "<li>OCMOD не загружен</li>";
    echo "<li>Путь к storage неверный</li>";
    echo "</ul>";
    echo "</div>";
}

// 5. Проверка модифицированного шаблона
echo "<h2>5️⃣ Модификация шаблона product.twig</h2>";
$tpl_mod = DIR_STORAGE . 'modification/catalog/view/theme/unishop2_free/template/product/product.twig';
echo "<p><strong>Проверяем:</strong> <code>" . $tpl_mod . "</code></p>";

if (file_exists($tpl_mod)) {
    echo "<p class='success'>✓ Модифицированный шаблон существует</p>";
    
    $tpl_content = file_get_contents($tpl_mod);
    if (strpos($tpl_content, 'installment_calculator') !== false) {
        echo "<p class='success'>✓✓ Код калькулятора найден в шаблоне!</p>";
    } else {
        echo "<p class='error'>✗ Код калькулятора НЕ найден в шаблоне</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Модифицированный шаблон не найден (возможно, ещё не создан)</p>";
}

// 6. Проверка событий (опционально)
echo "<h2>6️⃣ События (опционально)</h2>";
$q = $db->query("SELECT * FROM " . DB_PREFIX . "event WHERE code = 'installment_calculator'");
if ($q->num_rows) {
    echo "<table>";
    echo "<tr><th>Trigger</th><th>Action</th><th>Status</th></tr>";
    echo "<tr>";
    echo "<td>" . $q->row['trigger'] . "</td>";
    echo "<td>" . $q->row['action'] . "</td>";
    echo "<td>" . ($q->row['status'] ? '<span class="success">✓</span>' : '<span class="error">✗</span>') . "</td>";
    echo "</tr></table>";
} else {
    echo "<p class='warning'>⚠️ События не используются (нормально для OCMOD версии)</p>";
}

// Итоговый отчёт
echo "<hr>";
echo "<h2>📋 ИТОГОВЫЙ ОТЧЁТ</h2>";

$critical_errors = [];
$warnings = [];

if (!$q->num_rows) {
    $q = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE code = 'module_installment_calculator'");
    if (!$q->num_rows) {
        $critical_errors[] = "Модуль не установлен в системе";
    }
}

$q = $db->query("SELECT * FROM " . DB_PREFIX . "modification WHERE code = 'installment_calculator'");
if (!$q->num_rows) {
    $critical_errors[] = "OCMOD не загружен - загрузите install.xml";
}

if (!file_exists($mod_file)) {
    $critical_errors[] = "Модификации не применены - обновите модификаторы";
} elseif (file_exists($mod_file)) {
    $content = file_get_contents($mod_file);
    if (strpos($content, 'Installment Calculator') === false) {
        $critical_errors[] = "Модификация не применена к product.php - обновите модификаторы";
    }
}

if (!empty($missing)) {
    $critical_errors[] = "Отсутствуют " . count($missing) . " файлов - загрузите их по FTP";
}

if (empty($critical_errors)) {
    echo "<div class='box box-success'>";
    echo "<h3 style='color:green;margin:0;'>✅ ВСЁ НАСТРОЕНО ПРАВИЛЬНО!</h3>";
    echo "<p>Модуль должен отображаться на странице товара.</p>";
    echo "<p><strong>Если не видите модуль:</strong></p>";
    echo "<ol>";
    echo "<li>Нажмите <strong>Ctrl+F5</strong> для жесткого обновления страницы</li>";
    echo "<li>Откройте <strong>F12 → Console</strong> и проверьте ошибки JavaScript</li>";
    echo "<li>Убедитесь, что текущая тема действительно <strong>unishop2_free</strong></li>";
    echo "<li>Очистите кэш браузера</li>";
    echo "</ol>";
    echo "<p><a href='http://test.aeroclima.md/index.php?route=product/product&product_id=40' target='_blank' style='display:inline-block;background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>→ Открыть страницу товара</a></p>";
    echo "</div>";
} else {
    echo "<div class='box box-error'>";
    echo "<h3 style='color:red;margin:0;'>❌ НАЙДЕНЫ КРИТИЧЕСКИЕ ОШИБКИ:</h3>";
    echo "<ol>";
    foreach ($critical_errors as $error) {
        echo "<li><strong>" . $error . "</strong></li>";
    }
    echo "</ol>";
    echo "<p><strong>Порядок исправления:</strong></p>";
    echo "<ol>";
    echo "<li>Загрузите недостающие файлы (если есть)</li>";
    echo "<li>Загрузите install.xml через админку</li>";
    echo "<li>Обновите модификаторы</li>";
    echo "<li>Очистите кэш</li>";
    echo "<li>Перезапустите этот скрипт</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<div class='box box-info'>";
echo "<p><strong>⚠️ ВАЖНО:</strong> После успешной проверки <strong style='color:red;'>УДАЛИТЕ этот файл (test_installment.php)</strong> с сервера!</p>";
echo "</div>";
?>