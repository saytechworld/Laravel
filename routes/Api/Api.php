<?php
/**
*   User / Role / Permission Resource
**/

Route::group(['prefix' => 'v1'],function(){
   require_once(__DIR__.'/Version/v1.php');
});

Route::group(['prefix' => 'v2'],function(){
   require_once(__DIR__.'/Version/v2.php');
});

Route::group(['prefix' => 'v3'],function(){
   require_once(__DIR__.'/Version/v3.php');
});

Route::group(['prefix' => 'A/v4'],function(){
   require_once(__DIR__.'/Version/Android/v4.php');
});

Route::group(['prefix' => 'I/v4'],function(){
   require_once(__DIR__.'/Version/Ios/v4.php');
});

