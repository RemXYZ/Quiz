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
// checkWaitingMode($pid, $testID)
// checkForSession($pid, $testID)
// getSessionState($pid, $testID, $options = ["noRMID"=>false])
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/userService/sessionInfo.php";


$chechForResult = $_POST["checkResult"] ?? false;
$testID = $_COOKIE["testID"] ?? false;

$db = new PDOconnect();
$WBconf = new WebSiteConf();

$pidName = $db->domainPrefix."_pid";
$pid = $_COOKIE[$pidName];

$userVerified = checkToken($db);


function onExist($pid, $testID){
    global $db;
    $sql = "SELECT * FROM results WHERE participant_id = ? AND test_id = ?";
    $result = $db->run($sql,[$pid, $testID]);
    if(!$result) return false;
    
    $row = $result->fetch(PDO::FETCH_ASSOC);
    unset($row["id"]);
    return $row;
    
}


function sendResult($pid, $testID, $testResultArg, $full_points, $options = ["changeStatus"=>false]) {
    global $db;
    $testResult = json_encode($testResultArg);
    $sql = "INSERT INTO results (participant_id, test_id, result, full_points)
    VALUES (?,?,?,?)";
    $result = $db->run($sql,[$pid, $testID, $testResult, $full_points]);

    if ($result) {
        $sqlUpdatePart = "UPDATE `participants` SET status = 0 WHERE id = ?";
        $db->run($sqlUpdatePart, [$pid]);

        // #SESSION ADDITION
        removeCookie("waitForOthers");
        removeCookie("waitingMode");
        exit(json_encode(["responde"=>"ok"]));
    }else {
        exit(json_encode(["responde"=>"false"]));
    }
}


function checkTestResult($pid, $testID) {
    global $db;
    global $WBconf;

    if (onExist($pid, $testID) != false) throwStrError("noRepeat");

    // #SESSION ADDITION
    $wMode = getSessionInfo();
    $sessMode = $wMode["session_mode"] ?? 0;
    if ($wMode && $sessMode) {
        $cookieTime = $WBconf->getQuizCookieTime();
        setLocalCookie("waitForOthers", 1, $cookieTime);
        setLocalCookie("waitingMode", 1, $cookieTime);
        $sqlUpdatePart = "UPDATE `participants` SET status = 2 WHERE id = ?";
        $db->run($sqlUpdatePart, [$pid]);
        exit(json_encode(["responde"=>"waitForOthers"]));
    }
    

    $sqlQuestions = "SELECT answers.answer as 'my_answer', questions.answer as 'correct_answer', question_num, points
        FROM answers, questions
        WHERE
            question_id = questions.id
            AND participant_id = ?
            AND test_id = ?
    ";

    $qResult = $db->run($sqlQuestions,[$pid, $testID]);
    if (!$qResult) {
        sendResult($pid, $testID, [0], 0);
        //throwStrError("noPtpAnswer");
    }
    $fullPoints = 0;
    $correctAnswerArr = [];

    while($row = $qResult->fetch(PDO::FETCH_ASSOC)) {
        $myAnswer = json_decode($row["my_answer"]);
        $correctAnswer = json_decode($row["correct_answer"]);

        $myAnswerLng = count($myAnswer);
        $correctAnswerLng = count($correctAnswer);
        sort($myAnswer);
        sort($correctAnswer);

        $points = (int)$row["points"];
        $qNum = $row["question_num"];

        // Checking answer
        $answerStatus = 0;
        if ($myAnswerLng == $correctAnswerLng) {
            foreach ($correctAnswer as $k => $corA) {
                if ($corA == $myAnswer[$k]) {
                    $answerStatus = $points;
                }else {
                    $answerStatus = 0;
                }
            }
        }
        $fullPoints += $answerStatus;
        $correctAnswerArr[$qNum] = $answerStatus;

    }

    ksort($correctAnswerArr);
    sendResult($pid, $testID, $correctAnswerArr, $fullPoints);
    
}


// added 03.03.22
function getSessionInfo(){
    global $pid;
    global $testID;
    $sessInfo = getSessionState($pid, $testID, ["noWMode"=>true]);
    return $sessInfo;
    
}



if ($chechForResult) {
    $resp = onExist($pid, $testID);
    if ($resp) exit(json_encode($resp));
    
    $db->closeConnection();
    exit(json_encode(0));
}


checkTestResult($pid, $testID);
$db->closeConnection();
throwStrError("noAnswer");


?>