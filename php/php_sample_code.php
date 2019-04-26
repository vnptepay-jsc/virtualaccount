<?php
$response_va = RegiterVA();
if($response_va){
	echo '<span style="color: red">Co loi trong qua trinh tao tai khoan Virtual Account</span>';
}else{
	echo '<span style="color: green">Tao tai khoan Virtual Account thanh cong</span>';
}

function RegiterVA(){
	// Mã Merchant, sẽ được cung cấp cho Merchant qua Email thông số kết nối
	$merchantCode   = 'SAT';
	// Mã giao dịch, duy nhất. Định dạng: merchant_code +”_”+ chuỗi số: ví dụ: SAT2018018108334343
	$request_id = $merchantCode .'_'. strtoupper(date("dmYHis").rand(1000,9999));        
	// Ten tai khoan bat buoc
	$accountName    = 'anhthuong123';	
	// Link ket noi API dang ky tai khoan Virtual Account
	$linkCallVA     = 'http://172.16.10.75:10002/ApiResf_VirtualAccount/services/registerVA';
	
	//log_message('error', "Vao ham RegiterVA() -> request_id: ".$request_id);
	
	$arrExtend = array(
		'phone'     => '',  // Duoc phep de rong
		'email'     => '',  // Duoc phep de rong
		'address'   => '',  // Duoc phep de rong
		'id'        => '',  // Duoc phep de rong
	);

	$arrSign = array(
		'map_id'        => $accountName,
		'amount'        => 100000,
		'start_date'    => date('Ymd') . '000000',
		'end_date'      => 99990102235959,
		'condition'     => '01',
		'customer_name' => 'NGUYEN VAN A',
		'request_id'    => $request_id,
		'bank_code'     => 'WOORIBANK',
		'extend'        => $arrExtend
	);
	
	$dataSign  = json_encode($arrSign);
	//log_message('error', 'Tk: '.$accountName . ' dataSign -> '. print_r($dataSign, true));

	// Mã hóa TripleDes 
	$encryptData = mencrypt_3des($dataSign, '31feae316de0a42520ef5ec4');

	//Tham so truyen len ham procService
	$arrRequest = array(
		'pcode'         => '9000',
		'merchant_code' => $merchantCode,
		'data'          => $encryptData
	);

	$dataRequest = json_encode($arrRequest);
	//log_message('error', "Tk: ".$accountName . ' dataRequest -> '. $dataRequest);
	
	try {
		$responseData =callAPI('POST', $linkCallVA, $dataRequest);   
		//log_message('error', "Tk: ".$accountName . ' responseData -> '. $responseData);
		
		if($responseData == 'err'){
			//log_message('error', "Tk: ".$accountName . ' --- Loi ket noi callAPI VA ---');
			return false;
		}
		
		$jResponse  = json_decode($responseData);            

		if ($jResponse) {
			if (isset($jResponse->response_code)) {
				return $jResponse;
			} else {
				return false;
			}
		}else{
			return false;
		}
	} catch ( Exception $e ) {
		//log_message ( 'error', 'Loi goi callAPI(): Tk: '. $accountName . 'transid: '.$request_id.' | Exception: ' . $e->getMessage (), false, true );
		return false;
	}
	return false;
}

function callAPI($method, $url, $data){
	$curl = curl_init();

	switch ($method){
	   case "POST":
		  curl_setopt($curl, CURLOPT_POST, 1);
		  if ($data)
			 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		  break;
	   case "PUT":
		  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		  if ($data)
			 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
		  break;
	   default:
		  if ($data)
			 $url = sprintf("%s?%s", $url, http_build_query($data));
	}

	// OPTIONS:
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
	   'APIKEY: 31feae316de0a42520ef5ec4',
	   'Content-Type: application/json',
	));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

	// EXECUTE:
	$result = curl_exec($curl);
	if(!$result){
		return 'err';
	}
	curl_close($curl);
	return $result;
}

function mencrypt_3des($text, $key) {
	$text =pkcs5_pad($text, 8);  // AES?16????????
	$size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($size, MCRYPT_RAND);
	$bin = pack('H*', bin2hex($text));
	$encrypted = mcrypt_encrypt(MCRYPT_3DES, $key, $bin, MCRYPT_MODE_ECB, $iv);
	$encrypted = bin2hex($encrypted);
	return $encrypted;
}

function pkcs5_pad($text, $blocksize) {
	$pad = $blocksize - (strlen($text) % $blocksize);
	return $text . str_repeat(chr($pad), $pad);
}
