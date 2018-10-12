<?php
include "../lib/SecurityOne.php";

use eftec\SecurityOne;

$security=new SecurityOne(true);

if ($security->isLogged()===false) {
    header("location:1.basiclogin.php");
    die(1);
}
$security->getCurrent();

if (!$security->isMember("admin")) {
    ?>
    <h1 style="color:red">User is not member of administrator</h1>
    <a href="1.basiclogin.php">login</a><br>
    <a href="3.logout.php">logout</a><br>
    <?php
} else {
    ?>
    <h1 >Logged as Administrator <?=$security->user?> - <?=$security->fullName?></h1>
    <a href="1.basiclogin.php">login</a><br>
    <a href="3.logout.php">logout</a><br>
    <?php
}



