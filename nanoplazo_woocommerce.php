<?php
/**
 * Plugin Name: NanoPlazo Payment Gateway for WooCommerce
 * Description: WooCommerce payment gateway
 * Version: 1.4.0
 * Author: Nanoplazo
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    exit;
}
define("NANOPLAZO_BUILD", "1.0.1");
define('OPGFW_NANOPLAZO_ABSPATH', __DIR__ . '/');
define('OPGFW_WC_NANOPLAZO_URL', plugins_url('', __FILE__));
define('OPGFW_NANOPLAZO_TEST_URL', 'https://merchant-np.epay.mx.fg-example.com');
define('OPGFW_NANOPLAZO_URL', 'https://merchant.nanoplazo.mx');

function add_nanoplazo_gateway_class($methods)
{
    $methods[] = 'WC_Gateway_NanoPlazo_BNPL';
    return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_nanoplazo_gateway_class');

function init_nanoplazo_gateway_class()
{
    include_once 'gateways/bnpl.php';
}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nanoplazo_settings_link');

/**
 * Add setting link on plugin page.
 *
 * @param int $links Default links array.
 */
function nanoplazo_settings_link($links)
{
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=bnpl');
    $links[] = "<a href='{$url}'>" . __('Settings') . '</a>';
    return $links;
}


add_action('plugins_loaded', 'init_nanoplazo_gateway_class');

// load front settings
require_once(OPGFW_NANOPLAZO_ABSPATH . 'front/nanoplazo-settings.php');
// display product page
require_once(OPGFW_NANOPLAZO_ABSPATH . 'front/functions.php');
require_once(OPGFW_NANOPLAZO_ABSPATH . 'front/index.php');

//添加hook钩子，设置回调函数
add_action('woocommerce_api_wc_gateway_nanoplazo', 'nanoplazo_notify');
function nanoplazo_notify()
{
    $logger = new WC_Logger();
    //获取header信息
    $sign = !empty($_SERVER["HTTP_X_NANOPLAZO_SIGN"]) ? sanitize_text_field($_SERVER["HTTP_X_NANOPLAZO_SIGN"]) : "";
    $channel = !empty($_SERVER["HTTP_X_NANOPLAZO_CHANNEL"]) ? sanitize_text_field($_SERVER["HTTP_X_NANOPLAZO_CHANNEL"]) : "";
    $logger->add('nanoplazo', "Header : sign:$sign, channel:$channel");
    if (empty($sign) || empty($channel)) {
        new response("20001");
    }

    $bodyStr = file_get_contents('php://input');
    $logger->add('nanoplazo', "Body :$bodyStr");
    $data = json_decode($bodyStr, true);
    if (!isset($data['payment_id']) || !isset($data['state']) || empty($data['payment_id'])) {
        new response("20002");
    }
    // 根据插件验签
    if ($channel == WC_Gateway_NanoPlazo_BNPL::$channel) {
        $this_obj = new WC_Gateway_NanoPlazo_BNPL();
        $signature = $this_obj->auth($bodyStr);
    } else {
        new response("20003");
    }
    if (empty($signature) || $sign != $signature) {
        new response("20005");
    }
    // 订单验证
    $payment_arr = explode('-', $data['payment_id']);
    $order_id = $payment_arr[2];
    $order = wc_get_order($order_id);
    $logger->add('nanoplazo', 'order detail：' . $order);
    if (!$order) {
        new response("20004");
    }
    $noteMessage = "";
    switch ($data["state"]) {
        case 201:
            if ($order->needs_payment()) {
                // 更新订单状态
                try {
                    $order->set_transaction_id($data['transaction_id']);
                } catch (Exception $e) {
                }
                $order->set_status("processing");
                $order->save();
                $noteMessage = sprintf(__('Payment %s'), "successful");

            }
            break;
        case 301:
        case 401:
            if ($order->needs_payment()) {
                $order->set_status("failed");
                $order->save();
                $noteMessage = sprintf(__('Payment %s'), "failed");
            }
            break;
        case 501:
            $noteMessage = sprintf(__('Refund  %s'), "successful");
            break;
        case 601:
            $noteMessage = sprintf(__('Refund %s'), "failed");
            break;
    }
    if ($noteMessage) {
        $order->add_order_note($noteMessage);
    }
    new response("10000");
}


class response
{
    const CODE_SUCCESS = 10000;
    const CODE_UNKNOWN_HEADER = 20001;
    const CODE_INVALID_PARAMETER = 20002;
    const CODE_INVALID_PAYMENT_METHOD = 20003;
    const CODE_UNKNOWN_ORDER = 20004;
    const CODE_SIGNATURE_ERROR = 20005;
    const CODE_ORDER_STATUS_ERROR = 20006;

    private static $messages = array(
        self::CODE_SUCCESS => "Success",
        self::CODE_UNKNOWN_HEADER => "Unknown Header",
        self::CODE_INVALID_PARAMETER => "Invalid Parameter",
        self::CODE_INVALID_PAYMENT_METHOD => "Invalid payment method",
        self::CODE_UNKNOWN_ORDER => "Unknown Order",
        self::CODE_SIGNATURE_ERROR => "Signature Error",
        self::CODE_ORDER_STATUS_ERROR => "Order status not pending",
    );

    public function __construct($code)
    {
        $response = array(
            "code" => intval($code),
            "message" => isset(self::$messages[$code]) ? self::$messages[$code] : "Unknown error",
        );
        ob_clean();
        echo wp_json_encode($response);
        exit();
    }
}