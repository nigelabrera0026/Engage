<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Creating new post, validated with a session cookie.

    ****************/

    /*
        FIXED CREATE POST DONE
        APPLY ARTISTS OR SOME SHIT.
        MAKE GENRE DROP DOWN SO EXISTING GENRE IS A MUST!

    */
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
        
            if(empty($_POST['song_name']) || empty($_POST['song_genre'])) {
                $error[] = "Invalid Empty Field";

            } else {
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
                        $statement->bindValue('genre_name', strtolower($genre_name), PDO::PARAM_STR);
                        $statement->execute();

                        $genre_id = verify_genre($db, $genre_name); 

                    } else { // Retrieve genre id 
                        $genre_id = verify_genre($db, $genre_name); 
                        
                    }
                    

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
        <link rel="stylesheet" href="./style.css">
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
                            <a href="user_stuff.php?user_id=<?= $_SESSION['client_id'] ?>">
                                <?= username_cookie($_SESSION['client']) ?>
                            </a>
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
    </body>
</html>
<?php 

                // // Init code dump
                // $user_id = retrieve_userID($db);
                // $genre_name = filter_input(INPUT_POST, 'song_genre', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                // $genre_id;
    
                // // if genre does not exist
                // if(empty(verify_genre($db, strtolower($genre_name)))){
                //     $query = "INSERT INTO genres(genre_name) VALUES (:genre_name)";
    
                //     $statement = $db->prepare($query);
                //     $statement->bindValue(':genre_name', strtolower($genre_name), PDO::PARAM_STR);
                //     $statement->execute();

                //     $genre_id = verify_genre($db, $genre_name); 
                
    
                // } else { // Retrieve genre id 
                //     $genre_id = verify_genre($db, $genre_name); 
                    
                // }
                
                // // File upload pointers
                // $file_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0) 
                // && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] === 0);

                // $upload_error_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] > 0) 
                // && isset($_FILES['song_file']) && ($_FILES['song_file']['error'] > 0);
    
                // // Pointer for image existence
                // $image_upload_detected = isset($_FILES['image_cover']) && ($_FILES['image_cover']['error'] === 0);
                // $image_error_detected = ($_FILES['image_cover']['error'] > 0);

                // // Pointer for audio existence
                // $song_upload_detected = isset($_FILES['song_file']) && ($_FILES['song_file']['error'] === 0);
                // $song_error_detected = ($_FILES['song_file']['error'] > 0);

                // // Filtration and sanitization
                // $client_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
                // $title = filter_var($_POST['song_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                // // Init for scoping reasons.
                // $song_temp_path;
                // $song_name;
                // $song_upload_path;
                // $image_temp_path;
                // $image_name;
                // $image_upload_path;
                // $query;
                // $statement;
                // $new_upload_path;

                // // Verifies existence of title.
                // if(is_null(verify_title($db, $title))) {





                //     // If the user is an admin
                //     if(isset($_SESSION['isadmin'])){ 

                //         $img_set = false;
                //         $audio_set = false;

                //         if($image_upload_detected && $song_upload_detected) {
                //             $query = "INSERT INTO contents(admin_id, genre_id, images, image_name, song_file, title) 
                //             VALUES (:admin_id, :genre_id, :images, :image_name, :song_file, :title)";

                            
                //             // Image File
                //             $image_temp_path = $_FILES['image_cover']['tmp_name'];
                //             $image_name = $_FILES['image_cover']['name'];

                //             $image_upload_path = file_upload_path($image_name);

                //             // Audio File
                //             $song_temp_path = $_FILES['song_file']['tmp_name'];
                //             $song_name = $_FILES['song_file']['name'];

                //             $song_upload_path = file_upload_path($song_name);

                //             if(file_is_valid($image_temp_path, $image_upload_path) 
                //             && file_is_valid($song_temp_path, $song_upload_path)) {
                //                 move_uploaded_file($image_temp_path, $image_upload_path);
                //                 move_uploaded_file($song_temp_path, $song_upload_path);
            
                //                 $image_content = $_FILES['image_cover'];
                //                 $image_name = $_FILES['image_cover']['name'];
                //                 $song_content = $_FILES['song_file'];
                //                 $new_upload_path = file_upload_path($image_name);
            
                //                 // Preparation, Binding
                //                 $statement = $db->prepare($query);
                                
                //                 $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                //                 $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                //                 $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                //                 $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);
                //                 $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                //                 $statement->bindValue(':title', $title, PDO::PARAM_STR);
                                
                //             } else {
                //                 $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
                //             }
                //         } elseif(!$image_upload_detected && $song_upload_detected) {
                //             $query = "INSERT INTO contents(admin_id, genre_id, song_file, title) 
                //             VALUES (:genre_id, :user_id, :song_file, :title)";

                //             // Audio File
                //             $song_temp_path = $_FILES['song_file']['tmp_name'];
                //             $song_name = $_FILES['song_file']['name'];
 
                //             $song_upload_path = file_upload_path($song_name);

                //             if(file_is_valid($song_temp_path, $song_upload_path)) {
                //                 move_uploaded_file($song_temp_path, $song_upload_path);
                                
                //                 $song_content = $_FILES['song_file'];

                //                 // Preparation, Binding
                //                 $statement = $db->prepare($query);
                                
                //                 $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                //                 $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                //                 $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                //                 $statement->bindValue(':title', $title, PDO::PARAM_STR);

                //             } else {
                //                 $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
                //             }
                //         } elseif($image_upload_detected && !$song_upload_detected) {
                //             $query = "INSERT INTO contents(genre_id, user_id, images, image_name, title) 
                //             VALUES (:genre_id, :user_id, :images, :image_name, :title)";

                //             // Image File
                //             $image_temp_path = $_FILES['image_cover']['tmp_name'];
                //             $image_name = $_FILES['image_cover']['name'];

                //             $image_upload_path = file_upload_path($image_name);
                //             if(file_is_valid($image_temp_path, $image_upload_path)){
                        
                //                 move_uploaded_file($image_temp_path, $image_upload_path);
                                
            
                //                 $image_content = $_FILES['image_cover'];
                //                 $image_name = $_FILES['image_cover']['name'];
                                
                //                 $new_upload_path = file_upload_path($image_name);
            
                //                 // Preparation, Binding
                //                 $statement = $db->prepare($query);
                                
                //                 $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                //                 $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
                //                 $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                //                 $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);                    
                //                 $statement->bindValue(':title', $title, PDO::PARAM_STR);
            
                //             } else {
                //                 $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
                //             }
                //         } else {
                            
                //         }

                //         // // Image 
                //         // if($image_upload_detected) {
                //         //     $query = "INSERT INTO contents(admin_id, genre_id, images, image_name, song_file, title) 
                //         //     VALUES (:admin_id, :genre_id, :images, :image_name, :song_file, :title)";

                //         //     $img_set = true;

                //         //     // Image File
                //         //     $image_temp_path = $_FILES['image_cover']['tmp_name'];
                //         //     $image_name = $_FILES['image_cover']['name'];

                //         //     $image_upload_path = file_upload_path($image_name);
                        
                //         // } else { // If they don't want to upload image.
                //         //     $query = "INSERT INTO contents(admin_id, genre_id, song_file, title)
                //         //     VALUES (:admin_id, :genre_id, :song_file, :title)";

                //         // }
                        
                //         // // Audio
                //         // if($song_upload_detected) {
                //         //     if($img_set) {
                //         //         $query = "INSERT INTO contents(admin_id, genre_id, images, image_name, song_file, title) 
                //         //         VALUES (:admin_id, :genre_id, :images, :image_name, :song_file, :title)";

                //         //     } else {
                //         //         $query = "INSERT INTO contents(admin_id, genre_id, song_file, title) 
                //         //         VALUES (:admin_id, :genre_id, :song_file, :title)";
                                
                //         //     }
                            
                //         //     $audio_set = true;

                //         //     // Audio File
                //         //     $song_temp_path = $_FILES['song_file']['tmp_name'];
                //         //     $song_name = $_FILES['song_file']['name'];

                //         //     $song_upload_path = file_upload_path($song_name);
                        
                //         // } else { // If they don't want to upload image.
                //         //     $query = "INSERT INTO contents(admin_id, genre_id, song_file, title)
                //         //     VALUES (:admin_id, :genre_id, :song_file, :title)";

                //         // }
                        
                //         // if($img_set && $audio_set) {
                //         //     if(file_is_valid($image_temp_path, $image_upload_path) 
                //         //     && file_is_valid($song_temp_path, $song_upload_path)){
                        
                //         //         move_uploaded_file($image_temp_path, $image_upload_path);
                //         //         move_uploaded_file($song_temp_path, $song_upload_path);
            
                //         //         $image_content = $_FILES['image_cover'];
                //         //         $image_name = $_FILES['image_cover']['name'];
                //         //         $song_content = $_FILES['song_file'];

                //         //         $new_upload_path = file_upload_path($image_name);
            
            
                //         //         // Preparation, Binding
                //         //         $statement = $db->prepare($query);
                                
                //         //         $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                //         //         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                //         //         $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                //         //         $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);
                //         //         $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                //         //         $statement->bindValue(':title', $title, PDO::PARAM_STR);
            
                //         //     } else {
                //         //         $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
                //         //     }
                //         // } elseif($img_set && !$audio_set) {
                //         //     if(file_is_valid($image_temp_path, $image_upload_path)) {
                                
                //         //         move_uploaded_file($image_temp_path, $image_upload_path);
                                
                //         //         $image_content = $_FILES['image_cover'];
                //         //         $image_name = $_FILES['image_cover']['name'];
                //         //         $new_upload_path = file_upload_path($image_name);

                //         //         $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                //         //         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                //         //         $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                //         //         $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);                      
                //         //         $statement->bindValue(':title', $title, PDO::PARAM_STR);

                //         //     } else {
                //         //         $error[] = "Invalid file type or mime type for either image file.";

                //         //     }
                //         // } elseif(!$img_set && $audio_set) {
                //         //     if(file_is_valid($song_temp_path, $song_upload_path)) {

                //         //         move_uploaded_file($song_temp_path, $song_upload_path);

                //         //         $song_content = $_FILES['song_file'];

                //         //         // Preparation, Binding
                //         //         $statement = $db->prepare($query);
                                
                //         //         $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                //         //         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                //         //         $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                //         //         $statement->bindValue(':title', $title, PDO::PARAM_STR);
                                                         
                //         //     } else {
                //         //         $error[] = "Error: Invalid file type for audio.";

                //         //     }
                //         // } else {

                //         // }

                //         if(!empty($new_upload_path)) {
                //             if(in_array(pathinfo($new_upload_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png'])) {
                //                 $resized_medium_image = file_upload_path($image_name, 'uploads_medium');
                //                 $resized_thumbnail_image = file_upload_path($image_name, 'uploades_thumbnail');
    
                //                 try {
                //                     $image = new ImageResize($image_upload_path);
                //                     $image->resizeToWidth(400);
                //                     $image->save($resized_medium_image);
    
                //                     $image = new ImageResize($image_upload_path);
                //                     $image->resizeToWidth(50);
                //                     $image->save($resized_thumbnail_image);
    
                //                 } catch (ImageResize $e) {
                //                     $error[] = "Error: Something is wrong with Image Resize.";
                //                 }
                //             }
                //         }

                        
                //         if($statement->execute()) {
                //             header('Location: index.php');
                //             exit();
                //         }   

                        // if(!$image_upload_detected) {

                        //     if(file_is_valid($song_temp_path, $song_upload_path)) {
                        //         move_uploaded_file($song_temp_path, $song_upload_path);

                        //         $song_content = $_FILES['song_file'];

                        //         // Preparation, Binding, Execution
                        //         $statement = $db->prepare($query);
                                
                        //         $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                        //         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                        //         $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                        //         $statement->bindValue(':title', $title, PDO::PARAM_STR);
        
                        //         if($statement->execute()) {
                        //             header('Location: index.php');
                        //             exit();
                        //         }                            
                        //     } else {
                        //         $error[] = "Error: Invalid file type for audio.";

                        //     }
                        // } elseif(file_is_valid($image_temp_path, $image_upload_path) 
                        // && file_is_valid($song_temp_path, $song_upload_path)){
                    
                        //     move_uploaded_file($image_temp_path, $image_upload_path);
                        //     move_uploaded_file($song_temp_path, $song_upload_path);
        
                        //     $image_content = $_FILES['image_cover'];
                        //     $image_name = $_FILES['image_cover']['name'];
                        //     $song_content = $_FILES['song_file'];
        
        
                        //     // Preparation, Binding, Execution
                        //     $statement = $db->prepare($query);
                            
                        //     $statement->bindValue(':admin_id', $client_id, PDO::PARAM_INT);
                        //     $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
                        //     $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
                        //     $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);
                        //     $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
                        //     $statement->bindValue(':title', $title, PDO::PARAM_STR);
        
                        //     if($statement->execute()) {
                        //         header('Location: index.php');
                        //         exit();
                        //     }

                        // } else {
                        //     $error[] = "Error: Invalid file type or mime type for either image or song file.";
        
                        // }
        //             } else {
        //                 if($image_upload_detected && $song_upload_detected) {
        //                     $query = "INSERT INTO contents(genre_id, user_id, images, image_name, song_file, title) 
        //                     VALUES (:genre_id, :user_id, :images, :image_name, :song_file, :title)";

        //                     // Image File
        //                     $image_temp_path = $_FILES['image_cover']['tmp_name'];
        //                     $image_name = $_FILES['image_cover']['name'];

        //                     $image_upload_path = file_upload_path($image_name);

        //                     // Audio File
        //                     $song_temp_path = $_FILES['song_file']['tmp_name'];
        //                     $song_name = $_FILES['song_file']['name'];

        //                     $song_upload_path = file_upload_path($song_name);

        //                     if(file_is_valid($image_temp_path, $image_upload_path) 
        //                     && file_is_valid($song_temp_path, $song_upload_path)) {
        //                         move_uploaded_file($image_temp_path, $image_upload_path);
        //                         move_uploaded_file($song_temp_path, $song_upload_path);
            
        //                         $image_content = $_FILES['image_cover'];
        //                         $image_name = $_FILES['image_cover']['name'];
        //                         $song_content = $_FILES['song_file'];
        //                         $new_upload_path = file_upload_path($image_name);
            
        //                         // Preparation, Binding
        //                         $statement = $db->prepare($query);
                                
        //                         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
        //                         $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
        //                         $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
        //                         $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);
        //                         $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
        //                         $statement->bindValue(':title', $title, PDO::PARAM_STR);
                                
        //                     } else {
        //                         $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
        //                     }
        //                 } elseif(!$image_upload_detected && $song_upload_detected) {
        //                     $query = "INSERT INTO contents(genre_id, user_id, song_file, title) 
        //                     VALUES (:genre_id, :user_id, :song_file, :title)";

        //                     // Audio File
        //                     $song_temp_path = $_FILES['song_file']['tmp_name'];
        //                     $song_name = $_FILES['song_file']['name'];
 
        //                     $song_upload_path = file_upload_path($song_name);

        //                     if(file_is_valid($song_temp_path, $song_upload_path)) {
        //                         move_uploaded_file($song_temp_path, $song_upload_path);
                                
        //                         $song_content = $_FILES['song_file'];

        //                         // Preparation, Binding
        //                         $statement = $db->prepare($query);
                                
        //                         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
        //                         $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
        //                         $statement->bindValue(':song_file', file_get_contents($song_upload_path), PDO::PARAM_LOB);                        
        //                         $statement->bindValue(':title', $title, PDO::PARAM_STR);

        //                     } else {
        //                         $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
        //                     }
        //                 } elseif($image_upload_detected && !$song_upload_detected) {
        //                     $query = "INSERT INTO contents(genre_id, user_id, images, image_name, title) 
        //                     VALUES (:genre_id, :user_id, :images, :image_name, :title)";

        //                     // Image File
        //                     $image_temp_path = $_FILES['image_cover']['tmp_name'];
        //                     $image_name = $_FILES['image_cover']['name'];

        //                     $image_upload_path = file_upload_path($image_name);
        //                     if(file_is_valid($image_temp_path, $image_upload_path)){
                        
        //                         move_uploaded_file($image_temp_path, $image_upload_path);
                                
            
        //                         $image_content = $_FILES['image_cover'];
        //                         $image_name = $_FILES['image_cover']['name'];
                                
        //                         $new_upload_path = file_upload_path($image_name);
            
        //                         // Preparation, Binding
        //                         $statement = $db->prepare($query);
                                
        //                         $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
        //                         $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
        //                         $statement->bindValue(':images', file_get_contents($image_upload_path), PDO::PARAM_LOB);
        //                         $statement->bindValue(':image_name', $image_name, PDO::PARAM_STR);                    
        //                         $statement->bindValue(':title', $title, PDO::PARAM_STR);
            
        //                     } else {
        //                         $error[] = "Error: Invalid file type or mime type for either image or song file.";
            
        //                     }
        //                 } else {
        //                     $query = "INSERT INTO contents(genre_id, user_id, title) 
        //                     VALUES (:genre_id, :user_id, :title)";

        //                     $statement = $db->prepare($query);
        //                     $statement->bindValue(':genre_id', $genre_id, PDO::PARAM_INT);
        //                     $statement->bindValue(':user_id', $client_id, PDO::PARAM_INT);
        //                     $statement->bindValue(':title', $title, PDO::PARAM_STR);

        //                 }

        //                 if(!empty($new_upload_path)) {
        //                     if(in_array(pathinfo($new_upload_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png'])) {
        //                         $resized_medium_image = file_upload_path($image_name, 'uploads_medium');
        //                         $resized_thumbnail_image = file_upload_path($image_name, 'uploades_thumbnail');
    
        //                         try {
        //                             $image = new ImageResize($image_upload_path);
        //                             $image->resizeToWidth(400);
        //                             $image->save($resized_medium_image);
    
        //                             $image = new ImageResize($image_upload_path);
        //                             $image->resizeToWidth(50);
        //                             $image->save($resized_thumbnail_image);
    
        //                         } catch (ImageResize $e) {
        //                             $error[] = "Error: Something is wrong with Image Resize.";
        //                         }
        //                     }
        //                 }

        //                 if($statement->execute()) {
        //                     header('Location: index.php');
        //                     exit();
        //                 }   
        //             }
        //         } else {
        //             $error[] = "Error: Music file exists! no duplication.";

        //         }

?>