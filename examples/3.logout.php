<?php
include "../lib/SecurityOne.php";

use eftec\SecurityOne;

$security=new SecurityOne();

$security->logout();

header("location:1.basiclogin.php");
