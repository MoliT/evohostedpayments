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

class EvoSnapTools
{

	/**
	 * Calls EVO Snap* Hosted Payments Service
	 * @param array $params the CURL params to send to the service.
	 * @param string $url the URL to call.
	 * @param boolean $verifySsl flag to determine if the SSL cert must be validated. Default to true.
	 * @return array the resulting call.
	 */
	public static function callEvoSnap($params, $url, $verifySsl = true){
	    $fparams = EvoSnapTools::filterArray($params);
		$rCurl = curl_init ();
		curl_setopt ( $rCurl, CURLOPT_URL, $url );
		curl_setopt ( $rCurl, CURLOPT_SSL_VERIFYPEER, $verifySsl );
		curl_setopt ( $rCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $rCurl, CURLOPT_POST, true );
		curl_setopt ( $rCurl, CURLOPT_POSTFIELDS, EvoSnapTools::encodeRequest($fparams) );
		
		$result = ( array ) json_decode ( curl_exec ( $rCurl ) );
		
		curl_close ( $rCurl );
		
		return $result;
	}
	
	/**
	 * Creates the MAC signature.
	 * @param array $aParams the params.
	 * @param string $sPrivateKey the private key.
	 * @return string the MAC signature.
	 */
	public static function getMac($aParams, $sPrivateKey){
		$sUnencoded = '';
		if (!empty($aParams)){
			$aTemp = array();
			foreach ($aParams as $key => $val){
				$aTemp[$key] = $val;
			}
			ksort($aTemp);
			$sUnencoded.= implode('', $aTemp);
		}
		if (!empty($sPrivateKey))
			$sUnencoded.= $sPrivateKey;
	
		return md5($sUnencoded);
	}
	
	/**
	 * Encodes request data.
	 * @param array $aRequest the request to encode.
	 * @return string the encoded request.
	 */
	public static function encodeRequest($aRequest){
		$aFields = array();
		if(is_array($aRequest) && !empty($aRequest)){
			foreach ($aRequest as $key => $val){
				if (is_array($val) || is_object($val))
					$aFields[] = $key.'='.urlencode(json_encode($val));
				else
					$aFields[] = $key.'='.urlencode($val);
			}
		}
		
		return implode('&', $aFields);
	}

    /**
     * Filters an array removing false items.
     * @param array $a the array to filter.
     * @return array the resulting array.
     */
    public static function filterArray($a)
    {
        foreach ($a as &$value) {
            if (is_array($value)) {
                $value = EvoSnapTools::filterArray($value);
            }
        }
        
        return array_filter($a,array('EvoSnapTools', 'valueTest'));
    }
    
    public static function valueTest($value){
        $result = true;
        
        if(is_string($value)){
            $result = trim($value) !== '';
        }else if(is_array($value)){
            $result = !empty($value);
        }else{
            $result = !(is_null($value) || ($value === false));
        }
        
        return $result;
    }
	
	/**
	 * Gets a valid String to send to EVO Snap*.
	 * @param string $s the String to format.
	 * @param int $l the lenght of the string. Default = 20.
	 * @return string
	 */
	public static function getString($s, $l = 20){
		return substr(trim($s), 0, $l);
	} 

	/**
	 * Gets a valid Number to send to EVO Snap*.
	 * @param float $n the number to format.
	 * @return string
	 */
	public static function getNumber($n){
		return number_format($n, 2);
	}
	 
	/**
	 * Gets a valid Boolean to send to EVO Snap*.
	 * @param boolean $n the boolean to format.
	 * @return string
	 */
	public static function getBoolean($b){
		return $b? '1' : '0';
	}
	
	/**
	 * Formtas a ZIP code in a valid form for EVO Snap*.
	 * @param string $zip the ZIP code to format.
	 * @return string Zip Code in a valid format.
	 */
	public static function getZipCode($zip){
		return EvoSnapTools::getString(strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", $zip)), 9);
	}
	
	/**
	 * Gets an address formatted in a form ready to send to EVO Snap*.
	 * @param string $prefix the form variables prefix.
	 * @param SnapAddress $address the address.
	 * @return the address in an array.
	 */
	public static function getAddress($prefix, $address){
		$aAddress = array();
		
		if($address){
    		$aAddress[$prefix.'_company'] = EvoSnapTools::getString($address->company);
    		$aAddress[$prefix.'_first_name'] = EvoSnapTools::getString($address->first_name);
    		$aAddress[$prefix.'_last_name'] = EvoSnapTools::getString($address->last_name);
    		$aAddress[$prefix.'_po_box_number'] = EvoSnapTools::getString($address->po_box_number);
    		$aAddress[$prefix.'_address1'] = EvoSnapTools::getString($address->address1);
    		$aAddress[$prefix.'_address2'] = EvoSnapTools::getString($address->address2);
    		$aAddress[$prefix.'_house_number'] = EvoSnapTools::getString($address->house_number);
    		$aAddress[$prefix.'_city'] = EvoSnapTools::getString($address->city);
    		$aAddress[$prefix.'_zipcode'] = EvoSnapTools::getZipCode($address->zipcode);
    		$aAddress[$prefix.'_country'] = $address->country;
    		$aAddress[$prefix.'_state'] = $address->state;
		}
		
		return $aAddress;
	}
	
	/**
	 * Gets the languange code used by Snap* from ISO value.
	 * @param string $iso_language the language ID.
	 * @return string EVO Snap* language code.
	 */
	public static function getLanguage($iso_language){
		$result = 'ENG'; //Default English
		
		//By now, only Spanish supported.
		// TODO Change the way the language is selected as the number
		// of languages increases
		if(isset($iso_language) && $iso_language == 'es'){
			$result = 'SPA';
		}
		
		return $result;
	}
	
	/**
	 * Retrieves a value from the request.
	 * @param array $request the HTTP request.
	 * @param string $param parameter name.
	 * @return Ambigous <NULL, string>
	 */
	public static function getParam($request, $param){
		if(isset($request[$param])){
			$result = $request[$param];
		}else{
			$result = null;
		}
		
		return $result;
	}
}