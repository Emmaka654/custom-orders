jQuery(document).ready(function($) {
    $('.order-status-select').change(function() {
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
            success: function(response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                if (response.success) {
                    alert(response.data);
                } else {
                    alert('Ошибка: ' + response.data);
                }
            },
            error: function() {
                alert('Произошла ошибка при отправке запроса.');
            }
        });
    });
});
