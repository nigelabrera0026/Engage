<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Login page with authentication

    ****************/
    /* 
    TODO 

    LOGIN PAGE (General User, and Admin user)
        Logic: Differentiate using email

    ADD LINK TO REGISTER (not in requirements but need it)

    IN PROGRESS
    ADD logic if it's user or if it's admin (TO BE TESTED)

    DONE    
    Database Structure Integration

    TEST CASE:
    Test what filter input returns DONE
        

    DEV NOTES {
        ADMIN CAN BE THE ONLY ONE WHO CAN CHANGE ANYONE'S COMMENT'S OR POST'S.

        LOGGED USER
    }
    */

    require("connect.php");

    session_start();

    // Global vars
    $error = [];
    $is_admin = false;

    /**
     * Slicing the email and checking if it's admin or not.
     * @param email retrieves the email to be sliced.
     * @return bool 
     */
    function User_or_admin($email) {
        // Retrieves 2 parts of the param.
        $domain = explode('@', $email);

        if(count($domain) == 2) { // if it's a valid email.

            if($domain[1] == "engage.test") {
                return true;

            } else {
                return false;

            }
        } else {
            global $error;
            $error[] = "Invalid Email format!";

        }
    }


    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Validation
        if($_POST && (!empty($_POST['email']) || !empty($_POST['pwd'])) 
        || ($_POST['email'] == '' || $_POST['pwd'] == '')) {
            
            $error[] = "Invalid Fields!";
        
        } else {
            // Sanitization
            $user = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if(User_or_admin($user)) { // User or Admin
                $query = "SELECT a.email, a.password FROM admins a WHERE email = :user AND password = :password";

                $is_admin = true; 

                // Preparation, Binding, Execution, and Retrieval
                // Email Validation
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->bindValue(':password', $pwd, PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetch();

            } else {
                $query = "SELECT u.email, u.password FROM users u WHERE u.email = :user AND u.password = :password";

                // Preparation, Binding, Execution, and Retrieval
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->bindValue(':password', $pwd, PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetch();
            }
            
            if(!$result || ($result['password'] == null)) {
                $error[] .= "Invalid Credentials!";

            } else {
                setcookie("client", $user, time() + 60 * 60 * 60); // TODO Check time
                
                if($is_admin) {
                    setcookie("isadmin", 0, time() + 60 * 60 * 60);

                }

                header("Location: user_index.php");
                exit();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Page</title>
    </head>
    <body>
        <div>
            <h1><?php isset($error) ? $error : ""; ?></h1> 
        </div>
        <div>
            <form method="post" action="login.php">
                <label for="email">Email</label>
                <input type="email" id="email_login" name="email_login" />
                <label type="password">Password</label>
                <input type="password" id="pwd" name="pwd" required/>
                <button type="submit" id="login_submit">Sign In</button>
            </form>
        </div>
    </body>
</html>