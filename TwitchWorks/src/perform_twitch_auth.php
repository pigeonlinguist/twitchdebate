<?php
    
    // Copyright 2017-2018 HowToCompute. All Rights Reserved
    
    /*
     * Endpoint /twitch_callback.
     *
     * Usage:
     * GET /twitch_callback, automatically called from twitch
     *
     * Returns nothing, but processes a login request.
     */
     
    require("private/database.php");
    require("private/helpers.php");
    
    // Did the user provide the key and token?
    if(!isset($_GET['token']) or !isset($_GET['key']))
    {
        die("Invalid Login Attempt - Token Or Key Not Set. Please Try Loggin In Again.");
    }
    
    $sessionToken = $_GET['token'];
    $sessionKey = $_GET['key'];
    
    $CLIENT_ID = GetTwitchClientID();
    $REDIRECT_URI = GetTwitchRedirectURI();
    
    $conn = DatabaseConnect();
    
    // Prepare a prepared query (to mitigate SQL injections) that will check if there are any results returned for the user's token (was it a login that was asked for, or is it a malicious attempt?).    
    if (!($stmt = $conn->prepare("SELECT * FROM `TwitchUsers` WHERE `Key` = ?"))) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    
    if (!$stmt->bind_param("s", $sessionKey)) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    
    // Attempt to execute the prepared query
    $result = $stmt->execute();
    $stmt_result = $stmt->get_result();
    
    // Did the prepared query successfully execute?
    if (!$result) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    
    // Not 1 result? Something must have gone wrong, or this was an incorrect login attempt.
    if ($stmt->num_rows >= "1") {
        die("Invalid Login Attempt - Please Try Again");
    }
    
    # Redirect the user to the twitch authorization "page" so they can log in/authorize the app, and pass in the state to keep track of the request.
    header("Location: https://api.twitch.tv/kraken/oauth2/authorize?response_type=code&client_id=".$CLIENT_ID."&redirect_uri=".$REDIRECT_URI."&scope=user_read+chat_login&state=".$sessionToken);
    echo("<h1>Redirecting...</h1>");
    echo("Location: https://api.twitch.tv/kraken/oauth2/authorize?response_type=token&client_id=".$CLIENT_ID."&redirect_uri=".$REDIRECT_URI."&scope=chat_login+user_read&state=".$sessionToken);
    die();
?>