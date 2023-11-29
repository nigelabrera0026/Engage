<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/22/2023
        @description: Editing existing content.

    ****************/

    /*
        TODO FIXME LINE 257!


        DESCRIPTION

        In order to access this page the user should be the creator.
        When this page loads, include what's posted in the content
            { Probably check A3 and probably a query but where to get id of said post check get method}
        

        WORK FLOW:
        - Display content associated with the content id. DONE
        - Make it interactable. DONE
        - Then make the post work.
        - Ensure admin or client priv logic.

    
        CONTROL FLOW FOR UPDATE:

        TODO File Handling DONE!
        If empty(files like img and audio) continue without them 

        TODO Verification of the Title, Existence logic. To be Tested
        Create the logic for inserting Title 
        (TBH it doesn't reallly matter if you just insert it an all that. Fix it in your own project.);

        
    */

   
    require("connect.php");
    require("library.php");
    require ('ImageResize.php');
    require ('ImageResizeException.php');
    use Gumlet\ImageResize;

    session_start();

    // Global Var
    $error = [];

    /**
     * 
     * @param db
     * @param content_id
     * @param title
     * @return bool
     */
    function verify_content_title($db, $content_id, $title) {
        $query = "SELECT * FROM contents WHERE title = :title";

        $title = filter_var($title, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $statement = $db->prepare($query);
        $statement->bindValue(':title', $title, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetch(PDO::FETCH_ASSOC);

        // Check if there are exactly two columns in the result
        if (!empty($results) && count($results) == 2) {
            return true;
        } else {
            return false;
        }
    }




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

    $content_id = filter_var($_GET['content_id'], FILTER_SANITIZE_NUMBER_INT);;

    $statement = $db->prepare($query);
    $statement->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $statement->execute();
    $results = $statement->fetch(PDO::FETCH_ASSOC);



    // Form is submitted
    if(isset($_SESSION['client'])) { // TODO URL handling if the client is the real creator. prolly a function
        
        if($_SERVER['REQUEST_METHOD'] == "POST") {
            // Update Query
            if($_POST && $_POST['submit'] == 'Update'){
                // Filtration and Sanitization.
                $title = filter_input(INPUT_POST, 'song_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $genre_name = filter_input(INPUT_POST, 'song_genre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                
                // Pointer Variables
                $contents = [];
                $values = [];
                
                if(!empty($title) && !empty($genre_name)) {
                    // $contents[] = "title = :title";
                    // $values[':title'] = $title;

                    $genre_id;

                    // if genre does not exist
                    if(empty(verify_genre($db, strtolower($genre_name)))){
                        $query = "INSERT INTO genres(genre_name) VALUES (:genre_name)";
        
                        $statement = $db->prepare($query);
                        $statement->bindValue(':genre_name', strtolower($genre_name), PDO::PARAM_STR);
                        $statement->execute();

                        $genre_id = verify_genre($db, $genre_name); 
        
                    } else { // Retrieve genre id 
                        $genre_id = verify_genre($db, $genre_name); 
                        
                    }
                    
                    $contents[] = "genre_id = :genre_id";
                    $values[':genre_id'] = $genre_id;

                    // Verifies if the editor is an admin or user.
                    if(isset($_SESSION['isadmin'])) {
                        $contents[] = "admin_id = :admin_id";
                        $values[':admin_id'] = filter_var($_SESSION['client_id'], FILTER_SANITIZE_NUMBER_INT);
    
                    } elseif(isset($_SESSION['client_id'])) {
                        $contents[] = "user_id = :user_id";
                        $values[':user_id'] = filter_var($_SESSION['client_id'], FILTER_SANITIZE_NUMBER_INT);

                    } 

                    // TODO Point of Interest 
                    // If title exists in the db like ie changing a title to an existing one, invalid. 
                    // Tenta solution, create a function that will test if it exists or if it's the same as 
                    // Title upload
                    if(verify_title($db, $title)) {
                        $contents[] = "title = :title";
                        $values[':title'] = $title;
                    
                    } else {
                        $error[] = "Invalid! Duplicated Title!";

                    }
                    
                    $file_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0) 
                    && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] === 0);

                    $upload_error_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] > 0) 
                    && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] > 0);
        
                    // Pointer for image existence
                    $image_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0);
                    $image_error_detected = ($_FILES['image_cover']['error'] > 0);

                    // Pointer for audio existence
                    $song_upload_detected = isset($_FILES['song_file']) && ($_FILES['song_file']['error'] === 0);

                    // Check if audio file is uploaded also
                    $song_name = $_FILES['song_file']['tmp_name'];
                    $song_temp_path = $_FILES['song_file']['name'];

                    $song_upload_path = file_upload_path($song_name);

                    // Image upload
                    if($image_upload_detected) {
                        // Image
                        $image_temp_path = $_FILES['image_cover']['tmp_name'];
                        $image_name = $_FILES['image_cover']['name'];
                        $image_upload_path = file_upload_path($image_name);

                        if(file_is_valid($image_temp_path, $image_upload_path)){
                            move_uploaded_file($image_temp_path, $image_upload_path);

                            $contents[] = "images = :images";
                            $contents[] = "image_name = :image_name";
                            $values[':images'] = file_get_contents($image_upload_path);
                            $values[':image_name'] = $image_name;

                            
                        } else {
                            $error[] = "Error in uploading image.";

                        }
                    } 

                    // Audio upload
                    if($song_upload_detected) {
                        // Audio File
                        $song_temp_path = $_FILES['song_file']['tmp_name'];
                        $song_name = $_FILES['song_file']['name'];
                        
                        $song_upload_path = file_upload_path($song_name);

                        if(file_is_valid($song_temp_path, $song_upload_path)){
                            move_uploaded_file($song_temp_path, $song_upload_path);

                            $contents[] = "song_file = :song_file";
                            $values[':song_file'] = file_get_contents($song_upload_path);

                        } else {
                            $error[] = "Error in uploading audio files";

                        }
                    }

                    // Makes it 1 whole string with a , separator
                    $contents = implode(", ", $contents);

                    // Appending the values to be fed in the query.
                    $query = "UPDATE contents SET " . $contents . " WHERE content_id = $content_id";
                    
                    // Preparation
                    $statement = $db->prepare($query);

                    // Binding
                    foreach($values as $param => $value) {
                        $statement->bindValue($param, $value);
                        
                    }

                    // Execution
                    if($statement->execute()) {
                        header("Location: index.php");
                        exit();

                    } else {
                        $error[] = "Execution failed!";
                    }
                } else {
                    $error[] = "Invalid empty fields!";

                }
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
                    <?= $content_id ?>
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
                    <form action="edit.php?content_id=<?= $content_id ?>" method="post" enctype="multipart/form-data">
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
                                <select name="song_genre" id="song_genre">
                                    <?php $genres = retrieve_genres($db, null) ?>
                                    <option value="<?= retrieve_genre_name($db, $content_id); ?>" selected>
                                        <?= retrieve_genre_name($db, $content_id); ?>
                                    </option>
                                    <?php foreach($genres as $genre_list): ?>
                                        <?php if(is_null($genre_list['genre_name'])): ?>
                                        <?php else: ?>
                                            <option value="<?= $genre_list['genre_name'] ?>" >
                                                <?= ucfirst($genre_list['genre_name']) ?>
                                            </option>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                </select>
                                <!-- <label for="song_genre">Genre</label>
                                <input type="text" name="song_genre" id="song_genre" value="<?= retrieve_genre_name($db, $content_id); ?>"/> -->
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