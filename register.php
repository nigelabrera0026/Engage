<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/19/2023
        @description: Registration

    ****************/
    
    require("connect.php");
    require('library.php');

    $error = [];
    // TODO FIXME
    function verify_user_existence($db, $email) {
        if(user_or_admin($email)) { // true if admin
            $query = "SELECT * FROM admins WHERE email = :email";

        } else {
            $query = "SELECT * FROM users WHERE email = :email";
        }
        
        $statement = $db->prepare($query);
        $statement->bindValue(':email', $email, PDO::PARAM_STR);

        if($statement->execute()){
            return false;

        } else {
            return true;
        }
    }

    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }  



    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        // Filtration and Sanitization
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $confirm_password = filter_input(INPUT_POST,'confirm_password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if(empty($email) || empty($password) || empty($confirm_password)){
            $error[] = "Invalid Empty Fields.";
        
        } elseif($password != $confirm_password){
            $error[] = "Password doesn not match!";

        } else {
            if(!verify_user_existence($db, $email)) {
                // true if it's an admin
                if(user_or_admin($email)){
                    $query = "INSERT INTO admins(email, password) VALUES (:email, :password)";

                } else {
                    $query = "INSERT INTO users(email, password) VALUES (:email, :password)";

                }

                $statement = $db->prepare($query);
                $statement->bindValue(":email", $email, PDO::PARAM_STR);
                $statement->bindValue(':password', hash_password($password));

                if($statement->execute()) {
                    header("Location: Tindex.php");
                }
                
            } else {
                $error[] = "User Exists.";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <header>
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
            <nav>
                <ul>
                    <li>Engage</li> <!-- Logo -->
                    <li><a href="index.php">Home</a></li>
                    <?php if(isset($_SESSION['client'])): ?>
                        <li>
                            <a href="logout.php">
                                <button type="button">Log out</button>
                            </a>
                        </li>
                        <li><a href="user_stuff.php?user_id=<?= $_SESSION['client_id'] ?>">My stuff</a></li>
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
            <div>
                <form action="register.php" method="post">
                    <fieldset>
                        <legend>Creating New Profile</legend>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" />
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required />
                        <label for="confirm_password">Re-type Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" requied/>
                    </fieldset>
                    <button type="submit">Register</button>
                </form>
            </div>
        </main>
    </body>
</html>