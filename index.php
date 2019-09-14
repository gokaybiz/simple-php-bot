<?php
require(__DIR__	 . '/vendor/autoload.php');
include(__DIR__	 . '/bot.php');

Requests::register_autoloader();

try {
    $config = [
        "category" => "E-KPSS-257",
        "save_folder" => "photos" //image path
    ];
    $bot = new Bot($config);
    $fetch = $bot->crawl(); //crawl all products
    // $fetch = $bot->crawl([2]); //start from second product
    // $fetch = $bot->crawl([2, 6]); //between first .. sixth
    
    $products = $fetch->get(); //list all products
    // print_r($products);

    $length = count($products); //products length
    print_r($fetch->get($length - 1)); //list last product
    
    $fetch->download(); //download product images
} catch (Exception $e) {
    echo $e->getMessage();
}