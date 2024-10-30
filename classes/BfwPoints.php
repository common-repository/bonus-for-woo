<?php

defined('ABSPATH') or die;


use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class Points
 *
 * @version 2.5.1
 */
class BfwPoints
{


    /**
     * Declension of nouns after numerals.
     * Склонение существительных после числительных.
     *
     * @param  float  $points
     * @param  bool  $show  Включает значение $value в результирующею строку
     *
     * @return string
     * @version 2.10.1
     */
    public static function pointsLabel(float $points, bool $show = true): string
    {
        $value = get_option('bonus_option_name');
        $label_point = $value['label_point'] ?? __('Point', 'bonus-for-woo');
        $label_point_two = $value['label_point_two'] ??
            __('Points', 'bonus-for-woo');
        $label_points = $value['label_points'] ?? __('Points', 'bonus-for-woo');

        return BfwFunctions::declination(
            $points,
            $label_point,
            $label_point_two,
            $label_points
        );
    }


    /**
     * This solution is for those parameters where the preposition "up to" is used before the number, for example "up to 101 points"
     * Данное решение для тех параметров, где используется перед числом предлог "до" например "до 101 балла"
     *
     * @param  float  $points
     * @param  bool  $show
     *
     * @return string
     * @version 6.4.0
     */
    public static function pointsLabelUp(
        float $points,
        bool $show = true
    ): string {
        $value = get_option('bonus_option_name');

        $label_point_two = sanitize_text_field($value['label_point_two']) ??
            __('Points', 'bonus-for-woo');
        $label_points = sanitize_text_field($value['label_points']) ??
            __('Points', 'bonus-for-woo');

        return BfwFunctions::declination(
            $points,
            $label_point_two,
            $label_points,
            $label_points
        );
    }


    /**
     * The method decides whether to use the method with "before" or without "before"
     * Метод решает какой использовать метод с "до" или без "до"
     *
     * @param  string  $up
     * @param  float  $points
     *
     * @return string
     * @version 6.4.0
     */
    public static function howLabel(string $up, float $points): string
    {
        $val = get_option('bonus_option_name');
        if (empty($val['bonus-in-price-upto']) && ! empty($up)) {
            return self::pointsLabelUp($points);
        }

        return self::pointsLabel($points);
    }


    /**
     * Returns the number of bonus points the user has
     * Возвращает количество бонусных баллов пользователя
     *
     * @param  int  $userId
     *
     * @return float
     * @version 2.5.1
     */
    public static function getPoints(int $userId): float
    {
        $points = get_user_meta($userId, 'computy_point', true) ?? 0;
        return (float)$points;
    }


    /**
     * Rounds off points to the required number
     * Округляет баллы до нужного числа
     *
     * @param  float  $points
     *
     * @return float
     * @since  6.3.4
     */
    public static function roundPoints(float $points): float
    {
        $val = get_option('bonus_option_name');
        if (empty($val['round_points'])) {
            return round($points);
        }
        return round($points, 2);
    }


    /**
     * Returns the user's points that he wants to write off
     * Возвращает баллы пользователя, которые он хочет списать
     *
     * @param  int  $userId
     *
     * @return float
     * @version 2.5.1
     */
    public static function getFastPoints(int $userId): float
    {
        $points = get_user_meta($userId, 'computy_fast_point', true);
        return (float)$points;
    }


    /**
     * Refresh Bonus Points
     * Обновление бонусных баллов
     *
     * @param  int  $userId
     * @param  float  $newBalls
     *
     * @return bool
     * @version 4.8.0
     * @since 2.5.1
     */
    public static function updatePoints(int $userId, float $newBalls): bool
    {
        $newBalls = max(0, $newBalls);
        $newBalls = apply_filters(
            'bfw-update-points-filter',
            $newBalls,
            $userId
        );
        if (update_user_meta($userId, 'computy_point', $newBalls)) {
            return true;
        }
        return false;
    }


    /**
     * Updating the points that the user wants to write off
     * Обновление баллов, которые пользователь хочет списать
     *
     * @param  int  $userId
     * @param  float  $newBalls
     *
     * @version 4.8.0
     * @since 2.5.1
     */
    public static function updateFastPoints(int $userId, float $newBalls): void
    {
        $newBall = max(0, $newBalls);
        update_user_meta($userId, 'computy_fast_point', $newBall);
    }


    /**
     * Find the sum of all paid orders of the client
     * Находим сумму всех оплаченных заказов клиента
     * так как wc_get_customer_total_spent ($to_user->ID); включает сумму не оплаченных заказов тоже.
     *
     * @param  int  $userId
     *
     * @return float
     * @version 6.5.0
     *
     */
    public static function getSumUserOrders( $userId=null): float
    {

        if($userId==null){
            $current_user = get_current_user_id();
            if($current_user===0){
                return 0;
            }else{
                $userId =   $current_user;
            }

        }
        $val = get_option('bonus_option_name');
        if ( ! empty($val['add_points_order_status'])) {
            $order_staus = sanitize_text_field($val['add_points_order_status']);
        } else {
            $order_staus = 'completed';
        }

        $data_start = '';
        if (BfwRoles::isPro()) {
            /*С какой даты начинать считать сумму заказов*/
            $data_start = $val['order_start_date'] ?? '';
            if ( ! empty($val['order_start_date'])) {
                $datastart = sanitize_text_field($val['order_start_date']);

                if (class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled()) {
                    $data_start = "AND date_created_gmt >=   '$datastart' ";
                }else{
                    $data_start = "AND p.post_date >=   '$datastart' ";
                }

            }
        }

        $order_staus = "wc-".$order_staus;
        global $wpdb;


        if (class_exists(OrderUtil::class) && OrderUtil::custom_orders_table_usage_is_enabled()) {
            $total_all = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(total_amount) FROM {$wpdb->prefix}wc_orders  WHERE status = %s AND customer_id = %d {$data_start}",
                    $order_staus,
                    $userId
                )
            );

