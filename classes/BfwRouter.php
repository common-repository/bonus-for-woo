<?php

defined('ABSPATH') or die;

/**
 * Класс подключения actions, filters, shortcodes
 *
 * @version 5.10.0
 * @since 5.10.0
 */
class BfwRouter
{
    public function init()
    {
        $val = get_option('bonus_option_name');

        /**
         * ACTIONS
         */
        if ( ! function_exists('wp_get_current_user')) {
            include(ABSPATH."wp-includes/pluggable.php");
        }
        if (current_user_can('manage_options')) {
            /* Сохранение изменений в профиле клиента*/
            add_action(
                'personal_options_update',
                array('BfwAccount', 'profileUserUpdate')
            );
            add_action(
                'edit_user_profile_update',
                array('BfwAccount', 'profileUserUpdate')
            );
        }
        /*-------Действия после обновления-------*/
        add_action(
            'upgrader_process_complete',
            array('BfwFunctions', 'bfwUpdateCompleted'),
            10,
            2
        );

        // Исключаем скидку из налогов
        add_action(
            'woocommerce_cart_totals_get_fees_from_cart_taxes',
            array('BfwPoints', 'excludeCartFeesTaxes'),
            10,
            3
        );


        /** Accounts */


        /*-------Добавляем конечную точку bonuses-------*/
        add_action('init', array('BfwAccount', 'bonusesAddEndpoint'), 25);

        /*-------Поле дня рождения-------*/
        add_action(
            'woocommerce_edit_account_form_start',
            array('BfwAccount', 'bfwDobAccountDetails')
        );
        add_action(
            'woocommerce_save_account_details',
            array('BfwAccount', 'bfwDobSaveAccountDetails')
        );

        /*-------Вывод текста над формой регистрации-------*/
        add_action(
            'woocommerce_register_form_start',
            array('BfwAccount', 'formRegister'),
            2,
            1
        );
        /*-------Вывод текста над заголовком оставить отзыв-------*/
        add_action(
            'comment_form_before',
            array('BfwReview', 'liveReviewAndPoint')
        );

        /*-------Действия при авторизации пользователя-------*/
        add_action(
            'wp_login',
            array('BfwAccount', 'addBallWhenUserLogin'),
            10,
            2
        );

        /** Заголовок страницы бонусов в аккаунте **/
        add_action('bfw_account_title', array('BfwAccount', 'accountTitle'));

        /** Вывод основной информации: статус, процент кешбэка, количество бонусных баллов **/
        add_action(
            'bfw_account_basic_info',
            array('BfwAccount', 'accountBasicInfo')
        );

        if (BfwRoles::isPro()) {
            /** Ввод купонов(только для PRO-версии) **/
            add_action(
                'bfw_account_coupon',
                array('BfwAccount', 'accountCoupon')
            );

            /** Реферальная система (только для PRO-версии) **/
            add_filter(
                'bfw_account_referal',
                array('BfwAccount', 'accountReferral')
            );

            /*------- Действия  при регистрации пользователя-------*/
            add_action(
                'user_register',
                array('BfwAccount', 'actionPointsForRegistrationBfw')
            );
        }

        /** Прогресс бар **/
        add_action(
            'bfw_account_progress',
            array('BfwAccount', 'accountProgress')
        );

        /** История начисления баллов **/
        add_action('bfw_account_hystory', array('BfwAccount', 'accountHistory')
        );

        /*Вывод ссылки на условия бонусной системы*/
        add_action('bfw_account_rulles', array('BfwAccount', 'accountRules'));

        /*-------Добавляем контент на страницу бонусов-------*/
        add_action(
            'woocommerce_account_bonuses_endpoint',
            array('BfwAccount', 'accountContent'),
            25
        );

        /*-------Вывод кешбэка в корзине и в оформлении товара-------*/
        if ( ! empty($val['cashback-in-cart'])) {
            add_action(
                'woocommerce_review_order_after_order_total',
                array('BfwCashback', 'getCashbackInCart')
            );
            add_action(
                'woocommerce_cart_totals_after_order_total',
                array('BfwCashback', 'getCashbackInCart')
            );
        }

        /*-------Списание баллов в корзине и оформлении заказа-------*/
        add_action(
            'woocommerce_before_cart',
            array('BfwPoints', 'bfwoo_spisaniebonusov_in_cart'),
            9
        );
        add_action(
            'woocommerce_before_checkout_form',
            array('BfwPoints', 'bfwoo_spisaniebonusov_in_checkout'),
            9
        );

        /*-------Трата баллов-------*/
        add_action(
            'wp_ajax_computy_trata_points',
            array('BfwPoints', 'bfwoo_trata_points')
        );
        add_action(
            'wp_ajax_nopriv_computy_trata_points',
            array('BfwPoints', 'bfwoo_trata_points')
        );

        /*-------Получение баллов с купона-------*/
        add_action(
            'wp_ajax_bfw_take_coupon_action',
            array('BfwPoints', 'bfw_take_coupon_action')
        );


        /*-------Экспорт csv файла бонусов  */
        add_action(
            'wp_ajax_bfw_export_bonuses',
            array('BfwPoints', 'bfw_export_bonuses')
        );
        add_action(
            'wp_ajax_nopriv_bfw_export_bonuses',
            array('BfwPoints', 'bfw_export_bonuses')
        );

        /*-------Экспорт csv файла купонов  */
        add_action(
            'wp_ajax_bfw_export_coupons',
            array('BfwCoupons', 'bfwExportCoupons')
        );
        add_action(
            'wp_ajax_nopriv_bfw_export_coupons',
            array('BfwCoupons', 'bfwExportCoupons')
        );

        /*-------Добавляем скидку(комиссии)-------*/
        add_action(
            'woocommerce_cart_calculate_fees',
            array('BfwPoints', 'bfwoo_add_fee'),
            10,
            1
        );

        /*-------Очистка схваченных баллов-------*/
        add_action(
            'wp_ajax_clear_fast_bonus',
            array('BfwPoints', 'bfwoo_clean_fast_bonus')
        );
        add_action(
            'wp_ajax_nopriv_clear_fast_bonus',
            array('BfwPoints', 'bfwoo_clean_fast_bonus')
        );

        /*-------Очищение истории при удалении пользователя-------*/
        add_action('delete_user', array('BfwHistory', 'bfw_when_delete_user'));

        /*-------Удаление временных баллов при очистке корзины-------*/
        add_action(
            'woocommerce_remove_cart_item',
            array('BfwPoints', 'actionWoocommerceBeforeCartItemQuantityZero'),
            10,
            1
        );

        /*Действие сработает при изменении количества товаров*/
        add_action(
            'woocommerce_cart_item_set_quantity',
            array('BfwPoints', 'bfwCartItemSetQuantity'),
            10,
            3
        );

        /*Действие при удалении купона баллов(woo blocks)*/
        add_action(
            'woocommerce_removed_coupon',
            array('BfwCoupons', 'trueRedirectOnCouponRemoval'),
            20
        );

        /*-------Вывод списанных баллов в редактировании заказа админом-------*/
        add_action(
            'woocommerce_admin_order_totals_after_tax',
            array('BfwFunctions', 'bfwInAdminOrder')
        );

        /*-------Переводы @version 5.2.0 -------*/
        add_action(
            'plugins_loaded',
            array('BfwFunctions', 'langLoadBonusForWoo')
        );

        /*-------Если есть исключенный метод оплаты-------*/
        if ( ! empty($val['exclude-payment-method'])) {
            /* Добавляет обновление страницы при выборе метода оплаты*/
            add_action(
                'wp_footer',
                array('BfwFunctions', 'updatePageIfChangePaymentMethod')
            );
        }

        add_action('woocommerce_checkout_create_order_line_item', array('BfwFunctions','saveSaleStatusToOrderItemMeta'), 10, 4);

        /*-------Реферальная система-------*/
        if ( ! empty($val['referal-system'])) {
            add_action('init', array('BfwReferral', 'bfwSetCookies'));
            add_action('user_register', array('BfwReferral', 'registerInvate'));
        }

        add_action(
            'computy_copyright',
            array('BfwFunctions', 'computy_copyright'),
            25
        );

        /*------------Действие когда клиент подтверждает заказ - списание баллов------------*/
        $order_status_write = $val['write_points_order_status'] ?? 'processed';
        if ($order_status_write == 'processed') {
            $order_status_write_action = 'woocommerce_checkout_order_processed';
        } else {
            $order_status_write_action = 'woocommerce_order_status_'
                .$order_status_write;
        }
        add_action(
            $order_status_write_action,
            array('BfwPoints', 'newOrder'),
            20,
            1
        );

        /*------------Действие когда статус заказа выполнен - начисление баллов------------*/
        $order_status = $val['add_points_order_status'] ?? 'completed';
        add_action(
            'woocommerce_order_status_'.$order_status,
            array('BfwPoints', 'addPointsForOrder'),
            10,
            1
        );

        /*------------Действие когда оформлен возврат баллов-----------*/
        $order_status_refunded = $val['refunded_points_order_status'] ??
            'refunded';
        add_action(
            'woocommerce_order_status_'.$order_status_refunded,
            array('BfwPoints', 'refundedPoints'),
            10,
            1
        );

        //Если отзыв о товаре одобрен добавляет баллы
        add_action(
            'comment_unapproved_to_approved',
            array('BfwReview', 'bfwoo_approve_comment_callback')
        );

        //Если отзыв о товаре отклонен удаляет баллы
        add_action(
            'comment_approved_to_unapproved',
            array('BfwReview', 'bfwoo_unapproved_comment_callback')
        );

        /*cron Удаление баллов за бездействие. Находим старых клиентов*/
        add_action(
            'bfw_clear_old_cashback',
            array('BfwPoints', 'deleteBallsOldClients'),
            10,
            3
        );

        /*cron Начисление баллов в день рождение*/
        add_action(
            'bfw_search_birthday',
            array('BfwPoints', 'addBallsForBirthday'),
            10,
            3
        );


        /**
         * FILTERS
         */

        /** Accounts */

        /*-------Создаем ссылку в меню woocommerce account бонусная система-------*/
        add_filter(
            'woocommerce_account_menu_items',
            array('BfwAccount', 'bonusesLink'),
            25
        );

        add_filter('woocommerce_get_query_vars', function ($vars) {
            $vars['bonuses'] = 'bonuses';
            return $vars;
        });

        /*Кнопка удаления в подытоге(комиссии) */
        add_filter(
            'woocommerce_cart_totals_fee_html',
            array('BfwPoints', 'bfw_button_delete_fast_point'),
            10,
            2
        );

        if ( ! empty($val['fee-or-coupon'])) {
            /*Создаем виртуальный купон*/
            add_filter(
                'woocommerce_get_shop_coupon_data',
                array('BfwPoints', 'get_virtual_coupon_data_bfw'),
                10,
                2
            );
            /*Вид купонов в корзине*/
            add_filter(
                'woocommerce_cart_totals_coupon_html',
                array('BfwPoints', 'bfw_coupon_html'),
                99,
                2
            );
            /* Убираем "купон" в корзине*/
            add_filter(
                'woocommerce_cart_totals_coupon_label',
                array('BfwPoints', 'woocommerceChangeCouponLabelBfw'),
                10,
                2
            );
        }

        /*-------Возможность менеджерам настраивать плагин-------*/
        add_filter(
            'woocommerce_shop_manager_editable_roles',
            array('BfwRoles', 'bfwManagerRoleEditCapabilities')
        );

        /*-------Текст на странице товара (сколько бонусов получите)-------*/
        add_filter(
            'woocommerce_get_price_html',
            array('BfwSingleProduct', 'ballsAfterProductPriceAll'),
            100,
            2
        );


        /**
         * SHORTCODES
         */


        /**-----Шорткод: вывод суммы заказов клиента--**/
        add_shortcode('bfw_get_sum_orders', array('BfwPoints', 'getSumUserOrders'));


        /*-------Шорткод: вывод статуса клиента-------*/
        add_shortcode('bfw_status', array('BfwAccount', 'getStatus'));

        /*-------Шорткод: вывод процента кешбэка-------*/
        add_shortcode('bfw_cashback', array('BfwAccount', 'getCashback'));

        /*-------Шорткод: вывод количества баллов-------*/
        add_shortcode('bfw_points', array('BfwAccount', 'getPoints'));

        if ( ! current_user_can('manage_options')) {
            /*Шорт код для вставки кешбэка только на странице продукта*/
            add_shortcode(
                'bfw_cashback_in_product',
                array('BfwSingleProduct', 'ballsAfterProductPriceShortcode'),
                100,
                2
            );

            /*-------Шорткод: Вывод количества возможного кешбэка в корзине-------*/
            add_shortcode(
                'bfw_how_much_cashback',
                array('BfwCashback', 'bfwGetCashbackInCartForShortcode')
            );
        }

        /*шорткод списания баллов в корзине*/
        add_shortcode(
            'bfw-write-off-bonuses',
            array('BfwPoints', 'bfwoo_spisaniebonusov_in_cart')
        );

        /*шорткод списания баллов в оформлении заказа*/
        add_shortcode(
            'bfw-write-off-bonuses-checkout',
            array('BfwPoints', 'bfwoo_spisaniebonusov_in_checkout')
        );

        /*Вывод ссылки на условия бонусной системы*/
        add_shortcode('link_on_rulles', array('BfwAccount', 'accountRules'));

        /*-------Шорткод: вывод реферальной системы-------*/
        add_shortcode(
            'bfw_account_referral',
            array('BfwAccount', 'accountReferral')
        );

        /*-------Шорткод: Вывод реферальной ссылки клиента-------*/
        add_shortcode('bfw_ref', array('BfwAccount', 'getReferralLink'));

        /*-------Шорткод: вывод аккаунта пользователя-------*/
        add_shortcode(
            'bfw_account',
            array('BfwAccount', 'accountContentShortcode')
        );
    }

}
