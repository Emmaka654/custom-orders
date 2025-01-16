jQuery(document).ready(function ($) {
    $('.order-status-select').change(function () {
        const select = $(this);
        const newStatus = select.val();
        const postId = select.data('post-id');

        // Отправляем AJAX-запрос
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'update_order_status',
                nonce: ajax_object.nonce, // передаем nonce («число, используемое один раз» для защиты URL-адресов и форм от определенных видов неправомерного использования)
                post_id: postId,
                status: newStatus
            },
            success: function (response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Ошибка: ' + response.data);
                }
            },
            error: function () {
                alert('Произошла ошибка при отправке запроса.');
            }
        });
    });
});

jQuery(document).ready(function ($) {
    $('.remove-product').on('click', function () {

        const index = $(this).data('index');
        const post_id = $('#post_ID').val();

        if (confirm('Вы уверены, что хотите удалить этот товар?')) {
            $.ajax({
                url: ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'remove_product_from_order',
                    post_id: post_id,
                    index: index
                },
                success: function (response) {
                    if (response.success) {
                        location.reload(); // Перезагружаем страницу
                    } else {
                        alert('Ошибка при удалении товара.');
                    }
                },
                error: function () {
                    alert('Ошибка при удалении товара.');
                }
            });
        }
    });
});
//
jQuery(document).ready(function ($) {
    $('#add-product-button').on('click', function () {

        const productId = $('#new_product_id').val();
        const quantity = $('#new_product_quantity').val();
        const postId = $('#post_ID').val();

        if (!productId || !quantity) {
            alert('Пожалуйста, выберите товар и укажите количество.');
            return;
        }

        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'add_product_to_order', // Имя действия для обработки на сервере
                post_id: postId,
                product_id: productId,
                quantity: quantity
            },
            success: function (response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                if (response.success) {
                    // Перезагружаем страницу для обновления списка товаров
                    location.reload();
                } else {
                    alert('Ошибка при добавлении товара: ' + response.data);
                }
            },
            error: function () {
                alert('Произошла ошибка при добавлении товара.');
            }
        });
    });
});
