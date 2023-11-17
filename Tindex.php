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

        CONTROL FLOW:

        check if session is set for user or admin.
        if yes, prompt create, update, delete post by the user.
        if no, hide prompt
        fetch all content and display it 
        link to the login 
        Extra note: create log out logic, and register.
                    Season the pass and apply to logic
    */

    /*
    Control Flow - Generating content Pseudocode
    * Check if user or admin exists
        if user is true
            Generate Content with CUD priv. on their own contents.
        if admin is true
            Generate Content with CUD priv. to all of them.
        else
            Generate all content without CUD priv.

    Control flow for Comments.
    Control flow for Sortation
    */

    // If page reloads
    $query = "SELECT * FROM contents"; 

    $statement = $db->prepare($query);
    $results = $statement->execute();
    
    // use hash $results[0][]
    // $results->fetchAll();

    // TODO: LIMIT {dynamic} 

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
                </ul>
            </nav>
        </header>
        <main>
            <form action="Tindex.php" method="post">
                <fieldset>
                    <legend>Contents</legend>
                    <div>
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" />
                    </div>
                </fieldset>
                <input type="submit" name="submit" value="search_button"/>
            </form>
            <!-- Prototype form will be used for sortation-->
            <form action="Tindex.php" method="post">
                <nav>
                    <ul>
                        <li></li>
                        <li></li>
                    </ul>
                </nav>
            </form>
            <!-- Print out the list of contents here -->
            <!-- Categories -->
            <!-- Use loop to generate list, and differentiate user and admin -->

            <!-- Showing the contents -->
            <!-- Verify if user or admin exists -->
            <!-- Do non user first -->
            <div>
                <?php if(isset($_SESSION['isadmin'])):?>
                    <div></div>
                <?php //elseif(isset($_SESSION['']) ?>
                <?php endif ?>
            </div>
        </main>
    </body>
</html>