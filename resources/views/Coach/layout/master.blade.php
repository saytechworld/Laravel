<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"> <!-- Favicon-->
        <title>@yield('title') - {{ config('app.name') }}</title>
        <meta name="description" content="@yield('meta_description', config('app.name'))">
        <meta name="author" content="@yield('meta_author', config('app.name'))">
        @yield('meta')
        <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/font-awesome.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/jvectormap/jquery-jvectormap-2.0.3.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/morrisjs/morris.min.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/nouislider/nouislider.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/toastr/toastr.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/magnific-popup/magnific-popup.css') }}">

        <link rel="stylesheet" href="{{ asset('assets/vendor/fullcalendar/fullcalendar.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/fullcalendar/scheduler.min.css') }}"/>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css" rel="Stylesheet">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css"/>


        <link rel="stylesheet" href="{{ asset('assets/vendor/dropify/css/dropify.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/css/chatapp.css') }}"/>
        <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/color_skins.css') }}">
        <link rel="stylesheet" href="{{ asset('css/croppie.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/frontend/owl.carousel.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/frontend/owl.theme.default.css') }}">


        @if (trim($__env->yieldContent('page-styles')))
            @yield('page-styles')
        @endif
    </head>
    <?php 
        $setting = !empty($_GET['theme']) ? $_GET['theme'] : '';
        $theme = "theme-cyan";
        $menu = "";
        if ($setting == 'p') {
            $theme = "theme-purple";
        } else if ($setting == 'b') {
            $theme = "theme-blue";
        } else if ($setting == 'g') {
            $theme = "theme-green";
        } else if ($setting == 'o') {
            $theme = "theme-orange";
        } else if ($setting == 'bl') {
            $theme = "theme-blush";
        } else {
             $theme = "theme-cyan";
        }
    ?>

    <body class="theme-cyan <?php // $theme ?>">

    <!-- Product Tour -->

    <?php /* @if(auth()->user()->product_tour == 0)
        @include('Coach.layout.welcome')
    @endif */ ?>

        <!-- Page Loader -->
        <div class="page-loader-wrapper">
            <div class="loader">
                <div class="m-t-30"><img src="{!! asset('assets/img/logo-icon.svg') !!}" width="48" height="48" alt="Lucid"></div>
                <p>Please wait...</p>        
            </div>
        </div>
        <div class="circle_loader_sec processing-loader" style="display: none;">
            <div class="loader">
                <div class="m-t-30"><img src="{!! asset('assets/img/cricle-loader.gif') !!}" alt="Lucid"></div>
                <p>Please wait...</p>
            </div>
        </div>
        <div id="wrapper">
            @include('Coach.layout.navbar')
            <?php
                $CheckUserTrailPeriod = CheckUserTrailPeriod();
                $CheckUserSubscriptionPeriod = CheckUserSubscriptionPeriod();
              ?>
              @if(!empty($CheckUserTrailPeriod['status']))
              <div class="wel-message user_subscription_trail"><p>You have {{ $CheckUserTrailPeriod['trail_days'] }} days trail period remaining. To keep accessing all the features of Asportcoach please <a href="{{ route('coach.plan.index') }}"> buy subscription plan</a></p></div>
              @endif

              @if(!empty($CheckUserSubscriptionPeriod['status']))
              <div class="wel-message user_subscription_trail"><p>Your subscription is ending in {{ $CheckUserSubscriptionPeriod['trail_days'] }} days, please renew your subscription plan<a href="{{ route('coach.plan.index') }}"> buy subscription plan</a></p></div>
              @endif
            @include('Coach.layout.sidebar')
            <div id="main-content">
                <div class="container-fluid">
                    <div class="block-header">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">                        
                                <h2><a href="javascript:void(0);" class="btn btn-xs btn-link btn-toggle-fullwidth"><i class="fa fa-arrow-left"></i></a> @yield('title')</h2>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12"> 
                                @include('Common.includes.error')
                            </div>
                        </div>
                    </div>

                    @yield('content')
                </div>
            </div>
        </div>
    <div class="modal fade image-view-popup" id="myModal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <figure class="upload-img-view" id="image-popup">
                        <img src="">
                    </figure>
                </div>
            </div>
        </div>
    </div>
        <!-- Scripts -->
        <script type="text/javascript">
            window.Laravel = <?php echo json_encode([
                'csrfToken' => csrf_token(),
                'BASE_URL'  => url('/'),
                'AWS_BASE_URL'  => config('staging_live_config.AWS_URL'),
                'API_URL'   => chop(url('/'), '/public').'/api',
                'MESSAGE_IMAGE_SIZE' =>  config('staging_live_config.MESSAGE_IMAGE_SIZE'),
                'MESSAGE_VIDEO_SIZE' => config('staging_live_config.MESSAGE_VIDEO_SIZE'),
                'STRIPE_KEY' => config('staging_live_config.STRIPE.STRIPE_KEY'),
                'SERVICE_TAX' => config('staging_live_config.SERVICE_TAX'),
                'TRANSACTION_FEES' => config('staging_live_config.TRANSACTION_FEES'),
                'paypal_sandbox' => config('staging_live_config.paypal_sandbox'),
                'paypal_production' => config('staging_live_config.paypal_production'),
                'paypal_env' => config('staging_live_config.paypal_env'),
            ]); ?>

            setInterval(function() {
                let url = "{{ route('coach.check_session') }}";
                    $.ajax({
                        type: "GET",
                        url : url,
                        async: false,
                        success: function(data) {
                            console.log(data)
                        },
                        error: function(xhr) { // if error occured
                            swal("Your Session has been expired, Please Login!.")
                                .then((value) => {
                                   window.location.reload();
                                });
                        },
                        complete: function() {

                        },
                    });
                }, 3000);
        </script>

        @yield('blade-page-vue-script')
        <script src="{{ asset('assets/bundles/libscripts.bundle.js') }}"></script>    
        <script src="{{ asset('assets/bundles/vendorscripts.bundle.js') }}"></script>
        <script src="{{ asset('assets/bundles/morrisscripts.bundle.js') }}"></script><!-- Morris Plugin Js -->
        <script src="{{ asset('assets/bundles/jvectormap.bundle.js') }}"></script> <!-- JVectorMap Plugin Js -->
        <script src="{{ asset('assets/bundles/knob.bundle.js') }}"></script>

        <script src="{{ asset('assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/nouislider/nouislider.js') }}"></script>
        <script src="{{ asset('assets/vendor/dropify/js/dropify.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/LightboxGallery/mauGallery.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/LightboxGallery/scripts.js') }}"></script>


        
        <script src="{{ asset('assets/bundles/fullcalendarscripts.bundle.js') }}"></script>
        <script src="{{ asset('assets/vendor/fullcalendar/fullcalendar.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/fullcalendar/scheduler.min.js') }}"></script>
        <?php /*
        <script src="{{ asset('assets/js/pages/calendar.js') }}"></script>
        */ ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js" ></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.full.min.js" ></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
        <script src="{{ asset('assets/vendor/toastr/toastr.js') }}"></script>
        <script src="{{ asset('assets/vendor/magnific-popup/jquery.magnific-popup.js') }}"></script>
        <script src="{{ asset('assets/js/product-tour.js') }}"></script>

        {!! HTML::script('js/jquery.validate.min.js') !!}
        <script src="{{ asset('js/croppie.min.js') }}"></script>
        <script src="{{ asset('js/frontend/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('assets/bundles/mainscripts.bundle.js') }}"></script>
        <script src="{{ asset('assets/vendor/sweetalert/sweetalert.min.js') }}"></script>
        <script src="https://js.stripe.com/v3/"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/plupload/3.1.2/plupload.full.min.js"></script>
        @yield('blade-page-script')
        


        <script type="text/javascript">
            $(document).ready(function(){
              if ($(".user_subscription_trail").length > 0) {
                $('#left-sidebar').addClass('s-w-m-t');
                $('#main-content').addClass('w-m-t');
              }
            });
            $(document).on('click','.image-click',function(e){
                $("#image-popup").html('')
                $('#myModal').modal('show');
                let url = $(this).attr('src');
                var image = document.createElement('img');
                image.setAttribute('src', url);
                document.getElementById('image-popup').appendChild(image);
            });
            $(document).ready(function(){
                $(document).off('click','.read_user_notification').on('click','.read_user_notification', function(e){ 
                    if($(this).data('notification-uuid')){
                        var notification_id = $(this).data('notification-uuid');
                        var NOTIFICATION_URL = "{{ route('coach.notification.unreadnotification') }}";
                        $.ajax({
                            type: "POST",
                            url : NOTIFICATION_URL,
                            data:{
                                notification_id: notification_id,
                                _token : "{{ csrf_token() }}"
                            },
                            async: false, 
                            beforeSend: function() {
                              // setting a timeout

                            },
                            success: function(result) {
                                if(result.status){
                                    if(result.data.url_flag){
                                        window.location.href= result.data.redirect_url;
                                    }
                                    toastr.success(result.message);
                                }else{
                                    toastr.error(result.message);
                                }
                            },
                            error: function(xhr) { // if error occured
                              console.log("Error occured.please try again");
                            },
                            complete: function() {

                            },
                        });
                    }else{
                        toastr.error('Notification not valid.');
                    }

                });
                $(document).off('click','.read_all_notification').on('click','.read_all_notification', function(e){
                    var url = "{{ route('coach.notification.readallnotification') }}";
                    $.ajax({
                        type: "GET",
                        url : url,
                        async: false,
                        success: function(data) {
                            if(data.status){
                                $(".read_all_notification").find("span").remove();
                                $("a").removeClass("read_all_notification");
                            }
                        },
                        error: function(xhr) { // if error occured
                            console.log("Error occured.please try again");
                        },
                        complete: function() {

                        },
                    });
                });
                $(document).off('click','.accept-invitation').on('click','.accept-invitation', function(e){
                    if($(this).data('notification-uuid')){
                        var notification_id = $(this).data('notification-uuid');
                        var NOTIFICATION_URL = "{{ route('coach.notification.notificationaction') }}";
                        $.ajax({
                            type: "POST",
                            url : NOTIFICATION_URL,
                            data:{
                                notification_id: notification_id,
                                action: 1,
                                _token : "{{ csrf_token() }}"
                            },
                            async: false,
                            beforeSend: function() {
                                // setting a timeout

                            },
                            success: function(result) {
                                if(result.status){
                                    window.location.reload();
                                    toastr.success(result.message);
                                }else{
                                    toastr.error(result.message);
                                }
                            },
                            error: function(xhr) { // if error occured
                                console.log("Error occured.please try again");
                            },
                            complete: function() {

                            },
                        });
                    }else{
                        toastr.error('Notification not valid.');
                    }
                });
                $(document).off('click','.reject-invitation').on('click','.reject-invitation', function(e){
                    if($(this).data('notification-uuid')){
                        var notification_id = $(this).data('notification-uuid');
                        var NOTIFICATION_URL = "{{ route('coach.notification.notificationaction') }}";
                        $.ajax({
                            type: "POST",
                            url : NOTIFICATION_URL,
                            data:{
                                notification_id: notification_id,
                                action: 2,
                                _token : "{{ csrf_token() }}"
                            },
                            async: false,
                            beforeSend: function() {
                                // setting a timeout

                            },
                            success: function(result) {
                                if(result.status){
                                    window.location.reload();
                                    toastr.success(result.message);
                                }else{
                                    toastr.error(result.message);
                                }
                            },
                            error: function(xhr) { // if error occured
                                console.log("Error occured.please try again");
                            },
                            complete: function() {

                            },
                        });
                    }else{
                        toastr.error('Notification not valid.');
                    }
                });
            });
        </script>
        <script src="https://www.gstatic.com/firebasejs/4.9.1/firebase.js"></script>

        <script type="text/javascript">
            var js_firebaseConfig = {
                apiKey: "AIzaSyCCtXBMvS-Z6zo5SoojZMnfJiC8ld_MFcQ",
                authDomain: "coachbookdemo.firebaseapp.com",
                databaseURL: "https://coachbookdemo.firebaseio.com",
                projectId: "coachbookdemo",
                storageBucket: "coachbookdemo.appspot.com",
                messagingSenderId: "974966385354",
                appId: "1:974966385354:web:96dbd2e040d7b5df926233",
                measurementId: "G-V983NL4NFE"
            };
            // Initialize Firebase
            firebase.initializeApp(js_firebaseConfig);
            let auth = "{{ auth()->id() }}";
            firebase.database().ref('user_notifications/').on('value', function(snapshot) {
                var value = snapshot.val();
                snapshot.forEach((doc) => {
                    let item = doc.val()
                    item.key = doc.key;
                    if ( auth == item.to_user_id) {
                        if(item.type == 2 || item.type == 3){
                            $('ul.notification_show li.header').after('<li class="notification_list">\n' +
                                '                                <a href="javascript:void(0);" class="read_user_notification" data-notification-uuid="'+ item.notification_uuid +'">\n' +
                                '                                    <div class="media">\n' +
                                '                                        <div class="media-left">\n' +
                                '                                            <i class="icon-like text-success"></i>\n' +
                                '                                        </div>\n' +
                                '                                        <div class="media-body">\n' +
                                '                                            <p class="text">'+ item.data +'</p>\n' +
                                '                                        </div>\n' +
                                '                                    </div>\n' +
                                '                                </a>\n' +
                                '<div class="notification-action-button"><button class="btn btn-success accept-invitation" data-notification-uuid="'+item.notification_uuid+'">Accept</button> <button class="btn btn-danger reject-invitation" data-notification-uuid="'+item.notification_uuid+'">Reject</button></div>' +
                                '                            </li>');
                        } else {
                            $('ul.notification_show li.header').after('<li class="notification_list">\n' +
                                '                                <a href="javascript:void(0);" class="read_user_notification" data-notification-uuid="'+ item.notification_uuid +'">\n' +
                                '                                    <div class="media">\n' +
                                '                                        <div class="media-left">\n' +
                                '                                            <i class="icon-like text-success"></i>\n' +
                                '                                        </div>\n' +
                                '                                        <div class="media-body">\n' +
                                '                                            <p class="text">'+ item.data +'</p>\n' +
                                '                                        </div>\n' +
                                '                                    </div>\n' +
                                '                                </a>\n' +
                                '                            </li>');
                        }

                        $('ul.notification_show span.unread_notification_count').text($('ul.notification_show li.notification_list').length);

                        if (!$('#openNotificationDropdown span').length) {
                            $('#openNotificationDropdown').addClass('read_all_notification');
                            $('#openNotificationDropdown').append('<span class="notification-dot"></spna>')
                        }
                    }
                    firebase.database().ref('user_notifications/' + doc.key).remove();
                });
            });

          
            firebase.database().ref('chats/').on('value', (user_chat_ref_snapshot) => {
                user_chat_ref_snapshot.forEach((user_chat_ref_doc) => {
                    let user_chat_ref_item = user_chat_ref_doc.val();
                    user_chat_ref_item.key = user_chat_ref_doc.key;
                    if(user_chat_ref_item.user_conversations.chat_type == 1) {
                        if(auth == user_chat_ref_item.user_conversations.one_user_id || auth == user_chat_ref_item.user_conversations.two_user_id )
                        {
                            if (user_chat_ref_item.user_id  != auth) {
                                if (user_chat_ref_item.user_conversations.message_type == 1 || user_chat_ref_item.user_conversations.message_type == 2 || user_chat_ref_item.user_conversations.message_type == 3|| user_chat_ref_item.user_conversations.message_type == 11) {
                                    let message = ''
                                    if (user_chat_ref_item.user_conversations.message_type == 1 || user_chat_ref_item.user_conversations.message_type == 11) {
                                        message = 'You have a new message';
                                    } else if (user_chat_ref_item.user_conversations.message_type == 2) {
                                        message = "you received a image";
                                    } else {
                                        message = "you received a video";
                                    }
                                    toastr.success(message)
                                }
                            }
                            var UNREADMESSAGECOUNT_URL = "{{ route('coach.chat.unreadmessagecount') }}";
                            $.ajax({
                                type: "POST",
                                url : UNREADMESSAGECOUNT_URL,
                                data:{
                                    _token : "{{ csrf_token() }}"
                                },
                                async: false,
                                beforeSend: function() {
                                    // setting a timeout

                                },
                                success: function(result) {
                                    if(result.status){
                                        if(result.data.result > 0){
                                            $('.unread_message_flag').addClass('unread_message_flag_color');
                                            $('.unread_message_flag').after('<span class="notification-dot"></span>');
                                        }else{
                                            $('.unread_message_flag').removeClass('unread_message_flag_color');
                                            $(".remove-notification-dot span").remove();
                                        }
                                    }
                                },
                                error: function(xhr) { // if error occured
                                    console.log("Error occured.please try again");
                                },
                                complete: function() {

                                },
                            });
                        }
                    } else {
                        const found = user_chat_ref_item.user_conversations.group_users.some(el => el.user_id == auth && el.status == 1);
                        if (found) {
                            {
                                if (user_chat_ref_item.user_id != auth) {
                                    if (user_chat_ref_item.user_conversations.message_type == 1 || user_chat_ref_item.user_conversations.message_type == 2 || user_chat_ref_item.user_conversations.message_type == 3) {
                                        let message = '';
                                        if (user_chat_ref_item.user_conversations.message_type == 1) {
                                            message = 'You have a new message';
                                        } else if (user_chat_ref_item.user_conversations.message_type == 2) {
                                            message = "you received a image";
                                        } else {
                                            message = "you received a video";
                                        }
                                        toastr.success(message)
                                    }
                                }
                                var UNREADMESSAGECOUNT_URL = "{{ route('coach.chat.unreadmessagecount') }}";
                                $.ajax({
                                    type: "POST",
                                    url: UNREADMESSAGECOUNT_URL,
                                    data: {
                                        _token: "{{ csrf_token() }}"
                                    },
                                    async: false,
                                    beforeSend: function () {
                                        // setting a timeout

                                    },
                                    success: function (result) {
                                        if (result.status) {
                                            if (result.data.result > 0) {
                                                $('.unread_message_flag').addClass('unread_message_flag_color');
                                                $('.unread_message_flag').after('<span class="notification-dot"></span>');
                                            } else {
                                                $('.unread_message_flag').removeClass('unread_message_flag_color');
                                                $(".remove-notification-dot span").remove();
                                            }
                                        }
                                    },
                                    error: function (xhr) { // if error occured
                                        console.log("Error occured.please try again");
                                    },
                                    complete: function () {

                                    },
                                });
                            }
                        }
                    }
                });
            });




        </script>

    @if(request()->segment(2) == 'chat')
        <script>
            $( document ).ready(function() {
                $('body').click(function (event) {
                    let id = event.target.id;
                    if (id != 'openProfileDropdown' && id != 'openNotificationDropdown') {
                        $('.dropdown-profile').removeClass('show')
                        $('.dropdown-notification').removeClass('show')
                    }
                });

                $('#openProfileDropdown').click(function () {
                    $('.dropdown-profile').toggleClass('show')
                });

                $('#openNotificationDropdown').click(function () {
                    $('.dropdown-notification').toggleClass('show')
                });
            });
        </script>
    @endif
    </body>
</html>
