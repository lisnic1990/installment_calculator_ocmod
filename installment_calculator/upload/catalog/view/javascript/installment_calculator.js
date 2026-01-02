$(document).ready(function() {
    const $calculator = $('.installment-calculator');
    
    if (!$calculator.length) {
        console.log('Installment Calculator: блок не найден');
        return;
    }
    
    // ========================================
    // ПАРСИНГ ЦЕНЫ И ВАЛЮТЫ
    // ========================================
    
    let price = 0;
    let currency = '';
    
    // Получаем текст цены из разных возможных мест
    let priceText = '';
    let priceElement = null;
    
    // Попытка 1: .price-new
    priceElement = $('.price .price-new').first();
    if (priceElement.length) {
        priceText = priceElement.text();
    }
    
    // Попытка 2: любой h2 с ценой
    if (!priceText) {
        priceElement = $('ul.list-unstyled li h2').first();
        if (priceElement.length) {
            priceText = priceElement.text();
        }
    }
    
    // Попытка 3: любой элемент с классом price
    if (!priceText) {
        priceElement = $('.price').first();
        if (priceElement.length) {
            priceText = priceElement.text();
        }
    }
    
    console.log('Price text found:', priceText);
    
    if (priceText) {
        // ===== ИЗВЛЕЧЕНИЕ ВАЛЮТЫ (универсальное решение) =====
        // Удаляем все цифры, пробелы, точки и запятые - остается только валюта
        const currencyExtracted = priceText.replace(/[\d\s.,]+/g, '').trim();
        
        if (currencyExtracted) {
            currency = currencyExtracted;
        }
        
        console.log('Detected currency:', currency || '(not found)');
        
        // ===== ПАРСИНГ ЧИСЛОВОЙ ЧАСТИ ЦЕНЫ =====
        // Удаляем все кроме цифр, точки и запятой
        let cleanPrice = priceText.replace(/[^\d.,]/g, '');
        
        console.log('Clean price (step 1):', cleanPrice);
        
        // Определяем десятичный разделитель по эвристике:
        // Если последний разделитель (. или ,) идет перед ровно 2 цифрами - это десятичный разделитель
        let decimalSeparator = null;
        let thousandSeparator = null;
        
        const lastSepMatch = cleanPrice.match(/[.,](\d+)$/);
        if (lastSepMatch) {
            const digitsAfterLastSep = lastSepMatch[1].length;
            if (digitsAfterLastSep === 2) {
                // Последний разделитель с ровно 2 цифрами - это десятичный разделитель
                decimalSeparator = cleanPrice.match(/([.,])\d{2}$/)[1];
                thousandSeparator = decimalSeparator === '.' ? ',' : '.';
            } else if (digitsAfterLastSep === 3) {
                // Последний разделитель с 3 цифрами - скорее всего разделитель тысяч
                // Ищем предпоследний разделитель
                const beforeLast = cleanPrice.substring(0, cleanPrice.lastIndexOf(lastSepMatch[0]));
                const prevSepMatch = beforeLast.match(/[.,](\d+)$/);
                if (prevSepMatch && prevSepMatch[1].length === 2) {
                    // Предпоследний с 2 цифрами - это десятичный
                    decimalSeparator = prevSepMatch[0].charAt(0);
                    thousandSeparator = decimalSeparator === '.' ? ',' : '.';
                } else {
                    // Считаем все разделители тысячными
                    thousandSeparator = lastSepMatch[0].charAt(0);
                }
            }
        }
        
        console.log('Decimal separator:', decimalSeparator, 'Thousand separator:', thousandSeparator);
        
        // Обрабатываем строку в зависимости от найденных разделителей
        if (decimalSeparator && thousandSeparator) {
            // Есть и десятичный и тысячный разделители
            // Удаляем все разделители тысяч
            const thousandRegex = new RegExp('\\' + thousandSeparator, 'g');
            cleanPrice = cleanPrice.replace(thousandRegex, '');
            // Заменяем десятичный разделитель на точку
            if (decimalSeparator === ',') {
                cleanPrice = cleanPrice.replace(',', '.');
            }
        } else if (decimalSeparator) {
            // Есть только десятичный разделитель (без тысячных)
            if (decimalSeparator === ',') {
                cleanPrice = cleanPrice.replace(',', '.');
            }
        } else if (thousandSeparator) {
            // Есть только разделители тысяч (без десятичных)
            const thousandRegex = new RegExp('\\' + thousandSeparator, 'g');
            cleanPrice = cleanPrice.replace(thousandRegex, '');
        }
        
        console.log('Clean price (final):', cleanPrice);
        
        price = parseFloat(cleanPrice);
        
        console.log('Parsed price:', price, 'Currency:', currency);
    }
    
    if (!price || price <= 0 || isNaN(price)) {
        console.error('Installment Calculator: Не удалось получить цену товара');
        return;
    }
    
    // Если валюта не определена, используем значение по умолчанию
    if (!currency) {
        currency = 'р.';
        console.warn('Currency not detected, using default:', currency);
    }
    
    // Получение доступных месяцев
    let availableMonths = [12];
    try {
        const monthsData = $calculator.attr('data-months');
        if (monthsData) {
            availableMonths = JSON.parse(monthsData);
        }
    } catch (e) {
        console.error('Installment Calculator: Ошибка парсинга месяцев', e);
    }
    
    // Данные товара
    const productName = $('h1').first().text().trim() || $('title').text().trim();

    // Поиск пути на фото товара
    const selectors = ['a.thumbnail img', '#content img', '.product-info .image img', '.thumbnails img', 'img[itemprop="image"]'];
    const productImage = selectors
    .map(s => document.querySelector(s))
    .find(el => el?.src)?.src || null;

    const productUrl = window.location.href;
    
    console.log('Product:', productName);
    
    let currentMonths = availableMonths[availableMonths.length - 1] || 12;
    
    // Функция форматирования числа с разделителями тысяч
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }
    
    // Функция расчёта
    function calculate(months) {
        const monthly = Math.round(price / months * 100) / 100; // Округляем до копеек
        const monthlyFormatted = formatNumber(monthly.toFixed(2));
        const priceFormatted = formatNumber(price.toFixed(2));
        
        $('.installment-monthly').text(monthlyFormatted);
        $('.installment-period').text(months);
        $('.installment-total').text(priceFormatted);
        $('.installment-price').text(priceFormatted);
        $('.installment-currency').text(currency);
        currentMonths = months;
        
        // Обновляем активную кнопку
        $('.installment-period-btn').each(function() {
            const btnMonths = parseInt($(this).attr('data-months'));
            if (btnMonths === months) {
                $(this).addClass('active').css({
                    'background': '#5c6bc0',
                    'color': 'white',
                    'border-color': '#5c6bc0'
                });
            } else {
                $(this).removeClass('active').css({
                    'background': 'white',
                    'color': '#333',
                    'border-color': '#e5e5e5'
                });
            }
        });
    }
    
    // Инициализация
    calculate(currentMonths);
    
    // Обработчик выбора периода
    $('.installment-period-btn').on('click', function() {
        const selectedMonths = parseInt($(this).attr('data-months'));
        calculate(selectedMonths);
    });
    
    // Открытие модального окна
    $('.installment-btn').on('click', function(e) {
        e.preventDefault();
        
        const monthlyAmount = Math.round(price / currentMonths * 100) / 100;
        
        $('#popup-product-name').text(productName);
        $('#popup-product-image').attr('src', productImage);
        $('#popup-price').text(formatNumber(price.toFixed(2)));
        $('#popup-total').text(formatNumber(price.toFixed(2)));
        $('#popup-months').text(currentMonths);
        $('#popup-monthly').text(formatNumber(monthlyAmount.toFixed(2)));
        
        // Сохраняем валюту в data-атрибут для использования в форме
        $('#installment-popup').data('currency', currency);
        
        $('#installment-popup').modal('show');
    });
    
    // Обработка формы в popup
    $('#installment-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const nameValue = $form.find('input[name="name"]').val().trim();
        const phoneValue = $form.find('input[name="phone"]').val().trim();
        
        console.log('Form submit - Name:', nameValue, 'Phone:', phoneValue);
        
        // Валидация
        if (!nameValue || nameValue.length < 2) {
            $('#installment-message')
                .removeClass('alert-success')
                .addClass('alert alert-danger')
                .text('Пожалуйста, укажите ваше имя (минимум 2 символа)')
                .show();
            $form.find('input[name="name"]').focus();
            return;
        }
        
        if (!phoneValue || phoneValue.length < 6) {
            $('#installment-message')
                .removeClass('alert-success')
                .addClass('alert alert-danger')
                .text('Пожалуйста, укажите номер телефона (минимум 6 цифр)')
                .show();
            $form.find('input[name="phone"]').focus();
            return;
        }
        
        const monthlyAmount = Math.round(price / currentMonths * 100) / 100;
        
        const formData = {
            name: nameValue,
            phone: phoneValue,
            product_name: productName,
            price: formatNumber(price.toFixed(2)) + ' ' + currency,
            months: currentMonths,
            monthly: formatNumber(monthlyAmount.toFixed(2)) + ' ' + currency,
            product_url: productUrl
        };
        
        console.log('Sending form data:', formData);
        
        $.ajax({
            url: 'index.php?route=extension/module/installment_calculator/send',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $form.find('button[type="submit"]')
                    .prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Отправка...');
                $('#installment-message').hide();
            },
            complete: function() {
                $form.find('button[type="submit"]')
                    .prop('disabled', false)
                    .text('Отправить данные');
            },
            success: function(json) {
                console.log('Server response:', json);
                
                if (json.error) {
                    $('#installment-message')
                        .removeClass('alert-success')
                        .addClass('alert alert-danger')
                        .text(json.error)
                        .show();
                } else if (json.success) {
                    $('#installment-message')
                        .removeClass('alert-danger')
                        .addClass('alert alert-success')
                        .text(json.success)
                        .show();
                    
                    // Очистка формы
                    $form[0].reset();
                    
                    // Закрытие через 2 секунды
                    setTimeout(function() {
                        $('#installment-popup').modal('hide');
                        $('#installment-message').hide();
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                $('#installment-message')
                    .removeClass('alert-success')
                    .addClass('alert alert-danger')
                    .text('Ошибка отправки. Пожалуйста, попробуйте позже.')
                    .show();
            }
        });
    });
    
    // Закрытие сообщения при начале ввода
    $('#installment-form input').on('focus', function() {
        $('#installment-message').fadeOut();
    });
    
    // Очистка формы при закрытии popup
    $('#installment-popup').on('hidden.bs.modal', function() {
        $('#installment-form')[0].reset();
        $('#installment-message').hide();
    });
});