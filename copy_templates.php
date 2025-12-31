<?php
// Скрипт копирования шаблонов модуля в тему unishop2_free
// После выполнения УДАЛИТЕ этот файл!

$source_dir = 'catalog/view/theme/default/template/extension/module/';
$target_dir = 'catalog/view/theme/unishop2_free/template/extension/module/';

$files = [
    'installment_calculator.twig',
    'installment_popup.twig'
];

echo "<h1>Копирование шаблонов модуля</h1>";
echo "<hr>";

// Создаём целевую папку если её нет
if (!is_dir($target_dir)) {
    if (mkdir($target_dir, 0755, true)) {
        echo "<p style='color:green;'>✓ Создана папка: $target_dir</p>";
    } else {
        echo "<p style='color:red;'>✗ Не удалось создать папку: $target_dir</p>";
        echo "<p><strong>Решение:</strong> Создайте папку вручную через FTP</p>";
        exit;
    }
} else {
    echo "<p style='color:blue;'>ℹ️ Папка уже существует: $target_dir</p>";
}

// Копируем файлы
$success = 0;
$errors = 0;

foreach ($files as $file) {
    $source = $source_dir . $file;
    $target = $target_dir . $file;
    
    echo "<p><strong>Копирование:</strong> $file</p>";
    
    if (!file_exists($source)) {
        echo "<p style='color:red;'>  ✗ Исходный файл не найден: $source</p>";
        $errors++;
        continue;
    }
    
    if (copy($source, $target)) {
        echo "<p style='color:green;'>  ✓ Скопировано успешно</p>";
        echo "<p style='color:gray;'>    Размер: " . filesize($target) . " bytes</p>";
        $success++;
    } else {
        echo "<p style='color:red;'>  ✗ Ошибка копирования</p>";
        $errors++;
    }
    
    echo "<br>";
}

// Итог
echo "<hr>";
echo "<h2>Результат:</h2>";
echo "<p><strong>Успешно:</strong> $success файлов</p>";
echo "<p><strong>Ошибки:</strong> $errors файлов</p>";

if ($errors == 0) {
    echo "<div style='background:#e8f5e9;padding:15px;border-left:4px solid green;margin:20px 0;'>";
    echo "<h3 style='color:green;margin:0;'>✅ Копирование завершено успешно!</h3>";
    echo "<p>Теперь выполните:</p>";
    echo "<ol>";
    echo "<li>Обновите модификаторы в админке</li>";
    echo "<li>Очистите кэш</li>";
    echo "<li>Откройте страницу товара</li>";
    echo "<li><strong style='color:red;'>УДАЛИТЕ этот файл (copy_templates.php)!</strong></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background:#ffebee;padding:15px;border-left:4px solid red;margin:20px 0;'>";
    echo "<h3 style='color:red;margin:0;'>❌ Есть ошибки!</h3>";
    echo "<p>Скопируйте файлы вручную через FTP</p>";
    echo "</div>";
}

// Проверка файлов
echo "<hr>";
echo "<h2>Проверка установленных файлов:</h2>";
echo "<ul>";
foreach ($files as $file) {
    $path = $target_dir . $file;
    if (file_exists($path)) {
        echo "<li style='color:green;'>✓ $path (" . filesize($path) . " bytes)</li>";
    } else {
        echo "<li style='color:red;'>✗ $path <strong>НЕ НАЙДЕН</strong></li>";
    }
}
echo "</ul>";
?>