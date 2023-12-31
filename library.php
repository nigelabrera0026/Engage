<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/22/2023
        @description: Library of methods to be used throughout the site.

    ****************/

    // TODO DELETE UNUSED METHODS

    /**
     * Determines if the logged user is admin or not then will fetch a query.
     * @param db PHP Data Object to use to SQL queries.
     * @param email Email input to be used to get the username
     */
    function username_cookie($db, $email) {
        if(user_or_admin($email) && isset($_SESSION['isadmin'])) {
            $query = "SELECT username FROM admins WHERE email = :email";

            $statement = $db->prepare($query);
            $email = filter_var($email, FILTER_VALIDATE_EMAIL);
            $statement->bindValue('email', $email, PDO::PARAM_STR);
            $statement->execute();
            $results = $statement->fetch(PDO::FETCH_ASSOC);

            if(!empty($results)) {
                return $results['username'];

            }
        } else {
            $query = "SELECT username FROM users WHERE email = :email";

            $statement = $db->prepare($query);
            $email = filter_var($email, FILTER_VALIDATE_EMAIL);
            $statement->bindValue('email', $email, PDO::PARAM_STR);
            $statement->execute();
            $results = $statement->fetch(PDO::FETCH_ASSOC);

            if(!empty($results)) {
                return $results['username'];

            }
        }
    }

    /**
     * Checks if the input is an email or an username.
     * @param email The input of the user to be checked.
     * @return bool Returns true or false.
     */
    function email_or_username($email) {
        $email = explode('@', $email);

        if(!empty($email[1])) {
            return false;

        } else {
            return true;

        }
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

        if((!is_null($admin_id) && !is_null($user_id)) || 
           (is_null($admin_id) && !is_null($user_id))) {
            $query = "SELECT username FROM users WHERE user_id = :user_id";

            $statement = $db->prepare($query);
            // Validation
            $user_id = filter_var($user_id, FILTER_VALIDATE_INT);
            $statement->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        } elseif(!is_null($admin_id) && is_null($user_id)) {
            $query = "SELECT username FROM admins WHERE admin_id = :admin_id";

            $statement = $db->prepare($query);
            // Validation
            $admin_id = filter_var($admin_id, FILTER_VALIDATE_INT);
            $statement->bindValue(":admin_id", $admin_id, PDO::PARAM_INT);

        } else {
            global $error;
            $error[] = "something is wrong.";
        }

        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        return $result['username'];
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

    /**
     * Retrieves all the comments from the Database.
     * @param db PHP Data Object to use to SQL queries.
     * @param content_id The id associated with the comments.
     * @return results The array of data fetched.
     */
    function retrieve_comments($db, $content_id) {
        $query = "SELECT * FROM comments WHERE content_id = :content_id ORDER BY date_posted DESC";

        $statement = $db->prepare($query);
        $content_id = filter_var($content_id, FILTER_SANITIZE_NUMBER_INT);

        $statement->bindValue(':content_id', $content_id, PDO::PARAM_INT);

        $statement->execute();

        return $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    }

    /**
     * Hashing and salting using password_hash().
     * @param password The password to be hashed and salted.
     * @return password The hashed password.
     */
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }  

    /**
     * Verifies if user exists.
     * @param db PHP Data Object to use to SQL queries.
     * @return bool False if user doesn't exists, True if it does.
     */
    function verify_user_existence($db, $email) { // TODO TBT
        if(user_or_admin($email)) { // true if admin
            $query = "SELECT * FROM admins WHERE email = :email";

        } else {
            $query = "SELECT * FROM users WHERE email = :email";
        }
        
        $statement = $db->prepare($query);
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return(!empty($result));
        
    }


    /**
     *  NOT USED MIGHT DELETE BEFORE SUBMISSION
     * @param db PHP Data Object to use to SQL queries.
     * @param content_id The content 
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

    /**
     * Retrieving the username of the user.
     * @param db PHP Data Object to use to SQL queries.
     * @param user_id The user id of the user if it's not an admin.
     * @param admin_id The user id of the user if it's an admin.
     * @return result[username] The username of the user.
     */
    function get_username($db, $user_id, $admin_id) {

        $query;
        $statement;

        if(!is_null($admin_id) && !is_null($user_id)) {
            $query = "SELECT username FROM users WHERE user_id = :user_id";
            $statement = $db->prepare($query);
            $statement->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        } elseif(!is_null($admin_id)) {
            $query = "SELECT username FROM admins WHERE admin_id = :admin_id";
            $statement = $db->prepare($query);
            $statement->bindValue(':admin_id', $admin_id, PDO::PARAM_INT);
        } else {
            $query = "SELECT username FROM users WHERE user_id = :user_id";
            $statement = $db->prepare($query);
            $statement->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        }

        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result['username'];
    }


    /**
     * Retrieving the user's id using the username.
     * @param db PHP Data Object to use to SQL queries.
     * @param username The username of the input.
     * @return results[user_id] The user id associated to the username.
     */
    function get_user_id($db, $username) {
        $query = "SELECT * FROM users WHERE username = :username";

        $username = filter_var($username, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $statement = $db->prepare($query); 
        $statement->bindValue(':username', $username, PDO::PARAM_STR);
        $statement->execute();
        $results = $statement->fetch(PDO::FETCH_ASSOC);

        if(!empty($results)) {
            return $results['user_id'];

        } else {
            return null;
            
        }
    }

?>