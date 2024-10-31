jQuery(document).ready(function ($) {
    jQuery(".nanoplazo_checkout").on('click', function(event) {
        if(jQuery('.nanoplazo_checkout').is('.wc-variation-is-unavailable') != true){
            var qty = $('input[name="quantity"]').val() > 0 ? $('input[name="quantity"]').val() : 1;
            var data = {
                action: "nanoplazo_quick_checkout_action",
                product_id: jQuery(this).attr('key'),
                qty: qty,
            };
            console.log(data);
            //ajax request
            jQuery.post(nanoplazo_ajax_object.ajax_url,
                data
                , function (response) {
                    if (response != "not added")
                        document.location.href = response;
                });
        }else{
            event.preventDefault();
        }
    });
});