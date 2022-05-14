<?php
//xampp addition, unfortunately it's a crutch
$xppUrl = explode("/src/",$_SERVER["REQUEST_URI"])[0];
$xppUrl = "/src/pages/portugalia_project";
//mc_encrypt("text", ENCRYPTION_KEY);
//mc_decrypt($encrypted, ENCRYPTION_KEY);
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/config/crypt.php";
//new DataBase()
//new PDOconnect();
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/config/db.php";
// throwError($msg)
// removeUsersCookie()
// checkToken($token, $nick, $testKey, $userKey, $testID)
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/userService/userValidation.php";

removeUsersCookie();
exit("ok");

// $nick = $_COOKIE["nick"] ?? false;
// $token = $_COOKIE["token"] ?? false;
// $userKey = $_COOKIE["userKey"] ?? false;
// $testKey = $_COOKIE["testKey"] ?? false;
// $testID = $_COOKIE["testID"] ?? false;

// $db = new PDOconnect();
// $WBconf = new WebSiteConf();

// $userVerified = checkToken($db, $token, $nick, $testKey, $userKey, $testID);


?>