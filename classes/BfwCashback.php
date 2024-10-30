<?php

defined('ABSPATH') or die;

/**
 * Cashback Management Class
 * Класс управления вывода кешбэка кешбэком
 *
 * Class BfwCashback
 *
 * @version 6.4.0
 */
class BfwCashback
{

    /**
     * Cashback write-off output in the cart
     * Вывод суммы начисления кешбэка в корзине и в оформлении заказа
     *
     * @return array
     * @version 6.4.0
     */
    public static function bfw_get_cashback_in_cart(): array
    {
        $val = get_option('bonus_option_name');
        $bfwFunctions = new BfwFunctions();
        $woocommerce = WC();
        $percentUp = '';
        $upto = '';

        $total_order = (float)$total_order_user = $woocommerce->cart->total;
        if ( ! empty($val['exclude-fees-coupons'])) {
            /*сумма товаров в корзине без учета купонов и скидок*/
            $total_order
                = $total_order_user = $woocommerce->cart->get_subtotal();
        }

        if (BfwRoles::isPro() && empty($val['addkeshback-exclude'])) {
            $total_order = $bfwFunctions::bfwExcludeCategoryCashback($total_order);
            $total_order = $bfwFunctions::bfwExcludeProductCashback($total_order);
        }

        if (is_user_logged_in()) {
            $userid = get_current_user_id();
            $cashback_title = $val['how-mach-bonus-title'] ??
                __('Cashback:', 'bonus-for-woo');

            if (BfwRoles::isInvalve($userid)) {
                $total_all = BfwPoints::getSumUserOrders($userid);

                $sumbudet = $total_all + $total_order_user;

                global $wpdb;
                $all_role = $wpdb->get_results(
                    "SELECT summa_start FROM ".$wpdb->prefix."bfw_computy"
                );
                $all_role = json_decode(wp_json_encode($all_role), true);
                $all_role = $bfwFunctions::arrayMultisortValue(
                    $all_role,
                    'summa_start',
                    SORT_ASC
                );

                $summa = 0;
                foreach ($all_role as $a) {
                    if ($sumbudet >= $a['summa_start']) {
                        $summa = $a['summa_start'];
                    }
                }

                $you_next_role = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM ".$wpdb->prefix
                        ."bfw_computy WHERE summa_start=%s",
                        $summa
                    )
                );
                $this_percent = BfwRoles::getRole($userid);
                $percent = $this_percent['percent'] ?? 0;
                $percentUp = ' '.$percent.'%';

                if ($you_next_role
                    && $you_next_role[0]->percent !== $this_percent['percent']
                ) {
                    $percent = $you_next_role[0]->percent;
                    $percentUp = ' '.$percent."% ▲";
                }
            }
        } else {
            global $wpdb;
            if (empty($val['bonus-in-price-upto'])) {
                $upto = __('up to', 'bonus-for-woo');
            }
            $cashback_title = sprintf(
                __('Cashback %s', 'bonus-for-woo'),
                $upto
            );

            $percent = $wpdb->get_var(
                "SELECT MAX(CAST(percent AS SIGNED)) FROM ".$wpdb->prefix
                ."bfw_computy"
            );
        }

        $percent = empty($percent) ? 0 : $percent;

        if (isset($val['cashback-for-shipping'])) {
            $tot = $total_order - $woocommerce->cart->shipping_total;
        } else {
            $tot = $total_order;
        }

        $cashback_this_order = $percent * $tot / 100;

        if (BfwRoles::isPro() && isset($val['minimal-amount'])
            && $total_order < $val['minimal-amount']
            && ! empty($val['minimal-amount-cashback'])
        ) {
            $cashback_this_order = 0;
        }

//исключаем из кешбэка товары со скидкой
        if ( ! empty($val['cashback-on-sale-products'])) {
            $cart_items = $woocommerce->cart->get_cart();
            foreach ($cart_items as $cart_item) {
                $_product = $cart_item['data'];
                $id_tovara_vkorzine = $cart_item['product_id'];
                $productc = wc_get_product($id_tovara_vkorzine);
                if ($productc->is_on_sale()) {
                    $sum_percent = 0;
                    if ($percent > 0) {
                        $sum_percent = $_product->get_sale_price() / 100
                            * $percent * $cart_item['quantity'];
                    }
                    $cashback_this_order -= $sum_percent;
                }
            }
        }
//исключаем из кешбэка товары со скидкой


        if ( ! empty($val['buy_balls-cashback'])) {
            $bay_balls = array($val['buy_balls-cashback']);

            foreach ($cart_items as $cart_item) {
                $_product = $cart_item['data'];

                if (in_array($cart_item['product_id'], $bay_balls, true)) {
                    $sum_percent = 0;
                    if ($percent > 0) {
                        $sum_percent = $_product->get_price() / 100 * $percent
                            * $cart_item['quantity'];
                    }
                    $sum_bay_balls = $_product->get_price()
                        * $cart_item['quantity'] - $sum_percent;
                    $cashback_this_order += $sum_bay_balls;
                }
            }
        }

        if (is_user_logged_in()) {
            $userid = get_current_user_id();
            $computy_point_fast = BfwPoints::getFastPoints($userid);

            if ( ! empty($val['yous_balls_no_cashback']) && BfwRoles::isPro()
                && $computy_point_fast > 0
            ) {
                $cashback_this_order = 0;
            }
        } else {
            $percentUp = '';
        }

        $cashback_this_order = apply_filters(
            'bfw-cart-cashback-display-amount',
            $cashback_this_order
        );

        $return = array();
        if ($cashback_this_order > 0) {
            $return['cashback_title'] = $cashback_title;
            $return['percentUp'] = $percentUp;
            $return['cashback_this_order'] = BfwPoints::roundPoints(
                $cashback_this_order
            );
            $return['upto'] = $upto;
        }

        return $return;
    }


    /**
     * When is cashback output displayed in [woocommerce-cart]
     * Когда вывод кешбэка выводится в [woocommerce-cart]
     *
     * @return void
     * @version 6.4.0
     */
    public static function getCashbackInCart(): void
    {
        $return = self::bfw_get_cashback_in_cart();

        if ($return) {
            $cashback_title = esc_html($return['cashback_title']);
            $percent_up = esc_html($return['percentUp']);
            $upto_label = BfwPoints::howLabel(
                $return['upto'],
                $return['cashback_this_order']
            );

            echo <<<HTML
<tr class="order-cashback">
    <th><span class="order-cashback-title">{$cashback_title}{$percent_up}</span></th>
    <td colspan="2" data-title="{$cashback_title}{$percent_up}">
        <span class="order-cashback-value">{$return['cashback_this_order']} {$upto_label}</span>
    </td>
</tr>
HTML;
        }
    }


    /**
     * When the cashback output is displayed by shortcode
     * Когда вывод кешбэка выводится шорткодом
     *
     * @return string
     * @version 6.4.0
     */
    public static function bfwGetCashbackInCartForShortcode(): string
    {
        $return = self::bfw_get_cashback_in_cart();
        if ($return) {
            return '<div class="bfw-how-match-cashback-block">
                 <span class="order-cashback-title">'.$return['cashback_title']
                .$return['percentUp'].'</span>
            <span class="order-cashback-value">'.
                $return['cashback_this_order'].' '.BfwPoints::howLabel(
                    $return['upto'],
                    $return['cashback_this_order']
                ).'
            </span>
            </div>';
        }

        return '';
    }


}
