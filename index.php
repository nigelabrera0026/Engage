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
        TODO ADMIN CRUD PRIV FOR USERS !!!! CREATE A MODERATE BUTTON IF ADMIN EXISTS
        TODO USE BOOTSTRAP FOR CSS PENDING
        TODO FIXME search algorithm and, title, date sortation. PENDING

        Dev notes: session_start(); carries over all the session.

        CONTROL FLOW FOR LIST SORTATION
        LIST SORT BY TITLE, GENRE ( DONE ), CREATED DATE/ POSTED DATE
        USET GET

        CONTROL FLOW FOR SEARCH - ALL PAGES (PROBABLY SEPARATE THE REQUEST METHOD POST TO ANOTHER FILE SO IT COULD BE DYNAMIC?)

        Search for specific pages by keyword using a search form.
        - A search form is available at the top of all pages.

        - The keyword or keywords entered into the search form will 
        be used to search for pages that include the provided word or phrase.

        - At a minimum the page name will be searched using a SQL LIKE query with wildcards, 
        but other page properties can also be searched.

        - The search will result in a list of links to all found pages.

        Search for specific pages by keyword while limiting the search results to a specific category of pages.
        - Assumes page categories have been implemented as defined in feature 2.4.

        - This is not a search for categories. The user provided keywords are still used to search for pages. 

        - The search form includes a dropdown menu to restrict the search to pages from a specific category.

        - The provided category dropdown includes all page categories from feature 2.4, 
        along with the option to search all categories.

        - When "all categories" is selected search works as in 3.1, 
        otherwise search results only include pages from selected category.

        Search results are paginated. (probably not? will check, we need to implement bootstrap soon)
        - Pagination is the process of dividing up your search results 
        into discrete pages. Each of your result pages should include at most N search results.

        - Below each page of search results is a set of links to all available pages, 
        along with previous page and next page links (if applicable).

        - Pagination links are only shown if there are greater than N search results. 
        - For testing purposes it should be easy to switch the value of N to a smaller or larger number.

        
    */

    // Global
    $error = [];

    // if($_SESSION['genre_holder'] == 'none') {
    //     session_destroy($_SESSION['genre_holder']);
    // }
    // if(isset($_SESSION['date_sort'])): 

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
    

    // TODO TEST
    // If search is clicked. 
    if($_SERVER['REQUEST_METHOD'] == 'POST') {

        // Garbage
        if($_POST && !empty($_POST['Search'])){
            // Filtration and Sanitization.
            $user_query = filter_input(INPUT_POST, 'Search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sort_title = filter_input(INPUT_POST, 'sort_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sort_date = filter_input(INPUT_POST, 'sort_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Init
            $query;
            $statement;

            if(!empty($sort_title) && !empty($sort_date)) {
                $query = "SELECT * FROM contents WHERE title LIKE :title ORDER BY title $sort_title, date_posted $sort_date LIMIT 30";
                $statement = $db->prepare($query);

            } elseif(!empty($_POST['sort_title']) && isset($_POST['sort_title'])) {
                $query = "SELECT * FROM contents WHERE title LIKE :title ORDER BY title $sort_title LIMIT 30";
                $statement = $db->prepare($query);
                
            } elseif (!empty($_POST['sort_date']) && isset($_POST['sort_title'])) {
                $query = "SELECT * FROM contents WHERE title LIKE :title ORDER BY date_posted $sort_date LIMIT 30";
                $statement = $db->prepare($query);

            } else {
                // echo "It passed here";
                $query = "SELECT * FROM contents WHERE title LIKE :title";
                $statement = $db->prepare($query);

            }
            $user_query = '%' . $user_query . '%';
            echo $query;
            $statement->bindValue(':title', $user_query, PDO::PARAM_STR);
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        } else {
            // Do the common query which is this $query = "SELECT * FROM contents LIMIT 30"; 
            $sort_title = filter_input(INPUT_POST, 'sort_title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sort_date = filter_input(INPUT_POST, 'sort_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            // Init
            $query;
            $statement;

            if(!empty($sort_title) && !empty($sort_date)) {
                $query = "SELECT * FROM contents ORDER BY title $sort_title, date_posted $sort_date LIMIT 30";
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

                    if(isset($_SESSION['sort_title'])) {
                        unset($_SESSION['sort_title']);
    
                    }
                    
                    if(isset($_SESSION['date_sort'])) {
                        unset($_SESSION['date_sort']);
    
                    }

                } else {
                    header("Location: invalid_url.php");
                    exit();
                    
                }
            } else {
                // If "None" is selected, clear the session variable
                unset($_SESSION['genre_holder']);
                // Clear other session
                if(isset($_SESSION['sort_title'])) {
                    unset($_SESSION['sort_title']);

                }
                
                if(isset($_SESSION['date_sort'])) {
                    unset($_SESSION['date_sort']);

                }
            }
        // Title Sortation
        } elseif(isset($_GET['sort_title'])) {
            
            if($_GET['sort_title'] !== 'none') {
                $sort_title = filter_var($_GET['sort_title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                if(!empty($sort_title)) {
                    $query = "SELECT * FROM contents ORDER BY title $sort_title";

                    $statement = $db->prepare($query);
                    $statement->execute();
                    $results = $statement->fetchAll(PDO::FETCH_ASSOC);

                    $_SESSION['sort_title'] = $sort_title;

                    if(isset($_SESSION['genre_holder'])) {
                        unset($_SESSION['genre_holder']);
    
                    }
    
                    if(isset($_SESSION['date_sort'])) {
                        unset($_SESSION['date_sort']);
    
                    }

                } else {
                    header("Location: invalid_url.php");
                    exit();

                }
            } else {
                unset($_SESSION['sort_title']);

                if(isset($_SESSION['genre_holder'])) {
                    unset($_SESSION['genre_holder']);

                }

                if(isset($_SESSION['date_sort'])) {
                    unset($_SESSION['date_sort']);

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
                    
                    if(isset($_SESSION['genre_holder'])) {
                        unset($_SESSION['genre_holder']);
    
                    }
    
                    if(isset($_SESSION['sort_title'])) {
                        unset($_SESSION['sort_title']);
    
                    }

                } else {
                    header("Location: invalid_url.php");
                    exit();
                }
            } else {
                unset($_SESSION['date_sort']);

                if(isset($_SESSION['genre_holder'])) {
                    unset($_SESSION['genre_holder']);

                }

                if(isset($_SESSION['sort_title'])) {
                    unset($_SESSION['sort_title']);

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
                                    <a href="index.php?sort_genre=none&sort_title=none&date_sort=none" class="nav-link text-light">Home</a>
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
                </form> FIXME --> 
                <form action="index.php" method="post"> 
                    <div>
                        <label for="search">Search CHECK</label><!-- Check if you could dodge it -->
                        <input type="text" name="search" id="Search" />
                    </div>
                    <input type="submit" name="submit" value="Search"/>
                    <!-- Prototype form will be used for sortation-->
                    <!-- <label for="sort_title">Title</label> 
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
                    </select> -->
                </form>
            </div>
            <?php if(isset($_SESSION['client'])): ?>
                <div><!-- Link for creating new post -->
                    <a href="create_post.php">
                        <button type="button">New Post +</button>
                    </a>
                </div>
                <div> <!-- Drop Down for Genres -->
                    <?php $genres = retrieve_genres($db, null) ?>
                    <form action="index.php" method="get">
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
        </main>
        <?php include("footer.php") ?>
    </body>
</html>

