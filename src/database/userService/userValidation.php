<?php


function throwError($msg) {
    exit(json_encode(["error"=>$msg]));
}


function throwStrError($msg) {
    throwError($msg);
}


function removeUsersCookie() {
    $db = new DataBase();
    $pid = $db->domainPrefix."_pid";
    removeCookie($pid);
    removeCookie("token");
    removeCookie("nick");
    removeCookie("testKey");
    removeCookie("testID");
    removeCookie("userKey");
    removeCookie("fName");
    removeCookie("lName");

    removeCookie("currentAnswers");
    removeCookie("currentQuestion");
    removeCookie("questionsHistory");
    removeCookie("questionNum");
    removeCookie("qMax");

    removeCookie("waitingMode");
    removeCookie("waitForOthers");
    
}
$WSconf = new WebSiteConf();


function checkToken(
    $PDOdb, 
    $token = false, 
    $nick = false, 
    $testKey = false, 
    $userKey = false, 
    $testID = false
) {
    global $WSconf;

    $nick = $_COOKIE["nick"] ?? false;
    $token = $_COOKIE["token"] ?? false;
    $userKey = $_COOKIE["userKey"] ?? false;
    $testKey = $_COOKIE["testKey"] ?? false;
    $testID = $_COOKIE["testID"] ?? false;

    $pidName = $PDOdb->domainPrefix."_pid";
    $pid = $_COOKIE[$pidName];

    $PDOdb->genIP();
    $uIP = $PDOdb->myIP;
    $uinfo = array_merge([$token], [$nick], [$userKey], [$testID]);
    $uinfo = json_encode($uinfo);


    if (!$userKey || !$token || !$testID || empty($testID ) || !$nick) {  
        removeUsersCookie();
        throwError("noConn");
    }

    $sold = SERV_SOLD;
    $userInfo = $nick.$testKey.$userKey.$sold;
    $h = hash("sha256",$userInfo);

    $explodedToken = explode(".", $token);
    $myToken = $h.".".$explodedToken[1];

    $checkTokenSQL = "SELECT * 
        FROM participants 
        WHERE nickname = ? and token = ? and test_id = ?
    ";

    $result = $PDOdb->run($checkTokenSQL,[$nick, $myToken, $testID]);
    if (!$result) {
        throwError("badToken");
        removeUsersCookie();
        $wSql = "INSERT INTO warning_log (pid, ip, u_info, warning) 
        VALUES (?,?,?, 'badToken')";
        $PDOdb->run($wSql,[$pid, $uIP, $uinfo]);
    }else {
        $row = $result->fetch(PDO::FETCH_ASSOC);

        // IF USER CHANGED HIS PID, THIS WILL SET HIS REAL PID
        // BUT IT CAN'T BE MODIFIED BY SCRIPT, SO WE SHOULD WARNING OURSELF
        $cookieTime = $WSconf->getQuizCookieTime();
        $myID = $row["id"];
        if ($pid != $myID) {
            $wSql = "INSERT INTO warning_log (pid, ip, u_info, warning) 
                VALUES (?,?,?, 'badPID')
            ";
            $PDOdb->run($wSql,[$pid, $uIP, $uinfo]);
            removeUsersCookie();
            throwError("badPID");
        }else {
            setLocalCookie($pidName, $myID, $cookieTime);
        } 
    }
    
    return true;
    //$encodedExpDate = mc_decrypt($explodedToken[1], ENCRYPTION_KEY);
}

?>