            $total_shipping = 0;
            if ( ! empty($val['shipping-total-sum'])) {
                $total_shipping = $wpdb->get_var(
                    $wpdb->prepare("SELECT SUM(shipping_total_amount)
FROM  {$wpdb->prefix}wc_order_operational_data WHERE 
    order_id IN (
        SELECT 
            order_id
        FROM 
            {$wpdb->prefix}wc_orders
        WHERE 
          status = %s AND customer_id = %d {$data_start})", $order_staus, $userId)    );
            }


          $total_alls = $total_all - $total_shipping;

        } else {
            $total_all = $wpdb->get_var(

                $wpdb->prepare(
                    "SELECT SUM(pm.meta_value) FROM {$wpdb->prefix}postmeta as pm
  INNER JOIN {$wpdb->prefix}posts as p ON pm.post_id = p.ID
  INNER JOIN {$wpdb->prefix}postmeta as pm2 ON pm.post_id = pm2.post_id
  WHERE p.post_status LIKE %s AND p.post_type LIKE 'shop_order'
  AND pm.meta_key LIKE '_order_total' AND pm2.meta_key LIKE '_customer_user'
  AND pm2.meta_value LIKE %d $data_start 
  ",
                    $order_staus,
                    $userId
                )
            );


            $total_shipping = 0;
            if ( ! empty($val['shipping-total-sum'])) {
            $total_shipping = $wpdb->get_var(

                $wpdb->prepare(
                    "SELECT 
    SUM(meta_value)
FROM 
    {$wpdb->prefix}postmeta 
WHERE 
    meta_key = '_order_shipping'
    AND post_id IN (
                SELECT 
            ID 
        FROM 
            {$wpdb->prefix}posts 
        WHERE 
            post_type = 'shop_order'
            AND post_status LIKE %s
            AND post_author = %d )",  $order_staus,
                    $userId                )            );

            }
            $total_alls = $total_all - $total_shipping;




        }


        if (empty($total_alls)) {
            $total_alls = 0;
        }
        return $total_alls;
    }


    /**
     * Carrying out an offline order
     * Проведение оффлайн-заказа
     *
     * @param $price float
     * @param $user_id int
     *
     * @return void
     * @version 5.10.0
     * @since 5.1.0
     */
    public static function addOfflineOrder(float $price, int $user_id): void
    {
        /*1. Создаем офлайн продукт*/
        $offline_product = get_option('bonus-for-woo-offline-product');
        if (get_post($offline_product)) {
            BfwFunctions::setPostStatusBfw('publish', $offline_product);
            $post_id = $offline_product;
        } else {
            $val = get_option('bonus_option_name');
            $post_title = sanitize_text_field(
                $val['title-product-offline-order']
            ) ?? __('Offline product', 'bonus-for-woo');
            $post_id = wp_insert_post(array(
                'post_title'   => $post_title,
                'post_type'    => 'product',
                'post_status'  => 'publish',
                'post_content' => __(
                    'Technical product for accrual of bonus points',
                    'bonus-for-woo'
                )
            ));

            wp_set_object_terms($post_id, 'simple', 'product_type');
            update_post_meta(
                $post_id,
                '_visibility',
                'hidden'
            );/*скрыть с каталога*/
            update_post_meta($post_id, '_stock_status', 'instock');
            update_post_meta($post_id, '_virtual', 'yes');
            update_post_meta($post_id, '_regular_price', "1");
            update_post_meta($post_id, '_price', "1");

            update_option(
                'bonus-for-woo-offline-product',
                $post_id
            );/*указываем товар для проведения продаж офлайн*/
        }

        /*2. Создаем заказ клиенту на нужную сумму*/
        $order = wc_create_order();
        $order->add_product(wc_get_product($post_id), $price);
// Установим платёжный метод, например пусть это будет оплата наличными при получении
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        if ( ! empty($payment_gateways['cod'])) {
            $order->set_payment_method($payment_gateways['cod']);
        }

        $val = get_option('bonus_option_name');
        $add_points_order_status = $val['add_points_order_status'] ??
            'completed';

        // Пересчитываем заказ
        $order->calculate_totals();
        $current_user = wp_get_current_user();
        $order->add_order_note(
            __('Order created by administrator: ', 'bonus-for-woo')
            .$current_user->user_login
        );
       // update_post_meta($order->get_id(), '_customer_user', $user_id);
         $order->set_customer_id($user_id);


        if ($order->update_status($add_points_order_status)) {
            /*3. кидаем офлайн продукт в черновики*/
            BfwFunctions::setPostStatusBfw('draft', $post_id);
        }
    }


    /**
     * Earn daily points for your first login
     * Начисление ежедневных баллов за первый вход
     *
     * @param $user_id int
     *
     * @return void
     * @version 5.2.0
     * @since 5.2.0
     */
    public static function addEveryDays(int $user_id): void
    {
        if (BfwRoles::isInvalve($user_id)) {
            $val = get_option('bonus_option_name');
            $point_every_day = $val['every_days'] ?? 0;
            if ($point_every_day > 0) {
                //Проверяем получал ли сегодня клиент баллы
                $last_day = get_user_meta($user_id, 'points_every_day', true);
                if ($last_day !== gmdate('d')) {
                    //обновляем день
                    update_user_meta($user_id, 'points_every_day', gmdate('d'));
                    //Узнаем количество баллов клиента
                    $count_point = static::getPoints($user_id);
                    $new_point = $count_point + $point_every_day;


                    //Начисляем баллы клиенту
                    static::updatePoints($user_id, $new_point);


                    $cause = sprintf(
                        __('Daily %s for the login.', 'bonus-for-woo'),
                        $val['label_points']
                    );

                    //Записываем в историю
                    BfwHistory::add_history(
                        $user_id,
                        '+',
                        $point_every_day,
                        '0',
                        $cause
                    );
                    //отправляем письмо
                    if ( ! empty($val['email-when-everyday-login'])) {
                        /*Шаблонизатор письма*/

                        $text_email = $val['email-when-everyday-login-text'] ??
                            '';

                        $title_email = $val['email-when-everyday-login-title']
                            ?? __(
                                'Bonus points have been added to you!',
                                'bonus-for-woo'
                            );
                        $user = get_userdata($user_id);
                        $text_email_array = array(
                            '[user]'   => $user->display_name,
                            '[points]' => $point_every_day,
                            '[total]'  => $new_point
                        );

                        $message_email = (new BfwEmail())::template(
                            $text_email,
                            $text_email_array
                        );
                        /*Шаблонизатор письма*/
                        (new BfwEmail())->getMail(
                            $user_id,
                            '',
                            $title_email,
                            $message_email
                        );
                    }
                }
            }
        }
    }


    /**
     * Displaying write-offs in the shopping cart
     * Вывод списания в корзине
     *
     * @return void
     */
    public static function bfwoo_spisaniebonusov_in_cart()
    {
        if ( ! current_user_can('manage_options')) {
            $redirect = wc_get_cart_url();
            echo self::bfw_return_spisanie($redirect);
        }
    }

    /**
     * Display of points write-off in order processing
     * Вывод списания баллов в оформлении заказов
     *
     * @return void
     */
    public static function bfwoo_spisaniebonusov_in_checkout(): void
    {
        if ( ! current_user_can('manage_options')) {
            $val = get_option('bonus_option_name');
            if ( ! empty($val['spisanie-in-checkout'])) {
                $redirect = wc_get_checkout_url();
                echo self::bfw_return_spisanie($redirect);
            }
        }
    }


