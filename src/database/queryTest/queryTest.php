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
// needs
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/userService/userValidation.php";

// 03.03.22
// checkWaitingMode($pid, $testID)
// checkForSession($pid, $testID)
// getSessionState($pid, $testID, $options = ["noRMID"=>false])
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/database/userService/sessionInfo.php";


$db = new PDOconnect();
$WBconf = new WebSiteConf();

//$data = json_decode($_POST["json"]);

$testID = $_COOKIE["testID"] ?? false;

$currentQuestion = $_COOKIE["currentQuestion"] ?? 1;
$qNum = $_COOKIE["questionNum"] ?? 0;

$genQuestionVal = $_POST["genQuestion"] ?? 0;


$userVerified = checkToken($db);

$dPrefix = $db->domainPrefix;
$pidStr = $dPrefix."_pid";
$pid = $_COOKIE[$pidStr];
if (!isset($pid)) throwStrError("noPID");


//SOURCE: https://expange.ru/e/%D0%9F%D0%BE%D0%BC%D0%B5%D0%BD%D1%8F%D1%82%D1%8C_%D0%BC%D0%B5%D1%81%D1%82%D0%B0%D0%BC%D0%B8_2_%D1%8D%D0%BB%D0%B5%D0%BC%D0%B5%D0%BD%D1%82%D0%B0_%D0%BC%D0%B0%D1%81%D1%81%D0%B8%D0%B2%D0%B0_(PHP)

function array_swap(array &$array, $key, $key2)
{
    if (isset($array[$key]) && isset($array[$key2])) {
        list($array[$key], $array[$key2]) = array($array[$key2], $array[$key]);
        return true;
    }

    return false;
}

//END OF SOURCE


function shuffleArray($arr, $num) {
    $elAmount = count($arr) - 1;
    for ($i = 0; $num > $i; $i++) {
        //It will work with only two variables,
        //if we have two same questions, it will add 1 to index,
        //and if after adding, the element is out of bounds of the array
        //subtract 2 from index
        $randEl1 = rand(0, $elAmount);
        $randEl2 = rand(0, $elAmount);
        if ($randEl1 == $randEl2) {
            $randEl2 += 1;
            if ($randEl2 >= $elAmount) {
                $randEl2 -= 2;
            }
        }
        array_swap($arr, $randEl1, $randEl2);
        return $arr;
    }
}

// added 03.03.22
function getSessionInfo(){
    global $pid; 
    global $testID;
    $sessInfo = getSessionState($pid, $testID, ["noWMode"=>true]);
    return $sessInfo;
    
}


function parseQuestionElements($row, $questionNum) {
    global $WBconf;

    // answer part
    $tAnswer = (array)json_decode($row["answer"]);
    $fAnswer = (array)json_decode($row["f_answer"]);
    $answersExploded = array_merge($tAnswer, $fAnswer);

    $readyAnswers = shuffleArray($answersExploded, count($answersExploded));
    $row["answers"] = $readyAnswers;

    // sesstion part
    $sessInfo = getSessionInfo();
    $row["overTime"] = false;
    if ($sessInfo) {
        $row = array_merge($row, $sessInfo);
    }else {
        $row["overTime"] = true;
    }
    

    //delete unnecessary fields
    unset($row["answer"]);
    unset($row["f_answer"]);
    unset($row["q_range"]);
    unset($row["q_static"]);
    unset($row["public"]);

    unset($row["img_id"]);
    unset($row["section_id"]);
    $cookieTime = $WBconf->getQuizCookieTime();
    setLocalCookie("currentQuestion", $row["id"], $cookieTime);
    setLocalCookie("currentAnswers", json_encode($row["answers"]), $cookieTime);

    // write answers position to 
    // it is not needed in cookie
    // if (!isset($_COOKIE["answersPositionHistory"])) {
    //     setLocalCookie("answersPositionHistory", json_encode([$row["answers"]]) ,$cookieTime);
    // }else {

    // }
    
    
    if (!isset($_COOKIE["questionNum"]))
        setLocalCookie("questionNum", 0, $cookieTime);

    return $row;
}


