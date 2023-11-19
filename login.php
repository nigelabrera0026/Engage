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
 
    DONE    

    LOGIN PAGE (General User, and Admin user)
    Logic: Differentiate using email

    TEST CASE:

    DEV NOTES {
        ADMIN CAN BE THE ONLY ONE WHO CAN CHANGE ANYONE'S COMMENT'S OR POST'S.
        LOGGED USER
    }
    */

    require("connect.php");
    require("library.php");
    session_start();
/*
    // RE-engineering.

    class User {
        private $email;
        private $password;

        public function __construct($email, $password) {
            $this->email = $email;
            $this->password = $password;
        
        }

        public function getEmail() {
            return $this->email;
        
        }

        public function getPassword() {
            return $this->password;

        }
    }

    class
*/



    /*
    TODO
    Hash password for new registered users or admin.
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }  

    Verify the new logged in user to the password
    password_verify($password, $user["password"])
    */

    // Global vars
    $error = [];
    $is_admin = false;

    function retrieve_hashed_pwd($email) {
        if(user_or_admin($email)) {
            $query = "SELECT * FROM admins WHERE email = :email";
        } else {
            $query = "SELECT * FROM users WHERE email = :email";
        }

        $statement = $db->prepare($query);
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $result = $statement->execute();
        return $result['password'];
    }



    if($_SERVER['REQUEST_METHOD'] == "POST") {
        // Validation
        if(empty($_POST['email']) || empty($_POST['pwd'])) {
            $error[] = "Invalid Fields!";
        
        } else {
            // Sanitization
            $user = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if(user_or_admin($user)) { // User or Admin
                $query = "SELECT admin_id, email, password FROM admins WHERE email = :user AND password = :password";

                // Email Validation
                // Preparation, Binding, Execution, and Retrieval
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->bindValue(':password', $pwd, PDO::PARAM_STR);
                $statement->execute();

                $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                // If results returned correct information then
                if(!$result) {
                    // TODO EDIT ERROR MESSAGE
                    $error[] = "Invalid Credentials! part 1";
                
                // De-hashing password
                } elseif(!password_verify($result[0]['password'], retrieve_hashed_pwd($email[0]['email']))) {
                    $error[] = "Invalid Password!";

                } else {

                    $_SESSION['isadmin'] = 1;

                    $_SESSION['client_id'] = $result[0]['admin_id'];
                    $_SESSION['client'] = $result[0]['email'];
                    
                    // DEBUG DOC
                    // if(isset($_COOKIE['visit_count'])) {
                    //     $visit_count = $_COOKIE['visit_count'];
                    //     $visit_count++;
                    // } else {
                    //     $visit_count = 1;
                    // }
                    // setcookie('visit_count', $visit_count, time() + 60 * 60);

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
                $result = $statement->fetch();

                if(!$result) {
                    // TODO EDIT ERROR MESSAGE
                    $error[] = "Invalid Credentials! part 1";
                
                // De-hashing password
                } elseif(!password_verify($result[0]['password'], retrieve_hashed_pwd($email[0]['email']))) {
                    $error[] = "Invalid Password!";

                } else { 
                    $_SESSION['client'] = $user;
                    $_SESSION['client_id'] = $result['user_id'];
                    
                    header("Location: Tindex.php");
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
            <nav>
                <ul> <!-- Check if there's a milestone for adding stuff here. if not then it's redundancy -->
                    <a href="register.php"><button type="button">Register</button></a>
                </ul>
            </nav>
        </div>
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