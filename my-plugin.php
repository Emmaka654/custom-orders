<?php

/*
* Plugin Name:       Custom orders
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

const COLUMN_ID = 'order_status';

// Функция для проверки наличия типа записи
function check_order_post_type()
{
    // Проверяем, зарегистрирован ли тип записи 'order'
    if (post_type_exists('custom_order')) {
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

//функция __() принимает два параметра: строку для перевода и текстовый домен
//добавляем массив с колонками нашу колонку
function register_column(array $columns): array
{
    $columns[COLUMN_ID] = __('Статус заказа', 'my-plugin');
    return $columns;
}

add_filter('manage_edit-custom_order_columns', 'register_column');

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

add_action('manage_custom_order_posts_custom_column', 'render_column', 10, 2);//параметры 10 и 2 обозначают приоритет и количество аргументов

// Обработчик AJAX-запроса для обновления статуса заказа
function update_order_status()
{
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
function enqueue_admin_scripts()
{
    // Подключаем JS файл
    wp_enqueue_script('admin-script', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), null, true);

    // Передаем переменные в скрипт
    wp_localize_script('admin-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('update_order_status_nonce') //nonce - специальный токен, который используется для защиты от атак
    ));
}

add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');

// Добавляем мета-бокс для заказов
function add_custom_order_meta_box()
{
    add_meta_box(
        'custom_order_products', // ID мета-бокса
        'Товары в заказе', // Заголовок
        'render_custom_order_meta_box', // Функция для отрисовки
        'custom_order', // Тип поста
        'normal', // Контекст
        'high' // Приоритет
    );
}

add_action('add_meta_boxes', 'add_custom_order_meta_box');

// Функция для отрисовки мета-бокса
function render_custom_order_meta_box($post)
{
    // Получаем сохраненные товары
    $products = get_field('products', $post->ID);

    // Добавления нового товара
    echo '<div style="margin-bottom: 20px;">';
    echo '<h3>Добавить товар в заказ</h3>';
    echo '<select name="new_product_id" id="new_product_id">';
    echo '<option value="">Выберите товар</option>';

    // Получаем все товары
    $all_products = get_posts(array(
        'post_type' => 'product',
        'numberposts' => -1, //Берем все посты
    ));

    foreach ($all_products as $product) {
        echo '<option value="' . esc_attr($product->ID) . '">' . esc_html($product->post_title) . '</option>';
    }

    echo '</select>';
    echo '<input type="number" name="new_product_quantity" id="new_product_quantity" min="1" value="1" required>';
    echo '<button type="button" id="add-product-button" class="button">Добавить товар</button>';
    echo '</div>';

    if ($products) {
        echo '<table class="widefat fixed">';
        echo '<thead><tr>
                <th>Изображение</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Цена</th>
                <th>Размер</th>
                <th>Цвет</th>
                <th>Рейтинг</th>
                <th>Количество</th>
                <th>Действия</th>
                <th>Цена за данное количество товара</th>
              </tr></thead>';
        echo '<tbody>';

        $totalSumOfOrder = 0;
        foreach ($products as $index => $product) {
            $totalSumOfProduct = $product['price'] * $product['quantity'];
            $totalSumOfOrder += $totalSumOfProduct;
            echo '<tr>';
            echo '<td><img src="' . esc_url($product['image']) . '" width="50" height="50"></td>';
            echo '<td>' . esc_html($product['name']) . '</td>';
            echo '<td>' . esc_html($product['description']) . '</td>';
            echo '<td>' . esc_html($product['price']) . '</td>';
            echo '<td>' . esc_html(implode(', ', $product['size'])) . '</td>';
            echo '<td>' . esc_html($product['color']) . '</td>';
            echo '<td>' . esc_html($product['rating']) . '</td>';
            echo '<td><input type="number" name="products[' . $index . '][quantity]" value="' . esc_attr($product['quantity']) . '" min="1"></td>';
            echo '<td><button type="button" class="button remove-product" data-index="' . $index . '">Удалить</button></td>';
            echo '<td>' . $totalSumOfProduct . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '<div>' . 'Сумма заказа: ' . $totalSumOfOrder . '</div>';
        echo '<button type="submit" name="update_quantities" class="button button-primary">Сохранить изменения</button>';
    } else {
        echo '<p>Товары отсутствуют.</p>';

    }
}

// Сохранение изменений в мета-боксе
function save_custom_order_meta_box($post_id)
{
    // Проверяем, не является ли это автосохранением
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Обработка изменения количества существующих товаров
    if (isset($_POST['products'])) {
        $products = get_field('products', $post_id);

        // Обновляем количество товаров
        foreach ($_POST['products'] as $index => $data) {
            if (isset($products[$index])) {
                $products[$index]['quantity'] = intval($data['quantity']);
            }
        }

        // Сохраняем обновленный список товаров
        update_field('products', $products, $post_id);
    }
}

add_action('save_post_custom_order', 'save_custom_order_meta_box');

function remove_product_from_order()
{

    $post_id = intval($_POST['post_id']);
    $index = intval($_POST['index']);

    $products = get_field('products', $post_id);
    if (isset($products[$index])) {
        unset($products[$index]);
        $products = array_values($products); // Переиндексируем массив
        update_field('products', $products, $post_id);
        wp_send_json_success();
    } else {
        wp_send_json_error('Product not found.');
    }

    exit();
}

add_action('wp_ajax_remove_product_from_order', 'remove_product_from_order');

function add_product_to_order()
{

    $post_id = intval($_POST['post_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($product_id <= 0 || $quantity <= 0) {
        wp_send_json_error('Invalid product ID or quantity.');
        return;
    }

    $product = get_post($product_id);
    if (!$product) {
        wp_send_json_error('Product not found.');
        return;
    }

    $products = get_field('products', $post_id);
    if (!is_array($products)) {
        $products = [];
    }

    // Проверяем, есть ли уже такой товар в заказе
    $product_exists = false;
    foreach ($products as &$existing_product) { //любые изменения, которые мы вносим в $existing_product, будут напрямую изменять соответствующий элемент в массиве $products.
        if ($existing_product['id'] == $product_id) {
            $existing_product['quantity'] += $quantity;
            $product_exists = true;
            break;
        }
    }

    // Если товара нет в заказе, добавляем его
    if (!$product_exists) {
        $products[] = [
            'id' => $product_id,
            'name' => $product->post_title,
            'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
            'description' => $product->post_content,
            'price' => get_post_meta($product_id, 'price', true),
            'size' => get_post_meta($product_id, 'size', true),
            'color' => get_post_meta($product_id, 'color', true),
            'rating' => get_post_meta($product_id, 'product_rating', true),
            'quantity' => $quantity,
        ];
    }

    // Сохраняем обновленный список товаров
    update_field('products', $products, $post_id);

    wp_send_json_success(['message' => 'Товар успешно добавлен в заказ.']);
}

add_action('wp_ajax_add_product_to_order', 'add_product_to_order');