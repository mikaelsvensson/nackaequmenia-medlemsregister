<?php
$url = $_POST["url"];
$name = substr(strtolower(preg_replace('/[^A-Za-z0-9_.-]/', '', $_POST["name"])), 0, 50);

$path = sprintf('archive/upload-url-%s-%s', $name, uniqid());
$success = copy($url, $path);
if ($success) {
    http_response_code(201);
    header('Location: /medlemsregister/' . $path);
} else {
    http_response_code(500);
}
