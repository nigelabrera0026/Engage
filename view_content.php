<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    require("library.php");

    session_start();

    // Global var
    $error = [];

    // Trigger cookies if captcha is invalid.
    if(isset($_SESSION['form_data'])) {
        $form_data = $_SESSION['form_data'];
        unset($_SESSION['form_data']);

    } elseif(isset($_COOKIE['captcha_counter']) && ($_COOKIE['captcha_counter'] > 3)){
        unset($_COOKIE['captcha_counter']);

    }
    
    // POST for commenting.
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        if($_POST && ($_POST['submit_comment'] == 'Submit')){

            if(!empty($_POST['comment'])) {
                // Container for columns to be set
                $contents = [];
                $values = [];

                // Filtration and Sanitization
                $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $content_id = filter_var($_COOKIE['content_id'], FILTER_VALIDATE_INT);

                $captcha_trigger = false; // if we need to include a captcha
                $execute = false;         // Trigger if we allow execution of query


                $captcha = filter_input(INPUT_POST, 'captcha', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $captcha = $_POST['captcha'];

                if(isset($_SESSION['client'])) {
                    $client_id = filter_var($_SESSION['client_id'], FILTER_VALIDATE_INT);

                    if(isset($_SESSION['isadmin'])){
                        $contents[] = "admin_id";
                        $values[':admin_id'] = $client_id;

                    } else {
                        $contents[] = "user_id";
                        $values[':user_id'] = $client_id;

                    }

                    // ready to be executed
                    $execute = true; 
                } else {
                    // Apply the captcha thingy
                    $captcha_trigger = true;

                    if(empty($_POST['username'])) {
                        // For non users, make username = "anonymous" 
                        $contents[] = 'user_id';
                        $values[':user_id'] = 0; // Anonymous user's ID.

                    } else { // if guest inserted a username
                        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                        $contents[] = 'username';
                        $values[':username'] = $username;

                    }  
                }

                if($captcha_trigger) { // Need to include captcha in the submission.

                    if(empty($captcha)) {
                        $error[] = "Captcha is required!";
                        header("Location: view_content.php?content_id=$content_id");

                    } elseif($captcha === $_SESSION['captcha']) {
                        // ready to be executed
                        $execute = true;

                    } else {
                        if(isset($_COOKIE['captcha_counter'])){
                            
                            if($_COOKIE['captcha_counter'] == 3) {
                                unset($_SESSION['form_data']);
                                unset($_COOKIE['captcha_counter']);

                                header("Location: index.php");
                                exit();

                            } elseif($_COOKIE['captcha_counter'] < 3) {
                                setcookie('captcha_counter', $_COOKIE['captcha_counter'] + 1, time() + 60 * 60);

                            }
                        } else {
                            // Initialize cookie.
                            setcookie('captcha_counter', 1, time() + 60 * 60);

                        }
                        
                        $_SESSION['form_data'] = [
                            'form_username' => isset($username) ? $username : '',
                            'user_captcha' => isset($captcha) ? $captcha : '',
                            'comment' => isset($comment) ? $comment : ''
                        ];

                        // Saves the form to a session.
                        $error[] = "Invalid Captcha!";
                        header("Location: view_content.php?content_id=$content_id");
                    }
                }

                $contents[] = "content_id";
                $values[':content_id'] = $content_id;

                $contents[] = "comments_text";
                $values[':comments_text'] = $comment;


                // Makes it 1 whole string with a " , " separator
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

                // Trigger if we allow execution since everything pass the check.
                if($execute) {
                    // Execution
                    if($statement->execute()) {
                        setcookie('captcha_counter', null, time() - 3600);
                        header("Location: view_content.php?content_id=$content_id");
                        exit();

                    } else {
                        $error[] = "Execution failed!";
                        header("Location: view_content.php?content_id=$content_id");
                    }

                }
            } else {
                $error[] = "Invalid empty field!";
                header("Location: view_content.php?content_id=$content_id");

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
        <title>Engage</title>
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css">
        <script src="./scripts/captcha_scripts.js"></script>
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
        <div class="container mt-4">
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
                    <?php if(isset($_SESSION['isadmin']) || (isset($_SESSION['client_id']) && 
                    ($_SESSION['client_id'] == $content['user_id']))):?>
                        <a href="edit.php?content_id=<?=$content['content_id']?>">
                            <button type="button">Edit</button>
                        </a>
                    <?php endif ?>
                    <?php if(isset($content['images'])): ?>
                        <img src="data:image/*;base64,<?= base64_encode($content['images']) ?>" 
                        alt="<?= $content['image_name'] ?>"/>
                    <?php endif ?>
                    <h3><?= $content['title'] ?></h3>
                    <h4><?= $content['artist'] ?></h4>
                    <?php if(isset($content['song_file'])): ?>
                        <audio controls>
                            <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/mpeg"/>
                        </audio>
                    <?php endif ?>
                    <!-- Posted By-->
                    <p>
                        <?php if(!empty($content['user_id']) || !is_null($content['user_id'])): ?>
                             @<?= getUser($db, $content['admin_id'], $content['user_id']) ?>
                        <?php endif ?>
                    </p>
                    <!-- upload a comment-->
                    <div>
                        <form action="view_content.php" method="post">
                            <?php if(isset($_SESSION['client'])): ?>
                                <p><?= username_cookie($db, $_SESSION['client']) ?></p>
                            <?php else: ?>
                                <label for="username">Username</label>
                                <?php if(!empty($form_data['form_username'])): ?>
                                    <input type="text" name="username" id="username" value="<?=htmlspecialchars($form_data['form_username'])?>"/>
                                <?php else: ?>
                                    <input type="text" name="username" id="username" />
                                <?php endif ?>
                                <label for="captcha">Enter the letters below to prove you are human.</label>
                                <img src="captcha.php?rand=<?= rand() ?>" id="captcha_image" alt="CAPTCHA IMAGE" />
                                <?php if(!empty($form_data['user_captcha'])): ?>
                                    <input type="text" name="captcha" id="captcha" value="<?=htmlspecialchars($form_data['user_captcha'])?>"/>
                                <?php else: ?>
                                    <input type="text" name="captcha" id="captcha"/>
                                <?php endif ?>
                                <?php if(!empty($_COOKIE['captcha_counter'])): ?>
                                    <p>Invalid captcha! Try again.</p>
                                <?php else: ?>
                                    <p></p>
                                <?php endif ?>
                                <p>Can't read it? <a href="javascript: refreshCaptcha();">click here</a> to generate another one.</p>
                            <?php endif ?>
                            <label for="comment">Comment: </label>
                            <?php if(!empty($form_data['comment'])): ?>
                                <textarea name="comment" id="comment"><?=htmlspecialchars($form_data['comment'])?></textarea>
                            <?php else: ?>
                                <textarea name="comment" id="comment"></textarea>
                            <?php endif ?>
                            <input type="submit" name="submit_comment" value="Submit"/>
                        </form>
                    </div>
                    <!-- Comments -->
                    <?php $comments = retrieve_comments($db, $content_id); ?>
                    <div> <!-- Generating Comments -->
                        <?php foreach($comments as $user_comment): ?> <!-- Apply if comments have username-->
                            <?php if(empty($user_comment['username'])): ?>
                                <?php if(is_null($user_comment['user_id'])): ?>
                                    <p>@<?= get_username($db,  null, $user_comment['admin_id']) ?> ADMIN</p>
                                <?php elseif($user_comment['user_id'] == 0): ?>
                                    <p>@<?= get_username($db, 0, null) ?></p>
                                <?php else: ?>
                                    <p>@<?= get_username($db, $user_comment['user_id'], null) ?></p>
                                <?php endif ?>
                            <?php else: ?>
                                <p>@<?= $user_comment['username'] ?></p>
                            <?php endif ?>
                            <p><?= $user_comment['comments_text']?></p>
                            <?php if(isset($_SESSION['isadmin'])): ?>
                                <?php if(isset($_SESSION['client_id']) && ($_SESSION['client_id'] == $user_comment['admin_id'])):?>
                                    <a href="edit_comment.php?comment_id=<?= $user_comment['comment_id']?>">
                                        <button type="button">Edit Comment</button>
                                    </a>
                                <?php elseif(is_null($user_comment['user_id']) && !is_null($user_comment['admin_id'])): ?>
                                    <p></p>
                                <?php elseif(!is_null(($user_comment['user_id'])) && !is_null($user_comment['admin_id'])): ?>
                                    <a href="edit_comment.php?comment_id=<?= $user_comment['comment_id']?>">
                                        <button type="button">Edit Comment</button>
                                    </a>
                                <?php else: ?>
                                    <a href="edit_comment.php?comment_id=<?= $user_comment['comment_id']?>">
                                        <button type="button">Edit Comment</button>
                                    </a>
                                <?php endif ?>
                            <?php endif ?>
                            <p><?= $user_comment['date_posted']?></p>
                            <br><br><br>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <footer class="bg-dark text-white text-center py-2">
            <p>Created by Nigel Abrera</p>
        </footer>
        <script src="./bootstrap/js/bootstrap.js"></script>
    </body>
</html>
