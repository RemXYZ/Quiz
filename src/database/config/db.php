<?php
if (!class_exists("DataBase")) {


class DataBase {

    public function __construct($public = null) {
        if ($public) {
            $this->publicConnect();
        }
    }

    // учетные данные базы данных 
    public $conn = null;
    public $domainName = "azixon.com";
    public $domainPrefix = "azquiz";
    public $siteURL = "https://azixon.com/";
    public $charset = "utf8";

    public $myIP = NULL;
    public $myCN = NULL;
    public function genIP() {
        $this->myIP = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER['REMOTE_ADDR'] ?? NULL;
        $this->myCN = $_SERVER["HTTP_CF_IPCOUNTRY"] ?? NULL;
    }


    public function getDBname () {
        return $this->db_name;
    }


    public function getUTF () {
        return $this->servCountryUTF;
    }


    public function changeDB($db) {
        if (!$this->conn) return false;
        $this->conn->select_db($db);
        $this->db_name = $db;
    }


    public function connect() {
        //$this->publicConnect();
        
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
        if ($this->conn->connect_errno) {
            echo "No connection ".$this->conn->connect_errno;
        }
 
        return $this->conn;
    }


    public function PDOconnect() {
        //$this->publicConnect();
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name. ";charset=". $this->charset,
                $this->username, 
                $this->password);
        } catch(PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
            // echo "Connection error: " . $e->getMessage() . (int)$e->getCode();
        }
        return $this->conn;
    }


    public function showErrors() {
        //$db = $this->connect();
        $result = $db->query("SHOW ERRORS")->fetch_assoc();
        return $result;
        $db->close();
    }


    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $db_name = "...";

    private $servCountryUTF = '....';


    private function publicConnect() {
        $this->hostname = "...";
        $this->username = "...";
        $this->password = "...";
    }

}//END of DataBase
}//END OF IF class_exists




if (!class_exists("PDOconnect")) {


class PDOconnect extends DataBase {
    public function __construct(){
        $this->PDOconnect();
        $this->options = ["SQLerror"=>true];
        //if you work in web swap 0 on 1
        parent::__construct(0);
    }


    public $stmtID = null;


    public function run($sql, $args = NULL){
        $sqlRequest = $sql;
        $args = !is_array($args) && $args ? array($args) : $args;

        ///////////////////WITHOUT ARGUMENTS/////////////////////
        if (!$args){
            $st = $this->conn->query($sql);
            if (!$st) {
                $err = $this->conn->query("SHOW ERRORS;");
                while ($row = $err->fetch(PDO::FETCH_ASSOC)){
                    if ($this->options["SQLerror"]){
                        try {
                            throw new Exception(' SQL query: "'. $sql. '" has an error: ');
                        } catch (Exception $e) {
                            echo "[".$e->getLine()."]".$e->getMessage().$sqlRequest.'"'.$row["Message"];
                            echo "<br>";
                        }
                    }
                    
                }
                return false;
            }
            
            $this->stmtID = $this->conn->lastInsertId();
            return $st;
        }

        ///////////////////WITH ARGUMENTS/////////////////////
        $stmt = $this->conn->prepare($sql);
        $status = $stmt->execute($args);
        if (!$status) {
            if ($this->options["SQLerror"]){
                try {
                    throw new Exception(' SQL query: "'. $sql. '" has an error: ');
                } catch (Exception $e) {
                    echo "[".$e->getLine()."]".$e->getMessage().$stmt->errorInfo()[2]." <br>";
                }
            }
            
            return false;
        }
        if ($stmt->rowCount() == 0) return false;
        $this->stmtID = $this->conn->lastInsertId();
        return $stmt;
    }


    public function getConn() {
        return $this->conn;
    }


    public function getLastInsertId(){
        return $this->stmtID;
    }


    public function closeConnection() {
        $this->conn = null;
    }
}// END of PDOconnect

}//END OF IF class_exists



if (!class_exists("WebsiteConf")) {

class WebSiteConf {
    private $cookieTime = 2700;

    public function setQuizCookieTime() {
        $this->cookieTime;
    }


    public function getQuizCookieTime() {
        return $this->cookieTime;
    }
}// END of WebsiteConf

}//END OF IF class_exists



function setLocalCookie($name,$val,$time) {
    setcookie($name,$val,time()+$time, "/");
}

function setPublicCookie($name,$val,$time) {
    $db = new DataBase();

    $arrCookieOptions = array (
        'expires' => time() + $time,
        'path' => '/',
        'domain' => $db->domainName, // leading dot for compatibility or use subdomain
        'secure' => true,     // or false
        'httponly' => true,    // or false
        'samesite' => 'Strict' // None, Secure || Lax  || Strict
    );

    setcookie($name,$val, $arrCookieOptions);
}


function removeCookie($name) {
    setcookie($name, null, -1, "/");
}


?>