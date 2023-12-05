<?php  

    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Invalid URL

    ****************/
    
    require("connect.php");
    require("library.php");

    session_start();

    sleep(3);

    if(isset($_SESSION['client'])) {
        header("Location: index.php?sort_genre=none&sort_title=none&date_sort=none");

    } else {
        header("Location: login.php");

    }
    ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>The No No Page</title>
    </head>
    <body>
        <h1>ACCESS DENIED!</h1>
        <h1>RETURNING HOME IN A SECOND...</h1>
    </body>
</html>