    /**
     * Write-off of points in the cart and checkout
     * Вывод списания баллов в корзине и оформлении заказа
     *
     * @param $redirect
     *
     * @return string
     * @version 5.3.3
     */
    public static function bfw_return_spisanie($redirect): string
    {
        $return = '';
        $val = get_option('bonus_option_name');
        $BfwRoles = new BfwRoles();
        $woo = WC();
        if ( ! empty($val['exclude-fees-coupons'])) {
            /*Сумма товаров в корзине без учета купонов и скидок*/
            $items = $woo->cart->get_cart();
            $total = 0;
            foreach ($items as $item => $values) {
                $price = (int)get_post_meta(
                        $values['product_id'],
                        '_price',
                        true
                    )
                    * $values['quantity'];

                $total += $price;
            }
        } else {
            $total = $woo->cart->total;//сумма в корзине

            /*Убираем доставку из общей суммы*/
            if ($woo->cart->shipping_total > 0) {
                $total -= $woo->cart->shipping_total;
            }

            /*Убираем налог на доставку из общей суммы*/
            if ($woo->cart->shipping_tax_total > 0) {
                $total -= $woo->cart->shipping_tax_total;
            }

            /*Убираем левые комиссии из общей суммы*/
            $fees = $woo->cart->get_fees();
            foreach ($fees as $fee) {
                $name = $fee->name;
                $amount = $fee->amount;
                if ($name !== $val['bonus-points-on-cart']) {
                    $total -= $amount;
                }
            }
        }


        $alternative = '';

        $computy_point = self::getPoints(
            get_current_user_id()
        ); //всего баллов у покупателя


        /*Процент списание баллов для про*/
        if ($BfwRoles::isPro()) {
            $max_percent = $val['max-percent-bonuses'] ?? 100;
            $max_percent = apply_filters(
                'max-percent-bonuses-filter',
                $max_percent,
                $total
            );
        } else {
            $max_percent = 100;
        }
        /*Процент списание баллов для про*/


        $displaynone = '';

        /*Исключение товаров и категорий*/
        if ($BfwRoles::isPro()) {
            $total = BfwFunctions::bfwExcludeCategoryCashback($total);
            $total = BfwFunctions::bfwExcludeProductCashback($total);
        }
        /*Исключение товаров и категорий*/
        //$return .=$total;

        $i = 0;
        $s = 0;
        $cart_items = $woo->cart->get_cart(); // получаем корзину один раз
        foreach ($cart_items as $item) {
            $id_tovara_vkorzine = $item['product_id'];
            $s++;
            $product = wc_get_product($id_tovara_vkorzine);
            if ($product->is_on_sale()) {
                $i++;
                if ( ! empty($val['spisanie-onsale'])) {
                    /*Исключаем возможность тратить кешбэк на товары со скидкой */
                    $sum_exclud_sale = $item['data']->get_price()
                        * $item['quantity'];
                    $total -= $sum_exclud_sale;
                }
            }
        }

        if ($i === $s && ! empty($val['spisanie-onsale'])) {
            /*Если все товары в корзине со скидкой*/
            /*Если не разрешено списывать баллы у распродажи*/
            $displaynone = 'style="display:none"';
        }


        $user_fast_points = self::getFastPoints(get_current_user_id());

        $total_plus_fast = $total + $user_fast_points;


        $total_max_percent = $total_plus_fast * $max_percent / 100;
        $total_max_percent = self::roundPoints(
            $total_max_percent
        );/*округляем если надо*/

        $computy_point = self::roundPoints($computy_point);


        $total_max_percent = min($total_max_percent, $computy_point);

        $vozmojniy_ball_true = $total_max_percent - $user_fast_points;

        $vozmojniy_ball_true = max($vozmojniy_ball_true, $user_fast_points);


        if ($vozmojniy_ball_true < 0) {
            $vozmojniy_ball_true = $total_max_percent;
        }


        /*Высчитывание минимальной суммы заказа*/
        $minimal_amount = 100000;
        if ( ! empty($val['minimal-amount']) && $val['minimal-amount'] > 0) {
            $minimal_amount = $total - $val['minimal-amount'];
            $minimal_amount = max($minimal_amount, 0);
        }


        if ($computy_point > 0) {
            /*если есть другие комиссии, то вычесть их из возможных баллов*/

            if ($user_fast_points > 0) {
                $vozmojniy_ball_true += $user_fast_points;
                $vozmojniy_ball_true = self::roundPoints($vozmojniy_ball_true);
                $total = max($total, $vozmojniy_ball_true);
            }


            $vozmojniy_ball = min(
                $computy_point,
                $vozmojniy_ball_true,
                $total,
                $minimal_amount,
                $total_max_percent
            );
            $vozmojniy_ball = self::roundPoints(
                $vozmojniy_ball
            );/*округляем если надо*/


            if ($vozmojniy_ball === 0) {
                $displaynone = 'style="display:none"';
            }


            if ( ! empty($val['balls-and-coupon'])) {
                /*Если применяется купон*/

                if ($woo->cart->applied_coupons) {
                    $cart_discount = mb_strtolower(
                        $val['bonus-points-on-cart']
                    );

                    if ( ! empty($val['fee-or-coupon'])) {
                        //если система с помощью купонов
                        if (in_array(
                                $cart_discount,
                                $woo->cart->get_applied_coupons()
                            )
                            && count($woo->cart->get_applied_coupons()) > 1
                        ) {
                            $displaynone = 'style="display:none"';
                            $alternative
                                = '<div class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-error">'
                                .
                                sprintf(
                                    __(
                                        'To use %s, you must remove the coupon.',
                                        'bonus-for-woo'
                                    ),
                                    self::pointsLabel(5)
                                ).'</div> ';
                            /*Тут  очистить fastballs*/

                            foreach ($woo->cart->get_applied_coupons() as $code)
                            {
                                if (strtolower($code) === $cart_discount) {
                                    $woo->cart->remove_coupon($code);
                                }
                            }


                            self::updateFastPoints(get_current_user_id(), 0);
                            $woo->cart->calculate_totals(
                            );//Пересчет общей суммы заказа
                        }
                    } else {
                        $displaynone = 'style="display:none"';
                        $alternative
                            = '<div class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-error">'
                            .
                            sprintf(
                                __(
                                    'To use %s, you must remove the coupon.',
                                    'bonus-for-woo'
                                ),
                                self::pointsLabel(5)
                            ).'</div> ';
                        //если система с помощью fee
                        self::updateFastPoints(get_current_user_id(), 0);
                        $woo->cart->calculate_totals(
                        );//Пересчет общей суммы заказа
                    }
                }
            }


            $userid = get_current_user_id();
            if (isset($val['minimal-amount'])) {
                if ($total < $val['minimal-amount']) {
                    $displaynone = 'style="display:none"';
                    $alternative = '';
                    if ($vozmojniy_ball > 0) {
                        $alternative
                            = '<div class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-error">'
                            .
                            sprintf(
                                __(
                                    'To use %s, the order amount must be more than',
                                    'bonus-for-woo'
                                ),
                                self::pointsLabel(5)
                            ).' '.$val['minimal-amount'].' '
                            .get_woocommerce_currency_symbol();
                        if (self::getFastPoints(get_current_user_id()) > 0) {
                            $alternative .= '<form class="remove_points_form" action="'
                                .admin_url("admin-post.php").'" method="post">
                      <input type="hidden" name="action" value="clear_bonus" />';
                            $alternative .= '<input type="hidden" name="redirect" value="'
                                .$redirect.'">';
                            $alternative .= '<input type="submit" class="remove_points"  value="'
                                .$val['remove-on-cart'].'"> </form>';
                        }
                        $alternative .= '</div>';
                    }


                    $cart_discount = mb_strtolower(
                        $val['bonus-points-on-cart']
                    );
                    foreach ($woo->cart->get_applied_coupons() as $code) {
                        if (strtolower($code) === mb_strtolower(
                                $cart_discount
                            )
                        ) {
                            $woo->cart->remove_coupon($code);
                        }
                    }


                    self::updateFastPoints(get_current_user_id(), 0);
                    $woo->cart->calculate_totals();//Пересчет общей суммы заказа
                }
            }

            $return .= $alternative;
            $bonustext_in_cart = $val['bonustext-in-cart'] ?? __(
                'Use [points] points to get a [discount] discount on this order.',
                'bonus-for-woo'
            );

            $bonustext_in_cart_array = array(
                '[points]'   => '<b>'.$vozmojniy_ball.' '.self::pointsLabel(
                        $vozmojniy_ball
                    ).'</b>',
                '[discount]' => '<b>'.$vozmojniy_ball.' '
                    .get_woocommerce_currency_symbol().'</b>'
            );
            $bonustext_in_carts = (new BfwEmail())::template(
                $bonustext_in_cart,
                $bonustext_in_cart_array
            );


            $bonustext_in_cart4 = $val['bonustext-in-cart4'] ??
                __('Use points', 'bonus-for-woo');

            if ($vozmojniy_ball <= 0) {
                $displaynone = 'style="display:none"';
            }

            $return .= '<div '.$displaynone.' id="computy-bonus-message-cart" class="woocommerce-cart-notice woocommerce-cart-notice-minimum-amount woocommerce-info">
'.$bonustext_in_carts.'
 <span class="computy_skidka_link">'.$bonustext_in_cart4.'</span>   ';


            $return .= '
        <div class="computy_skidka_container" style="display: none;">
        <form class="computy_skidka_form" action="'.admin_url("admin-post.php").'" method="post">
         <input type="hidden" name="action" value="computy_trata_points" />';
            $return .= '<input type="hidden" name="maxpoints" value="'
                .$vozmojniy_ball.'"> ';
            $return .= '<input type="hidden" name="redirect" value="'.$redirect
                .'">';

            $return .= ' ';

            $computy_point_old = self::getFastPoints($userid);
            if ($computy_point_old > 0) {
                $usepointsoncart = $computy_point_old;
            } else {
                $usepointsoncart = $vozmojniy_ball;
            }
            $return .= '
         <input type="text" name="computy_input_points" class="input-text"   value="'
                .$usepointsoncart.'">
          
            
           ';
            $return .= ' <input type="submit" class="button"  value="'
                .$val['use-points-on-cart'].'">
            </form></div>';
            if (self::getFastPoints(get_current_user_id()) > 0) {
                $return .= '<form class="remove_points_form" action="'
                    .admin_url("admin-post.php").'" method="post">
                      <input type="hidden" name="action" value="clear_bonus" />';
                $return .= '<input type="hidden" name="redirect" value="'
                    .$redirect.'">';
                $return .= '<input type="submit" class="remove_points"  value="'
                    .$val['remove-on-cart'].'">
 
</form>';
            }
            $return .= '</div> ';
        }

        return $return;
    }


