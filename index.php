<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");

    /*
        Dev notes: session_start(); carries over all the session.
    */

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <header>
            <nav>
                <a href="index.php">Home</a>
                <a href="login.php">
                    <button type="button">Sign In</button>
                </a>
            </nav>
        </header>
        <h1>Test</h1>
    </body>
</html>