<?php
use eftec\SecurityOne;


include "common.php";


$security=new SecurityOne();

$security->logout();

header("location:1.basiclogin.php");
