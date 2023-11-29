<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/22/2023
        @description: Library of methods to be used throughout the site.

    ****************/

    function username_cookie($email) {
        $domain = explode('@', $email);

        return $domain[0];
    }

    /**
     * Slicing the email and checking if it's admin or not.
     * @param email retrieves the email to be sliced.
     * @return bool 
     */
    function user_or_admin($email) {
        // Retrieves 2 parts of the param.
        $domain = explode('@', $email);

        if(count($domain) == 2) { // if it's a valid email.
            if($domain[1] == "engage.com") {
                return true;

            } else {
                return false;
            }
        } else {
            global $error;
            $error[] = "Invalid Email format!";

        }
    }
    
    
    /**
     * Executes a query to retrieve the user's part of the email from the database of existing user.
     * @param db PHP Data Object to use to SQL queries.
     * @param admin_id The id of the admin if it's not null.
     * @param user_id The user's id if it's not null.
     * @return domain The user name before the domain.
     */
    function getUser($db, $admin_id, $user_id) {
        // Init
        $query;
        $statement;

        if(!is_null($admin_id)){
            $query = "SELECT email FROM admins WHERE admin_id = :admin_id";
            $statement = $db->prepare($query);
            $statement->bindValue(":admin_id", $admin_id, PDO::PARAM_INT);

        } elseif(!is_null($user_id)) {
            $query = "SELECT email FROM users WHERE user_id = :user_id";
            $statement = $db->prepare($query);
            $statement->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        } else {
            global $error;
            $error[] = "something is wrong.";
        }

        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if(!empty($result)) {
            $domain = explode('@', $result['email']);
            return $domain[0];

        } else {
            return "Error: DB Error.";
        }
    }

    /**
     * Retriving existing genre specified what's in the list of the genre.
     * @param db PHP Data Object to use to SQL queries.
     * @param genre_name The name of the genre to be searched.
     * @return results Array of the fetched data from the database.
     */
    function retrieve_genres($db, $genre_name) {
        if(is_null($genre_name)) {
            $query = "SELECT genre_name, genre_id FROM genres";

            $statement = $db->prepare($query);
        } else {
            $query = "SELECT genre_name, genre_id FROM genres WHERE genre_name = :genre_name";

            $statement = $db->prepare($query);
            $statement->bindValue(':genre_name', $genre_name, PDO::PARAM_STR);
        }

        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $results;
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
        if(!empty($result)) { 
            return $result['genre_id']; 
        }
    }

    /**
     * Retrieves id of the user.
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
     * Retrieves Title, to be used to verify its existence from users stuffs.
     * @param db PHP Data Object to use to SQL queries.
     * @param title The title's name to be verified, retrieved from user input.
     * @return result[title] The hash value of the title. Returns null if it doesn't exists.
     */
    function verify_title($db, $title) {
        if(isset($_SESSION['is_admin'])) {
            $query = "SELECT title FROM contents WHERE title = :title";
            $statement = $db->prepare($query);
            $statement->bindValue(':admin_id', $_SESSION['client_id'], PDO::PARAM_INT);

        } else {
            $query =  "SELECT title FROM contents WHERE title = :title AND :user_id = user_id";
            $statement = $db->prepare($query);
            $statement->bindValue(':user_id', $_SESSION['client_id'], PDO::PARAM_INT);

        }
        
        $statement->bindValue(':title', $title, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if(!is_null($result)){
            return true;

        } else {
            return false;
        }

        // $result = $statement->fetch();
        
    }
    
    /**
     * Initialize file path
     * @param original_filename The original name of the uploaded content.
     * @param upload_subfolder_name The initialized upload subfolder name.
     * @return path_segments The proper format for the path.
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
     * Validates file type and mime type.
     * @param temporary_path Location of the image stored temporarily.
     * @param new_path Location of the new path for the image.
     * @return file_extension_is_valid Returns Boolean.
     * @return mime_type_is_valid Returns Boolean.
     */
    function file_is_valid($temporary_path, $new_path) {
        $allowed_mime_types = ['image/jpg','image/jpeg', 'image/png', 'audio/mpeg'];
        $allowed_file_extension = ['jpg', 'jpeg', 'png', 'mp3'];

        $actual_file_extension = pathinfo($new_path, PATHINFO_EXTENSION);

        $actual_mime_type = mime_content_type($temporary_path);

        $file_extension_is_valid = in_array($actual_file_extension, $allowed_file_extension);
        $mime_type_is_valid = in_array($actual_mime_type, $allowed_mime_types);

        return $file_extension_is_valid && $mime_type_is_valid;
    }

?>