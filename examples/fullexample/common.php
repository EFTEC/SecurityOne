<?php

use eftec\bladeone\BladeOne;
use eftec\DaoOne;
use eftec\SecurityOne;

include "vendor/eftec/daoone/lib/DaoOne.php";
include "../../lib/SecurityOne.php";
include "vendor/eftec/bladeone/lib/BladeOne.php";
$conn=new DaoOne("127.0.0.1","root","abc.123","securitytest","log.txt"); //CREATE SCHEMA `securitytest` ;

try {
    $conn->connect();
} catch (Exception $e) {
    die("Error :".$e->getMessage());
}

$sec=new SecurityOne();


$sec->setLoginFn(function(SecurityOne $sec) {
    global $conn;
    // load the user from the database
    $user=$conn->select("*")
        ->from("user")
        ->where(['user'=>$sec->user])
        ->where(['password'=>$sec->password])
        ->first();
    if (empty($user)) {
        return false;
    } else {
        $sec->user=$user['user'];
        $sec->password=$user['password'];
        $sec->name=$user['fullname'];
        $sec->email=$user['email'];

        $sec->iduser=$user['iduser'];
        // load the roles (if any)
        $userxRole=$conn->select("r.name")
            ->from("userxrole ur")
            ->join("role r on ur.idrole=r.idrole")
            ->where("ur.iduser=?",['i',$sec->iduser])
            ->toList();
        $roles=[];
        foreach($userxRole as $tmp) {
            $roles[]=$tmp['name'];
        }

        $sec->group=$roles;
        return true;
    }
});

$sec->setStoreCookieFn(function (SecurityOne $sec) {
   global $conn;
   $conn->set(['iduser'=>$sec->iduser,'cookie'=>$sec->cookieID])
       ->from('usercookie')
       ->insert();
   // garbarge collector, we delete all expired cookies.
    $conn->from("usercookie")
        ->where("datecreated < DATE_SUB(NOW(),INTERVAL 1 YEAR)")
        ->delete();
});

$sec->setGetStoreCookieFn(function (SecurityOne $sec) {
    global $conn;
    return $conn->select("cookie")
        ->from("usercookie")
        ->where(['cookie'=>$sec->cookieID])
        ->firstScalar();
});

$blade=new BladeOne("view","compile");

