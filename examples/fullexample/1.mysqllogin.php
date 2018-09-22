<?php

use eftec\SecurityOne;

include "common.php";

if ($sec->getStoreCookie()) {
    // autologin
    echo "autologin";
    die(1);
}

$button=@$_POST['button'];
$message="";
$logged=false;

if ($button) {
    var_dump($_POST);
    $logged=$sec->login($_POST['user'],$_POST['password'],($_POST['remember']=='1'));
    $message=(!$logged)?"User or password incorrect":"";
    var_dump($logged);
    die(1);
}



if (!$button || !$logged) {
    echo $blade->run("login",['user'=>$sec->user,'password'=>$sec->password,'message'=>$message]);
} else {
    ?>
    <h1>User logged as <?=$sec->name;?></h1>
    <ul>
    <?php foreach($sec->group as $group) {
        echo "<li>$group</li>";
    }
    ?>
    </ul>
    <a href="2.logged.php">go to 2 as any user</a>
    <a href="2.logged.admin.php">go to 2 as administrator</a>
    <?php
}



