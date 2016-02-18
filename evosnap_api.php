<?php

/* Copyright (c) 2015 EVO Payments International - All Rights Reserved.
*
* This software and documentation is subject to and made
* available only pursuant to the terms of an executed license
* agreement, and may be used only in accordance with the terms
* of said agreement. This software may not, in whole or in part,
* be copied, photocopied, reproduced, translated, or reduced to
* any electronic medium or machine-readable form without
* prior consent, in writing, from EVO Payments International, INC.
*
* Use, duplication or disclosure by the U.S. Government is subject
* to restrictions set forth in an executed license agreement
* and in subparagraph (c)(1) of the Commercial Computer
* Software-Restricted Rights Clause at FAR 52.227-19; subparagraph
* (c)(1)(ii) of the Rights in Technical Data and Computer Software
* clause at DFARS 252.227-7013, subparagraph (d) of the Commercial
* Computer Software--Licensing clause at NASA FAR supplement
* 16-52.227-86; or their equivalent.
*
* Information in this software is subject to change without notice
* and does not represent a commitment on the part of EVO Payments International.
* 
* Sample Code is for reference Only and is intended to be used for educational purposes. It's the responsibility of 
* the software company to properly integrate into thier solution code that best meets thier production needs. 
*
* @author Antonio Molinero <antonio.molinero@evopayments.com>
* @copyright Copyright (c) 2015 EVO Snap* (http://www.evosnap.com)
* @license	EVO Payments International EULA
*/

class EvoSnapApi
{
	
