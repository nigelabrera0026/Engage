<?php 
    // This would be used for optimization

    /**
     * Slicing the email and checking if it's admin or not.
     * @param email retrieves the email to be sliced.
     * @return bool 
     */
    function user_or_admin($email) {
        // Retrieves 2 parts of the param.
        $domain = explode('@', $email);

        if(count($domain) == 2) { // if it's a valid email.
            if($domain[1] == "engage.com") {
                return true;

            } else {
                return false;
            }
        } else {
            global $error;
            $error[] = "Invalid Email format!";

        }
    }

?>