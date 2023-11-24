<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    session_start();

    /*
<<<<<<< HEAD
        TODO FIXME search algorithm and, title, date sortation.
        Sort Genre DONE
=======
        TODO FIXME title and date sortation.
        sort Genre DONE
>>>>>>> ec16bcfa51c660640ca99d644585480f2f04b0fc

        Dev notes: session_start(); carries over all the session.

        CONTROL FLOW:

        check if session is set for user or admin.
        if yes, prompt create, update, delete post by the user.
        if no, hide prompt
        fetch all content and display it 
        link to the login 

        
        CONTROL FLOW FOR SORTATION LOGIC
        
    */

    /*
  
    Control flow for Comments.
    Control flow for Sortation

    Logic for URL handling of edit
    in edit.php 
    if($_SESSION['isadmin']){}
    elseif($_SESSION == $content['user_id']){}
    else{header('index.php')}
    */

    // Logic for user profile.php
    // if(isset($_SESSION['isadmin'])) {
    //     $query = "SELECT * FROM contents WHERE "
    // } elseif(isset($_SESSION['client'])) {

    // } else {

    // }

    // Global
    $error = [];

    /**
<<<<<<< HEAD
     * Executes a query to retrieve the user's part of the email from the database of existing user.
     * @param db PHP Data Object to use to SQL queries.
     * @param admin_id The id of the admin if it's not null.
     * @param user_id The user's id if it's not null.
     * @return domain The user name before the domain.
=======
     * 
     * @param client
     * @return bool
>>>>>>> ec16bcfa51c660640ca99d644585480f2f04b0fc
     */
    function getUser($db, $admin_id, $user_id) {
        // Init
        $query;
        $statement;

        if(!is_null($admin_id)){
            $query = "SELECT email FROM admins WHERE admin_id = :admin_id";
            $statement = $db->prepare($query);
            $statement->bindValue(":admin_id", $admin_id, PDO::PARAM_INT);

        } elseif(!is_null($user_id)) {
            $query = "SELECT email FROM users WHERE user_id = :user_id";
            $statement = $db->prepare($query);
            $statement->bindValue(":user_id", $user_id, PDO::PARAM_INT);

        } else {
            global $error;
            $error[] = "something is wrong.";
        }

        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        if($result) {
            $domain = explode('@', $result['email']);
            return $domain[0];

        } else {
            return "Error: DB Error.";
        }
    }

    /**
<<<<<<< HEAD
     * Retriving existing genre specified what's in the list of the genre.
     * @param db PHP Data Object to use to SQL queries.
     * @param genre_name The name of the genre to be searched.
     * @return results Array of the fetched data from the database.
     */
=======
     * 
     * @return id
     * @return bool
     */
    function identify_client($id) {
        if(isset($id['admin_id'])) {
            return true;
        
        } else {
            return false;

        }
    }


