<?php

//SOURCE: https://codernotes.ru/articles/php/obratimoe-shifrovanie-po-klyuchu-na-php.html
$xppUrl = explode("/src/",$_SERVER["REQUEST_URI"])[0];
//now it calls extra support url
$xppUrl = "/src/pages/portugalia_project";
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/config/config.php";

/*
// Encrypt Function
function mc_encrypt($encrypt, $key) {
  $encrypt = serialize($encrypt);
  $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
  $key = pack('H*', $key);
  $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
  $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
  $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
  return $encoded;
}
 
// Decrypt Function
function mc_decrypt($decrypt, $key) {
  $decrypt = explode('|', $decrypt.'|');
  $decoded = base64_decode($decrypt[0]);
  $iv = base64_decode($decrypt[1]);
  if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }
  $key = pack('H*', $key);
  $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
  $mac = substr($decrypted, -64);
  $decrypted = substr($decrypted, 0, -64);
  $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
  if($calcmac!==$mac){ return false; }
  $decrypted = unserialize($decrypted);
  return $decrypted;
}

//END OF SOURCE
*/


  function genEsKey(){
    $abc = ['a','b','c','d','e','f'];
    $key = null;

    for ($i = 0; 16 > $i; $i++) {
      if(rand(0,1) == 0) {
        $key .= $abc[rand(0,5)];
      }else{
        $key .= rand(0,9);
      }
    }

    return $key;

  }