    /**
     * Spending points
     * Трата баллов
     *
     * @return void
     * @version 5.3.3
     */
    public static function bfwoo_trata_points(): void
    {
        if (isset($_POST['computy_input_points'], $_POST['maxpoints'], $_POST['redirect'])) {
            $balls = (int)$_POST['computy_input_points'];
            $max_points = (int)$_POST['maxpoints'];
            $userid = get_current_user_id();

            if ($userid) {
                $allpoint = self::getPoints($userid);
                $updatedPoints = min($balls, $max_points, $allpoint);
                self::updateFastPoints($userid, $updatedPoints);
                wp_send_json_success($_POST['redirect']);
            } else {
                wp_send_json_error('Ошибка получения ID пользователя.');
            }
        } else {
            wp_send_json_error('Отсутствуют необходимые данные в запросе.');
        }
    }


    /**
     * Clearing temporary points
     * Очищение временных баллов
     *
     * @return void
     * @version 5.3.3
     */
    public static function bfwoo_clean_fast_bonus(): void
    {
        $userid = get_current_user_id();
        self::updateFastPoints($userid, 0);
        $val = get_option('bonus_option_name');
        $woo = WC();
        if (isset($val['bonus-points-on-cart'])) {
            $cart_discount = mb_strtolower($val['bonus-points-on-cart']);

            if (isset($woo->cart) && null !== $woo->cart->get_applied_coupons()
                && ! empty($woo->cart->get_applied_coupons())
            ) {
                foreach ($woo->cart->get_applied_coupons() as $code) {
                    if (strtolower($code) === $cart_discount) {
                        $woo->cart->remove_coupon($code);
                    }
                }
            }
        }

        if (isset($_POST['redirect'])) {
            wp_send_json_success($_POST['redirect']);
        }
    }


    /**
     * Earning points from a coupon
     * Получение баллов с купона
     *
     * @return void
     * @version 5.3.3
     */
    public static function bfw_take_coupon_action(): void
    {
        if (isset($_POST['code_coupon'])) {
            $code_coupon = sanitize_text_field($_POST['code_coupon']);
            $userid = get_current_user_id();
            $bfw_coupons = new BfwCoupons();
            $zapros = $bfw_coupons::enterCoupon($userid, $code_coupon);

            if ($zapros === 'limit') {
                $code_otveta = 404;
                $message = __(
                    'Sorry. The coupon usage limit has been reached.',
                    'bonus-for-woo'
                );
            } elseif ($zapros === 'not_coupon') {
                $code_otveta = 404;
                $message = __('Sorry, no such coupon found.', 'bonus-for-woo');
            } else {
                $message = __('Coupon activated.', 'bonus-for-woo');
                $code_otveta = 200;
            }

            $redirect_url = isset($_POST['redirect']) ? esc_url(
                $_POST['redirect']
            ) : home_url();

            $return = array(
                'redirect' => $redirect_url,
                'message'  => esc_html($message),
                'cod'      => $code_otveta
            );
            wp_send_json_success($return);
        }
    }


