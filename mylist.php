<?php
header('Content-type: application/json');
$link_list = array(
    "title" => "Tiny Home Page", "value" => "https://www.tiny.cloud",
    "title" => "Tiny Blog", "value" => "https://www.tiny.cloud/blog",);
echo json_encode($link_list);
?>