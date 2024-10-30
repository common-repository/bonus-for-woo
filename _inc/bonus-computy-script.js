document.addEventListener('DOMContentLoaded', function() {

     document.addEventListener('click', function(e) {
           /*Удаление купона при старом выводе корзины*/
        if (e.target.id === 'bfw_remove_cart_point'){
               document.querySelector('.remove_points').click();
         }
     });

});




jQuery(document).ready(function($){

let body = $('body');
    /*Открытие закрытие использование бонусов в корзине*/
    body.on('click', '.computy_skidka_link', function(e){
        e.stopPropagation();
        $('.computy_skidka_container').show('slow');
        $(this).addClass('show_skidka');
    });
    body.on('click', '.show_skidka', function(e){
        e.stopPropagation();
        $('.computy_skidka_container').hide('slow');
        $(this).removeClass('show_skidka');
    });
    /*Открытие закрытие использование бонусов в корзине*/

    /*Копирование реферальной ссылки*/
    body.on('click', '#copy_referal', function(){
        let $tmp = $("<input>");
        $("body").append($tmp);
        $tmp.val($('#code_referal').text()).select();
        document.execCommand("copy");
        $('#copy_good').text("Скопировано в буфер обмена") ;
        setTimeout(function() {
            $("#copy_good").text(" ").empty();
        }, 2000);
        $tmp.remove();
    });
    /*Копирование реферальной ссылки*/

/*действие при списании баллов*/
$(document).on('submit', '.computy_skidka_form', function (e) {
    e.preventDefault();

    var $form = $(this);
    var maxpoints = $form.find('[name="maxpoints"]').val();
    var redirect = $form.find('[name="redirect"]').val();
    var computy_input_points = $form.find('[name="computy_input_points"]').val();

    $.ajax({
        type: 'POST',
        url: "/wp-admin/admin-ajax.php",
        data: {
            action: 'computy_trata_points',
            maxpoints: maxpoints,
            redirect: redirect,
            computy_input_points: computy_input_points,
        },
        success: function (data) {
            document.location.href = data.data;
        },
        error: function (error) {
            console.log(error);
        }
    });
});

    /*Введение купона */
    $(document).on('submit','.take_coupon_form', function (e) {
        let form = $(this);
        $(this).addClass('loading_coupon');
        $(".message_coupon").text('');
        e.preventDefault();

        $.ajax({
            type: 'POST',
            url: "/wp-admin/admin-ajax.php",
            data: {
                action: 'bfw_take_coupon_action',
                redirect: $(this).find('[name="redirect"]').val(),
                code_coupon: $(this).find('[name="code_coupon"]').val(),
            },
            success: function (data) {
                form.removeClass('loading_coupon');
                if(data.data.cod==='200'){
                    $(".message_coupon").text(data.data.message);
                    setTimeout(function () {
                        document.location.href = data.data.redirect;
                    }, 2000);

                }else{
                    $(".message_coupon").text(data.data.message);
                }

              //  message_coupon
            },
            error: function (error) {
                console.log(error);

            }
        });
        return false;
    });

    /*действие при удалении баллов*/
  $(document).on('submit','.remove_points_form', function (e) {
    e.preventDefault();
    $.ajax({
        type: 'POST',
        url: "/wp-admin/admin-ajax.php",
        data: {
            action: 'clear_fast_bonus',
            redirect: $(this).find('[name="redirect"]').val(),
            computy_input_points: $(this).find('[name="computy_input_points"]').val()
        },
        success: function (data) {
            if (data && data.data) {
                window.location.href = data.data;
            } else {
                console.error('Некорректный ответ сервера');
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
    return false;
});


    $('.checkout_coupon').on('submit', function() {
        $('.remove_points').trigger('click');
    });


    body.on('click', '.woocommerce-remove-coupon', function(){
           $('#computy-bonus-message-cart').show();
    });

    body.on('click', '.computy_skidka_container .button', function(){
        $(document.body).trigger('update_checkout');
    });



});