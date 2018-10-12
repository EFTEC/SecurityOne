<?php
include "../lib/SecurityOne.php";

use eftec\SecurityOne;
// set securityone
$security=new SecurityOne();
$security->setLoginFn(
    function(SecurityOne $sec) {
        if ($sec->user=='admin' && $sec->equalPassword('admin')) {
            // is an admin
            $sec->fullName="Bob.Admin";
            $sec->role='admin';
            $sec->group=['admin','user','sysop'];
            return true;
        }
        if ($sec->user=='user' && $sec->equalPassword('user')) {
            // is an admin
            $sec->fullName="Bob.User";
            $sec->role='user';
            $sec->group=['user'];
            return true;
        }
        return false;
    });


$button=@$_POST['button'];
$message="";
$logged=false;

if ($button) {
    $logged=$security->login($_POST['user'],$_POST['password']);
    $message=(!$logged)?"User {$_POST['user']} or password {$_POST['password']} incorrect":"";
}



if (!$button || !$logged) {
    ?>
    <form method="post">
        <h1>Use admin/admin or user/user to login</h1>
        <label for="user" style="display:inline-block; width:100px;">user:</label>
        <input type="text" id="user" name="user"/><br/>
        <label for="password" style="display:inline-block; width:100px;">password:</label>
        <input type="password" id="password" name="password"/><br/>
        <input type="submit" name="button" value="login"/><br/>
        <span style="color:red"><?=$message;?></span>
    </form>
    <?php
} else {
    ?>
    <h1>User logged as <?=$security->fullName;?></h1>
    <a href="2.logged.php">go to 2 as any user</a>
    <a href="2.logged.admin.php">go to 2 as administrator</a>
    <a href="3.logout.php">go to 3 logout</a>
    <?php
}



