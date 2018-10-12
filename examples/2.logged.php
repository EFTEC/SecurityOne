<?php
include "../lib/SecurityOne.php";

use eftec\SecurityOne;

$security=new SecurityOne(true);

if ($security->isLogged()===false) {
    header("location:1.basiclogin.php");
    die(1);
}
$user=$security->getCurrent();
?>
<h1>Logged as <?=$security->user?> - <?=$security->fullName?></h1>
<a href="3.logout.php">logout</a>


