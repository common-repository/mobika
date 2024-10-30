<?php
namespace Mobika;

class Receipt {
    public $data = array();
    private $products = array();
    private $payment = array();

    function __construct($type, $taxSystem=null, $customerContact='') {
        $this->data = array( 
            'receiptType' => (int)$type,
            'payment' => array(),
            'products' => array(),
        );
        if ($taxSystem !== null) {
            $this->data['taxSystem'] = (int)$taxSystem;
        }
        if ($customerContact) {
            $this->data['customerContact'] = $customerContact;
        }
    }
    
    public static function getTaxSystems() { 
        return array(
			0 => 'ОСН',
			1 => 'УСН (доход)',
			2 => 'УСН (доход-расход)',
			3 => 'ЕНВД',
			4 => 'ЕСХН',
			5 => 'Патент',
		);
    }
    
    public static function getVatList() {
        return array(
			1 => 'Ставка НДС 20%',
			2 => 'Ставка НДС 10%',
			3 => 'Ставка НДС 20/120',
			4 => 'Ставка НДС 10/110',
			5 => 'Ставка НДС 0%',
			6 => 'НДС не облагается',
		);
    }

    public function addProduct(Product $product) {
        $this->products[] = $product;
    }

    public function setPayment($type, $amount) {
        $this->payment[$type] = $amount;
    }

    public function getAmount() {
        $amount = 0;
        foreach ($this->products as $product) {
            $amount += $product->getSum();
        }
        return $amount;
    }

    public function toArray() {
        $result = $this->data;
        $result['products'] = array();
        foreach ($this->products as $product) {
            $result['products'][] = $product->toArray();
        }
        if (!empty($this->payment)) {
            $result['payment'] = $this->payment;
        } else {
            $result['payment']['ecash'] = $this->getAmount();
        }
        return $result;
    }
}
