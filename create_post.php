<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    session_start();

    /**
     * LAST TASK:
     * TODO PROMPT IMAGE WHEN CLICKED UPLOADED.
     * 
     * FIX INDEX:
     */

    // Global Var
    $error = [];

    /**
     * Retrieves id of the user
     * @param db PHP Data Object to use to SQL queries.
     * @return result Fetched user or admin id from the database.
     */
    function retrieve_userID($db) { 
        $user_type;

        if(isset($_SESSION['isadmin'])) {
            $query = "SELECT admin_id FROM admins WHERE email = :email";
            $user_type = "admin";

        } else {
            $query = "SELECT user_id FROM users WHERE email = :email";
            $user_type = "user";
        }

        // Sanitization, Preparation, Binding, Execution, and Retrieval
        $email_sanitized = filter_var($_SESSION['client'], FILTER_SANITIZE_EMAIL);
        $statement = $db->prepare($query);
        $statement->bindValue(':email', $email_sanitized, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch();

        return $result[$user_type . '_id'];
    }

    /**
     * Verification if genre exists in the database.
     * @param db PHP Data Object to use to SQL queries.
     * @param genre_name The genre specified by the fetched user input.
     * @return result Fetched id from the database refering what id of a specific genre is.
     */
    function verify_genre($db, $genre_name) {
        $query = "SELECT genre_id FROM genres WHERE genre_name = :genre_name";

        $statement = $db->prepare($query);
        $statement->bindValue(':genre_name', $genre_name, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch();
        return $result['genre_id'];

    }
    
    /**
     * Initialize file path
     * @param original_filename The original name of the uploaded content.
     * @param upload_subfolder_name The initialized upload subfolder name
     * @return path_segments The proper format for the path
     */
    function file_upload_path($original_filename, $upload_subfolder_name = 'uploads') {
        $current_folder = dirname(__FILE__);
        $upload_folder = join(DIRECTORY_SEPARATOR, [$current_folder, $upload_subfolder_name]);

        if(!file_exists($upload_folder)) {
            // Directory, octal representation of file type and permission
            mkdir($upload_folder, 0777, true);
        }

        $path_segments = [$current_folder, $upload_subfolder_name, basename($original_filename)];
        return join(DIRECTORY_SEPARATOR, $path_segments);
    }

    /**
     * Validates file type and mime type
     * @param temporary_path Location of the image stored temporarily.
     * @param new_path Location of the new path for the image.
     * @return file_extension_is_valid Returns Boolean.
     * @return mime_type_is_valid Returns Boolean.
     */
    function file_is_valid($temporary_path, $new_path) {
        $allowed_mime_types = ['image/jpeg', 'image/png', 'audio/mpeg'];
        $allowed_file_extension = ['jpg', 'jpeg', 'png', 'mp3'];

        $actual_file_extension = pathinfo($new_path, PATHINFO_EXTENSION);

        $actual_mime_type = mime_content_type($temporary_path);

        $file_extension_is_valid = in_array($actual_file_extension, $allowed_file_extension);
        $mime_type_is_valid = in_array($actual_mime_type, $allowed_mime_types);

        return $file_extension_is_valid && $mime_type_is_valid;
    }


    if(isset($_SESSION['client'])) {

        if($_SERVER['REQUEST_METHOD'] == "POST"){
        
            if(empty($_POST['song_name']) || empty($_POST['song_genre'] 
            || empty($_FILES['image_cover'])) || empty($_FILES['song_file'])) {
                
                $song_name_empt = empty($_POST['song_name']);
                $song_genre_empt = empty($_POST['song_genre']);
                $song_img_empt = empty($_POST['image_cover']);
                $song_file_empt = empty($_POST['song_file']);

                $error[] = "Invalid Empty Field";

            } else {
                // Init
                $user_id = retrieve_userID($db);
                $genre_name = filter_input(INPUT_POST, 'song_genre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $genre_id;
    
                // if genre does not exist
                if(empty(verify_genre($db, strtolower($genre_name)))){
                    $query = "INSERT INTO genres(genre_name) VALUES (:genre_name)";
    
                    $statement = $db->prepare($query);
                    $statement->bindValue(':genre_name', strtolower($genre_name));
                    $statement->execute();
                    $result = $statement->fetch(); 
    
                    $genre_id = $result['genre_id'];
    
                } else { // Retrieve genre id 
                    $query = "SELECT genre_id FROM genres WHERE :genre_name = genre_name";

                    $statement = $db->prepare($query);
                    $statement->bindValue(':genre_name', strtolower($genre_name));
                    $statement->execute();
                    $result = $statement->fetch(); 
                    $genre_id = $result['genre_id']; 
                    $error[] = $genre_id; 
                }
                
                // File upload pointers
                $file_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0) 
                && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] === 0);

                $upload_error_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] > 0) 
                && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] > 0);
    
    
                // If the user is an admin
                if(isset($_SESSION['isadmin'])){ 
            
                    $query = "INSERT INTO contents(admin_id, genre_id, images, song_file, title) 
                    VALUES (:admin_id, :genre_id, :images, :song_file, :title)";
    
                    // Filtration and sanitization
                    $client_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
                    $title = filter_var($_POST['song_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
                    // image and song file 
                    $image_temp_path = $_FILES['image_cover']['tmp_name'];
                    $song_temp_path = $_FILES['song_file']['tmp_name'];
    
                    $image_name = $_FILES['image_cover']['name'];
                    $song_name = $_FILES['song_file']['name'];
    
                    $image_upload_path = file_upload_path($image_name);
                    $song_upload_path = file_upload_path($song_name);
    
                    if(file_is_valid($image_temp_path, $image_upload_path) 
                    && file_is_valid($song_temp_path, $song_upload_path)){
                
                        move_uploaded_file($image_temp_path, $image_upload_path);
                        move_uploaded_file($song_temp_path, $song_upload_path);
    
                        $image_content = $_FILES['image_cover'];
                        $song_content = $_FILES['song_file'];
    
    
                        // Preparation, Binding, Execution
                        $statement = $db->prepare($query);
                        
                        $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);

                        
                        $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                        $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                        $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                        $statement->bindValue(':title', $title, PDO::PARAM_STR);
    
                        if($statement->execute()) {
                            header('Location: Tindex.php');
                            exit();
                        }

                    } else {
                        $error[] = "Error: Invalid file type or mime type for either image or song file.";
    
                    }
                } else {
                    $query = "INSERT INTO contents(genre_id, user_id, images, song_file, title) 
                    VALUES (:genre_id, :user_id, :images, :song_file, :title)";
    
                    // Filtration and sanitization
                    $client_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
                    $title = filter_var($_POST['song_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
                    // Filtration of image and song file 
                    $image_temp_path = $_FILES['image_cover']['tmp_name'];
                    $song_temp_path = $_FILES['song_file']['tmp_name'];
    
                    $image_name = $_FILES['image_cover']['name'];
                    $song_name = $_FILES['song_file']['name'];
    
                    $image_upload_path = file_upload_path($image_name);
                    $song_upload_path = file_upload_path($song_name);
    
                    if(file_is_valid($image_temp_path, $image_upload_path) 
                    && file_is_valid($song_temp_path, $song_upload_path)){
                
                        move_uploaded_file($image_temp_path, $image_upload_path);
                        move_uploaded_file($song_temp_path, $song_upload_path);
    
                        $image_content = $_FILES['image_cover'];
                        $song_content = $_FILES['song_file'];
    
                        // Preparation, Binding, Execution
                        $statement = $db->prepare($query);
                        
                        $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                        $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
                        $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                        $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);
                        $statement->bindValue(':title', $title, PDO::PARAM_STR);
                        if($statement->execute()) {
                            header('Location: Tindex.php');
                            exit();
                        }
                    } else {
                        $error[] = "Error: Invalid file type or mime type for either image or song file.";
    
                    }
                }
            }
        }
    } else { // Heads to Login
        $error[] = "Login succed fix session";

    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="./scripts/create_post_scripts.js"></script>
        <title>New Content</title>
        <!-- <script>
            document.addEventListener("DOMContentLoaded", load);

            function load() {
                // Function to display image preview
                function readURL(input) {
                    if (input.files && input.files[0]) {
                        let reader = new FileReader();

                        reader.onload = function (e) {
                            document.getElementById('image_preview').src = e.target.result;
                            document.getElementById('image_preview').style.display = 'block';
                        };

                        reader.readAsDataURL(input.files[0]);
                    }
                }

                // Trigger the function when a file is selected
                let imageCoverInput = document.getElementById("image_cover");
                if (imageCoverInput) {
                    imageCoverInput.addEventListener("change", function () {
                        readURL(this);
                    });
                }
            }
        </script> -->
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
                            <label for="image_cover">Image Preview</label>
                            <img id="image_preview" src="#" alt="Image Preview" style="max-width: 100%; max-height: 200px; display: none;">
                            <label for="image_cover">Image</label> <!-- prompt image -->
                            <input type="file" name="image_cover" id="image_cover" accept="image/*"/>
                        </div>
                        <div>
                            <label for="audio">Song</label>
                            <input type="file" name="song_file" id="song_file" accept="audio/*" />
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