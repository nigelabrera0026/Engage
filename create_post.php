<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Creating new post, validated with a session cookie.

    ****************/
    require("connect.php");
    require("library.php");
    require ('ImageResize.php');
    require ('ImageResizeException.php');

    use Gumlet\ImageResize;
    session_start();

    // Global Var
    $error = [];

    if(isset($_SESSION['client'])) {

        if($_SERVER['REQUEST_METHOD'] == "POST"){
        
            if(empty($_POST['song_name']) || empty($_POST['song_genre']) || empty($_FILES['song_file'])) {
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
                    $statement->bindValue(':genre_name', strtolower($genre_name), PDO::PARAM_STR);
                    $statement->execute();

                    $genre_id = verify_genre($db, $genre_name); 
                
    
                } else { // Retrieve genre id 
                    $genre_id = verify_genre($db, $genre_name); 
                    
                }
                
                // File upload pointers
                $file_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0) 
                && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] === 0);

                $upload_error_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] > 0) 
                && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] > 0);
    
                // Pointer for image existence
                $image_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0);
                $image_error_detected = ($_FILES['image_cover']['error'] > 0);


                // Filtration and sanitization
                $client_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
                $title = filter_var($_POST['song_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                // Verifies existence of title.
                if(is_null(verify_title($db, $title))) {
                    // If the user is an admin
                    if(isset($_SESSION['isadmin'])){ 
                        // Image is set
                        if($image_upload_detected) {
                            $query = "INSERT INTO contents(admin_id, genre_id, images, image_name, song_file, title) 
                            VALUES (:admin_id, :genre_id, :images, :image_name, :song_file, :title)";

                            // Image
                            $image_temp_path = $_FILES['image_cover']['tmp_name'];
                            $image_name = $_FILES['image_cover']['name'];

                            $image_upload_path = file_upload_path($image_name);
                        
                        } else { // If they don't want to upload image.
                            $query = "INSERT INTO contents(admin_id, genre_id, song_file, title)
                            VALUES (:admin_id, :genre_id, :song_file, :title)";

                        }
                        
                        // Audio File
                        $song_temp_path = $_FILES['song_file']['tmp_name'];
                        $song_name = $_FILES['song_file']['name'];
                        
                        $song_upload_path = file_upload_path($song_name);
                        

                        if(!$image_upload_detected) {

                            if(file_is_valid($song_temp_path, $song_upload_path)) {
                                move_uploaded_file($song_temp_path, $song_upload_path);

                                $song_content = $_FILES['song_file'];

                                // Preparation, Binding, Execution
                                $statement = $db->prepare($query);
                                
                                $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                                $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                                $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                                $statement->bindValue(':title', $title, PDO::PARAM_STR);
        
                                if($statement->execute()) {
                                    header('Location: index.php');
                                    exit();
                                }                            
                            } else {
                                $error[] = "Error: Invalid file type for audio.";

                            }
                        } elseif(file_is_valid($image_temp_path, $image_upload_path) 
                        && file_is_valid($song_temp_path, $song_upload_path)){
                    
                            move_uploaded_file($image_temp_path, $image_upload_path);
                            move_uploaded_file($song_temp_path, $song_upload_path);
        
                            $image_content = $_FILES['image_cover'];
                            $image_name = $_FILES['image_cover']['name'];
                            $song_content = $_FILES['song_file'];
        
        
                            // Preparation, Binding, Execution
                            $statement = $db->prepare($query);
                            
                            $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                            $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                            $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                            $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);
                            $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                            $statement->bindValue(':title', $title, PDO::PARAM_STR);
        
                            if($statement->execute()) {
                                header('Location: index.php');
                                exit();
                            }

                        } else {
                            $error[] = "Error: Invalid file type or mime type for either image or song file.";
        
                        }
                    } else {
                        // Image is set
                        if($image_upload_detected) {
                            $query = "INSERT INTO contents(genre_id, user_id, images, image_name, song_file, title) 
                            VALUES ( :genre_id, :user_id, :images, :image_name, :song_file, :title)";

                            // Image
                            $image_temp_path = $_FILES['image_cover']['tmp_name'];
                            $image_name = $_FILES['image_cover']['name'];

                            $image_upload_path = file_upload_path($image_name);
                        
                        } else { // If they don't want to upload image.
                            $query = "INSERT INTO contents(genre_id, user_id, song_file, title)
                            VALUES (:genre_id, :user_id, :song_file, :title)";

                        }
                        
                        // Audio File
                        $song_temp_path = $_FILES['song_file']['tmp_name'];
                        $song_name = $_FILES['song_file']['name'];
                        
                        $song_upload_path = file_upload_path($song_name);
                        

                        if(!$image_upload_detected) {
                            if(file_is_valid($song_temp_path, $song_upload_path)) {
                                move_uploaded_file($song_temp_path, $song_upload_path);
                                
                                // TODO Check if redundant.
                                $song_content = $_FILES['song_file'];

                                // Preparation, Binding, Execution
                                $statement = $db->prepare($query);
                                
                                $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
                                $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                                $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                                $statement->bindValue(':title', $title, PDO::PARAM_STR);
        
                                if($statement->execute()) {
                                    header('Location: index.php');
                                    exit();
                                }                            
                            } else {
                                $error[] = "Error: Invalid file type for audio.";

                            }
                        } elseif(file_is_valid($image_temp_path, $image_upload_path) 
                        && file_is_valid($song_temp_path, $song_upload_path)){
                    
                            move_uploaded_file($image_temp_path, $image_upload_path);
                            move_uploaded_file($song_temp_path, $song_upload_path);
        
                            $image_content = $_FILES['image_cover'];
                            $image_name = $_FILES['image_cover']['name'];
                            $song_content = $_FILES['song_file'];
        
                            // Preparation, Binding, Execution
                            $statement = $db->prepare($query);
                            
                            $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
                            $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                            $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                            $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);
                            $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                            $statement->bindValue(':title', $title, PDO::PARAM_STR);

                            // Moving Resized images
                            $new_upload_path = file_upload_path($image_name);

                            if(in_array(pathinfo($new_upload_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png'])) {
                                $resized_medium_image = file_upload_path($image_name, 'uploads_medium');
                                $resized_thumbnail_image = file_upload_path($image_name, 'uploades_thumbnail');

                                try {
                                    $image = new ImageResize($image_upload_path);
                                    $image->resizeToWidth(400);
                                    $image->save($resized_medium_image);

                                    $image = new ImageResize($image_upload_path);
                                    $image->resizeToWidth(50);
                                    $image->save($resized_thumbnail_image);

                                } catch (ImageResize $e) {
                                    $error[] = "Error: Something is wrong with Image Resize.";
                                }
                            }

                            if($statement->execute()) {
                                header('Location: index.php');
                                exit();
                            }
                        } else {
                            $error[] = "Error: Invalid file type or mime type for either image or song file.";
        
                        }
                    }
                } else {
                    $error[] = "Error: Music file exists! no duplication.";

                }
            }
        }
    } else { // Heads to Login 
        header("Location: login.php");
        exit();
        
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="./scripts/create_post_scripts.js"></script>
        <title>New Post</title>
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
                <form action="create_post.php" method="post" enctype="multipart/form-data">
                    <fieldset>
                        <legend>Upload Content</legend>
                        <div>
                            <label for="image_cover">Image Preview</label>
                            <img id="image_preview" src="#" alt="Image Preview" style="max-width: 100%; max-height: 200px; display: none;">
                            <label for="image_cover">Image</label> <!-- prompt image -->
                            <input type="file" name="image_cover" id="image_cover" accept="image/*"/>
                            <button type="button" id="remove_image" style="display: block;">Remove Image</button>
                        </div>
                        <div>
                            <label for="audio">Song</label>
                            <input type="file" name="song_file" id="song_file" accept="audio/*" />
                            <button type="button" id="remove_song_file" style="display: block;">Remove Song</button>
                            <label for="song_name">Title</label>
                            <input type="text" name="song_name" id="song_name" />
                            <label for="song_genre">Genre</label>
                            <input type="text" name="song_genre" id="song_genre" />
                        </div>
                    </fieldset>
                    <input type="submit" name="submit" value="Upload" />
                </form>
            </div>
        </main>
    </body>
</html>