	/**
	 * Retrieves orders.
	 * @param array $params request parameters.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return array the orders.
	 */
	public static function getOrders($params, $cfg){
		$aPost = EvoSnapApi::getEvoSnapOrdersPost($params, $cfg->code, $cfg->key);
		$orders = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($orders)) {
			return $orders['orders'];
		}
	}
	
	private static function getEvoSnapOrdersPost($params, $code, $key){
		$aPost = array(
				'action' => 'get_orders',
				'code' => $code,
				'return' => 'json'
		);
		$aPost['mac'] = EvoSnapApi::getOrdersMac($aPost, $key);
		$aPost = array_merge($aPost, $params);
		return $aPost;
	}
	
	private static function getOrdersMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}
	
	/**
	 * Retrieves an order.
	 * @param string $id the order ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return mixed the order.
	 */
	public static function getOrder($id, $cfg){
		$aPost = EvoSnapApi::getEvoSnapOrderPost($id, $cfg->code, $cfg->key);
		$order = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($order)) {
			$raw_order = $order['order'];
			EvoSnapApi::setOrderTransactionData($raw_order);
			return $raw_order;
		}
	}
	
	private static function setOrderTransactionData($raw_order){
		$total_refunded = 0;
		if(isset($raw_order->transactions) && is_array($raw_order->transactions)){
			foreach($raw_order->transactions as $txn){
				if($txn->txn_action === 'sale'){
					$raw_order->payment_transaction = $txn;
				}elseif($txn->txn_action === 'credit'){
					$total_refunded += $txn->txn_amount;
				}
			}
		}
		
		$raw_order->total_refunded = $total_refunded;
	}
	
	/**
	 * Gets order status.
	 * @param string $id the order ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return string the order status.
	 */
	public static function getOrderStatus($id, $cfg){
		$order = EvoSnapApi::getOrder($id, $cfg);
		
		if ($order != null) {
			$result = $order->status;
		} else {
			$result = null;
		}
		
		return $result;
	}
	
	private static function getEvoSnapOrderPost($id, $code, $key){
		$aPost = array(
				'action' => 'get_order',
				'code' => $code,
				'merchant_order_id' => $id,
				'return' => 'json'
		);
		$aPost['mac'] = EvoSnapApi::getOrderMac($aPost, $key);
		
		return $aPost;
	}
	
	private static function getOrderMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action'],
				'merchant_order_id' => $aPost['merchant_order_id']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}
	
	/**
	 * Gets checkout URL from Hosted Payments.
	 * @param SnapCheckoutAbstract $order the order checkout.
	 * @param float $trigger3ds value that triggers 3D secure.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return string the checkout URL.
	 */
	public static function getCheckoutUrl($order, $trigger3ds, $cfg) {
		$aPost = EvoSnapApi::getEvoSnapCehckoutPost($order, $trigger3ds, $cfg->code, $cfg->key);
		$result = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(true), $cfg->environment);
		if (EvoSnapApi::checkResult($result)) {
			return $result ['url'];
		}
	}
	
	/**
	 * 
	 * @param SnapCheckoutAbstract $checkout
	 * @param float $trigger3ds
	 * @param string $code
	 * @param string $key
	 * @return array
	 */
	private static function getEvoSnapCehckoutPost($checkout, $trigger3ds, $code, $key){
		$returnUrl = $checkout->return_url;
		$cancelUrl = $checkout->cancel_url;
		
		$customer = $checkout->customer;
		$aCustomer = array(
			'merchant_customer_id' => $customer->id,
			'first_name' =>EvoSnapTools::getString($customer->first_name),
			'last_name' => EvoSnapTools::getString($customer->last_name),
			'phone' => EvoSnapTools::getString($customer->phone, 15),
			'email' => EvoSnapTools::getString($customer->email, 50)
		);
		
		$aPost = array(
				'action' => $checkout->getAction(),
				'code' => $code,
				'customer' => $aCustomer,
				'return_url' => $returnUrl,
				'cancel_url' => $cancelUrl,
				'auto_return' => EvoSnapTools::getBoolean($checkout->auto_return),
				'checkout_layout' => $checkout->checkout_layout,
				'language' => $checkout->language,
		        'create_token' => EvoSnapTools::getBoolean($checkout->create_token)
		);
		
		if(property_exists($checkout, 'order') && !empty($checkout->order)){
		  $aPost = array_merge($aPost, EvoSnapApi::getOrderPost($checkout->order, $trigger3ds));
		}
		if(property_exists($checkout, 'subscription') && !empty($checkout->subscription)){
		   $aPost = array_merge($aPost, EvoSnapApi::getSubscriptionPost($checkout->subscription, $trigger3ds));
		}
		
		$aPost['mac'] = EvoSnapApi::getCheckoutMac($aPost, $key);
		
		return $aPost;
	}
	
	/**
	 * Gets order post
	 * @param SnapOrder $order
	 * @param float $trigger3ds
	 * @return array
	 */
	private static function getOrderPost($order, $trigger3ds){
	    $aOrder = array(
	        'merchant_order_id' => EvoSnapTools::getString($order->id, 255),
	        'total_subtotal' => EvoSnapTools::getNumber($order->total_subtotal),
	        'total_tax' => EvoSnapTools::getNumber($order->total_tax),
	        'total_shipping' => EvoSnapTools::getNumber($order->total_shipping),
	        'total_discount' => EvoSnapTools::getNumber($order->total_discount),
	        'total' => EvoSnapTools::getNumber($order->total),
	        'ship_method' => EvoSnapTools::getString($order->ship_method),
	        'currency_code' => EvoSnapTools::getString($order->currency_code),
	        'enable_3d' => EvoSnapTools::getBoolean(isset($trigger3ds) && ($order->total >= $trigger3ds))
	    );
	    
	    $aOrderLines = array();
	    $products = $order->lines;
	    for($i = 0; $i < count($products); $i++) {
	        $aOrderLines[$i] = EvoSnapApi::getOrderItem($products[$i]);
	    }
	    
	    $aOrder = array_merge($aOrder, EvoSnapTools::getAddress('billto', $order->billto_address));
	    if(isset($order->shipto_address)){
	        $aOrder = array_merge($aOrder, EvoSnapTools::getAddress('shipto', $order->shipto_address));
	    }
	    
	    return array('order' => $aOrder, 'order_item' => $aOrderLines);
	}
	
	/**
	 * Gets subscription post
	 * @param SnapSubscription $subscription
	 * @param float $trigger3ds
	 * @return array
	 */
	private static function getSubscriptionPost($subscription, $trigger3ds){
	    $aSubscription = array(
	        'merchant_subscription_id' => EvoSnapTools::getString($subscription->id, 255),
	        'interval_length' => $subscription->interval_length,
	        'interval_unit' => $subscription->interval_unit,
	        'start_date' => $subscription->start_date,
	        'total_occurrences' => $subscription->total_occurrences,
	        'trial_occurrences' => $subscription->trial_occurrences,
	        'auto_process' => $subscription->auto_process,
	        'total_subtotal' => EvoSnapTools::getNumber($subscription->total_subtotal),
	        'total_tax' => EvoSnapTools::getNumber($subscription->total_tax),
	        'total_shipping' => EvoSnapTools::getNumber($subscription->total_shipping),
	        'total_discount' => EvoSnapTools::getNumber($subscription->total_discount),
	        'total' => EvoSnapTools::getNumber($subscription->total),
	        'trial_amount' => EvoSnapTools::getNumber($subscription->trial_amount),
	        'ship_method' => EvoSnapTools::getString($subscription->ship_method),
	        'currency_code' => EvoSnapTools::getString($subscription->currency_code),
	        'enable_3d' => EvoSnapTools::getBoolean(isset($trigger3ds) && ($subscription->total >= $trigger3ds))
	    );
	    
	    if($subscription->trial_occurrences && ($subscription->trial_occurrences > 0)){
	        $aSubscription['trial_occurrences'] = $subscription->trial_occurrences;
	        $aSubscription['trial_amount'] = $subscription->trial_amount;
	    }
	    
	    $aSubscriptionLines = array();
	    $products = $subscription->lines;
	    for($i = 0; $i < count($products); $i++) {
	        $aSubscriptionLines[$i] = EvoSnapApi::getOrderItem($products[$i]);
	    }
	    
	    $aSubscription = array_merge($aSubscription, EvoSnapTools::getAddress('billto', $subscription->billto_address));
	    if(isset($subscription->shipto_address)){
	        $aSubscription = array_merge($aSubscription, EvoSnapTools::getAddress('shipto', $subscription->shipto_address));
	    }
	    
	    return array('sub' => $aSubscription, 'sub_item' => $aSubscriptionLines);
	}
	
	/**
	 * @param OrderLine $orderItem
	 * @return array
	 */
	private static function getOrderItem($orderItem){
		return array(
			'sku' => EvoSnapTools::getString($orderItem->sku, 25),
			'name' => EvoSnapTools::getString($orderItem->name, 30),
			'description' => EvoSnapTools::getString($orderItem->description, 300),
			'qty' => EvoSnapTools::getNumber($orderItem->qty),
			'price' => EvoSnapTools::getNumber($orderItem->price),
			'tax' => EvoSnapTools::getNumber($orderItem->tax)
		);
	}
	
	private static function getCheckoutMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'email' => $aPost['customer']['email']
		);
		
		if(array_key_exists('order', $aPost)){
		    $aOrderMACParams = array(
		        'order_total_subtotal' => $aPost['order']['total_subtotal'],
		        'order_total' => $aPost['order']['total'],
				'merchant_order_id' => $aPost['order']['merchant_order_id']
		    );
		    
		    $aMACParams = array_merge($aMACParams, $aOrderMACParams);
		}
		
		if(array_key_exists('sub', $aPost)){
		    $aSubMACParams = array(
		        'sub_total_subtotal' => $aPost['sub']['total_subtotal'],
		        'sub_total_occurrences' => $aPost['sub']['total_occurrences'],
		        'sub_total' => $aPost['sub']['total'],
		        'sub_trial_amount' => $aPost['sub']['trial_amount'],
		        'sub_trial_occurrences' => $aPost['sub']['trial_occurrences'],
		        'merchant_subscription_id' => $aPost['sub']['merchant_subscription_id']
		    );
		    
		    $aMACParams = array_merge($aMACParams, $aSubMACParams);
        }
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}

	/**
	 * Credits an order.
	 * @param string $id the order ID.
	 * @param string $txnId the transaction ID.
	 * @param float $amount the amount to credit, null value defaults to original transaction amount.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return string the transaction ID.
	 */
	public static function creditOrder($id, $txnId, $amount, $cfg){
		$aPost = EvoSnapApi::getEvoSnapCreditOrderPost($id, $txnId, $amount,
				$cfg->code, $cfg->key);
		$credit = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($credit)) {
			return $credit['txn_id'];
		}
	}

	private static function getEvoSnapCreditOrderPost($id, $txnId, $amount, $code, $key){
		$aPost = array(
				'action' => 'credit',
				'code' => $code,
				'merchant_order_id' => $id,
				'txn_id' => $txnId,
				'return' => 'json'
		);
		$aPost['mac'] = EvoSnapApi::getCreditOrderMac($aPost, $key);
		
		if($amount != null){
			$aPost['amount'] = $amount;
		}
		
		return $aPost;
	}
	
	private static function getCreditOrderMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action'],
				'merchant_order_id' => $aPost['merchant_order_id'],
				'txn_id' => $aPost['txn_id']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}

	/**
	 * Retrieves callbacks.
	 * @param array $params request parameters.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return array the callback list.
	 */
	public static function getCallbacks($params, $cfg){
	    $aPost = EvoSnapApi::getEvoSnapCallbacksPost($params, $cfg->code, $cfg->key);
	    $orders = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
	    if (EvoSnapApi::checkResult($orders)) {
	        return $orders['results'];
	    }
	}
	
	private static function getEvoSnapCallbacksPost($params, $code, $key){
	    $aPost = array(
	        'action' => 'get_callbacks',
	        'code' => $code,
	        'return' => 'json'
	    );
	    $aPost['mac'] = EvoSnapApi::getCallbacksMac($aPost, $key);
	    $aPost = array_merge($aPost, $params);
	    return $aPost;
	}
	
	private static function getCallbacksMac($aPost, $key){
	    $aMACParams = array(
	        'code' 	=> $aPost['code'],
	        'action' => $aPost['action']
	    );
	
	    return EvoSnapTools::getMac($aMACParams, $key);
	}
	
	/**
	 * Retrieves an subscription.
	 * @param string $id the subscription ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return mixed the subscription.
	 */
	public static function getSubscription($id, $cfg){
		$aPost = EvoSnapApi::getEvoSnapSubscriptionPost($id, 'get_subscription', $cfg->code, $cfg->key);
		$subscription = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($subscription)) {
			return $subscription['subscription'];
		}
	}
	
	/**
	 * Gets subscription status.
	 * @param string $id the subscription ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return string the order status.
	 */
	public static function getSubscriptionStatus($id, $cfg){
		$subscription = EvoSnapApi::getSubscription($id, $cfg);
		
		if ($subscription != null) {
			$result = $subscription->status;
		} else {
			$result = null;
		}
		
		return $result;
	}
	
	/**
	 * Performs a subscription payment process against an active,
	 * manually processed subscription with a payment due to process.
	 * @param string $id the subscription ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @param string $order_id (optional) order ID, will be auto-generated if omitted amount.
	 * @return array array containing the resulting 'merchant_order_id' and the 'txn_id' of the
	 * payment.
	 */
	public static function processSubscription($id, $cfg, $order_id = null){
		$aPost = EvoSnapApi::getEvoSnapSubscriptionPost($id, 'process_subscription',
				$cfg->code, $cfg->key);
		$aPost['merchant_order_id'] = $order_id;
		$payment = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($payment)) {
			return array(
			    'merchant_order_id' => $payment['merchant_order_id'],
			    'txn_id' => $payment['txn_id']
			);
		}
	}
	
	/**
	 * Suspends a subscription.
	 * @param string $id the subscription ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return boolean <code>true</code> if the accion is successfully completed.
	 */
	public static function suspendSubscription($id, $cfg){
		$aPost = EvoSnapApi::getEvoSnapSubscriptionPost($id, 'suspend_subscription',
				$cfg->code, $cfg->key);
		$suspend = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		return EvoSnapApi::checkResult($suspend);
	}
	
	/**
	 * Resumes a subscription.
	 * @param string $id the subscription ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return boolean <code>true</code> if the accion is successfully completed.
	 */
	public static function resumeSubscription($id, $cfg){
		$aPost = EvoSnapApi::getEvoSnapSubscriptionPost($id, 'resume_subscription',
				$cfg->code, $cfg->key);
		$resume = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		return EvoSnapApi::checkResult($resume);
	}

	/**
	 * Cancels a subscription.
	 * @param string $id the subscription ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return boolean <code>true</code> if the accion is successfully completed.
	 */
	public static function cancelSubscription($id, $cfg){
		$aPost = EvoSnapApi::getEvoSnapSubscriptionPost($id, 'cancel_subscription',
				$cfg->code, $cfg->key);
		$cancel = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		return EvoSnapApi::checkResult($cancel);
	}

	private static function getEvoSnapSubscriptionPost($id, $action, $code, $key){
		$aPost = array(
				'action' => $action,
				'code' => $code,
				'merchant_subscription_id' => $id,
				'return' => 'json'
		);
		$aPost['mac'] = EvoSnapApi::getSubscriptionMac($aPost, $key);
		
		return $aPost;
	}
	
	private static function getSubscriptionMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action'],
				'merchant_subscription_id' => $aPost['merchant_subscription_id']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}
	
	/**
	 * Gets checkout token
	 * @param SnapToken_Checkout $token the token checkout.
	 * @param boolean $enable3d enable 3D secure.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return string the checkout URL.
	 */
	public static function getTokenCheckoutUrl($token, $enable3d, $cfg) {
		$aPost = EvoSnapApi::getEvoSnapTokenCehckoutPost($token, $enable3d, $cfg->code, $cfg->key);
		$result = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(true), $cfg->environment);
		if (EvoSnapApi::checkResult($result)) {
			return $result ['url'];
		}
	}
	
	/**
	 * 
	 * @param SnapToken_Checkout $checkout
	 * @param boolean $enable3d
	 * @param string $code
	 * @param string $key
	 * @return array
	 */
	private static function getEvoSnapTokenCehckoutPost($checkout, $enable3d, $code, $key){
		$returnUrl = $checkout->return_url;
		$cancelUrl = $checkout->cancel_url;
		
		$customer = $checkout->customer;
		$aCustomer = array(
			'merchant_customer_id' => $customer->id,
			'first_name' =>EvoSnapTools::getString($customer->first_name),
			'last_name' => EvoSnapTools::getString($customer->last_name),
			'phone' => EvoSnapTools::getString($customer->phone, 15),
			'email' => EvoSnapTools::getString($customer->email, 50)
		);
		
		$token = $checkout->token;
		$aToken = array(
			'merchant_token_id' => EvoSnapTools::getString($token->id, 255),
		    'currency_code' => EvoSnapTools::getString($token->currency_code),
		    'auth_amount' => EvoSnapTools::getNumber($token->auth_amount),
			'enable_3d' => EvoSnapTools::getBoolean($enable3d)
		);
		
		$aToken = array_merge($aToken, EvoSnapTools::getAddress('billto', $token->billto_address));
		
		$aPost = array(
				'action' => $checkout->getAction(),
				'code' => $code,
				'customer' => $aCustomer,
				'token' => $aToken,
				'return_url' => $returnUrl,
				'cancel_url' => $cancelUrl,
				'auto_return' => '1',
				'checkout_layout' => $checkout->checkout_layout,
				'language' => $checkout->language
		);
		$aPost['mac'] = EvoSnapApi::getTokenCheckoutMac($aPost, $key);
		
		return $aPost;
	}
	
	private static function getTokenCheckoutMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'email' => $aPost['customer']['email'],
				'merchant_order_id' => $aPost['token']['merchant_token_id']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}

	/**
	 * Retrieves tokens.
	 * @param array $params request parameters.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return array the orders.
	 */
	public static function getTokens($params, $cfg){
		$aPost = EvoSnapApi::getEvoSnapTokensPost($params, $cfg->code, $cfg->key);
		$tokens = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($tokens)) {
			return $tokens['tokens'];
		}
	}
	
	private static function getEvoSnapTokensPost($params, $code, $key){
		$aPost = array(
				'action' => 'get_tokens',
				'code' => $code,
				'return' => 'json'
		);
		$aPost['mac'] = EvoSnapApi::getTokensMac($aPost, $key);
		$aPost = array_merge($aPost, $params);
		return $aPost;
	}
	
	private static function getTokensMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action'],
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}
	
	/**
	 * Retrieves a token.
	 * @param string $id the token ID.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return mixed the token.
	 */
	public static function getToken($id, $cfg){
		$aPost = EvoSnapApi::getEvoSnapTokenPost($id, $cfg->code, $cfg->key);
		$token = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($token)) {
			return $token['token'];
		}
	}
	
	private static function getEvoSnapTokenPost($id, $code, $key){
		$aPost = array(
				'action' => 'get_token',
				'code' => $code,
				'merchant_token_id' => $id,
				'return' => 'json'
		);
		$aPost['mac'] = EvoSnapApi::getTokenMac($aPost, $key);
		
		return $aPost;
	}
	
	private static function getTokenMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action'],
				'merchant_token_id' => $aPost['merchant_token_id']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}

	/**
	 * Process a token order.
	 * @param string $id the token ID.
	 * @param SnapOrder $order the order.
	 * @param HostedPayments $cfg Hosted Payments Configuration.
	 * @return mixed the token.
	 */
	public static function processTokenOrder($id, $order, $cfg){
		$aPost = EvoSnapApi::getEvoSnapProcessTokenOrderPost($id, $order, $cfg->code, $cfg->key);
		$token = EvoSnapTools::callEvoSnap($aPost, $cfg->getUrl(false), $cfg->environment);
		if (EvoSnapApi::checkResult($token)) {
			return $token;
		}
	}
	
	private static function getEvoSnapProcessTokenOrderPost($id, $order, $code, $key){
		$aOrder = array(
			'merchant_order_id' => EvoSnapTools::getString($order->id, 255),
			'total_subtotal' => EvoSnapTools::getNumber($order->total_subtotal),
			'total_tax' => EvoSnapTools::getNumber($order->total_tax),
			'total_shipping' => EvoSnapTools::getNumber($order->total_shipping),
			'total_discount' => EvoSnapTools::getNumber($order->total_discount),
			'total' => EvoSnapTools::getNumber($order->total),
			'ship_method' => EvoSnapTools::getString($order->ship_method)
		);
		
		$aOrderLines = array();
		$products = $order->lines;
		for($i = 0; $i < count($products); $i++) {
			$aOrderLines[$i] = EvoSnapApi::getOrderItem($products[$i]);
		}
		
		$aOrder = array_merge($aOrder, EvoSnapTools::getAddress('billto', $order->billto_address));
		if(isset($order->shipto_address)){
			$aOrder = array_merge($aOrder, EvoSnapTools::getAddress('shipto', $order->shipto_address));
		}
		
        $aPost = array(
            'action' => 'process_token',
            'code' => $code,
            'merchant_token_id' => $id,
            'token_order' => $aOrder,
            'token_order_item' => $aOrderLines,
            'return' => 'json'
        );
        
		$aPost['mac'] = EvoSnapApi::getProcessTokenOrderMac($aPost, $key);
		
		return $aPost;
	}
	
	private static function getProcessTokenOrderMac($aPost, $key){
		$aMACParams = array(
				'code' 	=> $aPost['code'],
				'action' => $aPost['action'],
				'merchant_token_id' => $aPost['merchant_token_id']
		);
		
		return EvoSnapTools::getMac($aMACParams, $key);
	}
	
	private static function checkResult($result){
	    if(is_array($result) && array_key_exists('success', $result)){
	        if($result['success'] != false){
	            return true;
	        }else{
	            throw new HostedPayments_Exception($result['message']);
 	        }
	    }else{
	        throw new HostedPayments_Exception('An error has occurred during communication with EVO Snap*.');
 	    }
	}

}