>>>>>>> ec16bcfa51c660640ca99d644585480f2f04b0fc
    function retrieve_genres($db, $genre_name) {
        if(is_null($genre_name)) {
            $query = "SELECT genre_name, genre_id FROM genres";

            $statement = $db->prepare($query);
        } else {
            $query = "SELECT genre_name, genre_id FROM genres WHERE genre_name = :genre_name";

            $statement = $db->prepare($query);
            $statement->bindValue(':genre_name', $genre_name, PDO::PARAM_STR);
        }

        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $results;

    }

    // When the page loads.
    $query = "SELECT * FROM contents LIMIT 30"; 

    $statement = $db->prepare($query);
    $statement->execute();
    
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    // TODO TEST
    // If search is clicked. 
    if($_SERVER['REQUEST_METHOD'] == 'POST') {

        if($_POST && !empty($_POST['search'])){
            // Filtration and Sanitization.
            $user_query = '%' . filter_var(INPUT_POST, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS) . '%';
            $sort_title = filter_var(INPUT_POST, 'sort_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sort_date = filter_var(INPUT_POST, 'sort_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Init
            $query;
            $statement;

            if(!empty($sort_title) && !empty($sort_date)) {
                $query = "SELECT * FROM contents WHERE title LIKE :title ORDER BY :title $sort_title, date_posted $sort_date LIMIT 30";
                $statement = $db->prepare($query);

            } elseif(!empty($_POST['sort_title']) && isset($_POST['sort_title'])) {
                $query = "SELECT * FROM contents WHERE title LIKE :title ORDER BY :title $sort_title LIMIT 30";
                $statement = $db->prepare($query);
                
            } elseif (!empty($_POST['sort_date']) && isset($_POST['sort_title'])) {
                $query = "SELECT * FROM contents WHERE title LIKE :title ORDER BY date_posted $sort_date LIMIT 30";
                $statement = $db->prepare($query);

            } else {
                $query = "SELECT * FROM contents WHERE title = :title";
                $statement = $db->prepare($query);

            }
            $statement->bindValue(':title', $user_query, PDO::PARAM_STR);
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        } else {
            // Do the common query which is this $query = "SELECT * FROM contents LIMIT 30"; 
            $sort_title = filter_var(INPUT_POST, 'sort_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sort_date = filter_var(INPUT_POST, 'sort_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Init
            $query;
            $statement;

            if(!empty($sort_title) && !empty($sort_date)) {
                $query = "SELECT * FROM contents WHERE ORDER BY title $sort_title, date_posted $sort_date LIMIT 30";
                $statement = $db->prepare($query);

            } elseif(!empty($_POST['sort_title']) && isset($_POST['sort_title'])) {
                $query = "SELECT * FROM contents ORDER BY title $sort_title LIMIT 30";
                $statement = $db->prepare($query);
                
            } elseif (!empty($_POST['sort_date']) && isset($_POST['sort_title'])) {
                $query = "SELECT * FROM contents ORDER BY date_posted $sort_date LIMIT 30";
                $statement = $db->prepare($query);

            } else {
                $query = "SELECT * FROM contents";
                $statement = $db->prepare($query);

            }

            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        }
    } 

    if($_SERVER['REQUEST_METHOD'] == 'GET') {
        if(isset($_GET['sort_genre']) && ($_GET['sort_genre'] !== 'none')){
            $genre = filter_var($_GET['sort_genre'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $query = "SELECT * FROM contents WHERE genre_id = :genre_id";

            $genre_id = retrieve_genres($db, $genre);

            $statement = $db->prepare($query);
            $statement->bindValue(':genre_id', $genre_id[0]['genre_id'], PDO::PARAM_INT);
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        }
    }

    // When content loads.
    
    // Logic is included in the search
    // if(isset($_GET['sort_title']) ) {
    //     $sort_title = $_GET['sort_title'];

    //     if($sort_title !== 'ASC' || $sort_title !== 'DESC') {
    //         $sort_title = 'ASC';

    //     } 

    //     $query = "SELECT * FROM contents WHERE title ORDER BY title $sort_title LIMIT 30";
    // } else {
    //     $query = "SELECT * FROM contents LIMIT 30"; 
    // }
    
    // if(isset($_GET['sort_date'])){
    //     $sort_date = $_GET['sort_date'];

    //     if($sort_date !== 'ASC' || $sort_date !== 'DESC'){
    //         $sort_date = 'ASC';

    //     } else {
            
            
    //     }
    // }

  

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
                        <li><!-- Style it to the middle-->
                            <a href="user_stuff.php?user_id=<?= $_SESSION['client_id'] ?>">My stuff</a>
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
        <main>
            <div> <!-- search form -->
                <!-- <form action="index.php" method="post">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search">

                    <label for="sort_type">Sort By:</label>
                    <select name="sort_type" id="sort_type">
                        <option value="date">Date</option>
                        <option value="title">Title</option>
                    </select>

                    <label for="order">
                        <select name="order" id="order">
                            <option value="ASC">ASC</option>
                            <option value="DESC">DESC</option>
                        </select>
                    </label>
                    <input type="radio" name="sort_type" value="date" onchange="this.form.submit();"/>Date
                    <input type="radio" name="sort_type" value="title" onchange="this.form.submit();">Title

                    <label for="orientation">Order:</label>
                    <input type=
                    <button type="submit"></button>
<<<<<<< HEAD
                </form> --> <?php //TODO FIXME ?>
=======
                </form> -->



>>>>>>> ec16bcfa51c660640ca99d644585480f2f04b0fc
                <form action="index.php" method="post"> 
                    <div>
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" />
                    </div>
                    <input type="submit" name="submit" value="search_button"/>
                    <!-- Prototype form will be used for sortation-->
                    <label for="sort_title">Title</label>
                    <select name="sort_title" id="sort_title" onchange="document.getElementById('sort_title').form.submit();">
                        <option value="" selected></option>
                        <option value="ASC">ASC</option>
                        <option value="DESC">DESC</option>
                    </select>
                    <label for="sort_date">Date</label>
                    <select name="sort_date" id="sort_date" onchange="this.form.submit();">
                        <option value="" selected></option>
                        <option value="ASC">ASC</option>
                        <option value="DESC">DESC</option>
                    </select>
                </form>
            </div>
            <div>
                <!-- Form Get for sort_date -->
            </div>
            <div>
                <!-- Form Get for sort_title. -->
            </div>
            <div> <!-- Linkl for creating new post -->
                <?php if(isset($_SESSION['client_id']) || isset($_SESSION['isadmin'])): ?>
                    <a href="create_post.php">
                        <button type="button">New Post +</button>
                    </a>
                <?php endif ?>
            </div>
            <div> <!-- Drop Down for Categories -->
                <form action="index.php" method="get">
                    <label for="sort_genre">
                        <select name="sort_genre" id="sort_genre" onchange="this.form.submit();"> 
                            <option value="none">None</option>
                            <?php $genres = retrieve_genres($db, null) ?>
                            <?php foreach($genres as $genre_list): ?>
                                <option value=<?= $genre_list['genre_name'] ?>>
                                    <?= ucfirst($genre_list['genre_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </label>
                </form>
            </div>
            <div><!-- Container in place holding the content -->
                <?php foreach($results as $content): ?>
                    <div>
                        <a href="view_content.php">
                            <button type="button">View Full Post</button>
                        </a>
                        <?php if(isset($_SESSION['isadmin']) || (isset($_SESSION['client_id']) && ($_SESSION['client_id'] == $content['user_id']))):?>
<<<<<<< HEAD
                            <a href="edit.php?content_id=<?=$content['content_id']?>">
=======
                            <a href="edit.php?user_id=<?=$content['user_id']?>">
>>>>>>> ec16bcfa51c660640ca99d644585480f2f04b0fc
                                <button type="button">Edit</button>
                            </a>
                        <?php endif ?>
                        <?php if(isset($content['images'])): ?>
                            <img src="data:image/*;base64,<?= base64_encode($content['images']) ?>" 
                            alt="<?= $content['image_name'] ?>"/>
<<<<<<< HEAD
=======
                        <?php else: ?>
                            <!-- Add the no image picture -->
                            <img src="./images/no_image.jpg" alt="No image available" />
>>>>>>> ec16bcfa51c660640ca99d644585480f2f04b0fc
                        <?php endif ?>
                        <a href="view_content.php">
                            <h2><?= $content['title'] ?></h2>
                        </a>
                        <audio controls>
                            <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/mpeg"/>
                        </audio>
                        <!-- Posted By-->
                        <p>
                            @<?= getUser($db, $content['admin_id'], $content['user_id']) ?>
                        </p>
                        <!-- add the comments preview here  with the link to view more -->
                    </div>
                <?php endforeach ?>
            </div>
        </main>
    </body>
</html>

