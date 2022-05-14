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

// 03.03.22
//checkWaitingMode($pid, $testID)
//checkForSession($pid, $testID)
// getSessionState($pid, $testID, $options = ["noRMID"=>false])
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/userService/sessionInfo.php";


$db = new PDOconnect();
$WBconf = new WebSiteConf();


$chechForResult = $_POST["checkWaitingMode"] ?? false;
$sessionState = $_POST["sessionState"] ?? false;
$addToSession = $_POST["createSession"] ?? false;
$startQuiz = $_POST["startQuiz"] ?? false;

$testID = $_COOKIE["testID"] ?? false;

$userVerified = checkToken($db);

$pidName = $db->domainPrefix."_pid";
$pid = $_COOKIE[$pidName];


function createSession() {
    global $db;
    global $pid;
    global $testID;
    $sessionStatus = checkForSession($pid, $testID);
    $waitingModeStatus = checkWaitingMode($pid, $testID);
    if ($waitingModeStatus == false) return false;
    if ($sessionStatus == false) {
        $sql = "INSERT INTO participant_sessions (initiator_pid, test_id, s_status)
        VALUES (?,?,1)";
        $db->run($sql,[$pid,$testID]);
        return true;
    }
    return false;
}


function startQuiz() {
    global $db;
    global $pid;
    global $testID;

    $sessionInfo = getSessionState($pid, $testID, ["noRMID" => 1]);
    $sID = $sessionInfo["id"];
    $reqSuccess = 0;
    if ($sessionInfo["over_waiting_time"] == "true" 
    || $sessionInfo["session_participant_num"] == $sessionInfo["participants_max"]
    || $sessionInfo["s_status"] == 0) {
        $sqlUpdateStatus = "UPDATE participants SET status = 3 WHERE id = ?";
        $resultPtp = $db->run($sqlUpdateStatus, [$pid]);
        $reqSuccess = $resultPtp ? 1 : 0;

        $sqlUpdateSession = "UPDATE participant_sessions SET s_status = 0 WHERE id = ?";
        $resultSess = $db->run($sqlUpdateSession,[$sID]);
        $reqSuccess = $resultSess ? 1 : 0;
        removeCookie("waitingMode");
    }
}


if ($chechForResult == 1) {
    $wModStatus = checkWaitingMode($pid, $testID);
    if ($wModStatus) exit(json_encode(true));
}
if ($addToSession == 1) {
    createSession() ? exit(json_encode(true)) : exit(json_encode(false));
}
if ($sessionState == 1) {
    $outData = getSessionState($pid, $testID);
    $outData != false ? exit (json_encode($outData)) : exit(json_encode(false));
    
}
if ($startQuiz == 1) {
    startQuiz();
}


$db->closeConnection();
throwError("noAnswer");

?>