    /**
     * Adding a discount
     * Добавляем скидку
     *
     * @return void
     * @version 5.3.3
     */
    public static function bfwoo_add_fee(): void
    {
        $val = get_option('bonus_option_name');
        $userId = get_current_user_id();
        $userPoints = self::getFastPoints($userId);
        $woo = WC();
        if ($userPoints > 0) {
            $computyPointOld = self::roundPoints($userPoints);

            if (empty($val['fee-or-coupon'])) {
                // Используем бонусы с помощью комиссий
                $woo->cart->add_fee(
                    $val['bonus-points-on-cart'],
                    -$computyPointOld,
                    false
                );
            } else {
                // Используем бонусы с помощью купонов
                $cartDiscount = mb_strtolower($val['bonus-points-on-cart']);
                if (isset($woo->cart)
                    && ! $woo->cart->has_discount(
                        $cartDiscount
                    )
                    && ! in_array(
                        $cartDiscount,
                        $woo->cart->applied_coupons,
                        true
                    )
                ) {
                    $woo->cart->applied_coupons[] = $cartDiscount;
                }
            }
        }
    }


    /**
     * Delete button in subtotal (using commissions)
     * Кнопка удаления в подытоге (с помощью комиссий)
     *
     * @param $cart_totals_fee_html
     * @param $fee
     *
     * @return string
     * @version 6.4.0
     */
    public static function bfw_button_delete_fast_point(
        $cart_totals_fee_html,
        $fee
    ): string {
        if ( ! empty($fee)) {
            $fee_name = $fee->name;
            $val = get_option('bonus_option_name');
            $cart_discount = $val['bonus-points-on-cart'];
            if ($cart_discount === $fee_name) {
                $remove_cart_text = $val['remove-on-cart'];
                $cart_totals_fee_html .= '<a id="bfw_remove_cart_point" title="'
                    .$remove_cart_text.'">'.$remove_cart_text.'</a>';
            }
        }
        return $cart_totals_fee_html;
    }

    /**
     * Create a virtual coupon
     * Создаем виртуальный купон
     *
     * @param $response  !!!не удаляем!!!
     * @param $coupon_data
     *
     * @return array|null
     * @version 6.4.0
     */
    public static function get_virtual_coupon_data_bfw($response, $coupon_data)
    {
        $val = get_option('bonus_option_name');
        $cart_discount = mb_strtolower($val['bonus-points-on-cart']);
        if ($coupon_data == $cart_discount) {
            $userid = get_current_user_id();
            $computy_point_old = self::getFastPoints(
                $userid
            ); //узнаем баллы которые он решил списать
            $computy_point_old = self::roundPoints($computy_point_old);
            $discount_type = 'fixed_cart';
            $coupon = array(
                'id'                         => time().wp_rand(2, 9),
                'amount'                     => $computy_point_old,
                'individual_use'             => false,
                'product_ids'                => array(),
                'exclude_product_ids'        => array(),
                'usage_limit'                => '',
                'usage_limit_per_user'       => '',
                'limit_usage_to_x_items'     => '',
                'usage_count'                => '',
                'expiry_date'                => '',
                'apply_before_tax'           => 'yes',
                'free_shipping'              => false,
                'product_categories'         => array(),
                'exclude_product_categories' => array(),
                'exclude_sale_items'         => false,
                'minimum_amount'             => '',
                'maximum_amount'             => '',
                'customer_email'             => '',
            );
            $coupon['discount_type'] = $discount_type;

            return $coupon;
        }
        return null;
    }

    /**
     * View of coupons in the basket
     * Вид купонов в корзине
     *
     * @param $html
     * @param $coupon
     *
     * @return string
     * @version 6.4.0
     */
    public static function bfw_coupon_html($html, $coupon)
    {
        $val = get_option('bonus_option_name');
        $cart_discount = mb_strtolower($val['bonus-points-on-cart']);
        $coupon_data = mb_strtolower($coupon->get_code());
        $userid = get_current_user_id();
        $computy_point_old = self::getFastPoints(
            $userid
        ); //узнаем баллы которые он решил списать
        $computy_point_old = self::roundPoints($computy_point_old);

        if (strtolower($coupon_data) === strtolower($cart_discount)) {
            $html = ' <span class="woocommerce-Price-amount amount">-'.wc_price(
                    $computy_point_old
                ).'</span>
    <a id="bfw_remove_cart_point" title="'.$val['remove-on-cart'].'">'
                .$val['remove-on-cart'].'</a>';
        }
        return $html;
    }

    /**
     * Remove the "coupon" from the cart
     * Убираем "купон" в корзине
     *
     * @param $sprintf
     * @param $coupon
     *
     * @return string
     * @version 6.4.0
     */
    public static function woocommerceChangeCouponLabelBfw(
        $sprintf,
        $coupon
    ): string {
        $val = get_option('bonus_option_name');

        $cart_discount = mb_strtolower($val['bonus-points-on-cart']);
        $coupon_data = $coupon->get_data();
        if ( ! empty($coupon_data)
            && strtolower($coupon_data['code']) === strtolower($cart_discount)
        ) {
            $sprintf = $val['bonus-points-on-cart'];
        }
        return $sprintf;
    }


    /**
     * Excluding tax deductions
     * Исключаем скидку из налогов
     *
     * @param $taxes
     * @param $fee
     * @param $cart
     *
     * @return array
     * @version 6.4.0
     */
    public static function excludeCartFeesTaxes($taxes, $fee, $cart): array
    {
        return [];
    }

    /**
     * Removing temporary points when emptying the recycle bin
     * Удаление временных баллов при очистке корзины
     *
     * @return void
     * @version 6.4.0
     */
    public static function actionWoocommerceBeforeCartItemQuantityZero(): void
    {
        self::updateFastPoints(get_current_user_id(), 0);
    }


    /**
     * Clearing time points when changing the quantity of goods
     * Очищение временных баллов при изменении количества товаров
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfwCartItemSetQuantity(): void
    {
        $val = get_option('bonus_option_name');
        $qty_cart = isset($val['clear-fast-bonus-were-qty-cart'])
            ? (int)$val['clear-fast-bonus-were-qty-cart']
            : 0;

        if ($qty_cart) {
            self::updateFastPoints(get_current_user_id(), 0);
        }
    }

    /**
     * Export bonus csv file
     * Экспорт csv файла бонусов
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfw_export_bonuses(): void
    {
        $array = json_decode(stripslashes($_POST['response']), true);
        $url_export_file
            = $array['data']['url']; /*ссылка на загруженный файл экспорта*/

