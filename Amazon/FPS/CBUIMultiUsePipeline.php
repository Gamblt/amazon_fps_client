<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     Amazon_FPS
 *  @copyright   Copyright 2008-2011 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2010-08-28
 */
/******************************************************************************* 
 *    __  _    _  ___ 
 *   (  )( \/\/ )/ __)
 *   /__\ \    / \__ \
 *  (_)(_) \/\/  (___/
 * 
 *  Amazon FPS PHP5 Library
 * 
 */

//require_once('CBUIPipeline.php');

class Amazon_FPS_CBUIMultiUsePipeline extends Amazon_FPS_CBUIPipeline {

    /**
     * @param string $accessKeyId    Amazon Web Services Access Key ID.
     * @param string $secretAccessKey   Amazon Web Services Secret Access Key.
     */
    function Amazon_FPS_CBUIMultiUsePipeline($awsAccessKey, $awsSecretKey) {
        parent::Amazon_FPS_CBUIPipeline("MultiUse", $awsAccessKey, $awsSecretKey);
    }

    /**
     * Set mandatory parameters required for multi use token pipeline.
     */
    function setMandatoryParameters($callerReference, $returnUrl, $globalAmountLimit) {
        $this->addParameter("callerReference", $callerReference);
        $this->addParameter("returnURL", $returnUrl);
        $this->addParameter("globalAmountLimit", $globalAmountLimit);
    }

    /**
     * Set usage limits for multi use token pipeline.
     */
    function setUsageLimit1($limitType, $limitValue, $limitPeriod) {
        $this->addParameter("usageLimitType1", $limitType);
        $this->addParameter("usageLimitValue1", $limitValue);
        if (isset($limitPeriod)) {
            $this->addParameter("usageLimitPeriod1", $limitPeriod);
        }
    }

    /**
     * Set usage limits for multi use token pipeline.
     */
    function setUsageLimit2($limitType, $limitValue, $limitPeriod) {
        $this->addParameter("usageLimitType1", $limitType);
        $this->addParameter("usageLimitValue1", $limitValue);
        if (isset($limitPeriod)) {
            $this->addParameter("usageLimitPeriod1", $limitPeriod);
        }
    }

    /**
     * Set recipient token list for multi use token pipeline.
     */
    function setRecipientTokenList($isRecipientCobranding, $tokens) {
        $this->addParameter("isRecipientCobranding", ($isRecipientCobranding ? "True" : "False"));
        if (!isset($tokens)) return;
		$tokenList = implode(",", $tokens);         
        $this->addParameter("recipientTokenList", $tokenList);
    }

    function validateParameters($parameters) {
        //mandatory parameters for multi use pipeline
        if (!isset($parameters["globalAmountLimit"])) {
            throw new Exception("globalAmountLimit is missing in parameters.");
        }
        
        //conditional parameters for multi use pipeline
        if (isset($parameters["isRecipientCobranding"]) and !isset($parameters["recipientTokenList"])) {
            throw new Exception("recipientTokenList is missing in parameters.");
        }

        if (isset($parameters["usageLimitType1"]) and !isset($parameters["usageLimitValue1"])) {
            throw new Exception("usageLimitValue1 is missing in parameters.");
        }

        if (isset($parameters["usageLimitType2"]) and !isset($parameters["usageLimitValue2"])) {
            throw new Exception("usageLimitValue2 is missing in parameters.");
        }
    }

}
