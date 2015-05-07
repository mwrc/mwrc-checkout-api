<?php

/**
 * curl_get performs a request on the MWRC server and returns xml
 *
 * @param string $mwrc_domain
 * 		- client's mwrc domain: http://[clients_subdomain].mwrc.net
 * 
 * @param string $mwrc_lang_abbrev
 * 		- typically: en
 * 
 * @param string $mwrc_script_name
 * 		- the API you are making a request against: category.xml, product.xml, locator.xml
 * 
 * @param string $mwrc_script_args
 * 		- the API parameters. All the possible params the API script can accept * 
 * 
 * @param string $cookie_string_for_api
 * 		- the value returned by mwrc_session_handler
 * 
 * @return string
 * 		- XML
 */

function have_mwrc_session() {
    return isset($_COOKIE['mwrc_session_code_1_1']) ? true : false ;
    
}

///////////////////////////////////////
function curl_get ($mwrc_domain, $mwrc_lang_abbrev, $mwrc_script_name, $mwrc_script_args)
///////////////////////////////////////
{
	$ch=curl_init();
	
	if($mwrc_lang_abbrev=="services") {
        $url = "$mwrc_domain/$mwrc_lang_abbrev/$mwrc_script_name?$mwrc_script_args";    	
	} else {
        $url = "$mwrc_domain/xml/$mwrc_lang_abbrev/$mwrc_script_name?$mwrc_script_args";    	
	}
// print_r($mwrc_script_args);
//     print "<pre>".$url."</pre>";

	curl_setopt($ch, CURLOPT_URL, $url);

	if (isset($_COOKIE['mwrc_session_code_1_1'])) {
    	$cookie = "mwrc_session_code_1_1=".$_COOKIE['mwrc_session_code_1_1'].";";
	    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

	$response=curl_exec($ch);
    
//     print "resp:".$response;
    
	$error = curl_error($ch);
// 	print "error: ".$error."*";
	
	$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

    if($error) return $error;
    
    $xmlobj = simplexml_load_string($response);
        	
	if($http_status==200) {

    	return $xmlobj;    	
	} else {
        return (string)$xmlobj[0];
	}

}