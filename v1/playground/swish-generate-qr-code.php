<?php

$http_request_payload = json_encode(array(
    'format' => 'png',
    'payee' => array(
        'editable' => false,
        'value' => '1233494234'
    ),
    'amount' => array(
        'editable' => false,
        'value' => 1.5
    ),
    'message' => array(
        'editable' => false,
        'value' => 'Testbetalning'
    ),
    'size' => 500,
    'border' => 1,
    'transparent' => false
));
$http_config = array(
    'http' => array(
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $http_request_payload
    )
);

$context = stream_context_create($http_config);

//print $http_request_payload;
$image_data = file_get_contents('https://mpc.getswish.net/qrg-swish/api/v1/prefilled', false, $context);
$write_result = file_put_contents(tempnam('../medlemsregister-temp', 'swish'), $image_data);
var_dump($write_result);
//header('Content-type: image/png');
//echo $image_data;