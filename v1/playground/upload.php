<?php
/**
 * Created by IntelliJ IDEA.
 * User: mikael.svensson
 * Date: 14/01/17
 * Time: 19:17
 */
$data = $_POST["imageData"];
$tag = $_POST["tag"];
$team = $_POST["team"];

list($type, $data) = explode(';', $data);
list(, $data) = explode(',', $data);
$data = base64_decode($data);

function sanitize($value)
{
    return preg_replace('/[^a-zA-Z0-9]/', '', $value);
}

$path = sprintf('archive/upload-test-%s-%s-%s.png', sanitize($team), sanitize($tag), uniqid());
file_put_contents($path, $data);

header('Content-Type: application/json');
echo json_encode(
    array(
        "success" => true,
        "recv" => strlen($data),
        "path" => $path
    )
);