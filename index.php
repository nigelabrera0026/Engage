<?php
    /*******w******** 
        
        @author: Nigel Abrera
        @date: 11/8/2023
        @description: Home page

    ****************/
    require("connect.php");
    require("library.php");
    session_start();

    // Global
    $error = [];

    // For create page.
    if(isset($_COOKIE['captcha_counter']) || isset($_SESSION['form_data'])) {
        setcookie('captcha_counter', '', time());
        unset($_SESSION['form_data']);

    } 

    // When the page loads.
    $query = "SELECT * FROM contents LIMIT 30"; 

    $statement = $db->prepare($query);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Form submitted using POST
    if($_SERVER['REQUEST_METHOD'] == 'POST') {

        if(isset($_POST['submit_search']) && !empty($_POST['search'])) {
            // Initialize var to be processed
            $user_search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $user_query;
            $user_set = false;

            // Sets the session to maintain searched query when page loads.
            $_SESSION['search_query'] = $user_search;

            // Checks if search input is about username of the authors.
            if(!empty(get_user_id($db, $user_search))) {
                $user_query = " OR (user_id = :user_id) ";
                $user_set = true;
            }
            
            $genre_query;
            $genre_set = false;
            // Verifies if input for genre is not == 'none'
            if(is_numeric($_POST['genre_search'])) {
                // Will append to the query
                $genre_query = " AND (genre_id = :genre_id) ";

                // Trigger for query
                $genre_set = true;

            } else {
                // Set session 
                $_SESSION['genre_search'] = 'none';

            }

            // Query to be executed.
            $query = "SELECT * FROM contents WHERE (title LIKE :title) OR (artist LIKE :artist)";

            // Appending values if true
            if($user_set) {
                $query .= $user_query;
            
            }
            
            if($genre_set) {
                $query .= $genre_query;

            } 

            // Preparation and Binding
            $statement = $db->prepare($query);
            $statement->bindValue(':title', '%' . $user_search . '%', PDO::PARAM_STR);
            $statement->bindValue(':artist', '%' . $user_search . '%', PDO::PARAM_STR);
            
            // Binding if true
            if($user_set) {
                $user_id = get_user_id($db, $user_search);
                $statement->bindValue(':user_id', $user_id, PDO::PARAM_INT);

            }
            
            if($genre_set) {
                $genre_search = filter_input(INPUT_POST, 'genre_search', FILTER_VALIDATE_INT);

                // Set session for dropdown
                $_SESSION['genre_search'] = $genre_search;
                $statement->bindValue(':genre_id', $genre_search, PDO::PARAM_INT);

            }

            // Unset Sessions
            $sessions = ['sort_title', 'date_sort', 'genre_holder'];

            foreach($sessions as $sesh){
                if(isset($_SESSION[$sesh])) {
                    unset($_SESSION[$sesh]);
                }
            }

            // Execute and fetch data, verify if there's content in it.
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

            if(empty($results)) {
                $no_content = "No Content Available";

            }
        } else {
            $error[] = "Invalid empty fields!";

            // Unset Sessions
            unset($_SESSION['genre_search']);
            unset($_SESSION['search_query']);
        }
    } 

    // Form submitted using GET
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Genre Sortation
        if(isset($_GET['sort_genre'])) {
            // Genre Sortation
            if($_GET['sort_genre'] !== 'none') {
                $genre = filter_var($_GET['sort_genre'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $genre_id = retrieve_genres($db, $genre);
    
                if (!empty($genre_id)) {
                    $query = "SELECT * FROM contents WHERE genre_id = :genre_id";
    
                    $statement = $db->prepare($query);
                    $statement->bindValue(':genre_id', $genre_id[0]['genre_id'], PDO::PARAM_INT);
                    $statement->execute();
                    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    
                    // Set the session variable
                    $_SESSION['genre_holder'] = $genre;

                    // Unset Sessions
                    $sessions = ['sort_title', 'date_sort'];

                    foreach($sessions as $sesh){
                        if(isset($_SESSION[$sesh])) {
                            unset($_SESSION[$sesh]);
                        }
                    }

                } else {
                    header("Location: invalid_url.php");
                    exit();
                    
                }
            } else {
                // If "None" is selected, clear the session variable
                unset($_SESSION['genre_holder']);

                // Clear other session
                $sessions = ['sort_title', 'date_sort'];

                foreach($sessions as $sesh){
                    if(isset($_SESSION[$sesh])) {
                        unset($_SESSION[$sesh]);
                    }
                }
            }
        // Title Sortation
        } elseif(isset($_GET['sort_title'])) {
            
            if($_GET['sort_title'] !== 'none') {
                $sort_title = filter_var($_GET['sort_title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                if(!empty($sort_title)) {
                    $query = "SELECT * FROM contents ORDER BY title $sort_title";

                    // Prepare, Execute, Fetch
                    $statement = $db->prepare($query);
                    $statement->execute();
                    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

                    $_SESSION['sort_title'] = $sort_title;

                    // Unset Sessions
                    $sessions = ['date_sort', 'genre_holder'];

                    foreach($sessions as $sesh){
                        if(isset($_SESSION[$sesh])) {
                            unset($_SESSION[$sesh]);
                        }
                    }

                } else {
                    header("Location: invalid_url.php");
                    exit();

                }
            } else {
                unset($_SESSION['sort_title']);
                $sessions = ['date_sort', 'genre_holder'];

                foreach($sessions as $sesh){
                    if(isset($_SESSION[$sesh])) {
                        unset($_SESSION[$sesh]);
                    }
                }
            }
        // Created Date Sortation
        } elseif(isset($_GET['date_sort'])){

            if($_GET['date_sort'] !== 'none') {
                $date_sort = filter_var($_GET['date_sort'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                
                if(!empty($date_sort)) {
                    $query = "SELECT * FROM contents ORDER BY date_posted $date_sort";

                    $statement = $db->prepare($query);
                    $statement->execute();
                    $results = $statement->fetchAll(PDO::FETCH_ASSOC);


                    $_SESSION['date_sort'] = $date_sort;

                    // Unset Sessions
                    $sessions = ['sort_title', 'genre_holder'];

                    foreach($sessions as $sesh){
                        if(isset($_SESSION[$sesh])) {
                            unset($_SESSION[$sesh]);
                        }
                    }

                } else {
                    header("Location: invalid_url.php");
                    exit();
                }
            } else {
                unset($_SESSION['date_sort']);
                $sessions = ['sort_title', 'genre_holder'];

                foreach($sessions as $sesh){
                    if(isset($_SESSION[$sesh])) {
                        unset($_SESSION[$sesh]);
                    }
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
        <title>Engage</title>
        <link rel="stylesheet" href="./bootstrap/css/bootstrap.css">
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
        <main>
            <div> <!-- Search -->
                <form action="index.php" method="post">
                    <label for="search"><!-- probably a logo --></label>
                    <input type="text" name="search" id="search" 
                    value="<?=(isset($_SESSION['search_query'])) ? $_SESSION['search_query'] : '' ?>"/>
                    <!-- Unset session right away -->
                    <?php if(isset($_SESSION['search_query'])):?>
                        <?php unset($_SESSION['search_query']) ?>
                    <?php endif ?>
                    <label for="genre_search">Genre</label>
                    <!-- Generate genre-->
                    <?php $genre_search = retrieve_genres($db, null) ?>
                    <select name="genre_search" id="genre_search">
                        <option value="none" 
                        <?= (!isset($_SESSION['genre_search']) || 
                        (isset($_SESSION['genre_search']) && ($_SESSION['genre_search'] == 'none')))  ? "selected" : ''?>>
                            None
                        </option>
                        <?php foreach($genre_search as $genre_searches): ?>
                            <option value="<?= $genre_searches['genre_id'] ?>"
                            <?= (isset($_SESSION['genre_search']) && 
                                ($_SESSION['genre_search'] == $genre_searches['genre_id'])) ? 'selected' : '' ?>>
                                <?= ucfirst($genre_searches['genre_name']); ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <input type="submit" name="submit_search" id="submit_search" value="Search"/>
                    <?php if(isset($_SESSION['genre_search'])): ?>
                        <?php unset($_SESSION['genre_search']) ?>
                    <?php endif ?>
                </form>        
            </div>
            <?php if(isset($_SESSION['client'])): ?>
                <div><!-- Link for creating new post -->
                    <a href="create_post.php">
                        <button type="button">New Post +</button>
                    </a>
                </div>
                <div> <!-- Drop Down for Genres -->
                    <form action="index.php" method="get">
                        <!-- Generate genre-->
                        <?php $genres = retrieve_genres($db, null) ?>
                        <label for="sort_genre">Genre</label>
                        <select name="sort_genre" id="sort_genre" onchange="this.form.submit();">
                            <option value="none" <?= (empty($_SESSION['genre_holder']) || ($_SESSION['genre_holder'] == 'none')) ? "selected" : '' ?>>
                                None
                            </option>
                            <?php foreach ($genres as $genre_list): ?>
                                <option value="<?= $genre_list['genre_name'] ?>" 
                                    <?= (!empty($_SESSION['genre_holder']) && 
                                    ($_SESSION['genre_holder'] == $genre_list['genre_name'])) ? 'selected' : '' ?>>
                                    <?= ucfirst($genre_list['genre_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </form>
                </div>
                <div> <!-- Title Sortation -->
                    <form action="index.php" method="get">
                        <label for="sort_title">Title </label>
                        <select name="sort_title" id="sort_title" onchange="this.form.submit();">
                            <option value="none" <?= (empty($_SESSION['sort_title']) || 
                            ($_SESSION['sort_title'] == 'none')) ? "selected" : '' ?>>None</option>
                            <option value="ASC" <?= (isset($_SESSION['sort_title']) && 
                            ($_SESSION['sort_title'] == 'ASC')) ? "selected" : '' ?>>Ascending</option>
                            <option value="DESC" <?= (isset($_SESSION['sort_title']) && 
                            ($_SESSION['sort_title'] == 'DESC')) ? "selected" : '' ?>>Descending</option>
                        </select>
                    </form>
                </div>
                <div> <!-- Created Date Sortation -->
                    <form action="index.php" method="get">
                        <label for="date_sort">Created Date</label>
                        <select name="date_sort" id="" onchange="this.form.submit();">
                            <option value="none" <?= (empty($_SESSION['date_sort']) || 
                            ($_SESSION['date_sort'] == 'none')) ? "selected" : '' ?>>None</option>
                            <option value="ASC" <?= (isset($_SESSION['date_sort']) && 
                            ($_SESSION['date_sort'] == 'ASC')) ? "selected" : '' ?>>Ascending</option>
                            <option value="DESC" <?= (isset($_SESSION['date_sort']) && 
                            ($_SESSION['date_sort'] == 'DESC')) ? "selected" : '' ?>>Descending</option>
                        </select>
                    </form>
                </div>
            <?php endif ?>
            <?php if(isset($no_content) && !empty($no_content)): ?>
                <div><h1>No Content Available.</h1></div>
            <?php else: ?>
                <div> <!-- Container in place holding the content -->
                    <?php foreach($results as $content): ?>
                        <div>
                            <a href="view_content.php?content_id=<?= $content['content_id'] ?>">
                                <button type="button">View Full Post</button>
                            </a>
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
                            <h4><?= $content['artist'] ?></h4>
                            <?php if(isset($content['song_file'])): ?>
                                <audio controls>
                                    <source src="data:audio/*;base64,<?= base64_encode($content['song_file']) ?>" type="audio/mpeg"/>
                                </audio>
                            <?php endif ?>
                            <p> <!-- Posted By-->
                                <?php if(!empty($content['user_id'])): ?>
                                    @<?= getUser($db, $content['admin_id'], $content['user_id']) ?>
                                <?php endif ?>
                            </p>
                            <!-- add the comments preview here  with the link to view more not required, only for design. -->
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </main>
        <?php include("footer.php") ?>
    </body>
</html>
