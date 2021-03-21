<?php
require_once __DIR__ . '/config.php';

function code_swish_qr_code_url($amount, $message)
{
    global $config;
    $http_request_payload = json_encode([
        'format' => 'png',
        'payee' => [
            'editable' => false,
            'value' => $config['swish']['number']
        ],
        'amount' => [
            'editable' => false,
            'value' => round($amount / 100, 2)
        ],
        'message' => [
            'editable' => false,
            'value' => $message
        ],
        'size' => 300,
        'border' => 0,
        'transparent' => false
    ]);

    $http_config = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $http_request_payload
        ]
    ];

    $context = stream_context_create($http_config);

    $image_data = file_get_contents('https://mpc.getswish.net/qrg-swish/api/v1/prefilled', false, $context);

    return 'data:image/png;base64,' . base64_encode($image_data);
}

function code_bankgiro_qr_code_url($amount, $message, $dueDate)
{
    global $config;
    $data_parameter = json_encode([
        'uqr' => 1,
        'tp' => 1,
        'pt' => 'BG',
        'acc' => $config['bankgiro']['number'],
        'nme' => $config['bankgiro']['name'],
        'cid' => $config['bankgiro']['company_id'],
        'iref' => strval($message),
        'ddt' => date('Ymd', strtotime($dueDate)),
        'due' => round($amount / 100, 2)
    ], JSON_UNESCAPED_SLASHES);

    $url = 'http://api.qrserver.com/v1/create-qr-code/?format=png&size=300x300&data=' . urlencode($data_parameter);

    $http_config = [
        'http' => [
            'method' => 'GET'
        ]
    ];

    $context = stream_context_create($http_config);

    $image_data = file_get_contents($url, false, $context);

    return 'data:image/png;base64,' . base64_encode($image_data);
}