<!-- Copyright (c) Microsoft. All rights reserved. Licensed under the MIT license. See full license at the bottom of this file. -->

<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    require_once('Constants.php');
    
    // Handle the authorization code part of the flow
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code'])) {
        if(isset($_GET['admin_consent'])){
            $_SESSION['admin_consent'] = $_GET['admin_consent'];
        }
        if(isset($_GET['code'])){
            $_SESSION['code'] =  $_GET['code'];
        }
        if(isset($_GET['session_state'])){
            $_SESSION['session_state'] =  $_GET['session_state'];
        }
        if(isset($_GET['state'])){
            $_SESSION['state'] =  $_GET['state'];
        }
        
        AuthenticationManager::getTokens();
        
        $redirect = 'SendMail.php';
        header("Location: " . $redirect);
        exit();
    }

    class AuthenticationManager{
        public static function connect(){
            $authUrl = Constants::AUTHORITY_URL . Constants::AUTHORIZE_ENDPOINT . '?response_type=code&client_id=' . Constants::CLIENT_ID . '&redirect_uri=' . Constants::REDIRECT_URI;
            header("Location: {$authUrl}");
            exit();
        }
        
        public static function getTokens(){
            $tokenEndpoint = Constants::AUTHORITY_URL . Constants::TOKEN_ENDPOINT;
            
            $curl = curl_init();
            
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $tokenEndpoint,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => array(
                    'client_id' => Constants::CLIENT_ID,
                    'client_secret' => Constants::CLIENT_SECRET,
                    'code' => $_SESSION['code'],
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => Constants::REDIRECT_URI,
                    'resource' => 'https://graph.microsoft.com/'
                )
            ));
            
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            
            // Send the request & save response to $resp
            $resp = curl_exec($curl);
            
            // Close request to clear up some resources
            curl_close($curl);
            
            $jsonResponse = json_decode($resp, true);
            foreach ($jsonResponse as $key=>$value) {
                $_SESSION[$key] = $value;
            }
            
            $startOfPayload = strpos($_SESSION['access_token'], ".") + 1;
            $endOfPayload = strpos($_SESSION['access_token'], ".", $startOfPayload);
            
            $decodedAccessTokenPayload = base64_decode(substr($_SESSION['access_token'], $startOfPayload, $endOfPayload - $startOfPayload));
            
            $jsonAccessTokenPayload = json_decode($decodedAccessTokenPayload, true);
            
            $_SESSION['given_name'] = $jsonAccessTokenPayload['given_name'];
            $_SESSION['family_name'] = $jsonAccessTokenPayload['family_name'];
            $_SESSION['unique_name'] = $jsonAccessTokenPayload['unique_name'];
        }
        
        public static function disconnect(){
            session_destroy();
            
            $redirect = (@$_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
            $redirect .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
            
            // Get the URL before the document and substitute with connect.php
            $redirect = substr($redirect, 0, strrpos( $redirect, '/') ) . '/connect.php';
 
            return Constants::AUTHORITY_URL . Constants::LOGOUT_ENDPOINT . '?post_logout_redirect_uri=' . $redirect;
        }
    }
?>

<!--
O365-PHP-Unified-API-Connect, https://github.com/OfficeDev/O365-PHP-Unified-API-Connect
 
Copyright (c) Microsoft Corporation
All rights reserved. 
 
MIT License:
Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:
 
The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.    
  
-->