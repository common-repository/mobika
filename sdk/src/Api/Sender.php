<?php
namespace Mobika\Api;

interface Sender {
    public function send($apiUrl, $groupId, $groupKey, $data, $timeout);
}
