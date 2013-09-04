<?php

/**
 * Description of PurchaseAWSFPS
 *
 * @author gambit
 */
class PurchaseAWSFPS
{
    static public function authorize($username, $amount_cent, $callback)
    {
        $amount = number_format(round(($amount_cent/100),'2'),'2');
        $reference = Utilites::generate_sequence(128);
        $pipeline = new Amazon_FPS_CBUIMultiUsePipeline(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
        $pipeline->setMandatoryParameters($reference, $callback, $amount);
        
        //optional parameters
        //$pipeline->setUsageLimit1("Amount", "10", "6 Months");
        //$pipeline->addParameter("usageLimitPeriod1", "1 Hour");

        PurchaseAWSDB::saveAuthorize($reference, $username, $amount_cent);
        
        $auth_url = $pipeline->getUrl();
        return $auth_url;
    }
    
    static public function validate(
            $username, $tokenID, 
            $signature, $expiry, 
            $signatureVersion, $signatureMethod, $certificateUrl, 
            $status, $callerReference, $callback)
    {
        $params = array();
        $params["signature"]        = $signature;
	$params["expiry"]           = $expiry;
	$params["signatureVersion"] = $signatureVersion;
	$params["signatureMethod"]  = $signatureMethod;
	$params["certificateUrl"]   = $certificateUrl;
	$params["tokenID"]          = $tokenID;
	$params["status"]           = $status;
	$params["callerReference"]  = $callerReference;
          
        $utils = new Amazon_FPS_SignatureUtilsForOutbound();
        $valid = $utils->validateRequest($params, $callback, "GET");

        return $valid;
        
        /*$date = DateTime::createFromFormat("m/Y/d", $params["expiry"]."/1");
        if ($date==false)
        {
            Logger::log(LOG_ERR,"Invalid date");
            return false;
        }*/
        //$date->format('Y-m-d') ."\n";
    }
    
     static public function validateIPN($post,$callback)
     {
        Logger::log(LOG_INFO,"IPN:: Start validation ...");
        $utils = new Amazon_FPS_SignatureUtilsForOutbound();
        $valid = $utils->validateRequest($post, $callback, "POST");
        return $valid;
     }
    
    
    static public function purchase($token, $purchaselist)
    {
        $service = new Amazon_FPS_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
        
        $response = array();
        foreach ($purchaselist as $key => $purchase)
        {
            $info = self::pay($service, $token, $purchase);
            
            Logger::log(LOG_INFO,"PAY RESULT: [".print_r($info,true)."]");
            
            if ($info["status_code"] != 0)
            {    
                $response[$key] = array(
                    "status_code"   => AWS_ValidPurchase, 
                    "status"        => $info['status'], 
                    "purchaseid"    => $info['purchaseid']);
            } else {
                $response[$key] = array(
                    "status_code"   => AWS_InvalidPurchase, 
                    "status"        => $info['error']['ErrorCode']);
                Logger::log(LOG_ERR,"INVALID PAY RESULT: [".print_r($info,true)."]");
            }
        }
        return $response;
    }

    static private function pay($service, $token, $purchase)
    {
        if (!isset($purchase['name'], $purchase['coast']))
        {
            return false;
        }
        $coast = number_format(round(($purchase['coast']/100),'2'),'2');
        $amount = new Amazon_FPS_Model_Amount(array("CurrencyCode" => "USD", "Value" => $coast));
        
        $reference = Utilites::generate_sequence(128);
        
        $request = new Amazon_FPS_Model_PayRequest();
        $request->setCallerReference($reference)
                ->setSenderTokenId($token)
                ->setSenderDescription($purchase['name'])
                ->setTransactionAmount($amount);
        
        $result = array("status_code" => 0);
        try
        {
            $response = $service->pay($request);
            
            if ($response->isSetPayResult())
            {
                $payResult = $response->getPayResult();
                if ($payResult->isSetTransactionId())
                {
                    $transaction = $payResult->getTransactionId();
                    
                    if ($payResult->isSetTransactionStatus())
                    {
                        $result['status'] = $payResult->getTransactionStatus();
                        $result['status_code'] = 200;
                        
                        //Add purchase to DB
                        $pid = Utilites::generate_sequence(32);
                        PurchaseAWSDB::savePurchase($pid, $reference, $token, $purchase['name'], $purchase['coast'], $transaction, $result['status']);
                        $result['purchaseid'] = $pid;
                    }
                }
            }
            if ($response->isSetResponseMetadata())
            {
                $responseMetadata = $response->getResponseMetadata();
                if ($responseMetadata->isSetRequestId())
                {
                    $result['request'] = $responseMetadata->getRequestId();
                }
            }
        }
        catch (Amazon_FPS_Exception $ex)
        {
            $result['error']['Message'] = $ex->getMessage();
            $result['error']['StatusCode'] = $ex->getStatusCode();
            $result['error']['ErrorCode'] = $ex->getErrorCode();
            $result['error']['Type'] = $ex->getErrorType();
            $result['error']['Request'] = $ex->getRequestId();
            $result['error']['XML'] = $ex->getXML();
            $result['error']['Trace'] = print_r($ex->getTrace(),true);
        }
        return $result;
    }

    
    static public function refund($username, $transaction)
    {
        $reference = Utilites::generate_sequence(128);
        
        $request = new Amazon_FPS_Model_RefundRequest();
        $request    ->setCallerReference($reference)
                    ->setTransactionId($transaction);
        
        $service = new Amazon_FPS_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY);
        
        $result = array("status_code" => 0);
        try
        {
            $response = $service->refund($request);

            if ($response->isSetRefundResult())
            {
                $refundResult = $response->getRefundResult();
                if ($refundResult->isSetTransactionId())
                {
                    $refund_transaction = $refundResult->getTransactionId();
                
                    if ($refundResult->isSetTransactionStatus())
                    {
                        $result['status'] = $refundResult->getTransactionStatus();
                        $result['status_code'] = 200;
                        PurchaseAWSDB::refundPurchase($transaction);
                        PurchaseAWSDB::saveRefund($reference, $username, $transaction, $result['status']);
                        $result['pid'] = $reference;
                    }
                }
            }
            if ($response->isSetResponseMetadata())
            {
                $responseMetadata = $response->getResponseMetadata();
                if ($responseMetadata->isSetRequestId())
                {
                    $result['request'] = $responseMetadata->getRequestId();
                }
            }
        }
        catch (Amazon_FPS_Exception $ex)
        {
            $result['error']['Message'] = $ex->getMessage();
            $result['error']['StatusCode'] = $ex->getStatusCode();
            $result['error']['ErrorCode'] = $ex->getErrorCode();
            $result['error']['Type'] = $ex->getErrorType();
            $result['error']['Request'] = $ex->getRequestId();
            $result['error']['XML'] = $ex->getXML();
            $result['error']['Trace'] = print_r($ex->getTrace(),true);
        }
        return $result;
    }
}