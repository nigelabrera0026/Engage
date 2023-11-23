<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/18/2023
        @description: Log out

    ****************/
    session_start();
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit();
?>