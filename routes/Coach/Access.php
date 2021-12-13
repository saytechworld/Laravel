<?php



	Route::get('dashboard', ['as'=>'dashboard','uses'=>'DashboardController@index']);
	Route::get('profile', ['as'=>'profile','uses'=>'DashboardController@fetchProfile']);
	Route::post('profile', ['as'=>'updateprofile','uses'=>'DashboardController@updateProfile']);

	Route::get('changepassword', ['as'=>'changepassword','uses'=>'DashboardController@changePassword']);
	Route::post('changepassword', ['as'=>'updatepassword','uses'=>'DashboardController@updatePassword']);
Route::match(['get', 'post'], 'updateuserprofileimage', ['as' => 'updateuserprofileimage', 'uses' => 'DashboardController@updateUserImage']);

	Route::get('paymentdetail', ['as'=>'createpaymentdetail','uses'=>'DashboardController@createPaymentDetail']);
	Route::post('paymentdetail', ['as'=>'updatepaymentdetail','uses'=>'DashboardController@updatePaymentDetail']);
	Route::get('bankaccount', ['as'=>'bankaccount','uses'=>'DashboardController@bankAccountForm']);
	Route::post('bankaccount', ['as'=>'bankaccount','uses'=>'DashboardController@createBankAccount']);
	Route::post('payout', ['as'=>'payout','uses'=>'DashboardController@payout']);
	Route::post('bankaccount/update', ['as'=>'bankaccount.update','uses'=>'DashboardController@updateBankAccount']);

	Route::get('editgameskill', ['as'=>'editgameskill','uses'=>'DashboardController@editGameSkill']);
	Route::post('editgameskill', ['as'=>'updategameskill','uses'=>'DashboardController@updateGameSkill']);
	
	Route::match(['get','post'],'deleteusercard', ['as'=>'deleteusercard','uses'=>'DashboardController@deleteStripeCard']);
	Route::group(['prefix' => 'plan', 'as' => 'plan.'],function(){
		Route::get('/', ['as'=>'index','uses'=>'PlanController@index']);
		Route::get('payment/{plan}/{plan_price}', ['as'=>'payment.show','uses'=>'PlanController@showPlanPayment']);
		Route::post('payment/{plan}/{plan_price}', ['as'=>'payment.store','uses'=>'PlanController@storePlanPayment']);
		Route::post('payment/paypal/{plan}/{plan_price}', ['as'=>'payment.paypal','uses'=>'PlanController@storePayPalPayment']);
	});

Route::get('paypal/return', ['as'=>'paypal.return','uses'=>'PlanController@paypalPaymentResponse']);

Route::get('order', ['as' => 'order', 'uses' => 'OrderController@orderHistory']);
Route::get('order/{id}/detail', ['as' => 'order.detail', 'uses' => 'OrderController@orderDetail']);
Route::match(['get','post'],'deleteuser', ['as'=>'deleteuser','uses'=>'DashboardController@deleteUserProfile']);

