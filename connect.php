<?php 
     define('DB_DSN','mysql:host=localhost;dbname=serverside;charset=utf8');
     define('DB_USER','serveruser');
     define('DB_PASS','gorgonzola7!'); 

     // in 000webhost it is Gorgonzola7!
     
    //  PDO is PHP Data Objects
    try {
        $db = new PDO(DB_DSN, DB_USER, DB_PASS);

    } catch (PDOException $e) {
        print "Error: " . $e->getMessage();
        
    }
?>
