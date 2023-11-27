
<?php 
    
    /**
     * SANDBOX FOR TEST
     * 
     * TO TEST:
     * 
     */

     
    require("connect.php");
    require("library.php");
    $error = [];

    // function verify_user_existence($db, $email) { // TODO TBT
    //     if(user_or_admin($email)) { // true if admin
    //         $query = "SELECT * FROM admins WHERE email = :email";

    //     } else {
    //         $query = "SELECT * FROM users WHERE email = :email";
    //     }
        
    //     $statement = $db->prepare($query);
    //     $statement->bindValue(':email', $email, PDO::PARAM_STR);
    //     $statement->execute();
    //     $result = $statement->fetch(PDO::FETCH_ASSOC);

    //     if(!empty($result)){
    //         return $result['email'];

    //     } else {
    //         return null;
    //     }
    // }

    $query = "SELECT title FROM contents WHERE title = :title";
    $title = filter_var($title, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $statement = $db->prepare($query);
    $statement->bindValue(':title', "magnets", PDO::PARAM_STR);
    $statement->execute();
    $results = $statement->fetchAll(PDO::FETCH_ASSOC);
    print_r($results);

  
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Page</title>
    </head>
    <body>
        <form action="sandbox.php" method="post" id="search_and_sort"> 
            <div>
                <label for="search">Search</label>
                <input type="text" name="search" id="search" />
            </div>
            
            <!-- Prototype form will be used for sortation-->
            <label for="sort_title">Title</label>
            <select name="sort_title" id="sort_title" onchange="this.form.submit()">
                <option value=""></option>
                <option value="ASC">ASC</option>
                <option value="DESC">DESC</option>
            </select>
            <label for="sort_date">Date</label>
            <select name="sort_date" id="sort_date" onchange="this.form.submit()">
                <option value=""></option>
                <option value="ASC">ASC</option>
                <option value="DESC">DESC</option>
            </select>
            <input type="submit" name="submit" value="search_button"/>
        </form>
        <div>
            <?php if(!empty($error)): ?>
                <h1>Error(s):</h1>
                <ul>
                    <?php foreach($error as $message): ?>
                        <li><?= $message ?></li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </div>
        <div> 
            <?= $_POST['search']  ?>
        </div>
    </body>
</html>

<!-- <div>
            <form method="post" action="#">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" />
                <label type="pwd">Password</label>
                <input type="password" id="pwd" name="pwd" required/>
                <button type="submit" id="login_submit">Sign In</button>
            </form>
        </div> -->