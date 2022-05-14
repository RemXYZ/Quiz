<?php
//API

setlocale(LC_ALL, 'utf8');
//xampp addition, unfortunately it's a crutch
//now it calls extra support url
$xppUrl = explode("/src/",$_SERVER["REQUEST_URI"])[0];
$xppUrl = "/src/pages/portugalia_project";


//mc_encrypt("text", ENCRYPTION_KEY);
//mc_decrypt($encrypted, ENCRYPTION_KEY);
//genEsKey() -> string
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/config/crypt.php";
//new DataBase()
//new PDOconnect();
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/config/db.php";
// throwError($msg)
// removeUsersCookie()
// checkToken($token, $nick, $testKey, $userKey, $testID)
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/userService/userValidation.php";


//IF USE POST
$nick = $_COOKIE["nick"] ?? $_POST["nick"] ?? false;
//if user use nick
$useNick = $_POST["useNick"] ?? false;
$fName = $_POST["fName"] ?? false;
$lName = $_POST["lName"] ?? false;

$testKey = $_COOKIE["testKey"] ?? $_POST["testKey"] ?? false;

//these data are gated after join to test
$checkTokenVar = $_POST["checkToken"] ?? false;
$token = $_COOKIE["token"] ?? $_POST["token"] ?? false;
$userKey = $_COOKIE["userKey"] ?? $_POST["userKey"] ?? false;
$testID = $_COOKIE["testID"] ?? $_POST["testID"] ?? false;


$db = new PDOconnect();
$WBconf = new WebSiteConf();


function checkKey($testKey) {
    global $db;

    if (!$testKey) return false;

    $sql = "SELECT * FROM tests WHERE t_key = ?";
    $result = $db->run($sql,[$testKey]);
    if (!$result) return false;

    if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($row["public"] == "0") return false;
        return $row;  
    }
    
    return false;
}


//I know, that this token is not like a normal one, but for the purpose of this project, it will be enough
function getUserEsToken($nick = null, $fName, $lName = NULL, $testKey) {
    global $db;

    date_default_timezone_set($db->getUTF());

    $userKey = genEsKey();
    $tmp_fName = $fName;
    $tmp_lName = $lName;
    // !!!
    //$tmp_fName = mb_substr($tmp_fName, 0, 3);
    //$tmp_lName = mb_substr($tmp_lName, 0, 3);
    $nick = $tmp_fName.$tmp_lName.'#'.substr($userKey, 0, 3);

    $expirationTime = 30;
    $expirationDate = date("Y-m-d H:i:s",mktime(
        date('H'), 
        date('i') + $expirationTime, 
        date('s') + rand(1,59), 
        date('m'), 
        date('d'),
        date('Y')
    ));
    //date("Y-m-d H:i:s");
    $sold = SERV_SOLD;
    $userInfo = $nick.$testKey.$userKey.$sold;
    $h = hash("sha256",$userInfo);
    //$encodedExpDate = mc_encrypt($expirationDate, ENCRYPTION_KEY);

    //pseudo token :)
    //$esToken = $h.".".$encodedExpDate;
    $esToken = $h.".".hash("sha256",$expirationDate);

    return ["token"=>$esToken, "nick"=>$nick, "testKey"=>$testKey, "userKey"=>$userKey, "fName"=>$fName, "lName"=>$lName];
}


function insertIntoDB($userInfo, $testInfo, $tokenToCheck = null) {
    global $db;
    global $WBconf;

    
    if($tokenToCheck)  {
        removeUsersCookie();
        throwError("cookieError");
    }

    $sql = "INSERT INTO participants (test_id, nickname, f_name, l_name, token) 
            VALUES (?, ?, ?, ?, ?)
    ";


    $result = $db->run($sql,[
        $testInfo["id"],
        $userInfo["nick"],
        $userInfo["fName"], 
        $userInfo["lName"],
        $userInfo["token"]
    ]);
    
    if (!$result) throwError("insertError");
    $myID = $db->conn->lastInsertId();

    //unset($userInfo["token"]);

    //35 min
    $cookieTime = $WBconf->getQuizCookieTime();
    $pid = $db->domainPrefix."_pid";
    setLocalCookie($pid, $myID, $cookieTime);
    setLocalCookie("token", $userInfo["token"], $cookieTime);
    setLocalCookie("nick", $userInfo["nick"], $cookieTime);
    setLocalCookie("testKey", $userInfo["testKey"], $cookieTime);
    setLocalCookie("userKey", $userInfo["userKey"], $cookieTime);
    setLocalCookie("fName", $userInfo["fName"], $cookieTime);
    setLocalCookie("lName", $userInfo["lName"], $cookieTime);

    setLocalCookie("testID", $testInfo["id"], $cookieTime);
    setLocalCookie("qMax", $testInfo["q_max"], $cookieTime);

    if ($testInfo["session_mode"] == 1) {
        setLocalCookie("waitingMode", 1, $cookieTime);
    }


    exit(json_encode(["access"=>"ok"]));
}

/*
...userVerification.php...
*/

//first we check for correct user names
if (!$checkTokenVar) {
    if ($useNick) {
        if (!$nick) throwError("nameError");
        if (strlen($nick) > 6) throwError("lenError");
    }else {
        if (!$fName || empty($fName)) throwError("fNameError");
        if (!$lName || empty($lName)) throwError("lNameError");
    }
}

//checks for tests key
$testData = null;
if($testsRow = checkKey($testKey)) {
    $testData = $testsRow;
}else {
    throwError("badKey");
}


//checks for token
//if participant have already joined
if ($checkTokenVar){
    $chackedToken = checkToken($db, $token, $nick, $testKey, $userKey, $testID);
    if ($chackedToken == true)
        exit(json_encode(["access"=>"ok"]));

}else {
    if (!$testKey) throwError("badKey");

    $esToken = getUserEsToken(null, $fName, $lName, $testKey);
    insertIntoDB($esToken, $testData, $token);
}

$db->closeConnection();
throwError("noAnswer");

?>