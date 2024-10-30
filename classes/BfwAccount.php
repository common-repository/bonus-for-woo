<?php

defined('ABSPATH') or die;
/**
 * Client Account Class
 * Класс аккаунта клиентов
 *
 * @version 6.4.0
 */
defined('ABSPATH') or die;

class BfwAccount
{

    /**
     * Account Bonus Page Title
     * Заголовок страницы бонусов в аккаунте
     *
     * @return void
     * @version 6.4.0
     */
    public static function accountTitle(): void
    {
        $val = get_option('bonus_option_name');
        $titleBonusPage = isset($val['title-on-account']) ? esc_html(
            $val['title-on-account']
        ) : __('Bonus page', 'bonus-for-woo');
        echo esc_html($titleBonusPage);
    }

    /**
     * Displaying client status
     * Вывод статуса клиента
     *
     * @return mixed
     * @version 6.4.0
     */
    public static function getStatus()
    {
        $roles = (new BfwRoles())::getRole(get_current_user_id());
        return $roles['name'];
    }

    /**
     * Display of cashback percentage
     * Вывод процента кешбэка
     *
     * @return string
     * @version 6.4.0
     */
    public static function getCashback(): string
    {
        $roles = (new BfwRoles())::getRole(get_current_user_id());
        return $roles['percent'].'%';
    }

    /**
     * Display basic information: status, cashback percentage, number of bonus points
     * Вывод основной информации: статус, процент кешбэка, количество бонусных баллов
     *
     * @return void
     * @throws Exception
     * @version 6.3.4
     */
    public static function accountBasicInfo(): void
    {
        $userid = get_current_user_id();
        $val = get_option('bonus_option_name');

        $title_my_status_on_account = $val['title-my-status-on-account'] ??
            __('My status', 'bonus-for-woo');
        $title_my_percent = $val['my-procent-on-account'] ??
            __('My cashback percentage', 'bonus-for-woo');
        $title_my_bonus_points = $val['bonus-points-on-cart'] ??
            __('Bonus points', 'bonus-for-woo');
        $return = '';
        $bfwRoles = new BfwRoles();
        $bfwPoints = new BfwPoints();
        $bfwPoints::addEveryDays($userid);
        $bfwRoles::updateRole($userid);
        $computy_point = $bfwPoints::getPoints($userid);

        $ostalos = 0;
        if (empty($val['burn_point_in_account']) && isset($val['day-inactive'])
            && $val['day-inactive'] > 0
            && $computy_point > 0
        ) {
            $customer = new WC_Customer($userid);
            $last_order = $customer->get_last_order();

            if ($last_order) {
                $order_date_created = $last_order->get_data(
                )['date_created']->format('Y-m-d h:i:s');
                $ostalos = $val['day-inactive'] - (new DateTime('now'))->diff(
                        new DateTime($order_date_created)
                    )->days;
            } else {
                $registered = strtotime(get_userdata($userid)->user_registered);
                $ostalos = $val['day-inactive'] - floor(
                        (strtotime('today') - $registered) / 86400
                    );
            }

            $ostalos = max(0, $ostalos);
        }

        if ( ! $bfwRoles::isInvalve($userid)) {
            echo '<p class="bfw_not_participant">'.__(
                    'You do not participate in the bonus system.',
                    'bonus-for-woo'
                ).'<p>';
        }
        $return .= '<div class="bfw-card">';
        if ( ! empty($val['rulles_url'])) {
            $return .= '<a target="_blank" href="'.esc_html($val['rulles_url'])
                .'" class="card_link_rulles" title="'.esc_html(
                    $val['rulles_value']
                ).'">'.esc_html($val['rulles_value']).'</a>';
        }

        $return .= '<div class="bonus_computy_account bfw-account_status_name"><span class="title_bca">'
            .esc_html($title_my_status_on_account)
            .':</span> <span class="value_bca"> '.$bfwRoles::getRole(
                $userid
            )['name'].'</span></div>
    <div class="bonus_computy_account bfw-account_percent"><span class="title_bca">'
            .esc_html($title_my_percent).':</span> <span class="value_bca"> '
            .$bfwRoles::getRole($userid)['percent'].'%</span></div>
    <div class="bonus_computy_account bfw-account_count_points"><span class="value_bca">'
            .$bfwPoints::roundPoints($computy_point).' '
            .$bfwPoints::pointsLabel($computy_point).'</span>';

        if ($ostalos > 0) {
            $days = __('days', 'bonus-for-woo');
            if (get_bloginfo('language') === 'ru-RU') {
                $days = BfwFunctions::declination(
                    $ostalos,
                    'день',
                    'дня',
                    'дней'
                );
            }

            $return .= '<div class="bfw-account_expire_points bfw-help-tip danger" data-tip="'
                .esc_html($title_my_bonus_points).' '.__(
                    'will expire after',
                    'bonus-for-woo'
                ).' '.$ostalos.' '.$days.'">'.$ostalos.' '.$days.'</div>';
        }

        $return .= '</div></div>';
        echo $return;
    }

