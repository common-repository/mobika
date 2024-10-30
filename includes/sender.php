<?php

namespace MobikaWP;

class Sender implements \Mobika\Api\Sender
{
	public function send($apiUrl, $groupId, $groupKey, $data, $timeout)
    {
        $body = $data;
        $args = array(
		    'body' => $body,
		    'timeout' => $timeout,
		    'redirection' => '5',
		    'httpversion' => '1.1',
		    'blocking' => true,
		    'headers' => array(
		    	"Content-Type" => "application/json",
		    	"Accept" => "application/json",
		    	'Authorization' => 'Basic ' . base64_encode( $groupId . ':' . $groupKey )
		    ),
		    'cookies' => array()
		);
		$response = wp_remote_post( $apiUrl, $args );
		if (is_wp_error($response)) {
			throw new \Exception($response->get_error_message());
		}
		return $response["body"];
    }

}