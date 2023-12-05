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
        
            if(empty($_POST['song_name']) || empty($_POST['song_genre']) || empty($_POST['artist'])) {
                $error[] = "Invalid Empty Field";

            } else {
                // Filtration and Sanitization.
                $title = filter_input(INPUT_POST, 'song_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $genre_name = filter_input(INPUT_POST, 'song_genre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $artist = filter_input(INPUT_POST, 'artist', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                // Pointer Variables
                $contents = [];
                $values = [];

                if(!empty($title) && !empty($genre_name) && !empty($artist)) {
                    $genre_id;
                    // if genre does not exist
                    if(empty(verify_genre($db, strtolower($genre_name)))){
                        $query = "INSERT INTO genres(genre_name) VALUES (:genre_name)";

                        $statement = $db->prepare($query);
                        $statement->bindValue('genre_name', strtolower($genre_name), PDO::PARAM_STR);
                        $statement->execute();

                        $genre_id = verify_genre($db, $genre_name); 

                    } else { // Retrieve genre id 
                        $genre_id = verify_genre($db, $genre_name); 
                        
                    }
                    
                    $contents[] = 'artist';
                    $values[':artist'] = $artist;

                    $contents[] = "genre_id";
                    $values[':genre_id'] = $genre_id;


                    // Verifies if the editor is an admin or user.
                    if(isset($_SESSION['isadmin'])) {
                        $contents[] = "admin_id";
                        $values[':admin_id'] = filter_var($_SESSION['client_id'], FILTER_SANITIZE_NUMBER_INT);

                    } elseif(isset($_SESSION['client_id'])) {
                        $contents[] = "user_id";
                        $values[':user_id'] = filter_var($_SESSION['client_id'], FILTER_SANITIZE_NUMBER_INT);

                    } 
                    // TODO Point of Interest 
                    // If title exists in the db like ie changing a title to an existing one, invalid. 
                    // Tenta solution, create a function that will test if it exists or if it's the same as 
                    // Title upload
                    if(verify_title($db, $title)) {
                        $contents[] = "title";
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

                            $contents[] = "images";
                            $contents[] = "image_name";
                            $values[':images'] = file_get_contents($image_upload_path);
                            $values[':image_name'] = $image_name;

                            if(in_array(pathinfo($image_upload_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png'])) {
                                $resized_medium_image = file_upload_path($image_name, 'uploads_medium');
                                $resized_thumbnail_image = file_upload_path($image_name, 'uploads_thumbnail');

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

                            $contents[] = "song_file";
                            $values[':song_file'] = file_get_contents($song_upload_path);

                        } else {
                            $error[] = "Error in uploading audio files";

                        }
                    }

                    // Makes it 1 whole string with a , separator
                    $contents = implode(", ", $contents);
                    
                    $placeholders = implode(", ", array_map(function ($param) {
                        return $param;
                    }, array_keys($values)));
                    
                    $query = "INSERT INTO contents($contents) VALUES ($placeholders)";
                    
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

            }
        }
    } else { // Heads to Login 
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
        <title>New Post</title>
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
                <form action="create_post.php" method="post" enctype="multipart/form-data">
                    <fieldset>
                        <legend>Upload Content</legend>
                        <div>
                            <img id="image_preview" src="#" alt="Image Preview" style="max-width: 100%; max-height: 200px; display: none;">
                            <label for="image_cover">Image</label> <!-- prompt image -->
                            <input type="file" name="image_cover" id="image_cover" accept="image/*"/>
                            <button type="button" id="remove_image"  >Remove Image</button>
                        </div>
                        <div>
                            <label for="song_file">Song</label>
                            <input type="file" name="song_file" id="song_file" accept="audio/*" />
                            <!-- FIXME Button should be hidden when songfile is not set, FIX IN JS -->
                            <button type="button" id="remove_song_file">Remove Song</button>
                            <label for="song_name">Title</label>
                            <input type="text" name="song_name" id="song_name" />
                            <label for="artist">Artist</label>
                            <input type="text" name="artist" id="artist" />
                            <select name="song_genre" id="song_genre">
                                <?php $genres = retrieve_genres($db, null) ?>
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
                    <input type="submit" name="submit" value="Upload" />
                </form>
            </div>
        </main>
        <script src="./bootstrap/js/bootstrap.js"></script>
    </body>
</html>