// ADDED 08.03.22
// here we split reletion question
function prepareResult($result) {
    // #questions
    $q = $result->fetchAll(PDO::FETCH_ASSOC);
    //var_dump($q);
    $qRes = $q[0];
    unset($qRes["img_id"]);
    unset($qRes["img_src"]);
    unset($qRes["bg"]);
    unset($qRes["front"]);
    unset($qRes["img_author"]);
    unset($qRes["img_author_link"]);
    unset($qRes["section_id"]);
    foreach($q as $v){
        ////img service/////
        if($v["bg"] == 1) {
            $qRes["bg_img"] = 1;
            $qRes["bg_img_src"] = $v["img_src"];
            $qRes["bg_img_author"] = $v["img_author"];
            $qRes["bg_author_link"] = $v["img_author_link"];
        }
        if($v["front"] == 1) {
            $qRes["front_img"] = 1;
            $qRes["front_img_src"] = $v["img_src"];
            $qRes["front_img_author"] = $v["img_author"];
            $qRes["front_author_link"] = $v["img_author_link"];
        }

        ////section service/////
    }
    return $qRes;
}


function getQuestion($testID, $questionNum) {
    global $db;
    global $pid;
    $sql = "SELECT questions_list, status FROM participants WHERE id = ?";
    $result = $db->run($sql, [$pid]);
    if (!$result) throwStrError("noAnswer");

    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row["status"] == 2) {
        throwStrError("waitingMode");
    }

    $questionListJSON = $row["questions_list"];
    $qList = json_decode($questionListJSON);


    $currentQ = $qList[$questionNum];
    $sqlQuestion = "SELECT questions.*, 
	question_imgs.id as 'img_id', question_imgs.src as 'img_src', question_imgs.bg, question_imgs.front, question_imgs.author as 'img_author', question_imgs.author_link as 'img_author_link',
    sections.id as 'section_id', sections.name as 'section',
    users.nickname as 'user_nickname'
    FROM questions 
        LEFT JOIN q_img_rel ON questions.id = q_img_rel.q_id
        LEFT JOIN question_imgs ON question_imgs.id = q_img_rel.img_id
        LEFT JOIN question_section_rel ON questions.id = question_section_rel.q_id
        LEFT JOIN sections ON sections.id = question_section_rel.section_id
        LEFT JOIN users ON questions.author_id = users.id
    WHERE 
    questions.id = ?";
    $qResult = $db->run($sqlQuestion, [$currentQ]);

    if ($qResult) {
        $qInfo = prepareResult($qResult);
        $myQuesction = parseQuestionElements($qInfo, $questionNum);
        exit(json_encode($myQuesction));
    }
}


//QUESTION LIST GENERATOR


// start function, which generate questions
// ЭТО ОЧЕНЬ РАНДОМНАЯ ФУНКЦИЯ, ПО ЭТОМУ ЕСЛИ ВСЕ ВОПРОСЫ БУДУТ БЕЗ ДИАПАЗОНА, ТО ТОГДА ВОЗМОЖНЫ АНОМАЛИИ, ПО ЭТОМУ НАДО ЭТО ПРОДУМАТЬ
function selectFullRangeQ($testID, $questionNum, $preparedQList, $qMax) {
    global $db;
    $qList = $preparedQList;
    $inNum = (count($qList) > 0) ?
        "AND id not in(".str_repeat('?,', count($qList) - 1) . '?'.")"
        :'';

    $sqlFullRange = "SELECT id
        FROM `questions`
        WHERE test_id = ? 
        AND q_range is null
        AND public = 1
        $inNum
    ";

    $stmtRangeArg = array_merge([$testID], $qList);
    $sqlFullRange = $db->run($sqlFullRange, $stmtRangeArg);
    if ($sqlFullRange) {
        $fullRangeNum = $sqlFullRange->rowCount();
        $showingChance = rand($fullRangeNum, $qMax);
        
        if ($showingChance == (int)round($qMax / 2)) {
            $request = $sqlFullRange->fetchAll(PDO::FETCH_ASSOC);
            $randomQuestion = rand(0, count($request)-1);
            $qID = $request[$randomQuestion]["id"];
            return $qID;
        }
    }

    return 0;
}


