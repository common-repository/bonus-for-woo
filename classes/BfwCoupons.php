<?php
/**
 * Class BfwCoupons
 * Класс купонов
 *
 * @version 6.4.0
 * @since 4.1.0
 */

class BfwCoupons
{

    /**
     * Adding a coupon
     * Добавление купона
     *
     * @param  string  $code  Код купона
     * @param  float  $sum  сумма
     * @param  string  $comment_admin  комментарий админа
     * @param  string  $status
     *
     * @return void
     * @version 6.4.0
     */
    public static function addCoupon(
        string $code,
        float $sum,
        string $comment_admin,
        string $status
    ): void {
        if ($code !== '') {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix.'bfw_coupons_computy', // указываем таблицу
                array(
                    'code'          => $code,
                    'created'       => gmdate('Y-m-d H:i:s'),
                    'sum'           => $sum,
                    'comment_admin' => $comment_admin,
                    'status'        => $status,
                ),
                array(
                    '%s', // %s - значит строка
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                )
            );
        }
    }


    /**
     * Show all coupons
     * Показ всех купонов
     *
     * @return void
     * @version 6.4.0
     */
    public static function getListCoupons(): void
    {
        global $wpdb;
        $table_bfw = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}bfw_coupons_computy ORDER BY id DESC"
        );

        if ($table_bfw) {
            ob_start();
            ?>
            <table class="table-bfw table-bfw-history-points"
                   id='table-coupons'>
                <thead>
                <tr>
                    <th>№</th>
                    <th><?php
                        echo __('Coupon code', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Sum', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Create date', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Comment admin', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Client', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Date of use', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Status', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Action', 'bonus-for-woo'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                foreach ($table_bfw as $bfw) {
                    $bgtr = ($bfw->status == 'active')
                        ? 'background:#fff;'
                        : (($bfw->status == 'noactive') ? 'background:#ff9a9a;'
                            : 'background:#89f784;');
                    ?>
                    <tr style="<?php
                    echo esc_attr($bgtr); ?>">
                        <td><?php
                            echo $i++; ?></td>
                        <td><b><?php
                                echo esc_html($bfw->code); ?></b></td>
                        <td><b><?php
                                echo BfwPoints::roundPoints(
                                    $bfw->sum
                                ); ?></b></td>
                        <td><?php
                            echo date_format(
                                date_create($bfw->created),
                                'd.m.Y H:i'
                            ); ?></td>
                        <td><?php
                            echo esc_html($bfw->comment_admin); ?></td>
                        <?php
                        if ( ! empty($bfw->user)) {
                            $user = get_userdata($bfw->user);
                            $nameuser = ! empty($user->first_name)
                                ? $user->first_name.' '.$user->last_name
                                : $user->user_login;
                            ?>
                            <td><a href="/wp-admin/user-edit.php?user_id=<?php
                                echo $bfw->user; ?>" target="_blank"><?php
                                    echo $nameuser; ?></a></td>
                        <?php
                        } else { ?>
                            <td>-</td>
                        <?php
                        } ?>
                        <td><?php
                            echo ($bfw->date_use != '0000-00-00 00:00:00')
                                ? $bfw->date_use : '-'; ?></td>
                        <?php

                        switch ($bfw->status) {
                            case 'active':
                                $statustext = __('Active', 'bonus-for-woo');
                                break;
                            case 'noactive':
                                $statustext = __('Not active', 'bonus-for-woo');
                                break;
                            case 'used':
                                $statustext = __('Used', 'bonus-for-woo');
                                break;
                            default:
                                $statustext = '';
                                break;
                        }
                        ?>
                        <td><?php
                            echo esc_html($statustext); ?></td>
                        <td style="display: flex; justify-content: space-between;">
                            <?php
                            if ($bfw->status === 'active') { ?>
                                <form method="post" action=""
                                      class="list_role_computy">
                                    <input type="hidden" name="status_coupon"
                                           value="active">
                                    <input type="hidden"
                                           name="bfw_edit_status_coupon"
                                           value="<?php
                                           echo esc_attr($bfw->id); ?>">
                                    <input type="submit" value="<?php
                                    echo __('Deactivate', 'bonus-for-woo'); ?>"
                                           class="button_activated_coupon"
                                           title="<?php
                                           echo __(
                                               'Deactivate',
                                               'bonus-for-woo'
                                           ); ?>">
                                </form>
                            <?php
                            } elseif ($bfw->status === 'noactive') { ?>
                                <form method="post" action=""
                                      class="list_role_computy">
                                    <input type="hidden" name="status_coupon"
                                           value="noactive">
                                    <input type="hidden"
                                           name="bfw_edit_status_coupon"
                                           value="<?php
                                           echo esc_attr($bfw->id); ?>">
                                    <input type="submit" value="<?php
                                    echo __('Activate', 'bonus-for-woo'); ?>"
                                           class="button_activated_coupon"
                                           title="<?php
                                           echo __(
                                               'Activate',
                                               'bonus-for-woo'
                                           ); ?>">
                                </form>
                            <?php
                            } ?>
                            <form method="post" action=""
                                  class="list_role_computy">
                                <input type="hidden" name="bfw_delete_coupon"
                                       value="<?php
                                       echo esc_attr($bfw->id); ?>">
                                <input type="submit" value="+"
                                       class="delete_role-bfw" title="<?php
                                echo __('Delete', 'bonus-for-woo'); ?>"
                                       onclick="return window.confirm('<?php
                                       echo __(
                                           'Are you sure you want to delete this coupon?',
                                           'bonus-for-woo'
                                       ); ?>');">
                            </form>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
            echo ob_get_clean();
        }
    }


    /**
     * Removes a coupon by ID
     * Удаляет купон по идентификатору
     *
     * @param  int  $id  Идентификатор купона
     *
     * @return void
     * @version 6.4.0
     */
    public static function deleteCoupon(int $id): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'bfw_coupons_computy';
        $wpdb->delete($table_name, array('id' => $id), array('%d'));
    }


    /**
     * Change coupon status
     * Изменение статуса купона
     *
     * @param  int  $id
     * @param  string  $status
     *
     * @return void
     * @version 6.4.0
     */

    public static function editStatusCoupon(int $id, string $status): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'bfw_coupons_computy';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE  {$table_name} SET `status`=%s WHERE  `id` = %d ",
                $status,
                $id
            )
        );
    }


    /**
     * Display of one coupon
     * Вывод одного купона
     *
     * @param  string  $code
     *
     * @return mixed
     * @version 6.4.0
     */
    public static function getCoupon(string $code)
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'bfw_coupons_computy';
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE code=%s", $code)
        );
    }


    /**
     * Coupon application by the customer
     * Применение купона клиентом
     *
     * @param  int  $userid
     * @param  string  $code_coupon
     *
     * @return string
     * @version 6.4.0
     */
    public static function enterCoupon(int $userid, string $code_coupon): string
    {
        $coupon = self::getCoupon($code_coupon);

        if (isset($coupon->code) && $coupon->status === 'active') {
            $daily_coupon = get_user_meta($userid, 'daily_coupon', true);
            $count_limit_day = 1;

            if ( ! empty($daily_coupon)) {
                $val = get_option('bonus_option_name');
                $qca = $val['quantity-coupon-applied'] ?? 1;

                $count_limit_day = $daily_coupon[1] + 1;

                if ($daily_coupon[0] === gmdate('d.m.y')
                    && $daily_coupon[1] >= $qca
                ) {
                    return 'limit';
                }
            }

            global $wpdb;
            $table_name = $wpdb->prefix.'bfw_coupons_computy';
            $wpdb->update(
                $table_name,
                array(
                    'status'   => 'used',
                    'user'     => $userid,
                    'date_use' => gmdate('Y-m-d H:i:s')
                ),
                array('id' => $coupon->id)
            );

            $old_points = BfwPoints::getPoints($userid);
            $coupon_sum = $coupon->sum;
            $new_ball = $old_points + $coupon_sum;
            BfwPoints::updatePoints($userid, $new_ball);

            update_user_meta(
                $userid,
                'daily_coupon',
                array(gmdate('d.m.y'), $count_limit_day)
            );

            $pricina = __('Coupon usage', 'bonus-for-woo');
            BfwHistory::add_history(
                $userid,
                '+',
                $coupon_sum,
                '0',
                $pricina
            );

            return 'good';
        }

        return 'not_coupon';
    }

    /**
     * Action when deleting a coupon of points(woo blocks)
     * Действие при удалении купона баллов(woo blocks)
     *
     * @param $coupon_code string
     *
     * @return void
     * @version 6.4.0
     */
    public static function trueRedirectOnCouponRemoval(string $coupon_code
    ): void {
        $val = get_option('bonus_option_name');
        $cart_discount = mb_strtolower($val['bonus-points-on-cart']);
        if (strtolower($coupon_code) === strtolower($cart_discount)) {
            BfwPoints::updateFastPoints(get_current_user_id(), 0);
            //todo тут надо перезагружать страницу
        }
    }


    /**
     * Export bonus csv file
     * Экспорт csv файла бонусов
     *
     * @return void
     * @version 6.4.0
     */

    public static function bfwExportCoupons(): void
    {
        $response = json_decode(stripslashes($_POST['response']), true);
        $url_export_file
            = $response['data']['url']; // ссылка на загруженный файл экспорта

        $limit = 100; // сколько строк обрабатывать в каждом пакете
        $fileHandle = fopen($url_export_file, "rb");

        if ($fileHandle === false) {
            die(
                __('Error opening', 'bonus-for-woo').' '.htmlspecialchars(
                    $url_export_file
                )
            );
        }

        global $wpdb;
        $table = $wpdb->prefix.'bfw_coupons_computy';

        while ( ! feof($fileHandle)) {
            $i = 0;
            while ($i < $limit && ($currRow = fgetcsv($fileHandle)) !== false) {
                $i++;

                $coupon = (string)$currRow[0];
                $sum = (float)$currRow[1];
                $comment_admin = $currRow[2];
                $status = in_array($currRow[3], ['active', 'noactive', 'used'])
                    ? $currRow[3] : 'noactive';

                if ($comment_admin !== 'comment_admin'
                    && ! $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT code FROM {$table} WHERE code = %s",
                            $coupon
                        )
                    )
                ) {
                    self::addCoupon($coupon, $sum, $comment_admin, $status);
                }
            }
        }

        fclose($fileHandle);
        echo 'good';
        exit();
    }


}
