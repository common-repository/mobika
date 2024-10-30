<?php
namespace Mobika\Api;

use Mobika\Receipt;

class OrderClient extends BaseClient {

    public function registration($orderId, Receipt $receipt, $timeout=null, $mandatory=false) {
        $params = array(
            'groupId' => $this->groupId,
            'orderId' => $orderId,
            'receipt' => $receipt->toArray(),
            'mandatory' => $mandatory,
        );
        $call_timeout = null;
        if ($timeout !== null) {
            $params['timeout'] = $timeout;
            $call_timeout = $timeout + 1;
        }
        return $this->call('order.Registration', $params, $call_timeout);
    }

    public function status($orderId) {
        $params = array(
            'groupId' => $this->groupId,
            'orderId' => $orderId,
        );
        return $this->call('order.Status', $params);
    }

    public function forPrint($orderId, $format) {
        $params = array(
            'groupId' => $this->groupId,
            'orderId' => $orderId,
            'format' => $format
        );
        return $this->call('order.Print', $params);
    }
}
