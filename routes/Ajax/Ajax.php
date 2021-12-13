<?php
/**
*   Ajax Route
**/

Route::post('user/uniqueuser/{user?}',['as' => 'user.uniqueuser',  'uses' => 'AjaxController@checkUniqueUser']); 
Route::post('user/uniqueuserbymobile/{user?}',['as' => 'user.uniqueuserbymobile',  'uses' => 'AjaxController@checkUniqueUserByMobile']);
Route::post('user/validatezip',['as' => 'user.validatezip',  'uses' => 'AjaxController@validateZipcode']);
Route::post('static_page/uniquetitle/{title?}',['as' => 'static_page.uniquetitle',  'uses' => 'AjaxController@checkUniqueStaticPageTitle']);
Route::get('first_login', ['as'=>'user.first_login','uses'=>'AjaxController@firstLogin']);

Route::post('static_page/tag/{title?}',['as' => 'static_page.tag',  'uses' => 'AjaxController@checkUniqueTagTitle']);
Route::post('permission/uniquepermissionname/{permission?}',['as' => 'permission.uniquepermissionname',  'uses' => 'AjaxController@checkUniquePermission']);

Route::post('tag/uniquetagtitle/{tag?}',['as' => 'tag.uniquetagtitle',  'uses' => 'AjaxController@checkUniqueTagTitle']); 




Route::post('fetchcountrystate',['as' => 'state.fetchstatebycountryid',  'uses' => 'AjaxController@fetchCountryState']); 
Route::post('fetchcountrystatecity',['as' => 'state.fetchstatecity',  'uses' => 'AjaxController@fetchStateCity']); 
Route::post('fetchgameskill',['as' => 'gameskill.fetchgameskill',  'uses' => 'AjaxController@fetchGameSkill']); 


Route::post('folder/uniquefolder/{type}',['as' => 'folder.uniquefolder',  'uses' => 'AjaxController@checkUniqueFolder'])->where('type','1|2'); 
Route::post('video/store',['as' => 'video.store',  'uses' => 'AjaxController@storeVideo']);
