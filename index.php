<?php
//var_dump($_COOKIE);

/*
xampp addition, unfortunately it's a crutch, because
on my computer I have my project in this path  "szkola4c\PortugaliaProject", 
and in xampp htdocs is implemented very poor quality, 
because in fact it can only work with one project
so if you work in the normal local server, please clear variable $xppUrl
*/
/*$xppUrl has:
    szkola4c\PortugaliaProject\src\database\config\crypt.php
    szkola4c\PortugaliaProject\src\database\queryTest\queryTest.php
    szkola4c\PortugaliaProject\src\database\queryTest\sendTest.php
    szkola4c\PortugaliaProject\src\database\userService\connUser.php
    szkola4c\PortugaliaProject\src\database\userService\sendResult.php
*/
$xppUrl = $_SERVER["REQUEST_URI"];
$xppUrl = $my_src;



//include main file:
include $_SERVER["DOCUMENT_ROOT"].$xppUrl ."/src/pages/index.html";

?>