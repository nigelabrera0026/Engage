<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    session_start();

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
    
    // FILE UPLOAD STUFFS
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

    function file_is_valid($temporary_path, $new_path) {
        $allowed_mime_types = ['image/jpeg', 'image/png', 'audio/mpeg3'];
        $allowed_file_extension = ['jpg', 'jpeg', 'png', 'mp3'];

        $actual_file_extension = pathinfo($new_path, PATHINFO_EXTENSION);

        $actual_mime_type = mime_content_type($temporary_path);

        $file_extension_is_valid = in_array($actual_file_extension, $allowed_file_extension);
        $mime_type_is_valid = in_array($actual_mime_type, $allowed_mime_types);

        return $file_extension_is_valid, $mime_type_is_valid;
    }



    if($_SERVER['REQUEST_METHOD'] == "POST"){

        if(empty($_POST['song_name']) || empty($_POST['song_genre'] 
        || !isset($_POST['image_cover'])) || !isset($_POST['song_file'])) {

            $error[] = "Invalid Empty Field";
            // die();
            
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
            
            // File upload pointers
            $file_upload_detected = isset($_FILES[]) && ($_FILES[]['error'] === 0);
            $upload_error_detected = isset($_FILES[]) && ($_FILES[]['error'] > 0);


            // If the user is an admin
            if(isset($_SESSION['isadmin'])){ // can be optimized but time eats away quality.
                $query = "INSERT INTO contents(admin_id, genre_id, images, song_file, title) 
                VALUES (:admin_id, :genre_id, :images, :song_file, :title)";

                // Filtration and sanitization
                $client_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
                $title = filter_var($_POST['song_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                // Filtration of image and song file 
                // TODO TEST IN SANDBOX
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

                    $image_content = file_get_contents($image_upload_path);
                    $song_content = file_get_contents($song_upload_path);

                        // Preparation, Binding, Execution
                    $statement = $db->prepare($query);
                    
                    $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                    $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                    $statement->bindValue(':images', $image_content, PDO::PARAM_LOB);
                    $statement->bindValue(':song_file', $song_content, PDO::PARAM_LOB);
                    $statement->bindValue(':title', $title, PDO::PARAM_STR);

                    $statement->execute();
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
                // TODO TEST IN SANDBOX
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

                    $image_content = file_get_contents($image_upload_path);
                    $song_content = file_get_contents($song_upload_path);

                        // Preparation, Binding, Execution
                    $statement = $db->prepare($query);
                    
                    $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                    $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
                    $statement->bindValue(':images', $image_content, PDO::PARAM_LOB);
                    $statement->bindValue(':song_file', $song_content, PDO::PARAM_LOB);
                    $statement->bindValue(':title', $title, PDO::PARAM_STR);

                    $statement->execute();
                } else {
                    $error[] = "Error: Invalid file type or mime type for either image or song file.";

                }

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