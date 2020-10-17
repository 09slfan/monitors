<?php

if(file_exists(CMF_ROOT."data/config/database.php")){
    $database=include CMF_ROOT."data/config/database.php";
}else{
    $database=[];
}

return $database;
