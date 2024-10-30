<?php

defined('ABSPATH') or die;

/**
 * Class functions
 * Класс с различными функциями, которые не подходят к другим классам
 *
 * @version 5.5.0
 *
 */
class BfwFunctions
{


    /**
     * Launched when the plugin is activated
     * Запускается при активации плагина
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfwActivate(): void
    {
        // Возможность менеджерам настраивать плагин
        $shop_manager = get_role('shop_manager');
        $shop_manager->add_cap('manage_options');
        $shop_manager->add_cap('edit_users');
        $shop_manager->add_cap('edit_user');

        delete_option('rewrite_rules');
        //Проверка бд
        BfwDB::checkDb();
        BfwAdmin::bfw_search_pro();
    }

    /**
     * Runs when the plugin is deactivated
     * Запускается при деактивации плагина
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfwDeactivate(): void
    {
        delete_option('rewrite_rules');
    }

    /**
     * Action after plugin update
     * Действие после обновления плагина
     *
     * @param $upgrader_object  !!не удалять!!
     * @param $options
     *
     * @return void
     * @version 5.10.0
     */
    public static function bfwUpdateCompleted($upgrader_object, $options): void
    {
        if ( ! empty($options['action'])) {
            $our_plugin = 'bonus-for-woo/index.php';
            if ($options['action'] === 'update'
                && $options['type'] === 'plugin'
            ) {
                foreach ($options['plugins'] as $plugin) {
                    if ($plugin === $our_plugin) {
                        set_transient('bfw_pro_updated', 1);
                    }
                }
            }
        }
    }

    /**
     * Find the points used in the order
     * Находим используемые баллы в заказе
     *
     * @param $order object
     *
     * @return float
     * @version 6.3.4
     */
    public static function feeOrCoupon(object $order): float
    {
        $val = get_option('bonus_option_name');
        $fee_total = 0;

        if ( ! empty($val['fee-or-coupon'])) {
            /* если бонусы с помощью купонов */
            $cart_discount = mb_strtolower($val['bonus-points-on-cart']);
            foreach ($order->get_coupon_codes() as $coupon_code) {
                $coupon = new WC_Coupon($coupon_code);
                if (strtolower($coupon->get_code()) === $cart_discount) {
                    $fee_total = $order->get_discount_total();
                    break; // Прерываем цикл, так как нашли нужный купон
                }
            }
        } else {
            /* если бонусы с помощью комиссий */
            foreach ($order->get_items('fee') as $item_id => $item_fee) {
                if ($item_fee->get_name() === $val['bonus-points-on-cart']) {
                    $fee_total = $item_fee->get_total();
                    break; // Прерываем цикл, так как нашли нужную комиссию
                }
            }
        }

        return abs($fee_total); // Используем abs вместо absint для float
    }


    /**
     * Display of written-off points in order editing by the admin
     * Вывод списанных баллов в редактировании заказа админом
     *
     * @param  int  $order_id
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfwInAdminOrder(int $order_id): void
    {
        $order = wc_get_order($order_id);
        $val = get_option('bonus_option_name');
        $fee_total = null;

        foreach ($order->get_items('fee') as $item_fee) {
            if ($item_fee->get_name() === $val['bonus-points-on-cart']) {
                $fee_total = $item_fee->get_total();
                break; // Прерываем цикл, так как нашли нужную комиссию
            }
        }

        if ($fee_total !== null) {
            echo ' <tr><td class="label">'.esc_html(
                    $val['bonus-points-on-cart']
                ).':</td><td width="1%"></td>
        <td class="total">
            <span class="woocommerce-Price-amount amount"><bdi>'.esc_html(
                    $fee_total
                ).' <span class="woocommerce-Price-currencySymbol">'.esc_html(
                    get_woocommerce_currency_symbol()
                ).'</span></bdi></span>
        </td>
        </tr>';
        }
    }


    /**
     * Translations
     * Переводы
     *
     * @return void
     * @version 6.4.0
     */
    public static function langLoadBonusForWoo(): void
    {
        load_plugin_textdomain(
            'bonus-for-woo',
            false,
            dirname(plugin_basename(__FILE__)).'/lang/'
        );
    }


