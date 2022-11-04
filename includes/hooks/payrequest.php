<?php
/**
 * Payrequest - Add $payrequest_payment_link to mail templates
 *
 * Simply use {$payrequest_payment_link} in your mail templates to show the payrequest mail template
 *
 * @package    PayRequest
 * @author     PayRequest <info@payrequest.io>
 * @copyright  Copyright (c) PayRequest 2022 - onwards
 * @license    UNLICENSED
 * @version    $Id$
 * @link       https://payrequest.io/
 */

	if (!defined("WHMCS"))
	    die("This file cannot be accessed directly");
	
	/**
	 * @param string $call
	 *
	 * @return string
	 */
	function hook_payrequest_ApiUrl(string $call = '/'): string
	{
		$baseurl = 'https://api.payrequest.io';
		if(substr($call, 0, 1)!=='/')
			$call = '/'.$call;
		return $baseurl.trim($call);
	}
	
	/**
	 * @param string $url
	 * @param array  $variables
	 *
	 * @return false|object
	 */
	function hook_payrequest_ApiCall(string $url = '/', array $variables = [])
	{
		$url = hook_payrequest_ApiUrl($url);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($variables));
		$response = curl_exec($ch);
		if (curl_error($ch)){
			/*payrequest_Log(__FUNCTION__, $variables, '"' . $url . '" returned the following error: ' . curl_error($ch));
			return null;*/
			return false;
		}
		curl_close($ch);
		/** @var object $data */
		$data = json_decode($response);
		return is_object($data)?$data:false;
	}
	
	/**
	 * @param array $vars
	 *
	 * @return array
	 */
	function add_pr_payment_link(array $vars = []): array
	{
		$merge_fields = [];
		if (in_array($vars['messagename'],['Third Invoice Overdue Notice','Second Invoice Overdue Notice','Invoice Payment Reminder','Invoice Created','First Invoice Overdue Notice'])) {
			if( $vars['mergefields']['invoice_payment_method']==='PayRequest' ){
				try{
					$re = '/(https\:\/\/link.payrequest.io\/[a-z0-9]+)/mi';
					preg_match($re, $vars['mergefields']['invoice_payment_link'], $matches, PREG_OFFSET_CAPTURE, 0);
					$merge_fields['payrequest_payment_link'] = $matches[0][0];
				}catch(Exception $e){
					$merge_fields['payrequest_payment_link'] = $vars['mergefields']['invoice_payment_link'];
				}
			}else{
				global $CONFIG;
				require_once ROOTDIR . '/includes/gatewayfunctions.php';
				if(function_exists('getGatewayVariables')){
					$prSettings = getGatewayVariables('payrequest');
					$re = '/([\d\.\,]+)/mi';
					preg_match($re, $vars['mergefields']['invoice_balance'], $matches, PREG_OFFSET_CAPTURE, 0);
					$client = localAPI('GetClientsDetails',['clientid'=>$vars['mergefields']['client_id']]);
					$data = [
						'token' => $prSettings['apiKey'],
						'type' => 'paymentlink',
						'title' => $vars['relid'],
						'amount' => $matches[0][0],
						'currency'=> $client['client']['currency_code'],
						'name' => $vars['mergefields']['client_name'],
						'email' => $vars['mergefields']['client_email'],
						'description' => 'Invoice - #'.$vars['relid'],
						'return' => 'response',
						'testmode' => $prSettings['testMode'],
						'website' => $CONFIG['SystemURL'],
						'callback' => $CONFIG['SystemURL'] . '/modules/gateways/callback/payrequest.php'
					];
					
					$call = hook_payrequest_ApiCall('/create', $data);
					if($call===false)
						$merge_fields['payrequest_payment_link'] = '';
					else
						$merge_fields['payrequest_payment_link'] = $call->url;
				}else
					$merge_fields['payrequest_payment_link'] = '';
			}
			if(isset($merge_fields['payrequest_payment_link'])&&!empty($merge_fields['payrequest_payment_link'])){
				$merge_fields['payrequest_payment_link_tag'] = '<a href="' . $merge_fields['payrequest_payment_link'] . '" target="_blank">' . $merge_fields['payrequest_payment_link'] . '</a>';
				$merge_fields['payrequest_qr_image'] = 'https://liveapi.payrequest.io/default/qrcode/'.str_ireplace('https://','',$merge_fields['payrequest_payment_link']);
				$merge_fields['payrequest_qr_image_tag'] = '<img src="' . $merge_fields['payrequest_qr_image'] . '" alt="Link to ' . $merge_fields['payrequest_payment_link'] . '" title="QR code to pay your invoice easily."/>';
			}
		}
		return $merge_fields;
	}
	
	add_hook("EmailPreSend",1,"add_pr_payment_link");
	add_hook("EmailTplMergeFields",1, function ($vars){
		$merge_fields = [];
		$merge_fields['payrequest_payment_link'] = "PayRequest Payment Link";
		$merge_fields['payrequest_payment_link_tag'] = "PayRequest Payment Link + Tag";
		$merge_fields['payrequest_qr_image'] = "PayRequest QR image";
		$merge_fields['payrequest_qr_image_tag'] = "PayRequest QR image + Tag";
		return $merge_fields;
	});