Route::group(['middleware' => 'CoachSubscription'],function(){
    Route::get('athlete/profileDetail/{user}', ['as' => 'athelete.profileDetail', 'uses' => 'HomeController@fetchProfile']);

	Route::get('chat', ['as'=>'chat.index','uses'=>'ChatController@index']);
	Route::get('chat/create', ['as'=>'chat.create','uses'=>'ChatController@create']);
	Route::post('chat', ['as'=>'chat.store','uses'=>'ChatController@store']);

	Route::match(['get','post'],'creategroup', ['as'=>'chat.creategroup','uses'=>'ChatController@createGroup']);

	Route::get('chatuser', ['as'=>'chat.chatuser','uses'=>'ChatController@fetchChatUser']);
	Route::get('media_archive', ['as'=>'chat.media_archive','uses'=>'ChatController@mediaArchive']);
    Route::get('fetchusercard', ['as'=>'chat.fetchusercard','uses'=>'ChatController@fetchUserCards']);
	Route::match(['get','post'],'folderMediaArchive', ['as'=>'chat.folderMediaArchive','uses'=>'ChatController@folderMediaArchive']);
	Route::match(['get','post'],'fetchusermessage', ['as'=>'chat.fetchusermessage','uses'=>'ChatController@fetchUserMessage']);
	Route::match(['get','post'],'fetchgroupmessage', ['as'=>'chat.fetchgroupmessage','uses'=>'ChatController@fetchGroupMessage']);
	Route::match(['get','post'],'fetchgroupinfo', ['as'=>'chat.fetchgroupinfo','uses'=>'ChatController@fetchGroupInfo']);
	Route::match(['get','post'],'fetchnewuser', ['as'=>'chat.fetchnewuser','uses'=>'ChatController@fetchNewUser']);
	Route::match(['get','post'],'addparticipant', ['as'=>'chat.addparticipant','uses'=>'ChatController@addParticipant']);
    Route::match(['get','post'],'removefromgroup', ['as'=>'chat.removefromgroup','uses'=>'ChatController@removeFromGroup']);
    Route::match(['get','post'],'exitgroup', ['as'=>'chat.exitgroup','uses'=>'ChatController@exitGroup']);
    Route::match(['get','post'],'chatuserdetail', ['as'=>'chat.chatuserdetail','uses'=>'ChatController@fetchChatUserDetail']);

    Route::match(['get','post'],'changegroupname', ['as'=>'chat.changegroupname','uses'=>'ChatController@changeGroupName']);

    Route::match(['get','post'],'fetchusersessionrequest', ['as'=>'chat.fetchusersessionrequest','uses'=>'ChatController@fetchSessonRequest']);

    Route::match(['get','post'],'sendusermessage', ['as'=>'chat.sendusermessage','uses'=>'ChatController@sendUserMessage']);
    Route::match(['get','post'],'sendgroupmessage', ['as'=>'chat.sendgroupmessage','uses'=>'ChatController@sendGroupMessage']);

	Route::match(['get','post'],'updateusersessionrequest', ['as'=>'chat.updateusersessionrequest','uses'=>'ChatController@updateSessonRequest']);

	Route::match(['get','post'],'usersession_start', ['as'=>'chat.usersession_start','uses'=>'ChatController@StartUserSession']);

	Route::match(['get','post'],'usersession_completed', ['as'=>'chat.usersession_completed','uses'=>'ChatController@CompletedUserSession']);
	Route::match(['get','post'],'rejectusersessionrequest', ['as'=>'chat.rejectusersessionrequest','uses'=>'ChatController@RejectedUserSession']);
    Route::match(['get','post'],'messagedelete', ['as'=>'chat.messagedelete','uses'=>'ChatController@deleteMessage']);
    Route::match(['get','post'],'chatdelete', ['as'=>'chat.chatdelete','uses'=>'ChatController@chatDelete']);
    Route::match(['get','post'],'mediafolderpopup', ['as'=>'chat.mediafolderpopup','uses'=>'ChatController@mediaFolderPopup']);
    Route::match(['get','post'],'movefile', ['as'=>'chat.movefile','uses'=>'ChatController@moveFileToFolder']);
    Route::delete('chat_media/{id}/delete', ['as'=>'chat_media.delete','uses'=>'ChatController@deleteMedia']);


	
	Route::match(['get','post'],'chat/readmessage', ['as'=>'chat.readmessage','uses'=>'ChatController@makeReadMessage']);
	Route::match(['get','post'],'chat/groupreadmessage', ['as'=>'chat.groupreadmessage','uses'=>'ChatController@makeGroupReadMessage']);

	Route::match(['get', 'post'], 'sendsessionrequest', ['as' => 'sessionrequest.sendsessionrequest', 'uses' => 'ChatController@storeSessionRequest']);

    Route::get('startuserchating/{user}', ['as' => 'chat.startuserchating', 'uses' => 'ChatController@startUserChating']);
    Route::get('startgroupchating/{group}', ['as' => 'chat.startgroupchating', 'uses' => 'ChatController@startGroupChating']);

	Route::group(['prefix' => 'notification', 'as' => 'notification.'],function(){
	    Route::get('list', ['as'=>'index','uses'=>'NotificationController@index']);
	});


	Route::get('video', ['as'=>'video.index','uses'=>'VideoController@index']);
	Route::get('video/create', ['as'=>'video.create','uses'=>'VideoController@create']);
	Route::get('video/create_dropzone', ['as'=>'video.create_dropzone','uses'=>'VideoController@createDropzone']);
	Route::post('video/create_ajax', ['as'=>'video.ajax_store','uses'=>'VideoController@storeAjax']);
	Route::post('video', ['as'=>'video.store','uses'=>'VideoController@store']);
	Route::get('video/{video}/edit', ['as'=>'video.edit','uses'=>'VideoController@edit']);
	Route::patch('video/{video}', ['as'=>'video.update','uses'=>'VideoController@update']);
    Route::get('video/{video}/delete', ['as'=>'video.delete','uses'=>'VideoController@delete']);

    Route::get('video/uploadchunkvideo', ['as'=>'video.resumabledchunked','uses'=>'VideoController@resumabledChunkVideo']);

    Route::get('video/chatvideo', ['as'=>'video.chatvideo','uses'=>'VideoController@fetchChatVideo']);
    Route::get('video/folder/{userfolder}', ['as'=>'video.userfolder','uses'=>'VideoController@fetchFolderVideo']);

    Route::get('video/folder/{userfolder}/create', ['as'=>'video.userfolder.create','uses'=>'VideoController@createFolderVideo']);
    Route::post('video/folder/{userfolder}', ['as'=>'video.userfolder.store','uses'=>'VideoController@storeFolderVideo']);

    Route::get('video/folder/{userfolder}/{video}/edit', ['as'=>'video.userfolder.edit','uses'=>'VideoController@editFolderVideo']);
    Route::patch('video/folder/{userfolder}/{video}', ['as'=>'video.userfolder.update','uses'=>'VideoController@updateFolderVideo']);
    Route::delete('video/folder/{userfolder}/delete', ['as'=>'video.userfolder.delete','uses'=>'VideoController@deleteFolder']);

    Route::get('photo', ['as' => 'photo.index', 'uses' => 'PhotoController@index']);
    Route::get('photo/create', ['as' => 'photo.create', 'uses' => 'PhotoController@create']);
    Route::post('photo', ['as' => 'photo.store', 'uses' => 'PhotoController@store']);
    Route::get('photo/{video}/edit', ['as' => 'photo.edit', 'uses' => 'PhotoController@edit']);
    Route::patch('photo/{video}', ['as' => 'photo.update', 'uses' => 'PhotoController@update']);
    Route::get('photo/{video}/delete', ['as'=>'photo.delete','uses'=>'PhotoController@delete']);

    Route::get('photo/chatmedia', ['as'=>'photo.chatmedia','uses'=>'PhotoController@fetchChatMedia']);
    Route::get('photo/folder/{userfolder}', ['as'=>'photo.userfolder','uses'=>'PhotoController@fetchFolderMedia']);

    Route::get('photo/folder/{userfolder}/create', ['as'=>'photo.userfolder.create','uses'=>'PhotoController@createFolderPhoto']);
    Route::post('photo/folder/{userfolder}', ['as'=>'photo.userfolder.store','uses'=>'PhotoController@storeFolderPhoto']);

    Route::get('photo/folder/{userfolder}/{video}/edit', ['as'=>'photo.userfolder.edit','uses'=>'PhotoController@editFolderPhoto']);
    Route::patch('photo/folder/{userfolder}/{video}', ['as'=>'photo.userfolder.update','uses'=>'PhotoController@updateFolderPhoto']);
    Route::delete('photo/folder/{userfolder}/delete', ['as'=>'photo.userfolder.delete','uses'=>'PhotoController@deleteFolder']);


	Route::get('event', ['as'=>'event.index','uses'=>'EventController@index']);
	Route::post('event', ['as'=>'event.store','uses'=>'EventController@store']);
	Route::get('event/{event}/edit', ['as'=>'event.edit','uses'=>'EventController@edit']);
    Route::patch('event/{event}', ['as'=>'event.update','uses'=>'EventController@update']);
    Route::get('event/{event}/delete', ['as'=>'event.delete','uses'=>'EventController@delete']);
	Route::get('event/list', ['as'=>'event.list','uses'=>'EventController@eventList']);

	Route::get('event/{event}/event_detail', ['as'=>'event.event_detail','uses'=>'EventController@fetchEventDetail']);
	Route::get('event/{event}/detail', ['as'=>'event.detail','uses'=>'EventController@fetchEvent']);
    Route::get('event/{event}/{action}/action', ['as'=>'event.action','uses'=>'EventController@eventAction']);

    Route::get('category', ['as'=>'category.index','uses'=>'EventCategoryController@index']);

    Route::post('category', ['as'=>'category.store','uses'=>'EventCategoryController@store']);
    Route::get('category/{category}/edit', ['as'=>'category.edit','uses'=>'EventCategoryController@edit']);
    Route::patch('category/{category}', ['as'=>'category.update','uses'=>'EventCategoryController@update']);
    Route::delete('category/{category}/delete', ['as'=>'category.delete','uses'=>'EventCategoryController@destroy']);

    Route::resource('team', 'TeamController');
    Route::get('team/{team}/chat', ['as' => 'team.chat', 'uses' => 'TeamController@teamChat']);

    Route::get('coachlist', ['as' => 'coachlist', 'uses' => 'HomeController@fetchCoach']);
    Route::get('coachdetail/{user}', ['as' => 'coachdetail', 'uses' => 'HomeController@fetchCoachDetail']);

    Route::get('sessionrequest', ['as'=>'sessionrequest.index','uses'=>'ChatController@showCoachSession']);

    Route::post('payment/post', array('as' => 'payment.post', 'uses' => 'PaymentController@postPaymentStripe'));
    Route::post('payment/paypal', array('as' => 'payment.paypal', 'uses' => 'PaymentController@postPaymentPaypal'));
    Route::post('paymentcomplete/paypal', array('as' => 'paymentcomplete.paypal', 'uses' => 'PaymentController@paymentCompletePaypal'));

    Route::get('join_meeting/{chat_uuid}', ['as'=>'meeting.join','uses'=>'ChatController@joinMeeting']);
    Route::get('leave_meeting/{chat_uuid}', ['as'=>'meeting.leave','uses'=>'ChatController@leaveMeeting']);
});

Route::match(['get','post'],'notification/unreadnotification', ['as'=>'notification.unreadnotification','uses'=>'NotificationController@makeUnreadNotification']);
Route::match(['get', 'post'], 'notification/notificationaction', ['as' => 'notification.notificationaction', 'uses' => 'NotificationController@notificationAction']);
Route::match(['get','post'],'chat/unreadmessagecount', ['as'=>'chat.unreadmessagecount','uses'=>'ChatController@unreadMessageCount']);
Route::get('notification/readallnotification', ['as'=>'notification.readallnotification','uses'=>'NotificationController@realAllNotification']);

Route::get("/check_session", ['as'=>'check_session','uses'=>'HomeController@checkSession']);



