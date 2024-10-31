<?php
if (!defined('ABSPATH')) exit;

function nanoplazo_wsppc_get_hook()
{
    return get_option('nanoplazo_wsppc_hook');
}

function nanoplazo_wsppc_get_hook_value($hook)
{
    $all_hook = nanoplazo_wsppc_get_hook();
    return $all_hook[$hook];
}

function nanoplazo_wsppc_output($meta)
{
    if (empty($meta)) {
        return "";
    }
    if (trim($meta) == '') {
        return "";
    }
    global $product;
    $price = sprintf("%.2f", $product->get_price() / 6);
    // 替换价格,价格单位分
    $content = html_entity_decode(wp_unslash($meta));
    $content = str_replace("{{estimate.price}}", $price, $content);
    $content = str_replace("{{plugins_url}}", OPGFW_WC_NANOPLAZO_URL, $content);
    // Output
    return do_shortcode($content);

}
