<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    session_start();

    /*
     * DEV NOTES:
     * Check if you need to verify existence of a session from user.
     */

    // Global Var
    $error = [];

    function retrieve_userID($db) {
        
        if(isset($_SESSION['isadmin'])) {
            $query = "SELECT admin_id FROM admins WHERE email = :email";

        } else {
            $query = "SELECT user_id FROM users WHERE email = :email";

        }

        // Sanitization, Preparation, Binding, Execution, and Retrieval
        $email_sanitized = filter_var($_SESSION['client'], FILTER_SANITIZE_EMAIL);
        $statement = $db->prepare($query);
        $statement->bindValue(':email', $email_sanitized, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetch();

    }

    function verify_genre($db, $genre_name) {
        
        $query = "SELECT genre_id FROM genres WHERE genre_name = :genre_name";

        $statement = $db->prepare($query);
        $statement->bindValue(':genre_name', $genre_name, PDO::PARAM_STR);
        $statement->execute();

        return $statement->fetch();

    }

    if($_SERVER['REQUEST_METHOD'] == "POST"){

        if(empty($_POST['song_name']) || empty($_POST['song_genre'] 
        || !isset($_POST['image_cover'])) || !isset($_POST['song_file'])) {

            $error[] = "Invalid Empty Field";
            die();
            
        } else {
            /**
             * FLOW CONTROL
             * 
             * GET user id using session client which returns email
             * associate insert of id
             * 
             * upload genre first then check if it exists
             * 
             * 
             * add functions for validation of mime type check previous samples.
             */
            
            // Init
            $user_id = retrieve_userID($db);
            $genre_name = filter_input(INPUT_POST, 'song_genre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $genre_id = 0;

            // if genre does not exist
            if(empty(verify_genre($db, strtolower($genre_name)))){
                $query = "INSERT INTO genres(genre_name) VALUES (:genre_name)";

                $statement = $db->prepare($query);
                $statement->bindValue(':genre_name', strtolower($genre_name));
                $statement->execute();

            } else { // Retrieve genre id
                $query = "SELECT genre_id FROM genres WHERE :genre_name = genre_name";

                $statement = $db->prepare($query);
                $statement->bindValue(':genre_name', strtolower($genre_id));
                $statement->execute();
                $result = $statement->fetch(); 

                $genre_id = $result['genre_id'];
            }

            if(isset($_SESSION['isadmin'])){
                $query = "INSERT INTO contents(admin_id, genre_id, images, song_file, title) 
                VALUES (:admin_id, :genre_id, :images, :song_file, :title)";

                // Filtration and sanitization
                $client_id = filter_var($_SESSION['client_id'], FILTER_SANITIZE_NUMBER_INT);
                $title = filter_var($_POST['song_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                // Preparation, Binding, Execution
                $statement = $db->prepare($query);
                
                $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                $statement->bindValue(':title', $title, PDO::PARAM_STR);

                
            
            } else {
                $query = "INSERT INTO contents() VALUES ()";

            }
            


        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <!-- Template for Data to be created -->
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
                    <?php // logout ?>
                    <li><a href="create_post.php">New Post</a></li>
                </ul>
            </nav>
        </header>
        <main>
            <!-- Structure for creating new stuff. -->
            <?php if(!empty($error)):?>
                <div>
                    <h1>Error(s):</h1>
                    <ul>
                        <?php foreach($error as $message): ?>
                            <li><?= $message ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>
            <div> <!-- wrapper -->
                <form action="create_post.php" method="post" enctype="multipart/form-data">
                    <fieldset>
                        <legend>Upload Song</legend>
                        <div>
                            <label for="image_cover">Image</label> <!-- prompt image -->
                            <input type="file" name="image_cover" id="image_cover" accept="image/*"/>
                        </div>
                        <div>
                            <label for="audio">Song</label>
                            <input type="file" name="song_file" id="song_file" accept="" />
                            <label for="song_name">Title</label>
                            <input type="text" name="song_name" id="song_name" />
                            <label for="song_genre">Genre</label>
                            <input type="text" name="song_genre" id="song_genre" />
                        </div>
                    </fieldset>
                    <input type="submit" name="submit" value="upload_content" />
                </form>
            </div>
        </main>
    </body>
</html>