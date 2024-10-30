<?php

defined('ABSPATH') or die;

/**
 * Class History
 * Класс истории начисления\списания бонусных баллов
 *
 * @version 6.2.3
 * @since 2.2.3
 */
class BfwHistory
{

    /**
     * Adding a record to history
     * Добавление записи в историю
     *
     * @param  int  $user_id
     * @param  string  $symbol  //+ -
     * @param  float  $points
     * @param  int  $order  номер заказа
     * @param  string  $cause  //причина
     * @param  string  $status  //добавляется id приглашенного реферала
     *
     * @return void
     * @version 6.2.3
     */
    public static function add_history(
        int $user_id,
        string $symbol,
        float $points,
        int $order,
        string $cause,
        string $status = ''
    ): void {
        if ($points != 0) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix.'bfw_history_computy', // указываем таблицу
                array(
                    'user'          => $user_id,
                    'date'          => current_time('Y-m-d H:i:s'),
                    'symbol'        => $symbol,
                    'points'        => $points,
                    'orderz'        => $order,
                    'comment_admin' => $cause,
                    'status'        => $status
                ),
                array(
                    '%d', // %d - значит число
                    '%s', // %s - значит строка
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s'
                )
            );
        }
    }


    /**
     * Showing the history of one client
     * Показ истории одного клиента
     *
     * @param  int  $user_id  Id пользователя
     *
     * @return void
     *
     * @version 6.2.3
     */
    public static function getHistory(int $user_id): void
    {
        $val = get_option('bonus_option_name');

        global $wpdb;
        $table_bfw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM ".$wpdb->prefix
                ."bfw_history_computy WHERE `user`=%d ORDER BY id DESC",
                $user_id
            )
        );
        if ($table_bfw) {
            $title_my_history = $val['title-on-history-account'] ??
                __('Points accrual', 'bonus-for-woo');
            ?>

            <h3><?php
                echo esc_html($title_my_history); ?></h3>

            <?php
            if (current_user_can('manage_options')) { ?>
                <a class="clear_history" href="javascript:AlertIt();"
                   onclick="return window.confirm(' ');"><?php
                    echo __('Clear the history', 'bonus-for-woo'); ?></a>
                <script>
                    function AlertIt() {
                        let answer = confirm("<?php echo __(
                            'Are you sure you want to clear this customer bonus points history?',
                            'bonus-for-woo'
                        );?>")
                        if (answer)
                            window.location = "/wp-admin/user-edit.php?user_id=<?php echo $user_id;?>&bfw_delete_all_post_history_points=<?php echo $user_id;?>";
                    }
                </script>
            <?php
            } ?>
            <table class="table-bfw table-bfw-history-points nowrap"
                   style="width:100%" id='table-history-points'>
                <thead>
                <tr>
                    <th>№</th>
                    <th><?php
                        echo __('Date', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo BfwPoints::pointsLabel(5); ?></th>
                    <th><?php
                        echo __('Event', 'bonus-for-woo'); ?></th>
                    <?php
                    if (current_user_can('manage_options')) { ?>
                        <th><?php
                            echo __('Action', 'bonus-for-woo'); ?></th>
                    <?php
                    } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                foreach ($table_bfw as $bfw) {
                    $getorderz = '';
                    if ($bfw->orderz != '0') {
                        if (current_user_can('manage_options')) {
                            $getorderz = '<a href="/wp-admin/post.php?post='
                                .$bfw->orderz.'&action=edit">'._x(
                                    'Order',
                                    'Order number',
                                    'bonus-for-woo'
                                ).' №'.$bfw->orderz.'</a> ';
                        } else {
                            $view_order_endpoint = get_option(
                                'woocommerce_myaccount_view_order_endpoint',
                                'view-order'
                            ); /*endpoint order*/
                            $getorderz = '<a href="'.get_permalink(
                                    get_option('woocommerce_myaccount_page_id')
                                ).$view_order_endpoint.'/'.$bfw->orderz.'">'._x(
                                    'Order',
                                    'Order number',
                                    'bonus-for-woo'
                                ).' №'.$bfw->orderz.'</a> ';
                        }
                    }

                    $color = $bfw->symbol === '+' ? '#23CE48'
                        : ($bfw->symbol === '-' ? '#FF001D' : '');


                    echo '<tr><td>'.$i++.'</td>
<td>'.date_format(date_create($bfw->date), 'd.m.Y H:i').'</td>
<td><span style="color:'.$color.' ">'.$bfw->symbol.BfwPoints::roundPoints($bfw->points).'</span></td>
<td>'.$getorderz.$bfw->comment_admin.'</td>';
                    if (current_user_can('manage_options')) {
                        echo '<td></form><form method="post" class="list_role_computy"><input type="hidden" name="bfw_delete_post_history_points" value="'
                            .$bfw->id.'" >
                  <input type="submit" value="+" class="delete_role-bfw" title="'
                            .__('Delete', 'bonus-for-woo')
                            .'" onclick="return window.confirm(\' '.__(
                                'Are you sure you want to remove this entry from your reward points history?',
                                'bonus-for-woo'
                            ).' \');"></form></td>';
                    }
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
            <?php
        }
    }


    /**
     * Show history of all clients
     * Показ истории всех клиентов
     *
     * @param  string  $date_start  С какой даты
     * @param  string  $date_finish  По какую дату
     *
     * @return void
     * @version 6.4.0
     */
    public static function getListHistory(
        $date_start = false,
        $date_finish = false
    ): void {
        $where = '';
        if ($date_start) {
            $limit = '';
            $endDate = $date_finish ?? gmdate('Y-m-d');
            $where = " WHERE date BETWEEN '".$date_start."' AND '".$endDate."'";
        } else {
            $limit = ' LIMIT 500';
        }
        global $wpdb;
        $table_bfw = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}bfw_history_computy {$where}  ORDER BY date DESC {$limit}"
        );
        if ($table_bfw) { ?>

            <table class="table-bfw table-bfw-history-points"
                   id='table-history-points'>
                <thead>
                <tr>
                    <th>№</th>
                    <th><?php
                        echo __('Date', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Client', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Status', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo BfwPoints::pointsLabel(5); ?></th>
                    <th><?php
                        echo __('Event', 'bonus-for-woo'); ?></th>
                    <th><?php
                        echo __('Action', 'bonus-for-woo'); ?></th>

                </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                foreach ($table_bfw as $bfw) {
                    $getorderz = '';
                    if ($bfw->orderz != '0') {
                        $getorderz = '<a href="/wp-admin/post.php?post='
                            .$bfw->orderz.'&action=edit">'.__(
                                'Order',
                                'bonus-for-woo'
                            ).' №'.$bfw->orderz.'</a> ';
                    }
                    $color = $bfw->symbol === '+' ? '#23CE48'
                        : ($bfw->symbol === '-' ? '#FF001D' : '');
                    echo '<tr><td>'.$i++.'</td>
<td>'.date_format(date_create($bfw->date), 'd.m.Y H:i').'</td>';

                    $user = get_userdata($bfw->user);
                    $role = BfwRoles::getRole($bfw->user);
                    if ( ! empty($user->first_name)) {
                        $nameuser = $user->first_name.' '.$user->last_name;
                    } else {
                        $nameuser = $user->user_login ?? 'login';
                    }
                    echo '<td><a href="/wp-admin/user-edit.php?user_id='
                        .$bfw->user.'" target="_blank">'.$nameuser
                        .'</a></td><td>'.$role['name'].'</td>
<td><span style="color:'.$color.' ">'.$bfw->symbol.BfwPoints::roundPoints($bfw->points).'</span></td>
<td>'.$getorderz.$bfw->comment_admin.'</td>';
                    if (current_user_can('manage_options')) {
                        echo '<td><form method="post" action="" class="list_role_computy"><input type="hidden" name="bfw_delete_post_history_points" value="'
                            .$bfw->id.'" >
   <input type="submit" value="+" class="delete_role-bfw" title="'.__(
                                'Delete',
                                'bonus-for-woo'
                            ).'" onclick="return window.confirm(\' '.__(
                                'Are you sure you want to remove this entry from your reward points history?',
                                'bonus-for-woo'
                            ).' \');">
                  </form> </td>';
                    }
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
            <?php
        } else {
            echo '<h3>'.__('No points accrual history found.', 'bonus-for-woo')
                .'</h3>';
        }
    }


    /**
     * Delete all history of a specific client
     * Удаление всей истории определенного клиента
     *
     * @param  int  $user_id
     *
     * @return void
     * @version 6.4.0
     */
    public static function clearAllHistoryUser(int $user_id): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'bfw_history_computy';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE `user` = %d",
                $user_id
            )
        );
    }


    /**
     * Deleting one entry in history
     * Удаление одной записи в истории
     *
     * @param  int  $id
     *
     * @return void
     * @version 2.5.1
     */
    public static function deleteHistoryId(int $id): void
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'bfw_history_computy';
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table_name} WHERE `id` = %d", $id)
        );
    }


    /**
     * Clearing history when deleting a user
     * Очищение истории при удалении пользователя
     *
     * @param  int  $user_id
     *
     * @return void
     * @version 6.4.0
     */
    public static function bfw_when_delete_user(int $user_id): void
    {
        self::clearAllHistoryUser($user_id);
    }
}
