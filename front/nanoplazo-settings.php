<?php
/**
 * Class Nanoplazo_Settings
 */
// title:前端支付方式名称
// description:前端支付方式描述
// single_product_page 单个商品页面支付暴露
class Nanoplazo_Settings {
    // 通用版本语言配置
    private static $normal_config = array(
        "logo" => "/assets/images/logo.png",
        "title" => 'NanoPlazo-Difiere tu compra sin comisión',
        "description" => "• Regístrate en minutos.<br />• ¡Disfruta tu compra! Consulta dónde y cuándo realizar tus pagos en la APP o página web de Nanoplazo",
        "single_product_page" => 'Pagas en <span style="color:#007B3D">6 cuotas</span> de <span style="color:#007B3D">{{estimate.price}}</span> y en minuto de aprobación <img src="{{plugins_url}}/assets/images/logo.png" width="100px">',
    );

    // 营销版本语言配置
    private static $sales_config = array(
        "logo" => "/assets/images/logo.png",
        "title" => 'NanoPlazo-Difiere tu compra sin comisión',
        "description" => "    •    Regístrate en minutos.<br />    •    ¡Disfruta tu compra! Consulta dónde y cuándo realizar tus pagos en la APP o página web de Nanoplazo",
        "single_product_page" => 'Pagas en <span style="color:#007B3D">6 cuotas</span> de <span style="color:#007B3D">{{estimate.price}}</span> y en minuto de aprobación <img src="{{plugins_url}}/assets/images/logo.png" width="100px">',
    );

    /**
     * 根据类型获取配置
     * @param string $setting_type 根据类型获取配置信息
     */
    public function settings($setting_type = "normal") {
        if ($setting_type == "sales") {
            return self::$sales_config;
        }
        return self::$normal_config;
    }
}