<?php
/*
Plugin Name: MOBIKA
Description: Регистрация чеков через сервис МОБИКА
Version: 1.0.3
Author: ООО Кассовые Решения
Author URI: https://mobika-online.ru/

Copyright 2018 ООО Кассовые Решения (email: info@mobika-online.ru)
*/

final class KassCloudPlugin {
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . '/sdk/autoload.php';
        require_once plugin_dir_path(__FILE__) . '/includes/sender.php';

        if (is_admin()) {
            add_action('admin_menu', array($this, 'menu'));
            add_action('admin_init', array($this, 'registerSettings'));
        }

        add_action('woocommerce_order_status_' . get_option('kasscloud_incoming_order_status'), array($this, 'incoming'));
        add_action('woocommerce_order_status_' . get_option('kasscloud_final_order_status'), array($this, 'finalCheck'));
        if (get_option('kasscloud_refunds')) {
            add_action('woocommerce_order_refunded', array($this, 'refund'), 10, 2);
        }
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option("kasscloud_api_url", "https://api.kkt.cloud/");
        add_option("kasscloud_timeout", "10");
        add_option("kasscloud_incoming_order_status", "processing");
        add_option("kasscloud_final_order_status", "completed");
        add_option("kasscloud_product_vat", "6");
        add_option("kasscloud_delivery_vat", "6");
        add_option("kasscloud_payment_gateway", array());
    }

    public function menu() {
        add_menu_page(
            "МОБИКА: Настройки",
            "МОБИКА",
            'manage_options',
            'kasscloud-settings-page',
            array($this, 'settingsPage'),
            plugin_dir_url(__FILE__) .'/icon.png',
            '56.3'
        );
    }

    public function registerSettings() {
        register_setting('kasscloud-settings', 'kasscloud_api_url');
        register_setting('kasscloud-settings', 'kasscloud_group_id');
        register_setting('kasscloud-settings', 'kasscloud_group_key');
        register_setting('kasscloud-settings', 'kasscloud_timeout');
        register_setting('kasscloud-settings', 'kasscloud_tax_system');
        register_setting('kasscloud-settings', 'kasscloud_product_vat');
        register_setting('kasscloud-settings', 'kasscloud_delivery_vat');
        register_setting('kasscloud-settings', 'kasscloud_incoming_order_status');
        register_setting('kasscloud-settings', 'kasscloud_final_order_status');
        register_setting('kasscloud-settings', 'kasscloud_refunds');
        register_setting('kasscloud-settings', 'kasscloud_payment_gateway');
    }

    public function settingsPage() {
        include 'kasscloud-settings.php';
    }

    public function taxSystems() {
        return \Mobika\Receipt::getTaxSystems();
    }

    public function vatList() {
        return \Mobika\Receipt::getVatList();
    }

    public function incoming($orderId) {
        return $this->registration($orderId, false, 'prepayment');
    }

    public function finalCheck($orderId) {
        return $this->registration($orderId, false, 'final');
    }

    public function refund($orderId, $refundId) {
        $current_meta_data = get_post_meta($orderId);
        if ( ! isset($current_meta_data['prepayment_check']) || $current_meta_data['prepayment_check'][0] != 'ok' ) {
            return;
        }
        
        $paymentStatus = 'prepayment';
        if ( isset($current_meta_data['final_check']) && $current_meta_data['final_check'][0] == 'ok'){
            $paymentStatus = 'final';
        }
        return $this->registration($orderId, $refundId, $paymentStatus);
    }

    public function checkPaymentMethod($method) {
        $vals = get_option('kasscloud_payment_gateway');
        return is_array($vals) && in_array($method, $vals);
    }

    public function registration($orderId, $refundId, $paymentStatus) {
        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        if (!$this->checkPaymentMethod($order->get_payment_method())) {
            return;
        }

        if (!$order->get_date_paid()) {
            return;
        }

        $receipt_type = 1;
        $doc = $order;

        $paymentMethod = null;
        switch ($paymentStatus) {
            case "prepayment":
                $paymentMethod = 1;
                break;
            case "final":
                $paymentMethod = 4;
                $prepaymentSum = intval($doc->get_total()) * 100; 
                break;
        }

        $refund = null;
        if ($refundId) {
            $refund = wc_get_order($refundId);
            if (!$refund) {
                return;
            }
            $receipt_type = 2;
            if ($refund->get_amount() < $doc->get_total()) {
                $doc = $refund;
            }
        }

        $timeout = intval(get_option('kasscloud_timeout'));
        $tax_system = intval(get_option('kasscloud_tax_system'));
        $receipt = new \Mobika\Receipt($receipt_type, $tax_system, $order->get_billing_email());
        
        if (isset($prepaymentSum)) {
            $receipt->setPayment('prepayment', $prepaymentSum);
        }

        if (sizeof($doc->get_items()) > 0 ) {
            foreach ($doc->get_items('line_item') as $item) {
                $receipt->addProduct(new \Mobika\Product(
                    $item->get_name(),
                    abs($doc->get_item_total($item, true, true)) * 100,
                    abs($item->get_quantity()) * 1000,
                    get_option('kasscloud_product_vat'),
                    '',
                    $paymentMethod
                )); 
            }
            foreach ($doc->get_items('shipping') as $item) {
                $product = new \Mobika\Product(
                    'Доставка',
                    abs($doc->get_item_total($item, true, true)) * 100,
                    abs($item->get_quantity()) * 1000,
                    get_option('kasscloud_delivery_vat'),
                    '',
                    $paymentMethod
                );
                $product->data["productType"] = 4;
                $receipt->addProduct($product);
            }
        }
        if ($refund) {
            $val = $refund->get_amount() * 100 - $receipt->getAmount();
            if ($val > 0) {
                $reason = $refund->get_reason() ? $refund->get_reason() : "Возврат";
                $receipt->addProduct(new \Mobika\Product(
                    $reason,
                    $val,
                    1000,
                    6
               ));
            }
        }

        $order_id = $this->getOrderId($refund ? $refund : $order, $paymentStatus);

        $client = new \Mobika\Api\OrderClient(
            get_option('kasscloud_api_url'),
            get_option('kasscloud_group_id'),
            get_option('kasscloud_group_key'),
            $timeout
        );
        $client->setSender(new \MobikaWP\Sender);
        try {
            $result = $client->registration($order_id, $receipt);
            $ok = $result && !empty($result['accepted']);
        } catch (\Exception $e) {
            error_log($e);
            $ok = false;
        }
        switch ($receipt_type) {
            case 1:
                switch ($paymentStatus) {
                    case "prepayment":
                        $order->update_meta_data('prepayment_check', $ok ? 'ok' : 'error');
                        break;
                    case "final":
                        $order->update_meta_data('final_check', $ok ? 'ok' : 'error');
                        break;
                }
                break;
            case 2:
                $order->update_meta_data('refund_check', $ok ? 'ok' : 'error');
                break;
        }
        $order->save();
    }

    private function getOrderId($order, $paymentStatus) {
        return $order->get_id() . "-" . $order->get_date_created()->date('ymd'). "-" . $paymentStatus;
    }
}

$kasscloud = new KassCloudPlugin();
