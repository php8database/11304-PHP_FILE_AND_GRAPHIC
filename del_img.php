<?php

$imgName=$_GET['file'];
unlink("./files/$imgName") ;
header("location:manage.php");