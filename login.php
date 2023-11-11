<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Login page with authentication

    ****************/
    /* 
    TODO 
    ADD LINK TO REGISTER (not in requirements but need it)

    IN PROGRESS
    ADD logic if it's user or if it's admin (TO BE TESTED)

    DONE    
    Database Structure Integration

    LOGIN PAGE (General User, and Admin user)
    Logic: Differentiate using email

    TEST CASE:

    DEV NOTES {
        ADMIN CAN BE THE ONLY ONE WHO CAN CHANGE ANYONE'S COMMENT'S OR POST'S.

        LOGGED USER
    }

    add client_id
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


    if ($_SERVER['REQUEST_METHOD'] == "POST") {
        // Validation
        // FIXME! wrong logic
        if(empty($_POST['email']) || empty($_POST['pwd'])) {
            
            $error[] = "Invalid Fields!";
        
        } else {
            // Sanitization
            $user = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if(User_or_admin($user)) { // User or Admin

                $query = "SELECT admin_id, email, password FROM admins WHERE email = :user AND password = :password";
                // echo "it passed here in line 82";

                $is_admin = true; 

                // Email Validation
                // Preparation, Binding, Execution, and Retrieval
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->bindValue(':password', $pwd, PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                // TODO HASH AND SALT THE PASSWORD
                // If results returned correct information then
                if(!$result || !isset($result[0]['password'])) {
                    // TODO EDIT ERROR MESSAGE
                    $error[] = "Invalid Credentials! part 1";
    
                } else {

                    if($is_admin) {
                        $_SESSION['isadmin'] = 1;
                        $_SESSION['client_id'] = $result['admin_id'];
                    
                    } else {
                        $_SESSION['client_id'] = $result['user_id'];
                    
                    }

                    // TODO Save login info using cookie or session
                    $_SESSION['client'] = $result['email'];
                    

                    // TODO Change this thing.
                    header("Location: Tindex.php");
                    exit();

                }
            } else {
                $query = "SELECT email, password FROM users WHERE email = :user AND password = :password";

                // Preparation, Binding, Execution, and Retrieval
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->bindValue(':password', $pwd, PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                if(!$result || !isset($result[0]['password'])) {
                    $error[] = "Invalid Credentials! part 2";
    
                } else {
                    $_SESSION['client'] = $user;

                    if($is_admin) {
                        $_SESSION['isadmin'] = 1;
    
                    }

                    header("Location: user_index.php");
                    exit();
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
        <title>Login Page</title>
    </head>
    <body>
        <?php if(!empty($error)): ?>
            <div>
                <h1>Error(s):</h1>
                <ul>
                    <?php foreach($error as $message): ?>
                        <li><?= $message ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>
        <div>
            <form method="post" action="login.php">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" />
                <label type="pwd">Password</label>
                <input type="password" id="pwd" name="pwd" required/>
                <button type="submit" id="login_submit">Sign In</button>
            </form>
        </div>
    </body>
</html>