        //Сколько строк обрабатывать в каждом пакете
        $limit = 100;
        $fileHandle = fopen($url_export_file, "rb");
        if ($fileHandle === false) {
            die(
                __('Error opening', 'bonus-for-woo').' '.htmlspecialchars(
                    $url_export_file
                )
            );
        }
        $by_email = isset($_POST['by_email']) && $_POST['by_email'] === '1';

        while ( ! feof($fileHandle)) {
            $i = 0;
            while (($currRow = fgetcsv($fileHandle)) !== false) {
                $i++;
                $id = $by_email ? get_user_by('email', $currRow[2])->ID
                    : (int)$currRow[0];
                $point = (float)$currRow[3];
                $user_point = (float)get_user_meta($id, 'computy_point', true);
                $comment = (string)($currRow[4] ?? '');

                if (get_user_meta($id, 'computy_point', true) !== $point) {
                    //Проверяем добавились ли баллы или убавились и записываем в историю
                    $difference = abs($user_point - $point);
                    BfwHistory::add_history(
                        $id,
                        $user_point > $point ? '-' : '+',
                        $difference,
                        '0',
                        $comment
                    );
                    update_user_meta($id, 'computy_point', $point);
                    /*Обновляем баллы пользователям*/
                }
                if ($i >= $limit) {
                    break;
                }
            }
        }
        fclose($fileHandle);
        echo 'good';
        exit();
    }

    /**
     * Action when the client confirms the order - write-off of points
     * Действие когда клиент подтверждает заказ - списание баллов
     *
     * @param $order_id int
     *
     * @return void
     * @version 6.4.0
     */
    public static function newOrder(int $order_id): void
    {
        $order = wc_get_order($order_id);
        $customer_user = $order->get_customer_id();
        $bfwEmail = new BfwEmail();

        $computy_point_old = self::getPoints($customer_user);
        $computy_point_fast = self::getFastPoints($customer_user);

        if ($computy_point_fast > 0) {
            $count_point = $computy_point_old - $computy_point_fast;
            self::updatePoints($customer_user, $count_point);

            $prichina = sprintf(
                __('Use of %s', 'bonus-for-woo'),
                self::pointsLabel(5)
            );
            BfwHistory::add_history(
                $customer_user,
                '-',
                $computy_point_fast,
                $order_id,
                $prichina
            );

            $val = get_option('bonus_option_name');
            $title_email = $val['email-when-order-confirm-title'] ??
                __('Writing off bonus points', 'bonus-for-woo');

            $text_email = $val['email-when-order-confirm-text'] ?? '';
            $user = get_userdata($customer_user);
            $get_referral = get_user_meta(
                $customer_user,
                'bfw_points_referral',
                true
            );

            $text_email_array = array(
                '[user]'          => $user->display_name,
                '[order]'         => $order_id,
                '[points]'        => $computy_point_fast,
                '[total]'         => $count_point,
                '[cause]'         => $prichina,
                '[referral-link]' => esc_url(
                    site_url().'?bfwkey='.$get_referral
                )
            );

            $message_email = $bfwEmail::template(
                $text_email,
                $text_email_array
            );

            if ( ! empty($val['email-when-order-confirm'])) {
                $bfwEmail->getMail(
                    $customer_user,
                    '',
                    $title_email,
                    $message_email
                );
            }
        }

        self::updateFastPoints($customer_user, 0);
    }


    /**
     * Action when the order status is completed - accrual of points
     * Действие когда статус заказа выполнен - начисление баллов
     *
     * @param $order_id int
     *
     * @return bool|null
     * @version 6.3.5
     */
    public static function addPointsForOrder(int $order_id): ?bool
    {
        $order = wc_get_order($order_id);
        $customer_user = $order->get_customer_id();

        if ($customer_user === 0) {
            return null;
        }

        $bfwRoles = new BfwRoles();
        $bfwFunctions = new BfwFunctions();
        $bfwHistory = new BfwHistory();
        $bfwEmail = new BfwEmail();
        $bfwReferral = new BfwReferral();

        $val = get_option('bonus_option_name');
        $order_total = (float)$order->get_total();
        $order_items = $order->get_items();

        $shipping_total = $order->get_shipping_total();
        if ( ! empty($val['cashback-for-shipping'])) {
            $order_total -= $shipping_total;
        }


        if ($bfwRoles::isPro()) {
            $payment_method = $order->get_payment_method();
            if ( ! empty($val['exclude-payment-method'])
                && in_array(
                    $payment_method,
                    $val['exclude-payment-method']
                )
            ) {
                return false;
            }


            foreach ($order_items as $item_id => $item) {
                $product_id = $item->get_product_id();
                $item_data = $item->get_data();

                //исключаем из кешбэка товары со скидкой
                if ( ! empty($val['cashback-on-sale-products'])) {
                    $productc = wc_get_product($product_id);
                    $was_on_sale = $item->get_meta('_was_on_sale');


                    if ($productc->is_on_sale() || $was_on_sale === 'yes') {
                        $order_total -= $item_data['subtotal'];
                    }
                }
                //исключаем из кешбэка товары со скидкой

                if (empty($val['addkeshback-exclude'])){
                    //исключение товаров и категорий
                    $exclude_tovar = $val['exclude-tovar-cashback'];
                    $tovars = apply_filters('bfw-excluded-products-filter', explode(",", $exclude_tovar),$exclude_tovar);

                    if (in_array($product_id, $tovars, true) && ! has_term($val['exclude-category-cashback'] ?? 'not', 'product_cat',$product_id)) {
                        $total_exclude = $item_data['subtotal'];
                        $order_total -= $total_exclude;
                    } elseif (has_term($val['exclude-category-cashback'] ?? 'not','product_cat',$product_id )) {
                        $total_exclude_cat = $item_data['subtotal'];
                        $order_total -= $total_exclude_cat;
                    }
                }




            }
        }

        $computy_point_old = self::getPoints($customer_user);

        if ($bfwRoles::isInvalve($customer_user)) {
            /*Если участвует в бонусной системе*/

            /*минимальная сумма заказа*/
            if ($bfwRoles::isPro()) {
                if (isset($val['minimal-amount'])) {
                    if ($order_total < $val['minimal-amount']
                        && ! empty($val['minimal-amount-cashback'])
                    ) {
                        return false;
                    }
                }
            }
            /*минимальная сумма заказа*/


            $bfwRoles::updateRole($customer_user); //обновляем роль
            $percent = $bfwRoles::getRole(
                $customer_user
            )['percent'];//находим процент


            /*-----добавляем бонусные баллы на счет клиента-----*/

            /*узнаем баллы, которые решил списать клиент*/
            $computy_point_fast = self::getFastPoints($customer_user);

            $computy_point_new = ($order_total - $computy_point_fast)
                * ($percent / 100);
            $computy_point_new = self::roundPoints($computy_point_new);

            $computy_point_new = apply_filters(
                'bfw-completed-points',
                $computy_point_new,
                $order_id,
                $order
            );

            $count_point = $computy_point_old + $computy_point_new;
            $count_point = self::roundPoints(
                $count_point
            ); //округляем если надо


            if ($computy_point_fast > 0) {
                $count_point -= $computy_point_fast;
            }


            /*Находим используемые баллы в заказе*/
            $fee_total = $bfwFunctions::feeOrCoupon($order);


            if (isset($val['yous_balls_no_cashback']) && $fee_total > 0) {
                //если используются баллы - кешбэк не дается.
            } else {
                if ((int)$computy_point_new !== 0) {
                    /*запись в историю*/
                    $reason = __('Points accrual', 'bonus-for-woo');
                    $bfwHistory::add_history(
                        $customer_user,
                        '+',
                        $computy_point_new,
                        $order_id,
                        $reason
                    );

                    /*email*/
                    $val = get_option('bonus_option_name');
                    $text_email = $val['email-when-order-change-text'] ?? '';
                    /*шаблонизатор письма*/
                    $title_email = $val['email-when-order-change-title'] ??
                        __('Points accrual', 'bonus-for-woo');
                    $user = get_userdata($customer_user);
                    $get_referral = get_user_meta(
                        $customer_user,
                        'bfw_points_referral',
                        true
                    );
                    $text_email_array = array(
                        '[user]'          => $user->display_name,
                        '[order]'         => $order_id,
                        '[points]'        => $computy_point_new,
                        '[total]'         => $count_point,
                        '[referral-link]' => esc_url(
                            site_url()
                            .'?bfwkey='.$get_referral
                        )
                    );
                    $message_email = $bfwEmail::template(
                        $text_email,
                        $text_email_array
                    );
                    /*шаблонизатор письма*/

                    if ( ! empty($val['email-when-order-change'])) {
                        $bfwEmail->getMail(
                            $customer_user,
                            '',
                            $title_email,
                            $message_email
                        );
                    }
                    /*email*/
                }
                self::updatePoints(
                    $customer_user,
                    $count_point
                );//добавляем баллы клиенту
            }

            self::updateFastPoints(
                $customer_user,
                0
            );//очищаем добавленную скидку


            $referalwork = isset($val['referal-system'])
                ? (int)$val['referal-system'] : 0;
            /*если включена реферальная система*/
            if ($referalwork) {
                //узнать если у пользователя инвайт
                //bog<-invaite<-client
                $get_referral_invite = get_user_meta(
                    $customer_user,
                    'bfw_points_referral_invite',
                    true
                );
                $get_referral_invite = (int)$get_referral_invite;
                if ($get_referral_invite > 0) {
                    $sumordersforreferral = $val['sum-orders-for-referral'] ??
                        0.0;
                    $totalref = self::getSumUserOrders($get_referral_invite);
                    if ($totalref >= $sumordersforreferral) {
                        /*процент от приглашенного первого уровня*/
                        $percent_for_referal = $val['referal-cashback'];

                        /*Добавляем баллы рефереру (пригласителю, спонсору) от реферала первого уровня*/
                        $bfwReferral::addReferralPoints(
                            $customer_user,
                            $percent_for_referal,
                            $order_total,
                            $computy_point_fast,
                            $get_referral_invite
                        );
                    }


                    /*Начисляем баллы от реферала второго уровня*/
                    if ( ! empty($val['level-two-referral'])) {
                        $get_referral_invite_two_level = get_user_meta(
                            $get_referral_invite,
                            'bfw_points_referral_invite',
                            true
                        );
                        $get_referral_invite_two_level
                            = (int)$get_referral_invite_two_level;
                        if ($get_referral_invite_two_level !== 0) {
                            $sumordersforreferral2
                                = $val['sum-orders-for-referral'] ?? 0.0;
                            $totalref2 = self::getSumUserOrders(
                                $get_referral_invite_two_level
                            );
                            if ($totalref2 >= $sumordersforreferral2) {
                                $percent_for_referal_two_level
                                    = $val['referal-cashback-two-level'];

                                /*Добавляем баллы рефереру (пригласителю, спонсору) от реферала второго уровня*/
                                $bfwReferral::addReferralPoints(
                                    $customer_user,
                                    $percent_for_referal_two_level,
                                    $order_total,
                                    $computy_point_fast,
                                    $get_referral_invite_two_level
                                );
                            }
                        }
                    }
                }
            }
        }


        return null;
    }

    /**
     * Action when points refund is issued
     * Действие когда оформлен возврат баллов
     *
     * @param $order_id int
     *
     * @return void
     * @version 6.3.5
     */
    public static function refundedPoints(int $order_id): void
    {
        global $wpdb;

        $order = wc_get_order($order_id);
        $customer_user = $order->get_customer_id();

        $computy_point_old = self::getPoints($customer_user);

        $fee_total = BfwFunctions::feeOrCoupon($order);
        $count_point = $computy_point_old + $fee_total;

        $cause = __('Refund of bonus points', 'bonus-for-woo');

        $getplusball = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT points FROM '.$wpdb->prefix
                .'bfw_history_computy WHERE user = %d AND symbol="+" AND orderz = %d ',
                $customer_user,
                $order_id
            )
        );

        $info_email = '';


        if ( ! empty($getplusball)) {
            $getplusball = self::roundPoints($getplusball);
        } else {
            $getplusball = 0;
        }

        if ($getplusball > 0) {
            BfwHistory::add_history(
                $customer_user,
                '-',
                $getplusball,
                $order_id,
                $cause
            );
            $count_point -= $getplusball;
            $info_email .= sprintf(
                __(
                    'The %1$s bonus points you earned for order no. %2$s have been canceled.',
                    'bonus-for-woo'
                ),
                $getplusball,
                $order_id
            );
        }

        if ($fee_total > 0) {
            /*Добавляем списанные баллы*/
            BfwHistory::add_history(
                $customer_user,
                '+',
                $fee_total,
                $order_id,
                $cause
            );
            $info_email .= sprintf(
                __(
                    'You have returned %1$d bonus points for order number %2$d.',
                    'bonus-for-woo'
                ),
                $fee_total,
                $order_id
            );
        }
        self::updatePoints(
            $customer_user,
            $count_point
        );//Обновляем баллы клиенту
        BfwRoles::updateRole($customer_user); //Обновляем роль клиенту
        /*email*/
        $val = get_option('bonus_option_name');


        /*Шаблонизатор письма*/
        $title_email = $val['email-when-order-change-title-vozvrat'] ??
            __('Refund of bonus points', 'bonus-for-woo');
        $text_email = $val['email-when-order-change-text-vozvrat'] ?? '';

        $user = get_userdata($customer_user);
        $get_referral = get_user_meta(
            $customer_user,
            'bfw_points_referral',
            true
        );
        $text_email_array = array(
            '[referral-link]' => esc_url(
                site_url().'?bfwkey='.$get_referral
            ),
            '[user]'          => $user->display_name,
            '[cashback]'      => $getplusball,
            '[order]'         => $order_id,
            '[points]'        => $fee_total,
            '[total]'         => $count_point
        );
        $message_email = BfwEmail::template(
            $text_email,
            $text_email_array
        );
        /*Шаблонизатор письма*/

        if ( ! empty($val['email-when-order-change'])) {
            if ($getplusball > 0 || $fee_total > 0) {
                (new BfwEmail())->getMail(
                    $customer_user,
                    '',
                    $title_email,
                    $message_email
                );
            }
        }
        /*email*/
    }

    /**
     * Removing points for inactivity.
     * Удаление баллов за бездействие.
     *
     * @return void
     * @version 6.3.5
     */
    public static function deleteBallsOldClients(): void
    {
        $val = get_option('bonus_option_name');
        $day_day = $val['day-inactive'] ?? 0;
        $exclude_role = $val['exclude-role'] ?? 'administrator';
        $exclude_role = apply_filters(
            'bfw-exclude-role-for-cron',
            $exclude_role
        );
        $args = array(
            'role__not_in' => $exclude_role,
            'meta_query'   => array(
                array(
                    'key'     => 'computy_point',
                    'value'   => 0,
                    'compare' => '>'
                )
            ),
        );
        $users = get_users($args);
        $today = strtotime(gmdate("d.m.Y"));
        global $wpdb;
        $bfwEmail = new BfwEmail();
        $bfwHistory = new BfwHistory();

        foreach ($users as $user) {
            $last_actions = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}bfw_history_computy WHERE user = {$user->ID} ORDER BY date DESC LIMIT 1"
            );
            $last_action = $last_actions ? strtotime(
                gmdate("d.m.Y", strtotime($last_actions[0]->date))
            ) : strtotime(gmdate("d.m.Y", strtotime($user->user_registered)));
            $seconds = abs($today - $last_action);
            $days = floor($seconds / 86400);

            if ( ! empty($val['day-inactive-notice'])) {
                $day_notice_remove_points = $val['day-inactive-notice'];
                if ($day_notice_remove_points !== ''
                    && $day_notice_remove_points > 0
                ) {
                    $notice = get_user_meta(
                        $user->ID,
                        'mail_remove_points',
                        true
                    ) ?? '';
                    if ($notice !== 'yes'
                        && $days > $day_day - $day_notice_remove_points
                    ) {
                        $title_email = $val['email-when-inactive-notice-title']
                            ?? __(
                                'Your points will be deleted soon.',
                                'bonus-for-woo'
                            );
                        $text_email = $val['email-when-inactive-notice-text'] ??
                            '';
                        $ball_user = self::getPoints($user->ID);
                        $text_email_array = array(
                            '[user]'   => $user->display_name,
                            '[days]'   => $day_notice_remove_points,
                            '[points]' => $ball_user
                        );
                        $message_email = $bfwEmail::template(
                            $text_email,
                            $text_email_array
                        );
                        if ( ! empty($val['email-when-inactive-notice'])) {
                            $bfwEmail->getMail(
                                $user->ID,
                                '',
                                $title_email,
                                $message_email
                            );
                        }
                        update_user_meta(
                            $user->ID,
                            'mail_remove_points',
                            'yes'
                        );
                    }
                }
            }

            if ($days > $day_day && $day_day > 1) {
                $computy_point_old = self::getPoints($user->ID);
                $bfwHistory::add_history(
                    $user->ID,
                    '-',
                    $computy_point_old,
                    '0',
                    sprintf(__('Inactivity %d days', 'bonus-for-woo'), $day_day)
                );
                update_user_meta($user->ID, 'mail_remove_points', 'no');
                self::updatePoints($user->ID, 0);
            }
        }
    }

    /**
     * Earning points on your birthday
     * Начисление баллов в день рождение
     *
     * @return void
     * @version 6.3.5
     */
    public static function addBallsForBirthday(): void
    {
        $bonus_option_name = get_option('bonus_option_name');
        $exclude_role = $bonus_option_name['exclude-role'] ?? 'administrator';
        $args = array(
            'role__not_in' => $exclude_role,
            'meta_query'   => array(
                array(
                    'key'     => 'dob',
                    'value'   => 0,
                    'compare' => '>'
                )
            ),
        );
        $users = get_users($args);

        $title_email = $bonus_option_name['email-whens-birthday-title'] ??
            __('Bonus points on your birthday', 'bonus-for-woo');
        $text_email = $bonus_option_name['email-when-birthday-text'] ?? '';
        $text_email_array = array(
            '[user]'                => '',
            '[points_for_birthday]' => $bonus_option_name['birthday']
        );
        $message_email = BfwEmail::template(
            $text_email,
            $text_email_array
        );

        $cause = __('Birthday', 'bonus-for-woo');

        foreach ($users as $user) {
            if (gmdate("d.m", strtotime($user->dob)) === gmdate('d.m')) {
                $count_point = self::getPoints($user->ID)
                    + $bonus_option_name['birthday'];
                if ( ! empty($user->this_year)) {
                    if ($user->this_year !== gmdate('Y')) {
                        self::updatePoints($user->ID, $count_point);
                        BfwHistory::add_history(
                            $user->ID,
                            '+',
                            $bonus_option_name['birthday'],
                            '0',
                            $cause
                        );
                        update_user_meta($user->ID, 'this_year', gmdate('Y'));

                        if ( ! empty($bonus_option_name['email-when-birthday'])) {
                            (new BfwEmail())->getMail(
                                $user->ID,
                                '',
                                $title_email,
                                $message_email
                            );
                        }
                    }
                } else {
                    self::updatePoints($user->ID, $count_point);
                    BfwHistory::add_history(
                        $user->ID,
                        '+',
                        $bonus_option_name['birthday'],
                        '0',
                        $cause
                    );
                    update_user_meta($user->ID, 'this_year', gmdate('Y'));

                    if ( ! empty($bonus_option_name['email-when-birthday'])) {
                        (new BfwEmail())->getMail(
                            $user->ID,
                            '',
                            $title_email,
                            $message_email
                        );
                    }
                }
            }
        }
    }
}
