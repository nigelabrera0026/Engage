<?php 
    
    /**
     * SANDBOX FOR TEST
     * 
     * TO TEST:
     * value if fetching 1 column, check if it's an array
     */

    require("connect.php");
    $error = [];


    if($_SERVER['REQUEST_METHOD'] == "POST") {
        $user = $_POST['email'];
        $pwd = $_POST['pwd'];

        $query = "SELECT admin_id, email, password FROM admins WHERE email = :user AND password = :password";
        $statement = $db->prepare($query);
        $statement->bindValue("user", $user, PDO::PARAM_STR);
        $statement->bindValue("password", $pwd, PDO::PARAM_STR);
        $statement->execute();
        $result = $statement->fetch();
        print_r($result);
        echo $result['admin_id'];
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
            <?php if(!empty($error)): ?>
                <h1>Error(s):</h1>
                <ul>
                    <?php foreach($error as $message): ?>
                        <li><?= $message ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
        <div>
            <form method="post" action="#">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" />
                <label type="pwd">Password</label>
                <input type="password" id="pwd" name="pwd" required/>
                <button type="submit" id="login_submit">Sign In</button>
            </form>
        </div>
    </body>
</html>