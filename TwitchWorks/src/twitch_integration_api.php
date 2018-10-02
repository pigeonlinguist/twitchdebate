<?php

    /*
     * Copyright 2017-2018 HowToCompute. All Rights Reserved
     *
     * Endpoint /twitch_integration_api.
     *
     * Usage:
     * GET /twitch_integration_api, will allow the game to create a twitch login request, and track it's status to process the login.
     *
     * Returns nothing, but handles
     */
     
    require("private/database.php");
    require("private/helpers.php");
    
    if ($_GET['action'] == "START")
    {
        // Start action - will set up a mechanism that allows a game's user to log in/authorize the game.
        
        // Create a cryptographically secure key/token to identify/authenticate the game in later requests.
        $sessionKey = bin2hex(openssl_random_pseudo_bytes(16));
        $sessionToken = bin2hex(openssl_random_pseudo_bytes(32));
        
        // Create a database connection
        $conn = DatabaseConnect();
        
        // Use a prepared query to add this "request" (consisting of a token and key) into the database.
        if (!($stmt = $conn->prepare("INSERT INTO TwitchUsers(`Token`, `Key`) VALUES (?, ?)"))) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        
        if (!$stmt->bind_param("ss", $sessionToken, $sessionKey)) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            header("HTTP/1.1 500 Internal Server Error");
            die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        }
        
        // Successfully created a "request"! Pass it back to the game to open the user's web browser to actually log in/authorize.
        header("Content-Type: application/json");
        $response = array(
            'key' => $sessionKey,
            'token' => $sessionToken
        );
        die(json_encode($response));
    }
    else if ($_GET['action'] == "POLL")
    {
        // Did the user provide the key and token?
        if(!isset($_GET['key']) or !isset($_GET['token']))
        {
            die("Invalid Login Attempt - Token Or Key Not Set. Please Try Loggin In Again.");
        }
        
        $sessionKey = $_GET['key'];
        $sessionToken = $_GET['token'];
        
        // Create a database connection
        $conn = DatabaseConnect();
        
        // Use a prepared SQL query to retrieve aditional data from the database so we can check if the user has authorized already, and if required pass the data back to the game. This will also validate the key/token.
        if (!($stmt = $conn->prepare("SELECT `ID`, `Username`, `OAuthToken` FROM TwitchUsers WHERE `Key` = ? AND `Token` = ?"))) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Content-Type: application/json");
            die(json_encode(array('key' => $sessionKey,'success' => false)));
        }
        
        if (!$stmt->bind_param("ss", $sessionKey, $sessionToken)) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Content-Type: application/json");
            die(json_encode(array('key' => $sessionKey,'success' => false)));
        }
        
        $result = $stmt->execute();
        $stmt_result = $stmt->get_result();
        
        if (!$result) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Content-Type: application/json");
            die(json_encode(array('key' => $sessionKey,'success' => false)));
        }
        
        
        while($row = $stmt_result->fetch_assoc()) {
            // Has the user not completed OAuth yet?
            if($row['Username'] == null or $row['OAuthToken'] == null)
            {
                // Indeed - we still need to wait for the user, so return false.
                header("Content-Type: application/json");
                die(json_encode(array('key' => $sessionKey,'success' => false)));
            }
            
            // The user's info has set, so assume the user has successfully logged in and pass the data back!
            $response = array(
                'key' => $sessionKey,
                'success' => true,
                'username' => $row['Username'],
                'token' => $row['OAuthToken']
            );
            
            header("Content-Type: application/json");
            die(json_encode($response));
        }
        
        // Some unknown error occured!
        header("Content-Type: application/json");
        die(json_encode(array('key' => $sessionKey,'success' => false)));
    }
    else
    {
        die("Error occured! Unsupported request ".$_GET['action']."!");
    }
?>