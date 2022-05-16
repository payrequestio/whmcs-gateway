<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");
	
/**
 * Generate API url
 *
 * @param string $call
 *
 * @return string
 */
function payrequest_ApiUrl(string $call = '/'): string
{
	$baseurl = 'https://api.payrequest.io';
	if(substr($call, 0, 1)!=='/')
		$call = '/'.$call;
	return $baseurl.trim($call);
}

/**
 * Define module related meta data.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function payrequest_MetaData(): array
{
    return [
        'DisplayName' => 'PayRequest Gateway Module',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * @param string $func
 * @param array  $variables
 * @param string $error
 * @param        $response
 * @param array  $replacements
 *
 * @return void
 */
function payrequest_Log(string $func = '', array $variables = [], string $error = '', $response = '', array $replacements = [])
{
	try{
		logModuleCall('payrequest-gateway-module', $func, $variables, $error, $response, $replacements);
	}catch(Exception $e){
		try{
			logActivity($error);
		}catch(Exception $e){}
	}
}

/**
 * @param string $url
 * @param array  $variables
 *
 * @return object|null
 */
function payrequest_ApiCall(string $url = '/', array $variables = []): ?object
{
	$url = payrequest_ApiUrl($url);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($variables));
	$response = curl_exec($ch);
	if (curl_error($ch)){
		payrequest_Log(__FUNCTION__, $variables, '"' . $url . '" returned the following error: ' . curl_error($ch));
		return null;
	}
	curl_close($ch);
	/** @var object $data */
	$data = json_decode($response);
	return is_object($data)?$data:null;
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function payrequest_config(): array
{
    return [
	    'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PayRequest Gateway Module',
	    ],
	    'apiKey' => [
            'FriendlyName' => 'Api Key',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Description' => 'Enter your PayRequest V2 api key here',
	    ],
	    'testMode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
	    ]
    ];
}

/**
 * Payment link.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function payrequest_link(array $params = []): string
{
    // Gateway Configuration Parameters
    $apiKey = $params['apiKey'];
    $testMode = $params['testMode'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

	$data = [
		'token' => $params['apiKey'],
		'type' => 'paymentlink',
		'title' => $invoiceId,
		'amount' => $amount,
		'currency', $currencyCode,
		'name' => $firstname." ".$lastname,
		'email' => $email,
		'description' => $description,
		'return' => 'response',
		'response' => $returnUrl,
		'testmode' => $testMode,
		'website' => $systemUrl,
		'callback' => $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php'
	];
	$call = payrequest_ApiCall('/create', $data);
	try{
		$url = $call->url;
	}catch(Exception $e){
		payrequest_Log(__FUNCTION__, $data, $e->getMessage());
		return false;
	}

    $htmlOutput = '<form method="get" action="' . $url . '">';
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

/**
 * Refund transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
/*function payrequest_refund(array $params = []): array
{
    // Gateway Configuration Parameters
    $apiKey = $params['apiKey'];
    $testMode = $params['testMode'];

    // Transaction Parameters
    $transactionIdToRefund = $params['transid'];
    $refundAmount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    // perform API call to initiate refund and interpret result

    return [
        // 'success' if successful, otherwise 'declined', 'error' for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => $responseData,
        // Unique Transaction ID for the refund transaction
        'transid' => $refundTransactionId,
        // Optional fee amount for the fee value refunded
        'fees' => $feeAmount,
    ];
}*/

