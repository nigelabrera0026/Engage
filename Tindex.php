<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    session_start();

    /*
        Dev notes: session_start(); carries over all the session.

        CONTROL FLOW:

        check if session is set for user or admin.
        if yes, prompt create, update, delete post by the user.
        if no, hide prompt
        fetch all content and display it 
        link to the login 
        Extra note: create log out logic, and register.
                    Season the pass and apply to logic
    */

    /*
    Control Flow - Generating content Pseudocode
    * Check if user or admin exists
        if user is true
            Generate Content with CUD priv. on their own contents.
        if admin is true
            Generate Content with CUD priv. to all of them.
        else
            Generate all content without CUD priv.

    Control flow for Comments.
    Control flow for Sortation
    */

    // Logic for user profile.php
    // if(isset($_SESSION['isadmin'])) {
    //     $query = "SELECT * FROM contents WHERE "
    // } elseif(isset($_SESSION['client'])) {

    // } else {

    // }

    function getUser($client) {
        $domain = explode('@', $client);

        return $domain[0];
    }

    function identify_client($id) {
        if(isset($id['admin_id'])) {
            return true;
        
        } else {
            return false;
        }
    }


    $query = "SELECT * FROM contents LIMIT 5"; 

    $statement = $db->prepare($query);
    $statement->execute();
    
    // use hash $results[0][]
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    // TODO: LIMIT {dynamic} 

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
                <nav>
                    <ul> <!-- Check if there's a milestone for adding stuff here. if not then it's redundancy -->
                        <?php if(isset($_SESSION['isadmin'])): ?> <? // Linking? ?>
                            <li>
                                <a href="create_post.php">
                                    <button type="button">Create Post</button>
                                </a>
                            </li>
                        <?php elseif(isset($_SESSION['client'])): ?>
                            <li>
                                <a href="create_post.php">
                                    <button type="button">Create Post</button>
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <a href="#"><!-- Register?  -->
                                    <button type="button">Join Us!</button>
                                </a>
                            </li>
                        <?php endif ?>
                    </ul>
                </nav>
            </div>
            <form action="Tindex.php" method="post">
                <fieldset>
                    <legend>Contents</legend>
                    <div>
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" />
                    </div>
                </fieldset>
                <input type="submit" name="submit" value="search_button"/>
            </form>
            <!-- Prototype form will be used for sortation-->
            <form action="Tindex.php" method="post">
                <nav>
                    <ul>
                        <li></li>
                        <li></li>
                    </ul>
                </nav>
            </form>
            <!-- Print out the list of contents here -->
            <!-- Categories -->
            <!-- Use loop to generate list, and differentiate user and admin -->

            <!-- Showing the contents -->
            <!-- Verify if user or admin exists -->
            <!-- Do non user first -->
            <div> <!-- TODO FIXME -->
                <?php if(isset($_SESSION['isadmin'])):?> <!-- Checks if it's able to edit posts from things-->
                    <div>
                        <?php foreach($results as $content):?>
                            <div>
                                <h2><?= $content['title'] ?></h2>
                                <!-- TODO Not sure how this link will work -->
                                <a href="edit.php?<?php identify_client($content) ? 'admin_id='$content['admin_id'] : 'user_id='$content['user_id'] ?>">
                                    <button type="button">Edit</button>
                                </a>
                                <?php if(isset($content['images'])): ?>
                                    <img src="data:image/*;base64,<?= base64_encode($content['images']) ?>" 
                                    alt="<?= $content['image_name'] ?>"/>
                                <?php else: ?>
                                    <!-- Add the no image picture -->
                                    <img src="./images/no_image.jpg" alt="No image available" />
                                <?php endif ?>
                                <audio controls>
                                    <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/*">
                                </audio>
                                <p>Posted By: <?= getUser($_SESSION['client']) ?></p>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php elseif(isset($_SESSION['client_id'])): ?>
                    <div>
                        <?php foreach($results as $content):?>
                            <div>
                                <h2><?= $content['title'] ?></h2>
                                <!-- TODO Not sure how this link will work -->
                                <?php if($_SESSION['client_id'] == $content['user_id']): ?>
                                    <a href="edit.php?user_id=<?=$content['user_id']?>">
                                        <button type="button">Edit</button>
                                    </a>
                                <?php endif ?>
                                <?php if(isset($content['images'])): ?>
                                    <img src="data:image/*;base64,<?= base64_encode($content['images']) ?>" 
                                    alt="<?= $content['image_name'] ?>"/>
                                <?php else: ?>
                                    <!-- Add the no image picture -->
                                    <img src="./images/no_image.jpg" alt="No image available" />
                                <?php endif ?>
                                <audio controls>
                                    <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/*">
                                </audio>
                                <p>Posted By: <?= getUser($_SESSION['client']) ?></p>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div>
                        <?php foreach($results as $content):?>
                            <div>
                                <h2><?= $content['title'] ?></h2>
                                <!-- TODO Not sure how this link will work -->
                                <a href="edit.php?<?php identify_client($content) ? 'admin_id='$content['admin_id'] : 'user_id='$content['user_id'] ?>">
                                    <button type="button">Edit</button>
                                </a>
                                <?php if(isset($content['images'])): ?>
                                    <img src="data:image/*;base64,<?= base64_encode($content['images']) ?>" 
                                    alt="<?= $content['image_name'] ?>"/>
                                <?php else: ?>
                                    <!-- Add the no image picture -->
                                    <img src="./images/no_image.jpg" alt="No image available" />
                                <?php endif ?>
                                <audio controls>
                                    <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/*">
                                </audio>
                                <p>Posted By: <?= getUser($_SESSION['client']) ?></p>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </main>
    </body>
</html>