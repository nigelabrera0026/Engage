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

    // Generate the content.
    $query = "SELECT * FROM contents WHERE content_id = :content_id";
    $statement = $db->prepare($query);

    $content_id = filter_var($_GET['content_id'], FILTER_SANITIZE_NUMBER_INT);

    $statement->bindValue('content_id', $content_id, PDO::PARAM_INT);

    // if($statement->execute()) {
        

    // } else {
    //     header("Location: invalid_url.php");
    //     exit(); 
    // }

    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);



    // POST for commenting.
    

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
                    <div>
                        <?php 
                            // LOGIC retrieve all the comments that has the content_id
                        
                        ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </body>
</html>