    /**
     * Copyright computy
     * Копирайт computy.ru
     *
     * @return void
     * @version 6.4.0
     */
    public static function computy_copyright(): void
    {
        if ( ! BfwRoles::isPro()) {
            ?>
            <div class="computy_copyright"><?php
                echo __('With the support of', 'bonus-for-woo'); ?> <a
                    href="https://computy.ru" target="_blank"
                    title="Разработка на WordPress"> computy </a></div>
            <?php
        }
    }

    /**
     * Checking the key
     *
     * @param  string  $key
     *
     * @return void
     * @version 6.4.3
     */
    public static function checkingKey(string $key): void
    {
        $get = array(
            'key'  => sanitize_text_field($key),
            'site' => get_site_url()
        );
        $response = wp_remote_get(
            'https://computy.ru/API/api.php?'.http_build_query($get)
        );

        if ( ! is_wp_error($response)) {
            $json = wp_remote_retrieve_body($response);
            $data = json_decode($json, true);

            if (isset($data['status']) && $data['status'] === 'OK') {
                if (isset($data['response']) && $data['response']) {
                    /*Да, вот так просто.☺ Есть вопросы и предложения пиши https://t.me/ca666ko , пообщаемся.*/
                    update_option('bonus-for-woo-pro', 'active');
                    wp_redirect(
                        '/wp-admin/admin.php?page=bonus_for_woo-plugin-options'
                    );
                } else {
                    echo '<div class="notice notice-error is-dismissible">'.__(
                            'The key is not correct! Contact info@computy.ru',
                            'bonus-for-woo'
                        ).'</div>';
                }
            } elseif (isset($data['error'])) {
                $dataError = '';
                if ($data['error'] == '2') {
                    $dataError = __('The key is not correct.', 'bonus-for-woo');
                }
                echo '<div class="notice notice-error is-dismissible">'.__(
                        'Error code:',
                        'bonus-for-woo'
                    ).$data['error'].' '.$dataError.'</div>';
            } else {
                echo '<div class="notice notice-error is-dismissible">'.__(
                        'Error while receiving data.',
                        'bonus-for-woo'
                    ).'</div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible">'.__(
                    'Error while receiving data.',
                    'bonus-for-woo'
                ).'</div>';
        }
    }

    /**
     * Exclusion of product categories
     * Исключение категорий товаров
     *
     * @param  float  $total_order
     *
     * @return float
     * @version 6.4.0
     */
    public static function bfwExcludeCategoryCashback(float $total_order): float
    {
        $val = get_option('bonus_option_name');
        $categoriexs = $val['exclude-category-cashback'] ?? 'not';
        if ($categoriexs !== 'not') {
            $cart_items = WC()->cart->get_cart(); // получаем корзину один раз
            foreach ($cart_items as $cart_item_key => $cart_item) {
                $_product = apply_filters(
                    'woocommerce_cart_item_product',
                    $cart_item['data'],
                    $cart_item,
                    $cart_item_key
                );
                if (has_term(
                    $categoriexs,
                    'product_cat',
                    $cart_item['product_id']
                )
                ) {
                    $sum_exclude_cat = $_product->get_price()
                        * $cart_item['quantity'];
                    $total_order -= $sum_exclude_cat;
                }
            }
        }
        return $total_order;
    }


    /**
     * Exclusion of products
     * Исключение товаров
     *
     * @param  float  $total_order
     *
     * @return float
     * @version 6.4.0
     */
    public static function bfwExcludeProductCashback(float $total_order): float
    {
        $val = get_option('bonus_option_name');
        $exclude_tovar = $val['exclude-tovar-cashback'];
        $products = apply_filters(
            'bfw-excluded-products-filter',
            explode(",", $exclude_tovar),
            $exclude_tovar
        );

        $cart_items = WC()->cart->get_cart(); // получаем корзину один раз
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $_product = apply_filters(
                'woocommerce_cart_item_product',
                $cart_item['data'],
                $cart_item,
                $cart_item_key
            );
            if (in_array($cart_item['product_id'], $products, true)) {
                $categoriexs = $val['exclude-category-cashback'] ?? 'not';
                if ( ! has_term(
                    $categoriexs,
                    'product_cat',
                    $cart_item['product_id']
                )
                ) {/*если еще не исключены категории, то:*/
                    $total_order -= $_product->get_price()
                        * $cart_item['quantity'];
                }
            }
        }

        return $total_order;
    }


