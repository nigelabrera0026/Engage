<?php 
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/29/2023
        @description: Login page with authentication

    ****************/

    require("connect.php");
    require("library.php");
    session_start();

    // Global vars
    $error = [];

    // TODO - Admin users must have the ability to view all registered users, add users, update users, and delete users.

    // When page first loads
    if(isset($_SESSION['isadmin'])) {
        $query = "SELECT * FROM users";
        $statement = $db->prepare($query);

        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    } else {
        header("Location: invalid_url.php");
        exit();
        
    }

    if($_SERVER['REQUEST_METHOD'] === "POST") {

        if($_POST && $_POST['submit'] == "Delete"); {

            $query = "DELETE FROM users WHERE user_id = :user_id";

            $statement = $db->prepare($query);
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $statement->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            if($statement->execute()) {
                header("refresh: 0");
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
        <title>Admin Page</title>
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
                                    <a href="admin_cud_users.php" class="nav-link">
                                        <button type="button" class="btn btn-warning">Moderate</button>
                                    </a>
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
                            <?php endif ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>
    <!-- Add Button for adding users-->
    <a href="add_user_admin.php"><button type="button" class="btn btn-primary">Add user</button></a>
    <div class="container-xxl border">
        <tr>
            <?php foreach($results as $results => $users): ?>
                <th>
                    <?= $users['username']?>
                </th>
                <th>
                    <?= $users['email'] ?>
                </th>
                <!-- Add buttons for update delete -->
                <a href="edit_user.php?user_id=<?= $users['user_id']?>" class="nav-link">
                    <button type="button" class="btn btn-warning">Edit</button>
                </a>
                <form action="admin_cud_users.php" method="post">
                    <input type="hidden" name="user_id" value="<?=$users['user_id']?>" />
                    <input type="submit" name="submit" class="btn btn-danger " value="Delete"/>
                </form> 
            <?php endforeach ?>
        </tr>
    </div>
    </body>
    <script src="./bootstrap/js/bootstrap.js"></script>
</html>