function genQuestion($testID, $questionNum, $qList, $qMax) {
    global $db;

    // UNSIGNED: only stores positive numbers (or zero).
    // SIGNED: can store negative numbers.
    $inNum = (count($qList) > 0) ?
        "AND id not in(".str_repeat('?,', count($qList) - 1) . '?'.")"
        : '';
    $sqlRange = "SELECT id
    FROM `questions`
    WHERE test_id = CAST( ? AS SIGNED int)
    AND public = 1
    AND CAST( ? AS SIGNED int) BETWEEN 
        SUBSTRING_INDEX(`q_range`, '-', 1) 
        AND SUBSTRING_INDEX(`q_range`, '-', -1)
        $inNum
    ";
    $sqlStatic = "SELECT id
        FROM `questions`
        WHERE test_id = CAST( ? AS SIGNED int) 
        AND CAST( ? AS SIGNED int) = SUBSTRING_INDEX(`q_range`, '-', 1)
        AND q_static = 1
        AND public = 1
    ";

    $stmtStatic = $db->run($sqlStatic, [$testID, $questionNum]);
    if ($stmtStatic) {
        $result = $stmtStatic->fetch(PDO::FETCH_ASSOC);
        $qID = $result["id"];
        return $qID;
    }

    $randQ = selectFullRangeQ($testID, $questionNum, $qList, $qMax);
    if ($randQ != 0) {
        return $randQ;
    }
    
    $stmtRangeArg = array_merge([$testID], [$questionNum], $qList);
    $stmtRange = $db->run($sqlRange, $stmtRangeArg);
    if ($stmtRange) {
        $request = $stmtRange->fetchAll(PDO::FETCH_ASSOC);
        $randomQuestion = rand(0, count($request)-1);
        $myQuesction = $request[$randomQuestion];
        $qID = $myQuesction["id"];
        return $qID;
    }

    return 0;

}


function genQuestionList($testID) {
    global $db;
    global $pid;
    global $WBconf;

    $selectParticipant = "SELECT test_id, status FROM participants WHERE id = ?";
    $result = $db->run($selectParticipant, [$pid]);
    if (!$result) throwStrError("noPID");

    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row["status"] == 0) throwStrError("denyAccess");
    if ($row["status"] > 1) return false;
    if ($row["test_id"] != $testID) throwStrError("denyAccess");


    // CHECKING TEST PART
    $sessionModeStatus = 3;
    $sqlTest = "SELECT q_max, public, session_mode FROM tests WHERE id = ?";
    $tResult = $db->run($sqlTest, [$testID]);
    $tRow = $tResult->fetch(PDO::FETCH_ASSOC);
    if ($tRow["public"] == 0) throwStrError("denyAccess");
    if ($tRow["session_mode"] == 1) $sessionModeStatus = 2;
    
    $sqlMaxQuestion = "SELECT COUNT(*) AS `max_rows` FROM questions WHERE test_id = ?";
    $qsResult = $db->run($sqlMaxQuestion, [$testID]);
    $qsRow = $qsResult->fetch(PDO::FETCH_ASSOC);
    $qMax = $qsRow["max_rows"];

    $qSetMax = $tRow["q_max"];
    $qList = [];

    for ($i = 1; $i <= $qSetMax; $i++) {
        $newQNum = genQuestion($testID, $i, $qList, $qMax);
        array_push($qList, $newQNum);
    }


    $preparedQuestions = json_encode($qList);
    $sqlUpdatePcp = "UPDATE `participants` SET questions_list = ?, status = $sessionModeStatus WHERE id = ?";
    $db->run($sqlUpdatePcp, [$preparedQuestions, $pid]);

    $cookieTime = $WBconf->getQuizCookieTime();
    setLocalCookie("questionNum", 0, $cookieTime);

    exit(json_encode(["questions"=>"ok"]));
}



$genTest = 0;
if ($genQuestionVal == 1) {
    $genTest = genQuestionList($testID);
}
if ($genTest == 0 && $genQuestionVal == 0) {
    getQuestion($testID, $qNum);
}

$db->closeConnection();
throwStrError("noAnswer");

?>