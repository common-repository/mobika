<?php
namespace Mobika;

class Product {
    public $data = array();

    function __construct($name, $price, $quantity, $taxType, $productId="", $paymentMethod=null) {
        $this->data = array( 
            'name' => $name,
            'price' => round($price),
            'quantity' => round($quantity),
            'taxType' => (int)$taxType,
        );
        if ($productId != "") {
            $this->data['productId'] = $productId;
        }
        if ($paymentMethod > 0) {
            $this->data['paymentMethod'] = $paymentMethod;
        }
    }

    public function getSum() {
        return round($this->data['price'] * $this->data['quantity'] / 1000);
    }

    public function toArray() {
        return $this->data;
    }
}
