<?php
/*
 * Plugin Name:     Bonus for Woo
 * Version:         6.5.0
 * Text Domain:     bonus-for-woo
 * Plugin URI:      https://computy.ru/blog/bonus-for-woo-wordpress
 * Description:     This plugin adds a cashback system to the woocommerce functionality in the form of bonuses. Also, adding a percentage of cashback depending on the user's role. Also, changing the user's role, depending on the amount of all his purchases.
 * Author:          computy
 * Requires Plugins: woocommerce
 * Author URI:      https://computy.ru
 *
 * WC requires at least: 6.0
 * WC tested up to: 9.3.3
 *
 * License:           GNU General Public License v3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined('ABSPATH')) {
    exit;
}
define('BONUS_COMPUTY_VERSION', '6.5.0'); /*версия плагина*/
define('BONUS_COMPUTY_VERSION_DB', '4'); /*версия базы данных*/
define('BONUS_COMPUTY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BONUS_COMPUTY_PLUGIN_URL', plugin_dir_url(__FILE__));

$val = get_option('bonus_option_name');
/**
 * Автозагрузка классов
 */
spl_autoload_register(static function ($class) {
    $file = str_replace('\\', '/', BONUS_COMPUTY_PLUGIN_DIR.'/classes/'.$class)
        .'.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

/*Поддержка новой системы заказов. Не убирать отсюда!*/
add_action('before_woocommerce_init', function () {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

/*Подключаем все экшены, фильтры, шорткоды*/
(new BfwRouter())->init();


/*-------Страница админки*-------*/
if (current_user_can('manage_options') || (defined('WP_CLI') && WP_CLI)) {
    add_action('init', array('BfwAdmin', 'init'));

    /*------- Создаем ссылку "настройки" на странице плагинов-------*/
    add_filter('plugin_action_links', function ($links, $file) {
        // Проверка - наш это плагин или нет
        if ($file !== plugin_basename(__FILE__)) {
            return $links;
        }
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=bonus_for_woo-plugin-options'),
            __('Settings', 'bonus-for-woo')
        );
        array_unshift($links, $settings_link);
        return $links;
    }, 10, 2);
}
/*-------Страница админки-------*/


/*-------Действия после обновления-------*/
if (get_transient('bfw_pro_updated')) {
    /*Проверка бд после обновления */
    BfwAdmin::bfw_search_pro();
    BfwDB::checkDb();
    delete_transient('bfw_pro_updated');
}
/*-------Действия после обновления-------*/


/*-------Функция, которая запускается при активации плагина-------*/
register_activation_hook(__FILE__, array('BfwFunctions', 'bfwActivate'));

/*------Функция, которая запускается при деактивации плагина------*/
register_deactivation_hook(__FILE__, array('BfwFunctions', 'bfwDeactivate'));


/*-------Добавляем стили на фронте-------*/
add_action('wp_enqueue_scripts', 'bfwooComputyStyles');
function bfwooComputyStyles(): void
{
    wp_register_style(
        'bonus-computy-style',
        plugin_dir_url(__FILE__).'_inc/bonus-computy-style.css',
        array(),
        BONUS_COMPUTY_VERSION
    );
    wp_enqueue_style('bonus-computy-style');
}

/*-------Добавляем стили на фронте-------*/


/*-------Добавляем скрипты на фронте-------*/
add_action('wp_enqueue_scripts', 'bfwooComputyScript');

function bfwooComputyScript(): void
{
    wp_register_script(
        'bonus-computy-script',
        plugin_dir_url(__FILE__).'_inc/bonus-computy-script.js',
        array(),
        BONUS_COMPUTY_VERSION,
        true
    );
    wp_enqueue_script('bonus-computy-script');
}

/*-------Добавляем скрипты на фронте-------*/


/*-------Действие при нажатии кнопки экспорта баллов

 * @since 4.4.0
 * @version 4.4.0
 */

if (isset($_GET['export_bfw_points']) && current_user_can('manage_options')) {
    $file_path = BONUS_COMPUTY_PLUGIN_DIR.'/export_bfw.csv';
    $buffer = fopen($file_path, 'w');

    // Add BOM (Byte Order Mark) to ensure proper UTF-8 encoding
    fwrite($buffer, "\xEF\xBB\xBF");

    global $wpdb;
    $users = $wpdb->get_results("SELECT * FROM {$wpdb->users} ORDER BY ID");
    $title_export = ['User id', 'User name', 'Email', 'Points', 'Comment'];
    fputcsv($buffer, $title_export, ',');

    $data = [];
    foreach ($users as $user) {
        $points = get_user_meta($user->ID, 'computy_point', true) ?? 0;
        $data[] = [
            'id'      => $user->ID,
            'name'    => $user->user_nicename,
            'email'   => $user->user_email,
            'points'  => $points,
            'comment' => '',
        ];
    }

    foreach ($data as $row) {
        fputcsv($buffer, $row, ',');
    }

    fclose($buffer);
}
/*-------Действие при нажатии кнопки экспорта баллов-------*/
