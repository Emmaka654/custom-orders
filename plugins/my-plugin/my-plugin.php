<?php

/*
* Plugin Name:       my-plugin
* Plugin URI:        http://localhost/wp/plugins/
* Description:       Handle the basics with this plugin.
* Version:           1.0.0
* Requires at least: 5.2
* Requires PHP:      7.2
* Author:            Emma
* Author URI:        https://author.example.com/
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
* Update URI:        https://localhost/wp/plugins/my-plugin/
* Text Domain:       my-basics-plugin
* Domain Path:       /languages
*/

// Функция для проверки наличия типа записи
function check_order_post_type()
{
    // Проверяем, зарегистрирован ли тип записи 'order'
    if (post_type_exists('order')) {
        return;
    }

    // Если тип записи не существует, деактивируем плагин
    deactivate_plugins(plugin_basename(__FILE__));

    // Выводим сообщение об ошибке
    // С помощью хука admin_notices выводится сообщение об ошибке на админ-странице WordPress.
    add_action('admin_notices', function () {
        echo '<div class="error"><p>Плагин требует наличие типа записи "order". Пожалуйста, установите его и активируйте плагин снова.</p></div>';
    });
}

// Хук для проверки наличия типа записи при активации плагина
register_activation_hook(__FILE__, 'check_order_post_type');

const COLUMN_ID = 'order_status';
//функция __() принимает два параметра: строку для перевода и текстовый домен
//добавляем массив с колонками нашу колонку
function register_column(array $columns): array
{
    $columns[COLUMN_ID] = __('Статус заказа', 'my-plugin');
    return $columns;
}

add_filter('manage_posts_columns', 'register_column');

// Отображаем order_status
function render_column($column_id, $post_id)
{
    if ($column_id !== COLUMN_ID) {
        return;
    }

    // Получаем значение мета-поля order_status
    $order_status = get_post_meta($post_id, COLUMN_ID, true);

    // Определяем доступные статусы
    $statuses = [
        'pending' => __('В ожидании'),
        'cancelled' => __('Отменен'),
        'completed' => __('Выполнен'),
    ];

    // Генерируем HTML для select элемента
    echo '<select class="order-status-select" data-post-id="' . esc_attr($post_id) . '">';
    foreach ($statuses as $value => $label) {
        // функция проверяет, соответствует ли текущее значение $order_status значению $value.
        // Если да, то возвращает строку selected="selected" для установки этой опции как выбранной в выпадающем списке.
        $selected = selected($order_status, $value, false);
        echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

add_action('manage_posts_custom_column', 'render_column', 10, 2);//параметры 10 и 2 обозначают приоритет и количество аргументов

// Обработчик AJAX-запроса для обновления статуса заказа
function update_order_status() {
    // Проверяем nonce
    check_ajax_referer('update_order_status_nonce', 'nonce'); //проверяем, совпадают ли переданный и сгенерированный

//     Проверяем права пользователя
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Недостаточно прав для выполнения этого действия.');
    }

    // Получаем данные из запроса
    $post_id = intval($_POST['post_id']);
    $new_status = sanitize_text_field($_POST['status']); //Функция sanitize_text_field в PHP используется для очистки текстовых данных (Удаление HTML-тегов, пробелов)

    // Обновляем мета-поле order_status
    update_post_meta($post_id, COLUMN_ID, $new_status);

    // Возвращаем успешный ответ
    $response = [
        'success' => true,
        'data' => 'Статус заказа обновлен.'
    ];

    echo json_encode($response);
    exit;
}

// Подключаем обработчик к AJAX
add_action('wp_ajax_update_order_status', 'update_order_status');


//Подключение JavaScript
function enqueue_admin_scripts() {
    // Подключаем jQuery
    wp_enqueue_script('jquery');

    // Подключаем JS файл
    wp_enqueue_script('admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), null, true);

    // Передаем переменные в скрипт
    wp_localize_script('admin-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('update_order_status_nonce') //nonce - специальный токен, который используется для защиты от атак
    ));
}

add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');
