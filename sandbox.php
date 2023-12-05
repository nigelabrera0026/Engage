
<?php 
    
    /**
     * SANDBOX FOR TEST
     * 
     * TO TEST:
     * 
     */

     
    require("connect.php");
    require("library.php");
    $error = [];

    // function verify_user_existence($db, $email) { // TODO TBT
    //     if(user_or_admin($email)) { // true if admin
    //         $query = "SELECT * FROM admins WHERE email = :email";

    //     } else {
    //         $query = "SELECT * FROM users WHERE email = :email";
    //     }
        
    //     $statement = $db->prepare($query);
    //     $statement->bindValue(':email', $email, PDO::PARAM_STR);
    //     $statement->execute();
    //     $result = $statement->fetch(PDO::FETCH_ASSOC);

    //     if(!empty($result)){
    //         return $result['email'];

    //     } else {
    //         return null;
    //     }
    // }

    // $query = "SELECT title FROM contents WHERE title = :title";
    // $title = filter_var($title, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    // $statement = $db->prepare($query);
    // $statement->bindValue(':title', "magnets", PDO::PARAM_STR);
    // $statement->execute();
    // $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    // print_r($results);


    // $contents = [];
    // $contents[] = "song_file";
    // $contents[] = "title";
    // $contents[] = "genre";

    // $values = [];
    // $values[":song_file"] = "Test song file";
    // $values[':title'] = "Test title";
    // $values[':genre'] = "Test Genre";
    
    // $contents = implode(", ", $contents);
    // $placeholders = implode(", ", array_map(function ($param) {
    //     return ":" . $param;
    // }, array_keys($values)));

    // foreach($values as $param => $values) {
    //     echo $param . " " . $values;
    // }
  
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Page</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <form action="create_post.php" method="post" enctype="multipart/form-data">
            <fieldset>
                <legend>Upload Content</legend>
                <div>
                    <img id="image_preview" src="#" alt="Image Preview" style="max-width: 100%; max-height: 200px; display: none;">
                    <label for="image_cover">Image</label> <!-- prompt image -->
                    <input type="file" name="image_cover" id="image_cover" accept="image/*"/>
                    <button type="button" id="remove_image" class="hidden">Remove Image</button>
                </div>
                <div>
                    <label for="song_file">Song</label>
                    <input type="file" name="song_file" id="song_file" accept="audio/*" />
                    <!-- FIXME Button should be hidden when songfile is not set, FIX IN JS -->
                    <button type="button" id="remove_song_file" class="hidden">Remove Song</button>
                    <label for="song_name">Title</label>
                    <input type="text" name="song_name" id="song_name" />
                    <label for="song_genre">Genre</label>
                    <input type="text" name="song_genre" id="song_genre" />
                </div>
            </fieldset>
            <input type="submit" name="submit" value="Upload" />
        </form>
    </body>
</html>
