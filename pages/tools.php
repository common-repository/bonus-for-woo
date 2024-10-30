<?php
/*
 * Страница инструментов
 * */
?>
<div class="wrap bonus-for-woo-admin">
    <?php  echo '<h1>'.__('Bonus for Woo', 'bonus-for-woo').' - '. __('Tools', 'bonus-for-woo').'</h1>'; ?>

    <div class="card">
        <h2 class="title"><?php echo __('Rules and Conditions Generator', 'bonus-for-woo'); ?></h2>
        <p><?php echo __('This generator will create text for you based on the plugin settings.', 'bonus-for-woo'); ?></p>
           <a class="pdf-button" href="?page=bonus-for-woo/pages/generator.php"><?php echo __('Rules and Conditions Generator', 'bonus-for-woo'); ?></a>
    </div>


    <div class="card">
        <h2 class="title"><?php echo __('Update user statuses', 'bonus-for-woo'); ?></h2>
        <p><?php echo __('Update all users statuses. Requires a lot of resources!', 'bonus-for-woo'); ?></p>
        <form method="post" action="">
            <input type="hidden" name="update_statuses" value="1">
            <input type="submit" class="pdf-button" value="<?php echo __('Update user statuses','bonus-for-woo'); ?>">
        </form>
<?php

if(!empty($_POST['update_statuses'])) {

    $val = get_option('bonus_option_name');
    $exclude_roles = $val['exclude-role'] ?? array('administrator');
    $args1=array(
        'role__not_in' => $exclude_roles ,//Исключенные роли
        'number' => -1, // Получить всех пользователей
       'fields' => 'ID'
    );
    $users_bs = get_users( $args1 );
    //Обновление статусов пользователей
    foreach ($users_bs as $user) {
       BfwRoles::updateRole($user,false);
    }
    echo '<p>'.__('Updated statuses:','bonus-for-woo').' '.count($users_bs).'</p>';
    BfwLogs::addLog('tool',get_current_user_id(), __('Updated all user statuses.','bonus-for-woo'));
}
?>

    </div>


    <div class="card">
        <h2 class="title"><?php echo __('Export/Import', 'bonus-for-woo').' '.$val['label_points']; ?></h2>
        <div class=" ">

            <?php $filename = BONUS_COMPUTY_PLUGIN_DIR . '/export_bfw.csv';
            // echo 'При нажатии кнопки "Создать CSV файл экспорта", рядом появиться ссылка для скачивания файла.';
            echo '<p>' . __('When you click the "Create CSV export file" button, a link to download the file will appear next to it. You can download the file and edit it and then import it in the form below. After that, the bonus points data will be updated.',
                    'bonus-for-woo') . '</p><br>';
            if (file_exists($filename)) {
                echo '<a class="bfw-admin-button" href="?page=bonus-for-woo%2Fpages%2Ftools.php&export_bfw_points=true">' . __('Recreate CSV export file','bonus-for-woo') . '</a> ';

                echo ' <a href="' . BONUS_COMPUTY_PLUGIN_URL . 'export_bfw.csv"   download><i class="exporticon"></i>' . __('download CSV file','bonus-for-woo') . '</a>';
            } else {
                echo '<a class="bfw-admin-button" href="?page=bonus-for-woo%2Fpages%2Ftools.php&export_bfw_points=true">'
                    .__('Сreate CSV export file', 'bonus-for-woo').'</a> ';

            }

            echo '<br><br><br>';
            echo '<form action="' . admin_url("admin-post.php") . '" class="bfw_export_bonuses"  method="post"  enctype="multipart/form-data">
                        <input type="hidden" name="action" value="bfw_export_bonuses" />
                        <lable for="bfw-file-export">' . __('Upload CSV file', 'bonus-for-woo') . '<br></lable>
                        <input name="file" type="file" id="bfw-file-export" required>
                        <br><label><input type="checkbox" id="by_email" name="by_email" value="1">'. __('Search for clients by email', 'bonus-for-woo').'</label>';
            echo '<input class="bfw-admin-button" type="submit" value="' . __('import',
                    'bonus-for-woo') . '" onclick="upload();return false;">
                        <div id="bfw-file-export-result"></div>
                    </form>';
            ?>
            <script type="text/javascript">
                function upload() {
                    let fileExtension = ['csv'];
                    if (jQuery.inArray(jQuery('#bfw-file-export').val().split('.').pop().toLowerCase(), fileExtension) === -1) {
                        jQuery('#bfw-file-export-result').html('<span style="color:red"><?php echo __('You can only use csv file format!',
                            'bonus-for-woo');?></span>');
                    } else {
                        jQuery('.bfw_export_bonuses').addClass('bfv_uplouads');
                        let formData = new FormData();
                        formData.append("action", "upload-attachment");
                        let fileInputElement = document.getElementById("bfw-file-export");
                        let by_email = 0;
                        if (document.getElementById('by_email').checked) {
                            by_email = 1;
                        }
                        formData.append("async-upload", fileInputElement.files[0]);
                        formData.append("name", fileInputElement.files[0].name);
                        <?php $my_nonce = wp_create_nonce('media-form');  ?>
                        formData.append("_ajax_nonce", "<?php  echo $my_nonce; ?>");
                        let xhr = new XMLHttpRequest();
                        xhr.onreadystatechange = function () {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                // console.log(xhr.responseText);
                                jQuery.ajax({
                                    type: 'POST',
                                    url: "/wp-admin/admin-ajax.php",
                                    data: {
                                        action: 'bfw_export_bonuses',
                                        response: xhr.responseText,
                                        by_email: by_email
                                    },
                                    success: function (data) {
                                        if(data==='wrong'){
                                            jQuery('#bfw-file-export-result').html('<span style="color:red;font-size: 20px;"><?php echo __('Security check error!','bonus-for-woo');?></span>');
                                            jQuery('.bfw_export_bonuses').removeClass('bfv_uplouads');
                                            jQuery('#bfw-file-export').val('');
                                        }
                                        if (data === 'good') {
                                            jQuery('#bfw-file-export-result').html('<span style="color:green;font-size: 20px;"><?php echo __('Export completed successfully!','bonus-for-woo');?></span>');
                                            jQuery('.bfw_export_bonuses').removeClass('bfv_uplouads');
                                            jQuery('#bfw-file-export').val('');
                                        }
                                    },
                                    error: function (error) {
                                        console.log(error);
                                    }
                                });
                            }
                        }
                        xhr.open("POST", "/wp-admin/async-upload.php", true);
                        xhr.send(formData);
                    }
                }
            </script>


            <hr>


        </div>


    </div>

</div>