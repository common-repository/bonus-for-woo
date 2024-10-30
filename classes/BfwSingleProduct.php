<?php

defined('ABSPATH') or die;

/**
 * Class for displaying bonus points on the product page
 * Класс отображения бонусных баллов на странице товара
 *
 * Class BfwSingleProduct
 *
 * @version 6.4.0
 */
class BfwSingleProduct
{

    /**
     * Common method for standard and shortcode output
     * Общий метод для стандартного вывода и вывода шорткодом
     *
     * @param  float  $prize  Цена товара
     * @param  float  $percent  Процент кешбэка
     * @param  int  $id  Идентификатор продукта
     * @param  string  $price_width_bonuses  Строка для вывода цен с бонусами
     * @param  mixed  $upto  До
     * @param  object  $product  Объект продукта
     *
     * @return string Возвращает строку с информацией о бонусах
     * @version 6.4.0
     */
    public static function bfwPointsInSinglePage(
        float $prize,
        float $percent,
        int $id,
        string $price_width_bonuses,
        $upto,
        object $product
    ): string {
        $val = get_option('bonus_option_name');
        // Рассчитываем количество бонусных баллов
        $ball = $prize / 100 * $percent;
        $ball = apply_filters('bfw_cashback_in_product', $ball);
        $ball = BfwPoints::roundPoints($ball); //Округляем, если нужно

        // Если нет бонусов, возвращаем исходную строку
        if (empty($ball)) {
            return $price_width_bonuses;
        }


        if ( ! empty($val['cashback-on-sale-products'])) {
            //Если товар со скидкой, то за него кешбэк не получим
            $productc = wc_get_product($id);
            if ($productc) {
                if ($productc->is_on_sale()) {
                    return $price_width_bonuses;
                }
            }
        }


        $tovars = [];
        // Проверяем, есть ли исключенные товары для кешбэка
        if (BfwRoles::isPro() && isset($val['exclude-tovar-cashback'])) {
            $exclude_tovar = $val['exclude-tovar-cashback'];
            $tovars = apply_filters(
                'bfw-excluded-products-filter',
                explode(",", $exclude_tovar),
                $exclude_tovar
            );
        }

        $categoriexs = $val['exclude-category-cashback'] ?? 'not';
        $hmb_title = $val['how-mach-bonus-title'] ??
            __('Cashback:', 'bonus-for-woo');

        // Проверяем, является ли продукт исключенным
        $isExcluded = in_array($id, $tovars, true) || has_term($categoriexs,'product_cat',$id);

        if ( ! empty($val['addkeshback-exclude']) || ! $isExcluded) {
            $price_width_bonuses .= '<div class="how_mach_bonus"><span class="how_mach_bonus_title">'
                .$hmb_title.'</span> '.sprintf(
                    __('%1$s %2$s %3$s', 'bonus-for-woo'),
                    $upto,
                    $ball,
                    BfwPoints::howLabel($upto, $ball)
                ).'</div>';
        }

        $userid = get_current_user_id();

        // Проверяем, нужно ли добавлять реферальные ссылки
        if (BfwRoles::isPro() && isset($val['ref-links-on-single-page'])
            && BfwRoles::isInvalve($userid)
            && is_user_logged_in()
        ) {
            if ((int)$val['ref-links-on-single-page'] === 1 && is_product()) {
                $get_referral = get_user_meta(
                    $userid,
                    'bfw_points_referral',
                    true
                );
                $url = esc_url(
                    get_permalink(get_the_ID()).'?bfwkey='.$get_referral
                );
                $description = get_bloginfo('description');
                $title = $product->get_title();
                $refer = BfwReferral::bfwSocialLinks(
                    $url,
                    $title,
                    $description
                );
                $price_width_bonuses .= $refer;
            }
        }

        return $price_width_bonuses;
    }


