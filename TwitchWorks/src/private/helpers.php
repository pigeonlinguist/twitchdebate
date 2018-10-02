<?php
    /*
     * Copyright 2017-2018 HowToCompute. All Rights Reserved
     */
    // Turn off error reporting due to security considerations. Here because all files include private/helpers.php
    error_reporting(0);
    
    function GetTwitchClientID()
    {
        $config = parse_ini_file("config.ini", true);
        return $config['twitch']['client_id'];
    }
    
    function GetTwitchClientSecret()
    {
        $config = parse_ini_file("config.ini", true);
        return $config['twitch']['client_secret'];
    }
    
    function GetTwitchRedirectURI()
    {
        $config = parse_ini_file("config.ini", true);
        return $config['twitch']['twitch_redirect_uri'];
    }
    
?>
