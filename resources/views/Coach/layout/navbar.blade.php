<nav class="navbar navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-btn">
            <button type="button" class="btn-toggle-offcanvas"><i class="lnr lnr-menu fa fa-bars"></i></button>
        </div>

        <div class="navbar-brand">
            <a href="{{route('coach.dashboard')}}"><img src="{{ asset('assets/img/logo.svg') }}" alt="Lucid Logo" class="img-responsive logo"></a>
        </div>
        
        <div class="navbar-right">
            <?php 
            use App\Models\Notification;
            use App\Models\Message;
            use Carbon\Carbon;
            $user_notifications = Notification::whereNull('read_at')->where('to_user_id',auth()->id())->orderBy('created_at','DESC')->get();
            $user_real_notifications = Notification::where('read_all', 0)->where('to_user_id',auth()->id())->get();
            $unread_messages_count =   Message::whereHas('user_conversations',function($query){
                                        $query->whereRaw( "((one_user_id = ".auth()->id()." OR two_user_id = ".auth()->id()."))");
                                    })->whereRaw("( user_id != ".auth()->id()." AND  read_flag != 1 ) && (group_delete_message is null OR NOT FIND_IN_SET(".auth()->id().",group_delete_message))")->count();

            ?>
            <div id="navbar-menu">
                <ul class="nav navbar-nav">
                    <li class="d-none d-sm-inline-block d-md-none d-lg-inline-block">
                        <a href="{{route('coach.video.index')}}" class="icon-menu"><i class="icon-social-youtube"></i></a>
                    </li>
                    <li class="d-none d-sm-inline-block d-md-none d-lg-inline-block">
                        <a href="{{route('coach.event.index')}}" class="icon-menu"><i class="icon-calendar"></i></a>
                    </li>
                    <li class="d-none d-sm-inline-block remove-notification-dot">
                        <a href="{{route('coach.chat.index')}}" class="icon-menu"><i class="icon-bubbles unread_message_flag {{ $unread_messages_count > 0 ?  'unread_message_flag_color' : '' }}"></i>
                            @if($unread_messages_count > 0)
                                <span class="notification-dot"></span>
                            @endif
                        </a>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle icon-menu{{ $user_real_notifications->count() > 0 ? ' read_all_notification' : '' }}" id="openNotificationDropdown" data-toggle="dropdown">
                            <i class="icon-bell"></i>
                            @if($user_real_notifications->count() > 0)
                                <span class="notification-dot"></span>
                            @endif
                        </a>
                        <ul class="dropdown-menu notifications notification_show dropdown-notification">
                            <li class="header"><strong >You have <span class="unread_notification_count">{!! $user_notifications->count() !!}</span> new Notifications</strong></li>
                            @if($user_notifications->count() > 0)
                            @foreach($user_notifications as $user_notification_key => $user_notification_val)
                            <li class="notification_list">
                                <a href="javascript:void(0);" class="{{$user_notification_val->type == 2 || $user_notification_val->type == 3 ? '' : 'read_user_notification' }}" data-notification-uuid="{{ $user_notification_val->notification_uuid }}">
                                    <div class="media">
                                        <div class="media-left">
                                            <i class="icon-like text-success"></i>
                                        </div>
                                        <div class="media-body">
                                            <p class="text">{{ $user_notification_val->data }}</p>
                                        </div>
                                    </div>
                                </a>
                                @if($user_notification_val->type == 2 || $user_notification_val->type == 3)
                                    <div class="notification-action-button">
                                        <button class="btn btn-success accept-invitation" data-notification-uuid="{{ $user_notification_val->notification_uuid }}">Accept</button>
                                        <button class="btn btn-danger reject-invitation" data-notification-uuid="{{ $user_notification_val->notification_uuid }}">Reject</button>
                                    </div>
                                @endif
                            </li>
                            @endforeach
                            @endif
                            <li class="footer notification_footer"><a href="{{ route('coach.notification.index') }}" class="more">See all notifications</a></li>
                        </ul>
                    </li>

                    <?php /*
                    <li class="d-none d-sm-inline-block">
                        <a href="#" class="icon-menu"><i class="icon-bell"></i><span class="notification-dot"></span></a>
                    </li>
                    */ ?>
                    <li>
                        <a href="{{route('frontend.auth.logout')}}" class="icon-menu"><i class="icon-login"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
