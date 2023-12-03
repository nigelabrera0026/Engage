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
            $user = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $pwd = filter_input(INPUT_POST, 'pwd', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if(!email_or_username($user)) { // if it's an email.
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
            } else { // if it's an username.
                $trigger_login = 0;

                $query = "SELECT * FROM admins WHERE username = :username";
                $statement = $db->prepare($query);
                $statement->bindValue(':username', $user, PDO::PARAM_STR);
        
                $statement->execute();
                $admin_result = $statement->fetch(PDO::FETCH_ASSOC);
        
                if(!empty($admin_result)) {
                    if(password_verify($pwd, $admin_result['password'])) {
    
                        $_SESSION['isadmin'] = 1;
    
                        $_SESSION['client_id'] = $admin_result['admin_id'];
                        $_SESSION['client'] = $admin_result['email'];
    
                        header("Location: index.php");
                        exit();
    
                    } else {
                        $error[] = "Invalid password!";
    
                    }
        
                } else {
                    $trigger_login++;
                }
        
                $query = "SELECT * FROM users WHERE username = :username";
                $statement = $db->prepare($query);
                $statement->bindValue(':username', $user, PDO::PARAM_STR);
                $statement->execute();
                $user_result = $statement->fetch(PDO::FETCH_ASSOC);
        
                if(!empty($user_result)) {
                    if(password_verify($pwd, $user_result['password'])) {
    
                        $_SESSION['client'] = $user_result['email'];
                        $_SESSION['client_id'] = $user_result['user_id'];
                        
                        header("Location: index.php");
                        exit();

                    } else {
                        $error[] = "Invalid password!";
    
                    }
                } else {
                    $trigger_login++;
                }

                if($trigger_login > 1) {
                    $error[] = "Invalid, No user found.";
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
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css" />
        <title>Login Page</title>
    </head>
    <body>
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
                                        <a href="index.php" class="nav-link text-light">Home</a>
                                    </li>
                                    <?php if(isset($_SESSION['client'])): ?>
                                        <li class="nav-item ms-3">
                                            <p class="nav-link text-light">Hello, <?= username_cookie($db, $_SESSION['client']) ?>!</p>
                                        </li>
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
                                        <li class="nav-item ms-2">
                                            <a href="register.php" class="nav-link">
                                                <button type="button" class="btn btn-primary">Register</button>
                                            </a>
                                        </li>
                                    <?php endif ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
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
                <label for="email">Email/Username</label>
                <input type="text" id="email" name="email" />
                <label for="pwd">Password</label>
                <input type="password" id="pwd" name="pwd" required/>
                <button type="submit" id="login_submit">Enter</button>
            </form>
        </div>
        <script src="./bootstrap/js/bootstrap.js"></script>
    </body>
</html>