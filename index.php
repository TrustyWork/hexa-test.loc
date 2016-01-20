<?php

require_once "vendor/autoload.php";

$savePath = __DIR__ . '/download';
$image = 'http://slavpeople.com/images/news_posters/kupit-google-za-12-dollarov_14437941531030.jpg';
//$images = ['http://slavpeople.com/images/news_posters/kupit-google-za-12-dollarov_14437941531030.jpg'];

$obj = new Downloader();
$obj->setSavePath($savePath);
$obj->setImages($image);
$obj->run();