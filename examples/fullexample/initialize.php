<?php
include "common.php";

echo "<h1>We are going to create the table and two users</h1>";
try {
    $sql="CREATE TABLE `user` (
          `iduser` INT NOT NULL,
          `user` VARCHAR(45) NOT NULL,
          `password` VARCHAR(64) NOT NULL,
          `fullname` VARCHAR(45) NOT NULL,
          `email` VARCHAR(45) NOT NULL,
          PRIMARY KEY (`iduser`));";

    $conn->runRawQuery($sql, array(), false);
} catch (Exception $e) {
    echo "Note: Table user not created (maybe it exists)<br>";
}

try {
    $sql="CREATE TABLE `usercookie` (
          `idcookie` INT NOT NULL auto_increment,
          `iduser` int not null,
          `cookie` VARCHAR(64) NOT NULL,
          `datecreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`idcookie`));";

    $conn->runRawQuery($sql, array(), false);
} catch (Exception $e) {
    echo "Note: Table usercookie not created (maybe it exists)<br>";
}


try {
    $sql="CREATE TABLE `role` (
      `idrole` INT NOT NULL,
      `name` VARCHAR(45) NOT NULL,
      PRIMARY KEY (`idrole`));
    ";
} catch (Exception $e) {
    echo "Note: Table role not created (maybe it exists)<br>";
}
try {
    $sql="CREATE TABLE `userxrole` (
      `iduser` INT NOT NULL,
      `idrole` VARCHAR(45) NOT NULL,
      PRIMARY KEY (`iduser`, `idrole`));
    ";
    $conn->runRawQuery($sql, array(), false);
} catch (Exception $e) {
    echo "Note: Table securitytest not created (maybe it exists)<br>";
}
// users
try {
    $conn->set(["iduser" => 1
        , "user" => "admin"
        , "password" =>$sec->encrypt("admin")
        , "fullname" => "John Doe"
        , "email" => "johndoe@email.com"])
        ->from("user")
        ->insert();
    $conn->set(["iduser"=>2
        ,"user"=>"user"
        ,"password"=>$sec->encrypt("user")
        ,"fullname"=>"Anna Smith"
        ,"email"=>"AnnaSmith@email.com"])
        ->from("user")
        ->insert();
} catch (Exception $e) {
    echo "Note: Insert 2 ommited ".$e->getMessage()."<br>";
}
// roles
try {
    $conn->set(['idrole'=>1,'name'=>'user'])->from("role")->insert();
    $conn->set(['idrole'=>2,'name'=>'admin'])->from("role")->insert();
    $conn->set(['idrole'=>3,'name'=>'sysop'])->from("role")->insert();
} catch (Exception $e) {
    echo "Note: Roles not created ".$e->getMessage()."<br>";
}
// userxrole
try {
    $conn->set(['iduser'=>1,'idrole'=>1])->from("userxrole")->insert();
    $conn->set(['iduser'=>1,'idrole'=>2])->from("userxrole")->insert();
    $conn->set(['iduser'=>2,'idrole'=>2])->from("userxrole")->insert();
} catch (Exception $e) {
    echo "Note: userxrole not created ".$e->getMessage()."<br>";
}


try {
    $cantidad = $conn->select("count(*) c")->from("user")->firstScalar();
} catch (Exception $e) {
    $cantidad=-1; // error
}

if ($cantidad>=2) {
    echo "OK: two or more users are ready ($cantidad)<br>";
} else {
    echo "ERROR: I can't find users in the table<br>";
}