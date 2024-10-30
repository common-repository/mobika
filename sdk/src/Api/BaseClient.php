<?php
namespace Mobika\Api;

abstract class BaseClient {
    private $sender;
    private $apiUrl;
    private $groupKey;
    private $timeout;

    protected $groupId;

    public function __construct($apiUrl, $groupId, $groupKey, $timeout=10) {
        $this->apiUrl = rtrim($apiUrl, '/') . '/jsonrpc/v1';
        $this->groupId = $groupId;
        $this->groupKey = $groupKey;
        $this->timeout = $timeout;
    }

    protected function call($method, $params, $timeout=null) {
        $data = array( 
            'id' => 1,
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
        );
        $opts = 0;
        if (version_compare(phpversion(), '5.4', '>=')) {
            $opts |= JSON_UNESCAPED_UNICODE;
        }
        $json = json_encode($data, $opts);
        if (false === $json) {
            throw new \Exception("Can't encode json");
        }
        if ($timeout === null) {
            $timeout = $this->timeout;
        }
        $result = $this->getSender()->send(
            $this->apiUrl,
            $this->groupId,
            $this->groupKey,
            $json,
            $timeout
        );
        $response = json_decode($result, true);
        if ($response) {
            if (!empty($response['error'])) {
                throw new \Exception($response['error']['message'], $response['error']['code']);
            }
            if (isset($response['result'])) {
                return $response['result'];
            }
        }
        throw new \Exception("JSON parsing error: " + json_last_error());
    }

    public function setSender(Sender $sender) {
        $this->sender = $sender;
    }

    public function getSender() {
        if (!$this->sender) {
            $this->sender = new CurlSender();
        }
        return $this->sender;
    }
}
