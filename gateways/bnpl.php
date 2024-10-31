<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_NanoPlazo_BNPL extends WC_Payment_Gateway
{

    /**
     * Logger instance
     *
     * @var WC_Logger
     */
    public static $log = false;

    /**
     * @var int 渠道
     */
    public static $channel = 4;

    /**
     * @var bool 测试模式
     */
    private $test_mode;

    /**
     * @var string 商户ID
     */
    private $merchant_id;

    /**
     * @var string Token
     */
    private $token;
    /**
     * @var string 货币单位
     */
    private $currency;

    /**
     * @var String 展示支付到商品页面
     */
    private $single_product_page_display;

    /**
     * @var string 快捷支付
     */
    private $quick_checkout;

    /**
     * 展示快捷支付到商品列表页面
     */
    private $quick_checkout_on_shop_page;
    /**
     * @var String 展示支付到商品页面的位置
     */
    private $single_product_page_display_postion = "woocommerce_after_product_price";

    public function __construct()
    {
        $this->id = 'bnpl';
        $this->icon = OPGFW_WC_NANOPLAZO_URL . "/assets/images/logo.png";
        $this->has_fields = true;
        $this->method_title = 'NanoPlazo';
        $this->method_description = 'Use our plugin and start accepting our pay later checkout solution - NanoPlazo, which allows consumers to buy what they want now and pay for it later';
        $this->supports = array(
            'products',
            'refunds',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->test_mode = 'yes' === $this->get_option('test_mode', 'no');
        $this->single_product_page_display = 'yes' === $this->get_option('single_product_page_display', 'no');
        $this->quick_checkout = 'yes' === $this->get_option('quick_checkout', 'no');
        //$this->quick_checkout_on_shop_page = 'yes' === $this->get_option('quick_checkout_on_shop_page', 'no');
        $this->merchant_id = $this->get_option('merchant_id');
        $this->token = $this->get_option('token');
        $this->currency = $this->get_option('currency');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_bnpl', array(&$this, 'thankyou_page'));

        // init Payment method for the frontend.
        // 根据支付类型获取配置
        $nanoplazo_settings = (new Nanoplazo_Settings())->settings($this->get_option("front_description_style", "normal"));
        $this->title = $nanoplazo_settings["title"];
        $this->description = $nanoplazo_settings["description"];
        $this->icon = OPGFW_WC_NANOPLAZO_URL . $nanoplazo_settings["logo"];
        $single_product_page = $nanoplazo_settings["single_product_page"];
        $nanoplazo_wsppc_hook = array();
        if ($this->enabled == 'yes' && $this->single_product_page_display) {
            $hook = $this->single_product_page_display_postion;
            $nanoplazo_wsppc_hook[$hook] = htmlentities($single_product_page);
        }
        update_option('nanoplazo_wsppc_hook', $nanoplazo_wsppc_hook);
        update_option('nanoplazo_quick_checkout', $this->quick_checkout);
        //update_option('nanoplazo_quick_checkout_on_shop_page', $this->quick_checkout_on_shop_page);
        //end init payment method for the frontend.
    }


    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woothemes'),
                'type' => 'checkbox',
                'label' => 'Open NanoPlazo Payment',
                'default' => 'no'
            ),

            'merchant_id' => array(
                'title' => 'Merchant AppID',
                'type' => 'text',
                'default' => '',
                'description' => 'The merchant AppID in merchant dashboard.',
                'custom_attributes' => array('required' => 'required'),
            ),
            'token' => array(
                'title' => 'Merchant App Token',
                'type' => 'text',
                'default' => '',
                'description' => 'The APP token in merchant dashboard.',
                'custom_attributes' => array('required' => 'required'),
            ),
            'currency' => array(
                'title' => 'Monetary Unit',
                'type' => 'select',
                'description' => 'Please select payment currency.',
                'default' => 'MXN',
                'options' => array(
                    'MXN' => 'MXN',
                ),
                'custom_attributes' => array('required' => 'required'),
            ),
            'single_product_page_display' => array(
                'title' => 'Display NanoPlazo in single product page',
                'label' => 'Enable',
                'type' => 'checkbox',
                'description' => 'Display NanoPlazo in single product page.',
                'default' => 'yes',
            ),
            'front_description_style' => array(
                'title' => 'NanoPlazo Display Style',
                'type' => 'select',
                'description' => 'Please select NanoPlazo display style',
                'default' => 'normal',
                'options' => array(
                    'normal' => 'Normal',
                    'sales' => "Sales",
                ),
                'custom_attributes' => array('required' => 'required'),
            ),
            'quick_checkout' => array(
                'title' => 'Quick Checkout',
                'label' => 'Enable/Disable Quick Checkout',
                'type' => 'checkbox',
                'description' => 'You can control when to enable and disable functionality.',
                'default' => 'yes',
            ),
/*            'quick_checkout_on_shop_page' => array(
                'title' => 'Quick Checkout On Shop Page',
                'label' => 'Enable/Disable quick checkout on Shop page',
                'type' => 'checkbox',
                'description' => 'You can control when to enable and disable functionality.',
                'default' => 'yes',
            ),*/
            'test_mode' => array(
                'title' => 'Test Mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Helpful while testing, please uncheck if live.',
                'default' => 'yes',
            ),
        );
    }

    /**
     * payment page description
     *
     * @return string
     */
    public function thankyou_page()
    {
        if ($this->description) {
            return wpautop(wptexturize($this->description));
        }
    }

    /**
     * Process Payment.
     *
     * Process the payment. Override this in your gateway. When implemented, this should.
     * return the success and redirect in an array. e.g:
     *
     *        return array(
     *            'result'   => 'success',
     *            'redirect' => $this->get_return_url( $order )
     *        );
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order || !$order->needs_payment()) {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }
        $this->log('order info: ' . $order);

        if ($this->test_mode) {
            $url = OPGFW_NANOPLAZO_TEST_URL;
        } else {
            $url = OPGFW_NANOPLAZO_URL;
        }
        $processUrl = $url . "/plazo/payment/create";

        // Body
        $amount = $order->get_total();
        $goods = $this->get_order_goods($order);
        $callbackUrl = add_query_arg('wc-api', 'wc_gateway_nanoplazo', home_url('/'));

        $buyer_address_extra = array(
            "country" => $order->get_billing_country(),
            "state" => $order->get_billing_state(),
            "city" => $order->get_billing_city(),
            "street" => sprintf("%s %s", $order->get_billing_address_1(), $order->get_billing_address_2()),
            "number" => "",
            "geo_coordinates" => "",
        );
        $buyer = array(
            "name" => $order->get_formatted_billing_full_name(),
            "mobile" => $order->get_billing_phone(),
            "email" => $order->get_billing_email(),
            "address" => sprintf("%s %s %s %s %s", $order->get_billing_address_1(), $order->get_billing_address_2(), $order->get_billing_city(), $order->get_billing_state(), $order->get_billing_country()),
            "postcode" => $order->get_billing_postcode(),
            "address_extra" => $buyer_address_extra,
        );

        // 回调用订单ID
        $paymentId = sprintf("WC-%s-%s", $this->merchant_id, $order_id);
        $data = [
            "payment_id" => $paymentId,
            "amount" => floatval($amount),
            "mobile" => "",
            'callback_url' => $callbackUrl,
            'return_url' => $this->get_return_url($order),
            'goods' => $goods,
            'buyer' => $buyer,
        ];

        $signature = $this->auth(json_encode($data));
        $header = [
            'Content-Type' => 'application/json',
            'X-NANOPLAZO-SIGN' => $signature,
            'X-NANOPLAZO-CHANNEL' => self::$channel,
            "X-NANOPLAZO-MERCHANTID" => $this->merchant_id,
        ];
        try {
            $body = json_encode($data);
            $result = $this->safe_http_post($processUrl, $header, $body);
            if (is_wp_error($result)) {
                $this->log('Process payment Failed: ' . $result->get_error_message(), 'error');
                throw new Exception($result->get_error_message());
            }
            return array(
                'result' => 'success',
                'redirect' => $result->redirect_url,
            );
        } catch (Exception $e) {
            $order->add_order_note(sprintf(__('Payment could not be created: %s'), $e->getMessage()));
            wc_add_notice("Payment Error:{$e->getMessage()}", 'error');
            return array(
                'result' => 'failure',
                'redirect' => $this->get_return_url($order)
            );
        }
    }

    /**
     * 获取商品列表
     * @param WC_Order $order
     * @return array
     */
    public function get_order_goods($order)
    {
        $items = array();
        $order_items = $order->get_items("line_item");
        if (!empty($order_items)) foreach ($order_items as $item_id => $item) {
            $goods = array(
                "name" => $item["name"],
                "product_id" => $item["product_id"] . "",
                "price" => floatval($item["subtotal"]),
                "pic_url" => "",
                "quantity" => intval($item["quantity"]),
                "desc" => $item["name"],
            );
            $items[] = $goods;
        }
        return $items;
    }


    /**
     *  safe http post
     * @param $url
     * @param $header
     * @param $body
     * @return array|object|WP_Error
     */
    public function safe_http_post($url, $header, $body)
    {
        $this->log('call api request url: ' . $url);
        $this->log('call api request header: ' . json_encode($header));
        $this->log('call api request body : ' . $body);
        $raw_response = wp_safe_remote_post(
            $url,
            array(
                'method' => 'POST',
                'body' => $body,
                'timeout' => 100,
                'user-agent' => 'WooCommerce/' . WC()->version,
                'httpversion' => '1.1',
                'headers' => $header,
            )
        );

        if (is_wp_error($raw_response)) {
            return $raw_response;
        } elseif (empty($raw_response['body'])) {
            return new WP_Error("error", 'Empty Response');
        }
        $this->log('call api response : ' . $raw_response['body']);
        $response = json_decode($raw_response['body']);
        if (!isset($response->code) || $response->code != "20000") {
            // 服务异常
            return new WP_Error('error', $response->message);
        }
        return $response;
    }

    /**
     * 生成验签
     * @param $auth_string
     * @return string
     */
    public function auth($auth_string)
    {
        $token = $this->token;
        return base64_encode(hash_hmac('sha512', $auth_string, $token));
    }

    /**
     * Process a refund if supported.
     *
     * @param int $order_id Order ID.
     * @param float $amount Refund amount.
     * @param string $reason Refund reason.
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);

        if (!$this->can_refund_order($order)) {
            return new WP_Error('error', __('Refund failed.', 'woocommerce'));
        }

        $result = $this->refund_transaction($order, $amount, $reason);
        if (is_wp_error($result)) {
            $order->add_order_note(sprintf(__('Refund initiated %s %s'), "failed", $result->message));
            $this->log('Refund Failed: ' . $result->get_error_message(), 'error');
            return new WP_Error('error', $result->get_error_message());
        }
        $this->log('Refund Result: ' . wc_print_r($result, true));

        // 退款状态
        $isRefund = (isset($result->state) && $result->state == 101);
        $refundMessage = sprintf(__('Refund initiated %s'), ($isRefund ? "successfully" : "failed"));
        $order->add_order_note($refundMessage);
        return $isRefund ? true : new WP_Error('error', $refundMessage);
    }

    /**
     * Refund an order
     *
     * @param WC_Order $order Order object.
     * @param float $amount Refund amount.
     * @param string $reason Refund reason.
     * @return object Either an object of name value pairs for a success, or a WP_ERROR object.
     */
    private function refund_transaction($order, $amount = null, $reason = '')
    {
        if ($this->test_mode) {
            $url = OPGFW_NANOPLAZO_TEST_URL;
        } else {
            $url = OPGFW_NANOPLAZO_URL;
        }
        $refundUrl = $url . "/plazo/payment/refund";

        //调用订单ID
        $paymentId = sprintf("WC-%s-%s", $this->merchant_id, $order->get_id());

        $data = [
            "payment_id" => $paymentId,
            "amount" => floatval($amount),
            "reason" => $reason
        ];

        $signature = $this->auth(json_encode($data));
        $header = [
            'Content-Type' => 'application/json',
            'X-NANOPLAZO-SIGN' => $signature,
            'X-NANOPLAZO-CHANNEL' => self::$channel,
            "X-NANOPLAZO-MERCHANTID" => $this->merchant_id,
        ];
        $body = json_encode($data);
        //$this->log('refund payment order request: ' . wc_print_r(["header" => $header, "body" => $data, "url" => $refundUrl], true));
        return $this->safe_http_post($refundUrl, $header, $body);
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level Optional. Default 'info'. Possible values:
     *                      emergency|alert|critical|error|warning|notice|info|debug.
     */
    public function log($message, $level = 'info')
    {
        if (empty(self::$log)) {
            self::$log = wc_get_logger();
        }
        self::$log->log($level, $message, array('source' => $this->id));
    }

}
