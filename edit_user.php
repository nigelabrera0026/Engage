<?php 
    
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 12/03/2023
        @description: Editing the user's information

    ****************/
    require("connect.php");
    require("library.php");
    session_start();

    $error = []; 

    if(isset($_SESSION['isadmin'])) {
        $query = "SELECT * FROM users WHERE user_id = :user_id";

        $statement = $db->prepare($query);
        $user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);

        $statement->bindValue(':user_id', $user_id, PDO::PARAM_INT);

        $statement->execute();
        $results = $statement->fetch();

    } else {
        header("Location: invalid_url.php");
        exit();

    }

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if($_POST && ($_POST['submit'] == 'update')) {

            if(!empty($_POST['email']) && !empty($_POST['username'])) {
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $query = "UPDATE users SET email = :email, username = :username WHERE user_id = :user_id";

                $statement = $db->prepare($query);
                $statement->bindValue(':email', $email, PDO::PARAM_STR);
                $statement->bindValue(':username', $username, PDO::PARAM_STR);
                $statement->bindValue(':user_id', $user_id, PDO::PARAM_INT);

                if($statement->execute()) {
                    header('Location: admin_cud_users.php');
                    exit();
                
                } 
            } else {
                $error[] = "Invalid empty fields!";
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css">
        <title>Edit user</title>
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
        <div> <!-- Error Message Display. -->
            <?php if(!empty($error)):?>
                <div>
                    <h1>Error(s):</h1>
                    <ul>
                        <?php foreach($error as $message): ?>
                            <li><?= $message ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>
        </div>
        <div>
            <form action="edit_user.php?user_id=<?= $results['user_id']?>" method="post">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= $results['email'] ?>"/>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= $results['username'] ?>"/>
                <input type="hidden" name="user_id" value="<?= $results['user_id']?>"/>
                <input type="submit" name="submit" value="update" />
            </form>
        </div>
        <?php include('footer.php') ?>
        <script src="./bootstrap/js/bootstrap.js"></script>
    </body>
</html>