<?php
// #ZMIEN ten caly plik NA CLASS ! 
//check if user status is on waiting mode
function checkWaitingMode($pid, $testID,  $options = ["noWMode"=>false]) {
    global $db;
    /*
        $options
        noWMode - check without waiting mode
    */
    $sql = "SELECT `t_header`, `tags`, `test_time`, `session_mode`, `waiting_time`, `participants_max`, `t_date`,
    participants.nickname, participants.log_date, participants.status
    FROM participants, tests 
    WHERE 
    tests.id = participants.test_id 
    AND participants.id = ?
    AND tests.id = ?
    AND public = 1";
    $result = $db->run($sql,[$pid, $testID]);
    if (!$result) return false;
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row["status"] == 0) return false;
    // #if user don't wait anymore
    if ($row["status"] > 2 && $options["noWMode"] == false) return false;
    // #wiem, ze wyglada to potwornie, ale to jest szybkie rozwiazanie
    if ($row["session_mode"] != 1 && $options["noWMode"] == true) {
        return ["noSessMode"=>1,"data"=>$row];
    }
    if ($row["session_mode"] != 1) return false;
    return $row;
}


// #check if the session exists at all
function checkForSession($pid, $testID, $options = ["noWMode"=>false]) {
    global $db;
    $testInfo = checkWaitingMode($pid, $testID, ["noWMode"=>$options["noWMode"]]);
    $sessionTime = $testInfo["test_time"] + $testInfo["waiting_time"];
    $waitingTime = $testInfo["waiting_time"];
    // $sessionTime = 60; 
    // $testTime = 30;
    // var_dump($sessionTime);
    //UNIX_TIMESTAMP(STR_TO_DATE(s_date, '%Y-%m-%d %H:%i:%s'))
    $sqlSession = "SELECT id, s_date, s_status, 
    UNIX_TIMESTAMP(MAX(s_date)) as `unix_session_time`,
    UNIX_TIMESTAMP(current_timestamp()) as `unix_time_now`,
    IF(UNIX_TIMESTAMP(MAX(s_date)) + CAST('$waitingTime' AS SIGNED INT) >= UNIX_TIMESTAMP(current_timestamp()), 'false','true') as `over_waiting_time`
    FROM participant_sessions
    WHERE test_id = ? 
    GROUP BY id
    HAVING unix_session_time + $sessionTime >= unix_time_now
    ORDER BY id DESC
    LIMIT 1
    ";

    $result = $db->run($sqlSession, [$testID]);
    // $row = $result->fetch(PDO::FETCH_ASSOC);
    // $row["over_waiting_time"] = true;
    // if ($row["unix_session_time"] + $testTime >= $row["unix_time_now"]) {
    //     $row["over_waiting_time"] = false;
    // }
    // if ($result) return $row;
    if ($result) {
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    else {
        return false;
    }
    
}


// #give information to user about his session state
function getSessionState($pid, $testID, $options = ["noRMID"=>false,"noWMode"=>false]) {
    global $db;
    /* #
        $options
        noRMID - don't remove session id,
        noWMode - check without waiting mode
    */
    
    $sessionInfo = checkForSession($pid, $testID, ["noWMode"=>$options["noWMode"]]);
    $testInfo = checkWaitingMode($pid, $testID, ["noWMode"=>$options["noWMode"]]);
    //var_dump($sessionInfo);
    // here is my bad, because I didn't use class, so this is condition if we set null in session mode and we want get the time this string exactly will do this
    if (isset($testInfo["noSessMode"])) {
        $testInfo = $testInfo["data"];
        $testInfo["unix_session_time"] = 0;
        $testInfo["unix_time_now"] = 0;
        $testInfo["waiting_time"] = 0;
        return $testInfo;
    }
    if (!$testInfo) return false;
    if (!$sessionInfo) return false;
    $unix_session_time = $sessionInfo["unix_session_time"];
    $sessionTime = $testInfo["test_time"] + $testInfo["waiting_time"];

    $sqlPtpCount = "SELECT UNIX_TIMESTAMP(`log_date`) as 'p_unix_time'
        FROM `participants`
        WHERE participants.test_id = ?
        GROUP BY id
        HAVING p_unix_time BETWEEN $unix_session_time AND $unix_session_time + $sessionTime
    ";
    $result = $db->run($sqlPtpCount,[$testID]);
    $sessionPtpNum = ["session_participant_num"=>1];
    if ($result) {
        $sessionPtpNum["session_participant_num"] = $result->rowCount();
    }
    
    $outData = array_merge($sessionInfo, $testInfo, (array)$sessionPtpNum);
    unset($outData["status"]);
    unset($outData["public"]);
    //unset($outData["session_mode"]);
    if ($options["noRMID"] != 1) {
        unset($outData["id"]);
    }
    
    return $outData;

    
}
?>