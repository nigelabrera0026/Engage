<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Login page with authentication

    ****************/

    require("connect.php");
    require("library.php");
    session_start();

    // Global vars
    $error = [];
    $is_admin = false;

    /**
     * Retrieving the hashed and salted password from the database.
     * @param db PHP Data Object to use to SQL queries.
     * @param email The email will be a pointer to the user's information.
     * @return result The password that is fetched in the database.
     */
    function retrieve_hashed_pwd($db, $email) {
        if(user_or_admin($email)) {
            $query = "SELECT * FROM admins WHERE email = :email";
        } else {
            $query = "SELECT * FROM users WHERE email = :email";
        }

        $statement = $db->prepare($query);
        $statement->bindValue(':email', $email, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
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

            // $hashedpwd = password_hash($pwd, PASSWORD_DEFAULT);

            if(user_or_admin($user)) { // User or Admin
                $query = "SELECT admin_id, email, password FROM admins WHERE email = :user ";

                // Email Validation
                // Preparation, Binding, Execution, and Retrieval
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                // If results returned correct information then
                if(!$result) {
                    $error[] = "Invalid Credentials!";
                
                // De-hashing password
                } elseif(password_verify($pwd, $result[0]['password'])) {

                    $_SESSION['isadmin'] = 1;

                    $_SESSION['client_id'] = $result[0]['admin_id'];
                    $_SESSION['client'] = $result[0]['email'];

                    header("Location: index.php");
                    exit();

                } else {
                    $error[] = "Invalid password!";

                }
            } else {
                $query = "SELECT user_id, email, password FROM users WHERE email = :user ";

                // Preparation, Binding, Execution, and Retrieval
                $statement = $db->prepare($query);
                $statement->bindValue(':user', $user, PDO::PARAM_STR);
                $statement->execute();
                $result = $statement->fetchAll(PDO::FETCH_ASSOC);

                if(!$result) {
                    $error[] = "Invalid Credentials!";
                
                // De-hashing password
                } elseif(password_verify($pwd, $result[0]['password'])) {
                    $_SESSION['client'] = $result[0]['email'];
                    $_SESSION['client_id'] = $result[0]['user_id'];
                    
                    header("Location: index.php");
                    exit();

                } else { 
                    $error[] = "Invalid password!";
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
        <header>
            <nav>
                <ul>
                    <li>Engage</li> <!-- Logo -->
                    <li><a href="index.php">Home</a></li>
                    <?php if(isset($_SESSION['client'])): ?>
                        <li><!-- Style it to the middle-->
                            <a href="user_stuff.php?user_id=<?= $_SESSION['client_id'] ?>">My stuff</a>
                        </li>
                        <li><!-- Style it to the far right -->
                            <a href="logout.php">
                                <button type="button">Sign out</button>
                            </a>
                        </li>
                    <?php else: ?>
                        <li> <!-- Style it to the far right -->
                            <a href="login.php">
                                <button type="button">Sign In</button>
                            </a> 
                        </li>   
                    <?php endif ?>
                </ul>
            </nav>
        </header>
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
                <label for="pwd">Password</label>
                <input type="password" id="pwd" name="pwd" required/>
                <button type="submit" id="login_submit">Enter</button>
            </form>
        </div>
    </body>
</html>