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
                <ul>
                    <li>Engage</li> <!-- Logo -->
                    <li><a href="index.php">Home</a></li>
                    <li>
                        <a href="login.php">
                            <button type="button">Sign In</button>
                        </a>        
                    </li>
                    <?php if(isset($_SESSION['client'])): ?>
                        <li><a href="create_post.php">New Post</a></li>
                    <?php endif ?>
                </ul>
            </nav>
        </header>
        <main>
            <!-- Print out the list of contents here -->
            <!-- Categories -->
            <!-- Use loop to generate list, and differentiate user and admin -->
            
        </main>
    </body>
</html>