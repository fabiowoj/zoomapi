<?php
 
//error_reporting(E_ALL);
//ini_set('display_errors', 'on');  
require_once 'config.php';

$plugin = ZoomApiPlugin::create();
$zoomPluginEnabled = $plugin->get('tool_enable');
$zoomPluginClientId = $plugin->get('clientid');
$zoomPluginClientSecret = $plugin->get('clientsecret');
$zoomPluginRedirectUri = api_get_path(WEB_PLUGIN_PATH).'zoomapi/callback.php';

//print_r($zoomPluginClientId);

//define('CLIENT_ID', $zoomPluginClientId);
//define('CLIENT_SECRET', $zoomPluginClientSecret);
//define('REDIRECT_URI', $zoomPluginRedirectUri);

try {
    $client = new GuzzleHttp\Client(['base_uri' => 'https://zoom.us']);
 
    $response = $client->request('POST', '/oauth/token', [
        "headers" => [
            "Authorization" => "Basic ". base64_encode($zoomPluginClientId.':'.$zoomPluginClientSecret)
        ],
        'form_params' => [
            "grant_type" => "authorization_code",
            "code" => $_GET['code'],
            "redirect_uri" => $zoomPluginRedirectUri
        ],
    ]);
 
    $token = json_decode($response->getBody()->getContents(), true);
 
    $db = new ZoomApi();
 
    
    $update = $db->update_access_token(json_encode($token));
    echo $update;
    
} catch(Exception $e) {
    echo $e->getMessage();
}
