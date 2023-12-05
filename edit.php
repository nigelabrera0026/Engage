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

    
        CONTROL FLOW FOR UPDATE:

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

    // When the page loads generate existing content which is equal to the retrieve content.
    $query = "SELECT * FROM contents WHERE content_id = :content_id";

    $content_id = filter_var($_GET['content_id'], FILTER_SANITIZE_NUMBER_INT);;

    $statement = $db->prepare($query);
    $statement->bindValue(':content_id', $content_id, PDO::PARAM_INT);
    $statement->execute();
    $results = $statement->fetch(PDO::FETCH_ASSOC);

    


    // Form is submitted
    if((isset($_SESSION['client']) && ($results['user_id'] == $_SESSION['client_id'])) 
        || isset($_SESSION['isadmin'])) { 

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

                    // TODO Point of Interest non functional requirements.
                    // If title exists in the db like ie changing a title to an existing one, invalid. 
                    // Tenta solution, create a function that will test if it exists or if it's the same as 
                    // Title upload
                    if(verify_title($db, $title)) {
                        $contents[] = "title = :title";
                        $values[':title'] = $title;
                    
                    } else {
                        $error[] = "Invalid! Duplicated Title!";

                    }
                    
                    // If updating the image to remove it, is checked
                    if(isset($_POST['remove_set_image']) && ($_POST['remove_set_image'] === "yes")){
                        $contents[] = "images = :images";
                        $values[':images'] = null;
                        $contents[] = "image_name = :image_name";
                        $values[':image_name'] = null;

                        if(isset($_POST['image_name_db'])) { // Deleting files in File system
                            $image_name = $_POST['image_name_db'];
                            unlink("./uploads/$image_name"); 
                            unlink("./uploads_thumbnail/$image_name"); 
                            unlink("./uploads_medium/$image_name"); 
                        }
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
                        header("Location: index.php?sort_genre=none&sort_title=none&date_sort=none");
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
                    header("Location: index.php?sort_genre=none&sort_title=none&date_sort=none");
                    exit();

                }
            }
        }
    } else {
        // probably prompt no for 2 seconds and head to index.php
        header("Location: invalid_url.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="./scripts/create_post_scripts.js"></script>
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css">
        <title>Edit Content</title>
    </head>
    <body>
        <!-- Template for Data to be created -->
        <header class="bg-dark text-white p-3">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <span class="navbar-brand">Engage</span>
                    </div>
                    <div class="col-md-8">
                        <nav class="navbar navbar-expand-md justify-content-end">
                            <ul class="navbar-nav">
                                <li class="nav-item ms-3">
                                    <a href="index.php?sort_genre=none&sort_title=none&date_sort=none" class="nav-link text-light">
                                        Home
                                    </a>
                                </li>
                                <?php if(isset($_SESSION['client'])): ?>
                                    <li class="nav-item ms-3">
                                        <p class="nav-link text-light">
                                            Hello, <?= username_cookie($db, $_SESSION['client']) ?>!
                                        </p>
                                    </li>
                                    <?php if(isset($_SESSION['isadmin'])): ?>
                                        <li class="nav-item ms-3">
                                            <a href="admin_cud_users.php" class="nav-link">
                                                <button type="button" class="btn btn-warning">Moderate</button>
                                            </a>
                                        </li>
                                    <?php endif ?>
                                    <li class="nav-item ms-3">
                                        <a href="logout.php" class="nav-link">
                                            <button type="button" class="btn btn-danger">Sign out</button>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="nav-item ms-3">
                                        <a href="login.php" class="nav-link">
                                            <button type="button" class="btn btn-primary">Sign In</button>
                                        </a>
                                    </li>
                                <?php endif ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
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
                                    <label for="remove_set_image">Remove image? </label>
                                    <input type="checkbox" id="remove_set_image" name="remove_set_image" value="yes"/>
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
                                <input type="hidden" name="image_name_db" value="<?= $results['image_name']?>"/>
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
        <?php include('footer.php');?>
    </body>
</html>