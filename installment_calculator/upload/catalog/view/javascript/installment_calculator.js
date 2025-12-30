$(document).ready(function() {
    const $calculator = $('.installment-calculator');
    const priceText = $('#product .price .price-new, #product .price').first().text();
    const price = parseFloat(priceText.replace(/[^\d.]/g, ''));
    const productName = $('h1').text().trim();
    const productImage = $('#product .thumbnail img').first().attr('src');
    const productUrl = window.location.href;
    
    let currentMonths = 12;
    let availableMonths = JSON.parse($('.installment-months').val() || '[12]');
    
    function calculate(months) {
        const monthly = Math.round(price / months);
        $('.installment-monthly').text(monthly);
        $('.installment-period').text(months);
        $('.installment-total').text(price);
        $('.installment-price').text(price);
        currentMonths = months;
    }
    
    calculate(12);
    
    $('.installment-btn').on('click', function() {
        $('#popup-product-name').text(productName);
        $('#popup-product-image').attr('src', productImage);
        $('#popup-price').text(price);
        $('#popup-total').text(price);
        $('#popup-months').text(currentMonths);
        $('#popup-monthly').text(Math.round(price / currentMonths));
        
        $('#installment-popup').modal('show');
    });
    
    $('#installment-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            name: $('input[name="name"]').val(),
            phone: $('input[name="phone"]').val(),
            product_name: productName,
            price: price + ' лей',
            months: currentMonths,
            monthly: Math.round(price / currentMonths) + ' лей',
            product_url: productUrl
        };
        
        $.ajax({
            url: 'index.php?route=extension/module/installment_calculator/send',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                $('#installment-form button').prop('disabled', true).text('Отправка...');
            },
            complete: function() {
                $('#installment-form button').prop('disabled', false).text('Отправить данные');
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
            error: function() {
                $('#installment-message')
                    .removeClass('alert-success')
                    .addClass('alert alert-danger')
                    .text('Ошибка отправки')
                    .show();
            }
        });
    });
});