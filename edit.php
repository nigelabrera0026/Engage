<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/22/2023
        @description: Editing existing content.

    ****************/

    /*
        DESCRIPTION

        In order to access this page the user should be the creator.
        When this page loads, include what's posted in the content
            { Probably check A3 and probably a query but where to get id of said post check get method}
        

        WORK FLOW:
        - Display content associated with the content id. DONE
        - Make it interactable. DONE
        - Then make the post work.
        - Ensure admin or client priv logic.

        CONTROL FLOW Pseudocode: what if it is admin priv.
        if isset isadmin 
        then if isset client session cookie
        else isset client session cookie
    
        CONTROL FLOW FOR UPDATE:

    */

    

    require("connect.php");

    session_start();
    // Global Var
    $error = [];


    /**
     * Retrieving the genre name associated with the contents' ID.
     * @param db PHP Data Object to use to SQL queries.
     * @param content_id The content ID that is associated to the genre_id.
     * @return genre_name The genre name that is retrieved from the database.
     */
    function retrieve_genre_name($db, $content_id) {
        global $error;

        $query = "SELECT genre_name FROM genres WHERE genre_id = (SELECT genre_id FROM contents WHERE content_id = :content_id)";
        $statement = $db->prepare($query);
        
        if(isset($_GET['content_id'])) {
            $content_id = filter_var($_GET['content_id'], FILTER_SANITIZE_NUMBER_INT);

        } else {
            $error[] = "Error! Invalid ID!";

        }

        $statement->bindValue(':content_id', $content_id, PDO::PARAM_INT);
        $statement->execute();
        $results = $statement->fetch(PDO::FETCH_ASSOC);

        if(!is_null($results)) {
            return $results['genre_name'];

        } else {
            $error[] = "Error! Database error.";

        }
    }


    // When the page loads generate existing content which is equal to the retrieve content.
    $query = "SELECT * FROM contents WHERE content_id = :content_id";

    $content_id;

    if(isset($_GET['content_id'])) {
        $content_id = filter_var($_GET['content_id'], FILTER_SANITIZE_NUMBER_INT);

    } else {
        $error[] = "Error! Content not found!";

    }

    $statement = $db->prepare($query);
    $statement->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $statement->execute();
    $results = $statement->fetch(PDO::FETCH_ASSOC);


    // Disgusting way of doing it

    // Form is submitted
    if(isset($_SESSION['client'])) {
        
        if($_SERVER['REQUEST_METHOD'] == "POST") {
            // Update Query
            if($_POST && $_POST['submit'] == 'Update'){
                // handle if admin edited it.
                // if(isset($_SESSION['isadmin'])) {
                // } else { 
                // }

                /*
                Logic:
                Make the query dynamic like SELECT then append if certain conditions are true.
                */


                // Filtration and Sanitization.
                $song_name = filter_input(INPUT_POST, 'song_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $genre_name = filter_input(INPUT_POST, 'song_genre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                
                // Pointer Variable
                $contents = '';
                
                if(isset($_SESSION['isadmin'])) {
                    $contents = ""
                } elseif() {

                } else {

                }


                $query = "UPDATE contents SET " . $contents . "WHERE content_id = $content_id";


            // Delete Query
            } elseif($_POST && $_POST['submit'] == 'Delete') {
                $query = "DELETE FROM contents WHERE content_id = :content_id";

                $statement = $db->prepare($query);
                $statement->bindValue(':content_id', $content_id, PDO::PARAM_INT);
                
                if($statement->execute()) {
                    // Check if change is needed to user_profile.php
                    header("Location: index.php");
                    exit();

                }
            }
        }
    } else {
        // probably prompt no for 2 seconds and head to index.php
    }



?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="./scripts/create_post_scripts.js"></script>
        <title>Edit Content</title>
    </head>
    <body>
        <!-- Template for Data to be created -->
        <header>
            <nav>
                <ul>
                    <li>Engage</li> <!-- Logo -->
                    <li><a href="index.php">Home</a></li>
                    <?php if(isset($_SESSION['client'])): ?>
                        <li><!-- Style it to the middle-->
                            <a href="user_stuff.php?user_id=<?= $_SESSION['client_id'] ?>">My stuff</a>
                        </li>
                        <li>
                            <a href="logout.php">
                                <button type="button">Sign out</button>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="login.php">
                                <button type="button">Sign In</button>
                            </a> 
                        </li>   
                    <?php endif ?>
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
                <?php if($results): ?>
                    <form action="edit.php" method="post" enctype="multipart/form-data"> <!-- FIXME ! -->
                        <fieldset>
                            <legend>Edit Content</legend>
                            <div>
                                <?php if(isset($results['images'])): ?>
                                    <label for="image_cover">Image Preview</label>
                                    <img id="image_preview" src="data:image/*;base64,<?= base64_encode($results['images']) ?>" alt="Image Preview" style="max-width: 100%; max-height: 200px;">
                                <?php else: ?>
                                    <label for="image_cover">Image Preview</label>
                                    <img id="image_preview" src="#" alt="Image Preview" style="max-width: 100%; max-height: 200px; display: none;">
                                <?php endif ?>
                                <label for="image_cover">Image</label> <!-- prompt image -->
                                <input type="file" name="image_cover" id="image_cover" accept="image/*"/>
                                <button type="button" id="remove_image" style="display: block;">Remove Image</button>
                            </div>
                            <div>
                                <audio controls>
                                    <source src="data:audio/*;base64,<?= base64_encode($results['song_file']) ?>" type="audio/mpeg"/>
                                </audio>
                                <label for="audio">Song</label>
                                <input type="file" name="song_file" id="song_file" accept="audio/*" />
                                <button type="button" id="remove_song_file" style="display: block;">Remove Song</button>
                                <label for="song_name">Title</label>
                                <input type="text" name="song_name" id="song_name" value="<?= $results['title']?>"/>
                                <label for="song_genre">Genre</label>
                                <input type="text" name="song_genre" id="song_genre" value="<?= retrieve_genre_name($db, $content_id); ?>"/>
                            </div>
                        </fieldset>
                        <input type="submit" name="submit" value="Update" />
                        <input type="submit" name="submit" value="Delete" />
                    </form>
                <?php else: ?>
                    <h1><?= "No content available. "?></h1>
                <?php endif ?>
            </div>
        </main>
    </body>
</html>