<?php 

// add helper functions here
// call from other files like this WCWPG/function_name()

namespace WCWPG;

function pr( $array ) {
	echo '<pre>';
	print_r($array);
	echo '</pre>';
}

function api_call() {
	$step1_args = [
		'schemaVersion'           => 1.0,
		'requestId'               => 'R17100517154655',
		'timestamp'               => '1625671859055',
		'channelName'             => 'WEB',
		'serviceName'             => 'HPP_PURCHASE',
		'serviceParams' => [
			'storeId'                => '1000159',
			'hppKey'                 => 'HPP-1062710944',
			'merchantUid'            => 'M0910136',
			'hppSuccessCallbackUrl'  => 'http://localhost/wc/',
			'hppFailureCallbackUrl'  => 'http://localhost/wc/',
			'hppRespDataFormat'      => 2,
			'paymentMethod'          => 'CREDIT_CARD',
			'payerInfo' => [
				'subscriptionId'        => '123456781'
			],
			'transactionInfo' => [
				'referenceId'           => 'wcwpg255',
				'invoiceId'             => 'inv255',
				'amount'                => 1500.00,
				'currency'              => 'USD',
				'description'           => 'Test Description',
			],
		],
	];

	$step1_res = wp_remote_post(
		'https://stagingsandbox.safarifoneict.com/asm',
		[
			'headers'      => [ 'Content-Type' => 'application/json; charset=utf-8' ],
			'body'         => wp_json_encode( $step1_args ),
			'method'       => 'POST',
			'timeout'      => 60,
			'redirection'  => 5,
			'blocking'     => true,
		],
	);

	$code        = wp_remote_retrieve_response_code( $step1_res );
	$step1_data  = json_decode( wp_remote_retrieve_body( $step1_res ) );
	//pr($step1_data);

	$hppurl        = $step1_data->params->hppUrl;
	$hpprequestid  = $step1_data->params->hppRequestId;
	$referenceid   = $step1_data->params->referenceId;

	$step2_args = [
		'method'       => 'POST',
		'timeout'      => 60,
		'redirection'  => 5,
		'blocking'     => true,
		'body'        => [
			'hppRequestId' => $hpprequestid,
			'referenceId'  => $referenceid
		],
	];

	$response = wp_remote_post( $hppurl, $step2_args );
	//unset($response['body']);
	//pr($response);
	return $response['body'];

	if ( ! is_wp_error( $response ) ) {
		// return error message
	}

	//$code = wp_remote_retrieve_response_code( $response );

	if (200 !== $code) {
		//return (object) [ 'status' => 'error', 'message' => 'Response code is not 200' ];
	}

	//return (object) [ 'status' => 'success', 'data' => json_decode( wp_remote_retrieve_body( $response ) ) ];
}

