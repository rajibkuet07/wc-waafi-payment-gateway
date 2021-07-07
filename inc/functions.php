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
		'requestId'               => 'R17100517154423',
		'timestamp'               => '1625657184',
		'channelName'             => 'WEB',
		'serviceName'             => 'HPP_PURCHASE',
		'serviceParams' => [
			'storeId'                => '20001179',
			'hppKey'                 => 'API-835711313AHX',
			'merchantUid'            => 'M0910298',
			'hppSuccessCallbackUrl'  => 'http://localhost/wc/waafi-success',
			'hppFailureCallbackUrl'  => 'http://localhost/wc/waafi-error',
			'hppRespDataFormat'      => 2,
			'paymentMethod'          => 'CREDIT_CARD/MWALLET_ACCOUNT/MWALLET_BANKACCOUNT',
			'payerInfo' => [
				'subscriptionId'        => '123456789'
			],
			'transactionInfo' => [
				'referenceId'           => '102101asa',
				'invoiceId'             => '125125ca',
				'amount'                => 500.00,
				'currency'              => 'USD',
				'description'           => 'Test Description',
			],
		],
	];

	$step1_res = wp_remote_post( 'https://sandbox.safarifoneict.com/asm', json_encode( $step1_args ) );

	$code = wp_remote_retrieve_response_code( $step1_res );
	$step1_data = json_decode( wp_remote_retrieve_body( $step1_res ) );
	if ( 200 !== $code ) {
		return (object) [ 'status' => 'error', 'data' => $step1_data ];
	}

	$hppurl = $step1_data['params']['hppUrl'];
	$hpprequestid = $step1_data['params']['hppRequestId'];
	$referenceid = $step1_data['params']['referenceId'];

	$step2_args = [
		'body'        => array(
			'hppRequestId' => $hpprequestid,
			'referenceId'  => $referenceid
		),
	];

	$response = wp_remote_post( $hppurl, $step2_args );

	// if( ! is_wp_error( $response ) ) {
	// 	$code = wp_remote_retrieve_response_code( $response );

	// 	if (200 !== $code) {
	// 		return (object) [ 'status' => 'error', 'message' => 'Response code is not 200' ];
	// 	}
	// }

	return (object) [ 'status' => 'success', 'data' => json_decode( wp_remote_retrieve_body( $response ) ) ];
}

