<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/29/2023
        @description: Login page with authentication

    ****************/

    require("connect.php");
    require("library.php");
    session_start();

    // Global vars
    $error = [];
    

    if(isset($_SESSION['isadmin'])) {
        $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

        // if();
    } else {
        header("Location: invalid_url.php");
        exit();
        
    }



?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit user</title>
    </head>
    <body>
        
    </body>
</html>