    /**
     * Display number of points
     * Вывод количества баллов
     *
     * @return float
     * @version 6.4.0
     */
    public static function getPoints(): float
    {
        return BfwPoints::getPoints(get_current_user_id());
    }

    /**
     * Display coupons (PRO version only)
     * Ввод купонов(только для PRO-версии)
     *
     * @return void
     */
    public static function accountCoupon(): void
    {
        if (BfwRoles::isInvalve(get_current_user_id())) {
            $val = get_option('bonus_option_name');
            if ( ! empty($val['coupon-system'])) {
                echo '<div class="bonus_computy_account add_coupon">
<div class="title computy_skidka_link">'
                    .sprintf(
                        __(
                            'Enter coupon code to take %s.',
                            'bonus-for-woo'
                        ),
                        BfwPoints::pointsLabel(25)
                    ).'</div>
<div class="computy_skidka_container coupon_form" style="display: none;">
<form class="take_coupon_form" action="'.admin_url("admin-post.php").'" method="post">
 <input type="hidden" name="action" value="bfw_take_coupon_action" />
 <input type="hidden" name="redirect" value="'
                    .get_permalink(get_option('woocommerce_myaccount_page_id')).'/bonuses">
<input type="text" name="code_coupon" placeholder="'.__(
                        'Coupon code',
                        'bonus-for-woo'
                    ).'" required>
<input type="submit" class="code_coupon_submit" value="'.__(
                        'To take',
                        'bonus-for-woo'
                    ).'"><div class="message_coupon"></div>
</form>
</div>
</div>';
            }
        }
    }


    /**
     * Progress bar
     * Прогресс бар
     *
     * @return void
     * @version 6.3.4
     */
    public static function accountProgress(): void
    {
        $progress = '';
        $userid = get_current_user_id();
        $bfwRoles = new BfwRoles();
        $bfwPoints = new BfwPoints();

        $nextrole = $bfwRoles::getNextRole($userid);
        $procentzarabotannogo = $nextrole['percent-zarabotannogo'] ?? 0;

        if ($bfwRoles::isInvalve($userid)
            && in_array(
                $nextrole['status'],
                ['next', 'max']
            )
        ) {
            $val = get_option('bonus_option_name');
            $total = $bfwPoints::getSumUserOrders($userid);
            $tab_bfw = $bfwRoles::getRoles();
            $you_role = $bfwRoles::getRole($userid)['name'];

            $progress .= '<ol class="bfw-progress-bar">';

            foreach ($tab_bfw as $bfw) {
                $isComplete = $bfw->summa_start < $total ? ' is-complete' : '';
                $isActive = $you_role === $bfw->name ? ' is-active' : '';
                $progress .= '<li class="'.$isComplete.$isActive.'"><span>'
                    .$bfw->name.'</span></li>';
            }
            $progress .= '</ol>';

            $progress .= '<div class="bfw-progressbar-block">
            <style>
                #bfw-progressbar > div {width: '.($nextrole['status'] !== 'max'
                    ? $procentzarabotannogo : '100').'%; }
                #bfw-progressbar > div span { '.($procentzarabotannogo < 10
                    ? 'left:6px;' : 'right: 6px;').'}
            </style>
            <div class="bfw-progressbar-title">
                <div class="bfw-progressbar-title-one">'.$bfwRoles::getRole(
                    $userid
                )['name'].'</div>
                <div class="bfw-progressbar-title-two">'.($nextrole['status']
                !== 'max' ? $nextrole['name'] : '').'</div>
            </div>
            <div id="bfw-progressbar">
                <div><span>'.$bfwPoints::roundPoints($total)
                .get_woocommerce_currency_symbol().'</span></div>
            </div>
        </div>';

            $message_body = '';
            if ($nextrole['status'] === 'next') {
                $remaining_amount = $val['remaining-amount'] ?? __(
                    'Up to [percent]% cashback and «[status]» status, you have [sum] left to spend.',
                    'bonus-for-woo'
                );
                $remaining_amount_array = [
                    '[percent]' => $nextrole['percent'],
                    '[status]'  => $nextrole['name'],
                    '[sum]'     => $nextrole['sum'].' '
                        .get_woocommerce_currency_symbol().'</b>'
                ];
                $message_body = (new BfwEmail())::template(
                    $remaining_amount,
                    $remaining_amount_array
                );
            } elseif ($nextrole['status'] === 'max') {
                $message_body = __(
                    'You have the maximum cashback!',
                    'bonus-for-woo'
                );
            } elseif ($nextrole['status'] === 'no') {
                $message_body = __(
                    'At the moment, the bonus system is not available.',
                    'bonus-for-woo'
                );
            }

            $progress .= '<small class="remaining-amount">'.$message_body
                .'</small>';
        }

        echo $progress;
    }


    /**
     * Referral system (only for PRO version)
     * Реферальная система (только для PRO-версии)
     *
     * @return string
     * @throws Exception
     * @version 6.3.4
     */
    public static function accountReferral(): string
    {
        $userid = get_current_user_id();
        $referral = '';
        $bfwRoles = new BfwRoles();
        $bfwReferral = new BfwReferral();
        $bfwPoints = new BfwPoints();

        if ($bfwRoles::isInvalve($userid)) {
            $val = get_option('bonus_option_name');

            if ( ! empty($val['referal-system'])) {
                $get_referral = get_user_meta(
                    $userid,
                    'bfw_points_referral',
                    true
                );
                $get_referral_invite = get_user_meta(
                    $userid,
                    'bfw_points_referral_invite',
                    true
                );

                if (empty($get_referral)) {
                    $referral_key = $bfwReferral::bfw_create_referal_code();
                    update_user_meta(
                        $userid,
                        'bfw_points_referral',
                        $referral_key
                    );
                    $get_referral = $referral_key;
                }

                if (empty($get_referral_invite)) {
                    update_user_meta($userid, 'bfw_points_referral_invite', 0);
                    $get_referral_invite = 0;
                }

                $argsa['meta_query'] = [
                    [
                        'key'     => 'bfw_points_referral_invite',
                        'value'   => $userid,
                        'compare' => '==',
                    ],
                ];
                $refere_data = get_users($argsa);

                $refere_data_two_two = 0;
                if ( ! empty($val['level-two-referral'])) {
                    foreach ($refere_data as $refere_data_two) {
                        $argsatwo['meta_query'] = [
                            [
                                'key'     => 'bfw_points_referral_invite',
                                'value'   => $refere_data_two->ID,
                                'compare' => '==',
                            ],
                        ];
                        $refere_data_two_two += count(get_users($argsatwo));
                    }
                }

                $sumordersforreferral = $val['sum-orders-for-referral'] ?? 0.0;
                $total = $bfwPoints::getSumUserOrders($userid);

                if ($total >= $sumordersforreferral) {
                    if (empty($get_referral)) {
                        $referral .= '<div class="bonus_computy_account bfw-account_referral"><span class="title_bca">'
                            .__(
                                'Referral link generated. Please refresh the page.',
                                'bonus-for-woo'
                            ).'</span></div>';
                    } else {
                        $url = esc_url(site_url().'?bfwkey='.$get_referral);
                        $title = get_bloginfo('name');
                        $description = get_bloginfo('description');

                        $referral .= '<div class="bonus_computy_account bfw-account_referral"><span class="title_bca">'
                            .__('My referral link', 'bonus-for-woo')
                            .':</span> <code id="code_referal" class="value_bca">'
                            .$url.'</code> <span title="'.__(
                                'Copy link',
                                'bonus-for-woo'
                            )
                            .'" id="copy_referal"></span><span id="copy_good"></span> </div>';
                        $referral .= $bfwReferral::bfwSocialLinks(
                            $url,
                            $title,
                            $description
                        );
                        $referral .= '<div class="bonus_computy_account"><span class="title_bca">'
                            .__('You invited', 'bonus-for-woo')
                            .':</span> <span class="value_bca">'.count(
                                $refere_data
                            ).' '.__('people', 'bonus-for-woo').'</span></div>';

                        if ( ! empty($val['level-two-referral'])) {
                            $referral .= '<div class="bonus_computy_account"><span class="title_bca">'
                                .__('Your friends invited', 'bonus-for-woo')
                                .':</span> <span class="value_bca">'
                                .$refere_data_two_two.' '.__(
                                    'people',
                                    'bonus-for-woo'
                                ).'</span></div>';
                        }
                    }
                } else {
                    $ostalos = $sumordersforreferral - $total;
                    $referral .= '<small class="remaining-amount">'.__(
                            'To start using the referral system, you need to buy goods for ',
                            'bonus-for-woo'
                        ).' '.$ostalos.' '.get_woocommerce_currency_symbol()
                        .'</small>';
                }
            }
        }
        return $referral;
    }

    /**
     * Referral link output
     * Вывод реферальной ссылки
     *
     * @return string|null
     * @version 6.3.4
     */
    public static function getReferralLink(): ?string
    {
        $userid = get_current_user_id();
        $get_referral = get_user_meta($userid, 'bfw_points_referral', true);
        if ( ! empty($get_referral)) {
            return '<div class="bonus_computy_account bfw-account_referral"><span class="title_bca">'
                .__(
                    'My referral link',
                    'bonus-for-woo'
                )
                .':</span> <code id="code_referal" class="value_bca">'
                .esc_url(site_url().'?bfwkey='.$get_referral)
                .'</code> <span  title="'.__('Copy link', 'bonus-for-woo')
                .'"  id="copy_referal"></span><span id="copy_good"></span> </div>';
        }

        return null;
    }

    /**
     * Create a link in the menu woocommerce account bonus system
     * Создаем ссылку в меню woocommerce account бонусная система
     *
     * @param $menu_links
     *
     * @return array
     * @version 6.3.4
     */
    public static function bonusesLink($menu_links): array
    {
        $val = get_option('bonus_option_name');
        $poryadok = $val['poryadok-in-account'] ?? 4;
        $title_page = $val['title-on-account'] ??
            __('Bonus page', 'bonus-for-woo');
        $menu_links = array_slice(
                $menu_links,
                0,
                $poryadok,
                true
            ) + array('bonuses' => $title_page)
            + array_slice(
                $menu_links,
                $poryadok,
                null,
                true
            );
        $menu_links['bonuses'] = $title_page;

        return $menu_links;
    }

    /**
     * Points accrual history
     * История начисления баллов
     *
     * @return void
     * @version 6.3.4
     */
    public static function accountHistory(): void
    {
        $val = get_option('bonus_option_name');
        if (empty($val['hystory-hide'])) {
            require_once BONUS_COMPUTY_PLUGIN_DIR.'/pages/datatable.php';
            (new BfwHistory())::getHistory(get_current_user_id());
        }
    }

    /**
     * Output of a link to the terms of the bonus system
     * Вывод ссылки на условия бонусной системы
     *
     * @return void
     * @version 6.3.4
     */
    public static function accountRules(): void
    {
        $val = get_option('bonus_option_name');
        if ( ! empty($val['rulles_url'])) {
            echo '<a class="bfw_link_rulles" href="'.esc_url($val['rulles_url'])
                .'">'.esc_html($val['rulles_value']).'</a>';
        }
    }


    /**
     * Adding points when registering a user
     * Добавление баллов при регистрации пользователя
     *
     * @param $user_id int
     *
     * @return void
     * @version 6.4.0
     */
    public static function actionPointsForRegistrationBfw(int $user_id): void
    {
        if ( ! empty($user_id)) {
            // Не начисляем ежедневные баллы в день регистрации
            update_user_meta($user_id, 'points_every_day', gmdate('d'));

            $bfwRoles = new BfwRoles();
            $bfwRoles::updateRole($user_id, false);

            $val = get_option('bonus_option_name');
            $pointsForReg = $val['points-for-registration'] ?? 0;
            $allowPoints = 1;

            // Только для рефералов
            if ( ! empty($val['register-points-only-referal'])) {
                $cookieVal = isset($_COOKIE['bfw_ref_cookie_set'])
                    ? sanitize_text_field(
                        wp_unslash($_COOKIE['bfw_ref_cookie_set'])
                    )
                    : '';
                if (empty($cookieVal)) {
                    $allowPoints = 0;
                }
            }

            // Начисление баллов
            if ($allowPoints === 1 && $pointsForReg > 0) {
                $bfwPoints = new BfwPoints();
                $bfwPoints::updatePoints($user_id, $pointsForReg);

                $reason = sprintf(
                    __('%s for registration', 'bonus-for-woo'),
                    $bfwPoints::pointsLabel(5)
                );

                // Записываем в историю
                $bfwHistory = new BfwHistory();
                $bfwHistory::add_history(
                    $user_id,
                    '+',
                    $pointsForReg,
                    '0',
                    $reason
                );

                // Отправляем email клиенту
                $user = get_userdata($user_id);

                // Шаблон письма
                $textEmail = $val['email-when-register-text'] ?? '';
                $titleEmail = $val['email-when-register-title'] ??
                    __('Bonus points have been added to you!', 'bonus-for-woo');
                $referralLink = get_user_meta(
                    $user_id,
                    'bfw_points_referral',
                    true
                );
                $textEmailArray = array(
                    '[referral-link]' => esc_url(
                        site_url().'?bfwkey='
                        .$referralLink
                    ),
                    '[user]'          => $user->display_name,
                    '[points]'        => $pointsForReg,
                    '[total]'         => $pointsForReg,
                    '[cause]'         => $reason
                );
                $messageEmail = (new BfwEmail())::template(
                    $textEmail,
                    $textEmailArray
                );

                // Отправляем email клиенту
                if ( ! empty($val['email-when-register'])) {
                    (new BfwEmail())->getMail(
                        $user_id,
                        '',
                        $titleEmail,
                        $messageEmail
                    );
                }
            }
        }
    }


    /**
     * Birthday field
     * Поле дня рождения
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfwDobAccountDetails(): void
    {
        $val = get_option('bonus_option_name');
        if (isset($val['birthday']) && $val['birthday'] > 0) {
            $user = wp_get_current_user();
            $disabled = '';
            if ( ! empty(esc_attr($user->dob))) {
                $disabled = 'disabled';
            }

            ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="dob"><?php
                    esc_html_e('Date of birth', 'bonus-for-woo'); ?></label>
                <input type="date"
                       class="woocommerce-Input woocommerce-Input--text input-text"
                       name="dob" id="dob" value="<?php
                echo esc_attr($user->dob); ?>" <?php
                echo esc_attr($disabled); ?>/>
            </p>
            <?php
        }
    }

    /**
     * Saving the birthday field
     * Сохранение поля дня рождения
     *
     * @param $user_id int
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfwDobSaveAccountDetails(int $user_id): void
    {
        if (isset($_POST['dob'])) {
            update_user_meta(
                $user_id,
                'dob',
                sanitize_text_field($_POST['dob'])
            );
        }
    }

    /**
     * Display text above the registration form
     * Вывод текста над формой регистрации
     *
     * @return void
     * @version 6.4.0
     */
    public static function formRegister(): void
    {
        $val = get_option('bonus_option_name');

        if ( ! empty($val['points-for-registration'])
            && empty($val['register-points-only-referal'])
        ) {
            echo '<div class="bfw-in-register-form">'
                .sprintf(
                    __('Register and get %1$d %2$s.', 'bonus-for-woo'),
                    $val['points-for-registration'],
                    (new BfwPoints())::pointsLabel(
                        $val['points-for-registration']
                    )
                ).'</div>';
        }
    }


    /**
     * Adding points when a user logs in
     * Добавление баллов при авторизации пользователя
     *
     * @param $user_login  !!!нельзя удалять параметр!!!
     * @param $user
     *
     * @return void
     * @version 6.4.0
     */
    public static function addBallWhenUserLogin($user_login, $user): void
    {
        //Начисление ежедневных баллов за первый вход
        (new BfwPoints())::addEveryDays($user->ID);
    }

    /**
     * Add the "bonuses" endpoint
     * Добавляем конечную точку bonuses
     *
     * @return void
     * @version 6.4.0
     */
    public static function bonusesAddEndpoint(): void
    {
        add_rewrite_endpoint('bonuses', EP_PAGES);
    }

    /**
     * Adding content to the bonus page
     * Добавляем контент на страницу бонусов
     *
     * @return void
     * @version 6.4.0
     */
    public static function accountContent(): void
    {
        //Если есть шаблон в теме, то используем его
        if (file_exists(
            get_stylesheet_directory()
            .'/bonus-for-woo/account.php'
        )
        ) {
            get_template_part('bonus-for-woo/account');
        } else {
            require_once BONUS_COMPUTY_PLUGIN_DIR.'/templates/account.php';
        }
    }

    /**
     * Account output via shortcode
     * Вывод аккаунта через шорткод
     *
     * @return string
     * @version 6.4.0
     */
    public static function accountContentShortcode(): string
    {
        ob_start();
        if (file_exists(
            get_stylesheet_directory()
            .'/bonus-for-woo/account.php'
        )
        ) {
            get_template_part('bonus-for-woo/account');
        } else {
            require_once BONUS_COMPUTY_PLUGIN_DIR.'/templates/account.php';
        }
        $string = ob_get_contents();

        ob_end_clean();
        return $string;
    }


    /**
     * Saving changes to the client profile
     * Сохранение изменений в профиле клиента
     *
     * @param $user_id int
     *
     * @return void
     * @version 6.4.0
     */
    public static function profileUserUpdate(int $user_id): void
    {
        $roles = new BfwRoles();
        $points = new BfwPoints();
        $history = new BfwHistory();
        $email = new BfwEmail();


        if ($roles::isPro()) {
            if ( ! empty($_POST['bfw_offline_order_price'])) {
                $points::addOfflineOrder(
                    sanitize_text_field($_POST['bfw_offline_order_price']),
                    $user_id
                );
                return;
            }

            if (isset($_POST['dob'])) {
                update_user_meta(
                    $user_id,
                    'dob',
                    sanitize_text_field($_POST['dob'])
                );
            }

            if (isset($_POST['bfw-referall-link'])) {
                $get_referral = get_user_meta(
                    $user_id,
                    'bfw_points_referral',
                    true
                );
                $bfw_referral_new
                    = sanitize_text_field($_POST['bfw-referall-link']);

                if ($get_referral !== $bfw_referral_new) {
                    $args['meta_query'] = [
                        [
                            'key'     => 'bfw_points_referral',
                            'value'   => trim($bfw_referral_new),
                            'compare' => '==',
                        ],
                    ];
                    $refere_data = get_users($args);

                    if (empty($refere_data)) {
                        update_user_meta(
                            $user_id,
                            'bfw_points_referral',
                            $bfw_referral_new
                        );
                    }
                }
            }
        }

        if (isset($_POST['computy_input_points'])) {
            $addball = (float)sanitize_text_field(
                $_POST['computy_input_points']
            );
            $prichina = sanitize_text_field($_POST['prichinaizmeneniya']) ??
                __('Not specified.', 'bonus-for-woo');
            $oldpoint = $points::getPoints($user_id);
            if ($addball !== $oldpoint) {
                $naskoko = abs($addball - $oldpoint);
                $action = $addball > $oldpoint ? '+' : '-';
                $history::add_history(
                    $user_id,
                    $action,
                    $naskoko,
                    '0',
                    $prichina
                );

                $val = get_option('bonus_option_name');
                $title_email_key = $addball > $oldpoint
                    ? 'email-change-admin-title'
                    : 'email-change-admin-title-spisanie';
                $title_email = $val[$title_email_key] ??
                    __('Bonus points update', 'bonus-for-woo');

                $user = get_userdata($user_id);
                $text_email_key = $addball > $oldpoint
                    ? 'email-change-admin-text'
                    : 'email-change-admin-text-spisanie';
                $text_email = $val[$text_email_key] ?? '';

                $get_referral = get_user_meta(
                    $user_id,
                    'bfw_points_referral',
                    true
                );
                $text_email_array = [
                    '[referral-link]' => esc_url(
                        site_url().'?bfwkey='.$get_referral
                    ),
                    '[user]'          => $user->display_name,
                    '[points]'        => $naskoko,
                    '[total]'         => $addball,
                    '[cause]'         => $prichina
                ];

                $message_email = $email::template(
                    $text_email,
                    $text_email_array
                );

                if ( ! empty($val['email-change-admin'])) {
                    $email->getMail($user_id, '', $title_email, $message_email);
                }

                $points::updatePoints($user_id, $addball);
            }
        }
    }


}
