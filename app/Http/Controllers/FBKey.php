<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facebook;
use GuzzleHttp\Client;

class FBKey extends Controller
{
    public function index()
    {
        $fb = new Facebook\Facebook([/* . . . */]);

        $helper = $fb->getRedirectLoginHelper();
        if (isset($_GET['state'])) {
          $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }
        try {
            $accessToken = $helper->getAccessToken();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        if (isset($accessToken)) {
            // Logged in!
            $_SESSION['facebook_access_token'] = (string) $accessToken;

        // Now you can redirect to another page and use the
    // access token from $_SESSION['facebook_access_token']
        } elseif ($helper->getError()) {
            // The user denied the request
            exit;
        }
        $consul_url = 'http://127.0.0.1:8500';
        $client = new Client(['base_uri' => $consul_url]);
        $client->request('PUT', '/v1/kv/fbkey', ['body' => $accessToken]);
        
        return $accessToken;
    }
    public function login()
    {
        $fb = new Facebook\Facebook([/* . . . */]);
        $helper = $fb->getRedirectLoginHelper();
        $permissions = ['manage_pages', 'publish_pages', 'publish_actions']; // optional
        $loginUrl = $helper->getLoginUrl('https://rssparse.test/', $permissions);

        echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';
    }
}
