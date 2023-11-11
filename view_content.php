<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    session_start();
    /*
        Dev notes: session_start(); carries over all the session.

        VIEW CONTENT WILL BE VIEWED WITH COMMENTS.
        AND COMMENTS CAN INCLUDE DOCS.
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
                <a href="login.php"> <!-- Change to logout button -->
                    <button type="button">Sign In</button>
                </a>
            </nav>
        </header>
        <main>
            <a href="create_post.php"><button type="submit"></button></a>
            
        </main>
        <h1>Test</h1>
    </body>
</html>