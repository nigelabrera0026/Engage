<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    require("library.php");
    session_start();
    /*
        Dev notes: session_start(); carries over all the session.

        VIEW CONTENT WILL BE VIEWED WITH COMMENTS.
        AND COMMENTS CAN INCLUDE DOCS.
    */

    // Global var
    $error = [];
    
    // POST for commenting.
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST && ($_POST['submit_comment'] == 'comment')){

            if(!empty($_POST['comment'])) {
                // Container for columns to be set
                $contents = [];
                $values = [];

                // Filtration and Sanitization
                $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $content_id = filter_var($_COOKIE['content_id'], FILTER_VALIDATE_INT);

                if(isset($_SESSION['client'])) {
                    $client_id = filter_var($_SESSION['client_id'], FILTER_VALIDATE_INT);

                    if(isset($_SESSION['isadmin'])){
                        $contents[] = "admin_id";
                        $values[':admin_id'] = $client_id;

                    } else {
                        $contents[] = "user_id";
                        $values[':user_id'] = $client_id;

                    }

                } else {
                    // For non users, make username = "anonymous" 
                    $contents[] = 'user_id';
                    $values[':user_id'] = 0; // Anonymous user's ID.

                }

                $contents[] = "content_id";
                $values[':content_id'] = $content_id;

                $contents[] = "comments_text";
                $values[':comments_text'] = $comment;

                // Makes it 1 whole string with a , separator
                $contents = implode(", ", $contents);
                    
                $placeholders = implode(", ", array_map(function ($param) {
                    return $param;
                }, array_keys($values)));
                
                $query = "INSERT INTO comments($contents) VALUES ($placeholders)";
                
                // Preparation
                $statement = $db->prepare($query);

                // Binding
                foreach($values as $param => $value) {
                    $statement->bindValue($param, $value);
                    
                }

                // Execution
                if($statement->execute()) {
                    header("Location: index.php");
                    exit();

                } else {
                    $error[] = "Execution failed!";
                }

            } else {
                $error[] = "Invalid empty field!";

            }
        }
    }

    // Generate the content.
    $query = "SELECT * FROM contents WHERE content_id = :content_id";

    $statement = $db->prepare($query);
    $content_id = filter_var($_GET['content_id'], FILTER_VALIDATE_INT);
    $statement->bindValue('content_id', $content_id, PDO::PARAM_INT);
    $statement->execute();
    setcookie('content_id', $content_id, time() + 60 * 60 * 24 * 1); 
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title></title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header id="main-header">
            <nav>
                <ul>
                    <li>Engage</li> <!-- Logo -->
                    <li><a href="index.php">Home</a></li>
                    <?php if(isset($_SESSION['client'])): ?>
                        <li><!-- Style it to the middle-->
                            <a href="user_stuff.php?user_id=<?= $_SESSION['client_id'] ?>">
                                <?= username_cookie($_SESSION['client'])  ?>
                            </a>
                        </li>
                        <li>
                            <a href="logout.php">
                                <button type="button">Sign out</button>
                            </a>
                        </li>
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
        <div>
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
        <div> <!-- Link for creating new post -->
            <?php foreach($results as $content): ?>
                <div>
                    <?php if(isset($_SESSION['isadmin']) || (isset($_SESSION['client_id']) && ($_SESSION['client_id'] == $content['user_id']))):?>
                        <a href="edit.php?content_id=<?=$content['content_id']?>">
                            <button type="button">Edit</button>
                        </a>
                    <?php endif ?>
                    <?php if(isset($content['images'])): ?>
                        <img src="data:image/*;base64,<?= base64_encode($content['images']) ?>" 
                        alt="<?= $content['image_name'] ?>"/>
                    <?php endif ?>
                    <h3><?= $content['title'] ?></h3>
                    <?php if(isset($content['song_file'])): ?>
                        <audio controls>
                            <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/mpeg"/>
                        </audio>
                    <?php endif ?>
                    <!-- Posted By-->
                    <p>
                        <?php if(isset($_SESSION['isadmin'])): ?>
                            <?php if(!empty($content['user_id']) || !is_null($content['user_id'])): ?>
                                <a href="admin_cud_users.php?user_id=<?= $content['user_id']?>">
                                    @<?= getUser($db, $content['admin_id'], $content['user_id']) ?>
                                </a>
                            <?php endif ?>
                        <?php else: ?>
                            @<?= getUser($db, $content['admin_id'], $content['user_id']) ?>
                        <?php endif ?>
                    </p>
                    <!-- Comments -->
                    <?php $comments = retrieve_comments($db, $content_id); ?>
                    <!-- <?= print_r($comments)?> -->
                    <div>
                        <?php 
                            /* LOGIC retrieve all the comments that has the content_id */
                        ?>
                        <?php foreach($comments as $user_comment): ?>
                            <!-- <?= print_r($user_comment)?> -->
                            <?php if(is_null($user_comment['user_id'])): ?>
                                <p>@<?= get_username($db,  null, $user_comment['admin_id']) ?> ADMIN</p>
                            <?php elseif($user_comment['user_id'] == 0): ?>
                                <p>@<?= get_username($db, 0, null) ?></p>
                            <?php else: ?>
                                <p>@<?= get_username($db, $user_comment['user_id'], null) ?></p>
                            <?php endif ?>
                            <p><?= $user_comment['comments_text']?></p>
                        <?php endforeach ?>
                    </div>
                    <!-- probably add some verification if session is set -->
                    <!-- LOGIC: IF client exists proceed to post-->
                    <!-- if non clients wants to comment they need to leave a username and do CAPTCHA -->
                    <!-- if they don't leave a username, label it as anonymous -->
                    <form action="view_content.php" method="post">
                        <?php if(isset($_SESSION['client'])): ?>
                            <p><?= username_cookie($_SESSION['client']) ?></p>
                        <?php endif ?>
                        <label for="comment">Comment:</label>
                        <textarea name="comment" id="comment"></textarea>
                        <input type="submit" name="submit_comment" value="comment"/>
                    </form>
                </div>
            <?php endforeach ?>
        </div>
    </body>
</html>