    /**
     * Sorting an array in ascending order
     * Сортировка массива по возрастанию
     *
     * @return array
     * @version 6.4.0
     */
    public static function arrayMultisortValue(): array
    {
        $args = func_get_args();
        $data = array_shift($args);

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $args[$n] = array_column($data, $field);
            }
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        return $data;
    }

    /**
     * Changing the status of a post\product
     * Меняем статус поста\товара
     *
     * @param $post_status
     * @param $post
     *
     * @return bool
     * @version 6.4.0
     */
    public static function setPostStatusBfw($post_status, $post = null): bool
    {
        $post = get_post($post);

        if ( ! is_object($post)) {
            return false;
        }

        return wp_update_post(array(
            'ID'          => $post->ID,
            'post_status' => $post_status
        ));
    }

    /**
     * Declension
     * Склонение
     *
     * @param  int  $points
     * @param  string  $label_point
     * @param  string  $label_point_two
     * @param  string  $label_points
     *
     * @return string
     * @version 6.4.0
     */
    public static function declination(
        float $points,
        string $label_point,
        string $label_point_two,
        string $label_points
    ): string {
        $words = array($label_point, $label_point_two, $label_points);
        $points = (int)$points;
        $num = $points % 100;
        if ($num > 19) {
            $num %= 10;
        }

        switch ($num) {
            case 1:
                $out = $words[0];
                break;
            case 2:
            case 3:
            case 4:
                $out = $words[1];
                break;
            default:
                $out = $words[2];
                break;
        }

        return $out;
    }


    /**
     * Refreshes the page when choosing a payment method
     * Обновляет страницу при выборе метода оплаты
     *
     * @return void
     * @version 6.4.0
     */
    public static function updatePageIfChangePaymentMethod(): void
    {
        $val = get_option('bonus_option_name');
        $expm = $val['exclude-payment-method'] ?? array();
        if (isset($expm[0])) {
            $count_expm = count($expm);
            $ert = "ert==='".$expm[0]."'";
            $et = "et==='".$expm[0]."'";
            if ($count_expm > 1) {
                for ($i = 1, $iMax = count($expm); $i < $iMax; $i++) {
                    $ert .= " || ert==='".$expm[$i]."' ";
                    $et .= " || et==='".$expm[$i]."' ";
                }
            }
            ?>
            <script>

                jQuery(document).ready(function ($) {
                    let ert = $('input[name^="payment_method"]:checked').val();
                    if (<?php echo $ert; ?>) {
                        $('.order-cashback').hide();
                        $('#computy-bonus-message-cart').hide();
                        $('.bfw-how-match-cashback').hide();
                    }

                    $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
                        let et = $(this).val();
                        if (<?php echo $et; ?>) {
                            $('.order-cashback').hide();
                            $('#computy-bonus-message-cart').hide();
                            $('.bfw-how-match-cashback').hide();
                            $(".remove_points").trigger("click");
                        } else {
                            $('.order-cashback').show();
                            $('#computy-bonus-message-cart').show();
                            $('.bfw-how-match-cashback').show();
                        }
                    });
                });
            </script>
            <?php
        }
        ?>
        <script>jQuery(document).ready(function ($) {
                $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
                    $(document.body).trigger('update_checkout');
                });
            });
        </script>
        <?php
    }


    /**
     * Показ всплывающих подсказок.
     *
     * @param  string  $text  Help tip text.
     * @param  string  $event  danger | faq | info
     *
     * @return string
     * @since  5.0.0
     */
    public static function helpTip(string $text, string $event = 'faq'): string
    {
        return '<span class="bfw-help-tip '.esc_attr($event).'" data-tip="'
            .$text.'"></span>';
    }


    /**
     * Сохраняет метаданные о товаре, что он был со скидкой
     * @param $item
     * @param $cart_item_key
     * @param $values
     * @param $order
     *
     * @return void
     * @since  6.4.9
     */
    public static function saveSaleStatusToOrderItemMeta(
        $item,
        $cart_item_key,
        $values,
        $order
    ): void {
        // Проверяем, был ли товар со скидкой
        if (isset($values['data']) && $values['data']->is_on_sale()) {
            // Добавляем метаданные к элементу заказа
            $item->update_meta_data('_was_on_sale', 'yes');
        }
    }


}
