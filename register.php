<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/19/2023
        @description: Registration

    ****************/
    
    require("connect.php");
    require('library.php');

    /*
    TODO: ADD LOGIC FOR INSERTING USERNAME.
    


    */

    $error = [];
    

 


    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        // Filtration and Sanitization
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $confirm_password = filter_input(INPUT_POST,'confirm_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if(empty($email) || empty($password) || empty($confirm_password) || empty($username)){
            $error[] = "Invalid Empty Fields.";
        
        } elseif($password != $confirm_password){
            $error[] = "Password doesn not match!";

        } else {
            if(verify_user_existence($db, $email)) { // if it's true
                $error[] = "User Exists.";
                
            } else {
                // true if it's an admin
                if(user_or_admin($email)){ // TODO add if username exists - non functional requirement.
                    $query = "INSERT INTO admins(email, password, username) VALUES (:email, :password, :username)";

                } else {
                    $query = "INSERT INTO users(email, password, username) VALUES (:email, :password, :username)";
                }

                $statement = $db->prepare($query);
                $statement->bindValue(":email", $email, PDO::PARAM_STR);
                $statement->bindValue(':password', hash_password($password));
                $statement->bindValue(':username', $username, PDO::PARAM_STR);


                if($statement->execute()) {
                    header("Location: login.php");
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
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css"/>
        <title>Join Us!</title>
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
                                    <a href="index.php?sort_genre=none&sort_title=none&date_sort=none" class="nav-link text-light">Home</a>
                                </li>
                                <?php if(isset($_SESSION['client'])): ?>
                                    <li class="nav-item ms-3">
                                        <p class="nav-link text-light">Hello, <?= username_cookie($db, $_SESSION['client']) ?>!</p>
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
        <div class="container mt-3">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <h1>Error(s):</h1>
                    <ul>
                        <?php foreach ($error as $message): ?>
                            <li><?= $message ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>
        </div>
        <main>
            <div>
                <form action="register.php" method="post">
                    <fieldset>
                        <legend>Creating New Profile</legend>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" />
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" />
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required />
                        <label for="confirm_password">Re-type Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" requied/>
                    </fieldset>
                    <button type="submit">Register</button>
                </form>
            </div>
        </main>
        <script src="./bootstrap/js/bootstrap.js"></script>
    </body>
</html>