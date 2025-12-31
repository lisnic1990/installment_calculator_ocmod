$(document).ready(function() {
    const $calculator = $('.installment-calculator');
    
    if (!$calculator.length) {
        console.log('Installment Calculator: блок не найден');
        return;
    }
    
    // Получение цены - UniShop2 специфично
    let price = 0;
    
    // Попытка 1: стандартный селектор
    let priceText = $('.price .price-new').first().text();
    
    // Попытка 2: для UniShop2
    if (!priceText) {
        priceText = $('ul.list-unstyled li h2').first().text();
    }
    
    // Попытка 3: любая цена на странице
    if (!priceText) {
        priceText = $('h2:contains("р.")').first().text();
    }
    
    console.log('Price text found:', priceText);
    
    price = parseFloat(priceText.replace(/[^\d]/g, ''));
    
    if (!price || price <= 0) {
        console.error('Installment Calculator: Не удалось получить цену товара');
        // Пробуем взять из meta или data атрибута
        price = parseFloat($('meta[property="product:price:amount"]').attr('content')) || 0;
    }
    
    console.log('Final price:', price);
    
    if (price <= 0) {
        return;
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
    
    // Данные товара - для UniShop2
    const productName = $('h1').first().text().trim() || $('title').text().trim();
    const productImage = $('.thumbnail img').first().attr('src') || 
                         $('.product-thumb img').first().attr('src') ||
                         $('img[itemprop="image"]').first().attr('src');
    const productUrl = window.location.href;
    
    console.log('Product:', productName, productImage);
    
    let currentMonths = availableMonths[availableMonths.length - 1] || 12;
    
    // Функция расчёта
    function calculate(months) {
        const monthly = Math.round(price / months);
        $('.installment-monthly').text(monthly);
        $('.installment-period').text(months);
        $('.installment-total').text(price);
        $('.installment-price').text(price);
        currentMonths = months;
        
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
    $('.installment-btn').on('click', function() {
        $('#popup-product-name').text(productName);
        $('#popup-product-image').attr('src', productImage);
        $('#popup-price').text(price);
        $('#popup-total').text(price);
        $('#popup-months').text(currentMonths);
        $('#popup-monthly').text(Math.round(price / currentMonths));
        
        $('#installment-popup').modal('show');
    });
    
    // Отправка формы
    $('#installment-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('input[name="name"]').val().trim(),
            phone: $('input[name="phone"]').val().trim(),
            product_name: productName,
            price: price + ' р.',
            months: currentMonths,
            monthly: Math.round(price / currentMonths) + ' р.',
            product_url: productUrl
        };
        
        if (!formData.name) {
            $('#installment-message')
                .removeClass('alert-success')
                .addClass('alert alert-danger')
                .text('Пожалуйста, укажите ваше имя')
                .show();
            return;
        }
        
        if (!formData.phone) {
            $('#installment-message')
                .removeClass('alert-success')
                .addClass('alert alert-danger')
                .text('Пожалуйста, укажите номер телефона')
                .show();
            return;
        }
        
        $.ajax({
            url: 'index.php?route=extension/module/installment_calculator/send',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#installment-form button[type="submit"]')
                    .prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> Отправка...');
                $('#installment-message').hide();
            },
            complete: function() {
                $('#installment-form button[type="submit"]')
                    .prop('disabled', false)
                    .text('Отправить данные');
            },
            success: function(json) {
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
                    
                    $('#installment-form')[0].reset();
                    
                    setTimeout(function() {
                        $('#installment-popup').modal('hide');
                        $('#installment-message').hide();
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#installment-message')
                    .removeClass('alert-success')
                    .addClass('alert alert-danger')
                    .text('Ошибка отправки. Пожалуйста, попробуйте позже.')
                    .show();
            }
        });
    });
    
    $('#installment-form input').on('focus', function() {
        $('#installment-message').fadeOut();
    });
});