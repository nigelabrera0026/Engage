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
     * Hashing and salting using password_hash().
     * @param password The password to be hashed and salted.
     * @return password The hashed password.
     */
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }  


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
        <title>Join Us!</title>
    </head>
    <body>
        <header>
            <?php if(!empty($error)): ?> <!-- move error -->
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
    </body>
</html>