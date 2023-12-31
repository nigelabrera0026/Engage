<?php 
    /*******w******** 
        
        Name: Nigel Abrera
        Date: 12/1/2023
        Description: Editing comments for admin privileges only.

    ****************/
    
    require("connect.php");
    require("library.php");
    session_start();

    $error = [];

    // Retrieve specific comment.
    $query = "SELECT * FROM comments WHERE comment_id = :comment_id";

    $comment_id = filter_var($_GET['comment_id'], FILTER_SANITIZE_NUMBER_INT);

    $statement = $db->prepare($query);
    $statement->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
    $statement->execute();
    $result = $statement->fetch(PDO::FETCH_ASSOC);

    // If form is submitted.
    if(isset($_SESSION['isadmin'])) {

        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            if($_POST && $_POST['submit'] == 'Update') {
                $content_id = filter_var($_POST['content_id'], FILTER_VALIDATE_INT);
                $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
                
                if(empty($_POST['comment'])) {
                    $error[] = "Invalid empty field, cannot update.";
                    
                } else { 
                    $comments = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    $admin_id = filter_var($_SESSION['client_id'], FILTER_VALIDATE_INT);

                    $query = "UPDATE comments 
                    SET comments_text = :comments_text, admin_id = :admin_id
                    WHERE comment_id = :comment_id";

                    $statement = $db->prepare($query);
                    $statement->bindValue(':comments_text', $comments, PDO::PARAM_STR);
                    $statement->bindValue(':admin_id', $admin_id, PDO::PARAM_INT);
                    $statement->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);

                    if($statement->execute()) {
                        header('Location: view_content.php?content_id=' . $content_id);
                        exit();
                    }
                }
            } elseif($_POST && $_POST['submit'] == 'Delete') {
                $query = "DELETE FROM comments WHERE comment_id = :comment_id";

                $statement = $db->prepare($query);
                $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
                $content_id = filter_var($_POST['content_id'], FILTER_VALIDATE_INT);
                $statement->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);

                if($statement->execute()) {
                    header('Location: view_content.php?content_id=' . $content_id);
                    exit();
                }
            }
        }
    } else {
        header("Location: invalid_url.php");
        exit();

    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Comment</title>
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css" />
    </head>
    <body>
        <div>
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
                                        <a href="index.php?sort_genre=none&sort_title=none&date_sort=none" class="nav-link text-light">
                                            Home
                                        </a>
                                    </li>
                                    <?php if(isset($_SESSION['client'])): ?>
                                        <li class="nav-item ms-3">
                                            <p class="nav-link text-light">
                                                Hello, <?= username_cookie($db, $_SESSION['client']) ?>!
                                            </p>
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
            <div class="container-fluid">
                <form action="edit_comment.php?comment_id=<?= $result['comment_id']?>" method="post">
                    <?php if(empty($result['username'])): ?>
                        <?php if(is_null($result['user_id'])): ?>
                            <p>@<?= get_username($db,  null, $result['admin_id']) ?> [ADMIN]</p>
                        <?php elseif($result['user_id'] == 0): ?>
                            <p>@<?= get_username($db, 0, null) ?></p>
                        <?php else: ?>
                            <p>@<?= get_username($db, $result['user_id'], null) ?></p>
                        <?php endif ?>
                    <?php else: ?>
                        <p>@<?= $result['username'] ?></p>
                    <?php endif ?>
                    <label for="comment">Comment: </label>
                    <textarea name="comment" id="comment"><?= htmlspecialchars($result['comments_text']) ?></textarea>
                    <p><?= $result['date_posted']?></p>
                    <input type="hidden" name="comment_id" value="<?= $result['comment_id']?>">
                    <input type="hidden" name="content_id" value="<?= $result['content_id']?>">
                    <input type="submit" name="submit" value="Update"/>
                    <input type="submit" name="submit" value="Delete"/>
                </form>
            </div>
            <footer class="bg-dark text-white text-center py-2">
                <p>Created by Nigel Abrera</p>
            </footer>
            <script src="./bootstrap/js/bootstrap.js"></script>
        </div>
    </body>
</html>