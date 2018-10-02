<?php

    /*
     * Copyright 2017-2018 HowToCompute. All Rights Reserved
     *
     * Endpoint /twitch_callback.
     *
     * Usage:
     * GET /twitch_callback, will check the key is valid and then redirect the user to twitch to log in and to authorize the app.
     *
     * Returns nothing, but processes a login request.
     */
     
    require("private/database.php");
    require("private/helpers.php");
    
    $servername = "localhost";
    $username = "imlolly";
    $password = "";
    $database = "TwitchTest";
    
    
    $sessionToken = $_GET['state'];
    $oauthToken = "";
    
    $CLIENT_ID = GetTwitchClientID();
    
    $CLIENT_SECRET = GetTwitchClientSecret();
    $REDIRECT_URI = GetTwitchRedirectURI();
    
    // Exchange the "temporary" token for a proper OAuth token we can use to actually do things
    $ch = curl_init("https://api.twitch.tv/kraken/oauth2/token");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    $fields = array(
        'client_id' => $CLIENT_ID,
        'client_secret' => $CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $REDIRECT_URI,
        'code' => $_GET['code']
    );
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    $data = curl_exec($ch);
    
    $json = json_decode($data, true);
    $oauthToken = $json['access_token'];
    $info = curl_getinfo($ch);
    
    // Retrieve information about the user whom the token belongs to.
    if(!$json = json_decode(file_get_contents('https://api.twitch.tv/kraken?oauth_token='.$oauthToken), true))
    {
        die("error! Unable to fetch user info.");
    }
    
    $username = $json['token']['user_name'];
    $displayName = $json['display_name'];
    $email = $json['email'];
    
    /*
    The below code will update the user's entry in the database so the game can use it's token/verification code to retrieve the user's OAuth token
    */
    
    // Connect to the database
    $conn = DatabaseConnect();
    
    if (!($stmt = $conn->prepare("UPDATE `TwitchUsers` SET `Username` = ?, `OAuthToken` = ? WHERE `Token` = ?"))) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    
    if (!$stmt->bind_param("sss", $username, $oauthToken, $sessionToken)) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    
    // Execute the prepared query, and fetch the returned data
    $result = $stmt->execute();
    $stmt_result = $stmt->get_result();
    
    if (!$result) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    
    echo('<!DOCTYPE html><html> <head> <meta charset="utf-8"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <meta name="viewport" content="width=device-width, initial-scale=1"> <title>Twitch Login Success!</title> <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous"> <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"> <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script> <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script> <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script> <script type="text/javascript">$(window).on(\'load\', function(){$(\'#loginSuccessModal\').modal(\'show\');}); </script> </head> <body style="background-color:#8BC34A"> <div class="modal fade" id="loginSuccessModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true"> <div class="modal-dialog" role="document"> <div class="modal-content"> <div class="modal-header"> <h5 class="modal-title" id="loginSuccessModalTitle">Success!</h5> <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button> </div><div class="modal-body"> You have successfully logged in to twitch>'.$username.'! Please switch back to the game, and start playing! <br/><br/> <pre>NOTE: The game may take a few seconds to pick up the login,<br />please be patient and retry if you are still facing the<br />loading screen after more than 15 seconds.</pre> </div><div class="modal-footer"> </div></div></div></div></body></html');
    echo($_GET['access_token']);
?>