    /**
     * We get information about the product type
     * Получаем информацию о типе продукта
     *
     * @param $product
     *
     * @return array
     * @version 6.4.0
     */
    public static function typeProduct($product): array
    {
        $upto = '';
        $val = get_option('bonus_option_name');
        $type = $product->get_type();
        $price = 0;

        if ($type === 'simple') {
            $price = $product->get_sale_price() ?: $product->get_price();
        } elseif ($type === 'variable') {
            $maxPrice = $product->get_variation_sale_price('max', true);
            $minPrice = $product->get_variation_sale_price('min', true);

            $price = $maxPrice;

            if ($maxPrice !== $minPrice && empty($val['bonus-in-price-upto'])) {
                $upto = __('up to', 'bonus-for-woo');
            }
        }

        if (is_user_logged_in()) {
            $userid = get_current_user_id();//  id пользователя
            $getRole = BfwRoles::getRole($userid);
            $percent = $getRole['percent'];
            if ($percent === 0 && BfwRoles::isInvalve($userid)) {
                $percent = BfwRoles::maxPercent();
                $upto = __('up to', 'bonus-for-woo');
            }
        } else {
            //Если не зарегистрирован, то максимальны кешбэк до
            $percent = BfwRoles::maxPercent();
            $upto = empty($val['bonus-in-price-upto']) ? __(
                'up to',
                'bonus-for-woo'
            ) : '';
        }

        return [
            'percent' => $percent,
            'price'   => $price,
            'upto'    => $upto
        ];
    }

    /**
     * Standard method for displaying the price of a product with bonuses
     * Стандартный метод вывода цены товара с бонусами
     *
     * @param  string  $price  - цена товара
     * @param  object  $_product  - объект продукта
     *
     * @return string - строка с ценой и бонусами
     * @version 6.4.0
     */
    public static function ballsAfterProductPriceAll(
        string $price,
        object $_product
    ): string {
        global $post;
        global $product;

        // Получаем настройки бонусов
        $val = get_option('bonus_option_name');
        $price_width_bonuses = ''; // Инициализация переменной для бонусов

        // Получаем ID товара
        $id = $post->ID;
        if (empty($id)) {
            return $price; // Если ID товара отсутствует, возвращаем исходную цену
        }
        // Получаем информацию о типе продукта
        $productInfo = self::typeProduct($_product);
        $prize = (float)$productInfo['price'];
        $upto = $productInfo['upto'];
        $percent = (float)$productInfo['percent'];

        // Возвращаем результат функции с расчетом бонусов
        $price_width_bonuses = self::bfwPointsInSinglePage(
            $prize,
            $percent,
            $id,
            $price_width_bonuses,
            $upto,
            $_product
        );

        if ( ! empty($val['bonus-in-price-loop']) && ! is_product()) {
            // Отображать на других страницах, всех кроме страницы товара
            $price .= $price_width_bonuses;
        }

        if ( ! empty($val['bonus-in-price']) && is_product()) {
            $price .= $price_width_bonuses;
        }

        return $price;
    }


    /**
     * Method: output by shortcode
     * Метод: вывод шорткодом
     *
     * @param $product
     *
     * @return string|void
     * @version 6.4.0
     */
    public static function ballsAfterProductPriceShortcode($product)
    {
        // Проверяем, что мы не находимся в админке и находимся на странице продукта
        if ( ! current_user_can('manage_options') && is_product()) {
            global $post;
            global $product;

            $price_width_bonuses = ''; // Инициализируем переменную для бонусов
            $id = $product->get_id(); // Получаем ID товара

            // Получаем информацию о товаре
            $product_info = self::typeProduct($product);
            $price = $product_info['price']; // Цена товара
            $upto = $product_info['upto'];
            $percent = $product_info['percent']; // Процент

            // Возвращаем результат функции с расчетом бонусов
            return self::bfwPointsInSinglePage(
                $price,
                $percent,
                $id,
                $price_width_bonuses,
                $upto,
                $product
            );
        }

        // Если находимся в админке или не на странице продукта, возвращаем сообщение об ошибке
        if (current_user_can('manage_options') || ! is_product()) {
            return __(
                'Use shortcode [bfw_cashback_in_product] only on product page!',
                'bonus-for-woo'
            );
        }
    }


}
