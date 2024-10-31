<?php

/** Front Side Global Content Print Start */

add_action('init', 'nanoplazo_wsppc_global_content_print_function');

function nanoplazo_wsppc_global_content_print_function()
{
    $wsppc_hooks = nanoplazo_wsppc_get_hook();

    if (!empty($wsppc_hooks)) {
        foreach ($wsppc_hooks as $key => $wsppc_hook) {
            if ($key == 'woocommerce_after_product_title') {
                add_action('woocommerce_single_product_summary', 'nanoplazo_wsppc_woocommerce_after_product_title', 5);
            } elseif ($key == 'woocommerce_after_product_price') {
                add_action('woocommerce_single_product_summary', 'nanoplazo_wsppc_woocommerce_after_product_price', 10);
            }
        }
    }
}

function nanoplazo_wsppc_woocommerce_after_product_title()
{
    $wsppc_hooks = nanoplazo_wsppc_get_hook();
    echo "<div class='nonoplazo_wsppc_div_block woocommerce_after_product_title'>";
    echo nanoplazo_wsppc_output($wsppc_hooks['woocommerce_after_product_title']);
    echo "</div>";
}

function nanoplazo_wsppc_woocommerce_after_product_price()
{
    $wsppc_hooks = nanoplazo_wsppc_get_hook();
    echo "<div class='nonoplazo_wsppc_div_block woocommerce_after_product_price'>";
    echo nanoplazo_wsppc_output($wsppc_hooks['woocommerce_after_product_price']);
    echo "</div>";
}

/** Front Side Global Content Print End */

/** front side sss */
function nanoplazo_wsppc_front_site_css_add()
{
    ?>
    <style>
        .nonoplazo_wsppc_div_block {
            display: inline-block;
            width: 100%;
            margin-top: 10px;
        }
        .nanoplazo_checkout {
            width: 100%;
            background: #00B876;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1px;
            margin-bottom: 1px;
            text-align: center;
        }
    </style>
    <?php
}

add_action('wp_head', 'nanoplazo_wsppc_front_site_css_add');

/**
 * adding quick checkout funciton
 */
if (get_option('nanoplazo_quick_checkout')) {
    add_action('woocommerce_after_add_to_cart_button', 'woocommerce_after_add_to_cart_form');
    //add_action('woocommerce_after_shop_loop_item', 'woocommerce_after_add_to_cart_form');
    function woocommerce_after_add_to_cart_form()
    {
        global $product;
        if ($product->get_type() == "simple") {
            $id = $product->get_id();
            echo('<div key="' . esc_attr($id) . '"  class="nanoplazo_checkout" >NanoPlazo-Difiere tu compra sin comisión</div>');
        }
    }
    // add quick checkout ajax script
    add_action('init', 'nanoplazo_adding_ajax');
    function nanoplazo_adding_ajax()
    {
        wp_enqueue_script('nanoplazo-ajax-script',
            OPGFW_WC_NANOPLAZO_URL . '/assets/js/direct_checkout.js',
            array('jquery'), time(), true
        );
        wp_localize_script('nanoplazo-ajax-script', 'nanoplazo_ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    // add quick checkout add to cart action
    add_action('wp_ajax_nopriv_nanoplazo_quick_checkout_action', 'nanoplazo_quick_checkout_action');
    add_action('wp_ajax_nanoplazo_quick_checkout_action', 'nanoplazo_quick_checkout_action');
    function nanoplazo_quick_checkout_action()
    {
        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : false;
        $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
        if ($product_id) {
            global $woocommerce;
            // 清空当前购物车
            $woocommerce->cart->empty_cart();
            $woocommerce->cart->add_to_cart($product_id, $qty);
            // 设置默认支付方式
            WC()->session->set('chosen_payment_method', 'bnpl');
            echo get_permalink(wc_get_page_id('checkout'));
        } else {
            echo "not added";
        }
        wp_die(); // All ajax handlers die when finished
    }
}

