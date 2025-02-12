<?php

// include LibrePanelAPI helper class
require __DIR__ . '/LibrePanelAPI.php';

// create object of LibrePanelAPI with URL, apikey and apisecret
$fapi = new LibrePanelAPI('http://localhost/api.php', 'your-api-key', 'your-api-secret');

// send request
$response = $fapi->request('LibrePanel.listFunctions');

// check for error
if ($fapi->getLastStatusCode() != 200) {
    echo "HTTP-STATUS: " . $fapi->getLastStatusCode() . PHP_EOL;
    echo "Description: "  . $response['message'] . PHP_EOL;
    exit();
}

// view response data
var_dump($response);
