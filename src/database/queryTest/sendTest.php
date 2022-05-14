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

$nick = $_COOKIE["nick"] ?? false;
$token = $_COOKIE["token"] ?? false;
$userKey = $_COOKIE["userKey"] ?? false;
$testKey = $_COOKIE["testKey"] ?? false;
$testID = $_COOKIE["testID"] ?? false;

$currentQuestion = $_COOKIE["currentQuestion"];
$currentAnswersData = $_COOKIE["currentAnswers"];
$qNum = $_COOKIE["questionNum"] ?? null;

//participants answer
$partnAnswers = $_POST["myAnswers"];


$db = new PDOconnect();
$WBconf = new WebSiteConf();

$pidName = $db->domainPrefix."_pid";
$pid = $_COOKIE[$pidName];


$userVerified = checkToken($db, $token, $nick, $testKey, $userKey, $testID);


function checkQMAX($testID, $qNum) {
    global $db;
    $sql = "SELECT q_max FROM tests WHERE id = ?";
    $result = $db->run($sql,[$testID]);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($qNum > $row["q_max"]-1) return throwError("overMax");
    if ($qNum == $row["q_max"]-1) return "max";
    return false;
}


function incrementQNum($qNum, $isMax) {
    global $WBconf;
    
    if ($isMax == false) {
        // var_dump($isMax, $isMax ==false );
        $cookieTime = $WBconf->getQuizCookieTime();
        setLocalCookie("questionNum", $qNum + 1, $cookieTime);

    }
}


function checkAnswer($answersArg, $answersDataArg) {
    $answers = json_decode($answersArg);
    $answersData = json_decode($answersDataArg);
    if (is_null($answers)) {
        return NULL;
    }
    sort($answers, SORT_REGULAR);
    $finalAnswer = [];
    $ansDataLng = count($answersData);
    foreach($answers as $v) {
        if ($v > $ansDataLng) {
            $v = $ansDataLng;
        }
        array_push($finalAnswer, $answersData[$v]);
    }
    return $finalAnswer;
}


function onUpdateAnswer($pid, $testID, $qNum, $answer, $isMax) {
    global $db;
    
    // $JSONanswer = json_encode($answer);
    //Póżniej będzie można zmienić odpowiedz odnosząc się do zmienanonego pliku cookie $currentAnswersData przez JS, w tym miejscu, gdzie automatycznie pokazuje zapisane wcześniej odpowiedzi "showSelectedAnswer() JS"
    $sqlAnswer = "SELECT answers.id as 'answer_id', answers.answer as 'answer', question_id
    FROM answers, tests, questions
    WHERE 
        question_id = questions.id AND questions.test_id = tests.id
        AND participant_id = ? AND question_num = ? AND tests.id = ?
    ";
    $result = $db->run($sqlAnswer,[$pid, $qNum, $testID]);
    if (!$result) return false;

    $row = $result->fetch(PDO::FETCH_ASSOC);
    $qID = $row["answer_id"];

    //optional check for changing cookies by the user, if the script does not find the id that I received from the request, then something is wrong
    $qHis = $_COOKIE["questionsHistory"] ?? throwError("badQuestionsHistory");
    $JSONqHis = json_decode($qHis);
    $noMatches = true;
    foreach ($JSONqHis as $v) {
        if ($v == $qID) $noMatches = false;
    }
    if ($noMatches) throwError("badQuestionsHistory");

    
    $sqlUpdate = "UPDATE `answers` SET `answer`= ?, a_date = NOW()  
    WHERE id = ?";
    $updateResult = $db->run($sqlUpdate, [$answer, $qID]);

    incrementQNum($qNum, $isMax);
    exit(json_encode(["answer"=>"update"]));
}


function writeAnswerToDatabase($pid, $testID, $currentQuestion, $answer, $qNum) {
    global $db;
    global $WBconf;
    $sql = "INSERT INTO `answers`(`participant_id`, `question_id`, question_num, `answer`) 
        VALUES (?,?,?,?);
    ";
    //SELECT LAST_INSERT_ID();";
    $preparedAnswer = json_encode($answer);
    $result = $db->run($sql,[$pid, $currentQuestion, $qNum, $preparedAnswer]);
    if (!$result) throwError("insertError");
    $answerID = $db->conn->lastInsertId();

    return $answerID;
}


function writeAnswersHistory($answerID, $qNum) {
    global $WBconf;

    $cookieTime = $WBconf->getQuizCookieTime();
    $myAnswerID = json_encode([$answerID]);

    if (!isset($_COOKIE["questionsHistory"])) {
        setLocalCookie("questionsHistory", $myAnswerID, $cookieTime);

    }else {
        $answersCookie = $_COOKIE["questionsHistory"];
        $answers = json_decode($answersCookie);
        //var_dump($answers);

        array_push($answers, $answerID);
        $JSONanswers = json_encode($answers);
        setLocalCookie("questionsHistory", $JSONanswers, $cookieTime);

    }

}




$overQMax = checkQMAX($testID, $qNum);
// var_dump($overQMax);
//var_dump($partnAnswers);
$answerChecked = onUpdateAnswer($pid, $testID, $qNum, $partnAnswers, $overQMax);
if (!$answerChecked && ($overQMax == false || $overQMax == "max")) {
    
    $answerMod = checkAnswer($partnAnswers, $currentAnswersData);
    $answerID = writeAnswerToDatabase($pid, $testID, $currentQuestion, $answerMod, $qNum);
    incrementQNum($qNum, $overQMax);

    if ($answerID) {
        writeAnswersHistory($answerID, $qNum);
        exit(json_encode(["answer"=>"save"]));
    }
}

$db->closeConnection();
throwError("noAnswer");

?>