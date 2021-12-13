@extends('Coach.layout.master')
@section('title', 'Dashboard')
@section('parentPageTitle', 'Dashboard')

@section('content')
    <?php
    use Carbon\Carbon;
    ?>
    <div class="row clearfix">
        <div class="col-lg-4 col-md-4 col-sm-12">
            <div class="card overflowhidden number-chart">
                <div class="body">
                    <div class="number">
                        <h6>EARNINGS</h6>
                        <span>{{ $earning ?? '' }} €</span>
                    </div>
                </div>
            </div>
            <div class="card overflowhidden number-chart">
                <div class="body">
                    <div class="number">
                        <h6>Total Sessions</h6>
                        <span>{{ $total_session ?? ''}}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-12">
            <div class="card">
                <div class="body">
                    <div class="header p-0">
                        <h2>Recent Chats</h2>
                        <hr>
                    </div>
                    <ul class="right_chat list-unstyled mb-0">
                        @if($chats->count() > 0)
                            @foreach($chats as $chat_key => $chat)
                                @php
                                    $chat->one_user_id == auth()->id() ? $userData = $chat->two_users : $userData = $chat->one_users
                                @endphp
                                <li>
                                        @if( $chat->chat_type == 1)
                                            <a href="{{ route('coach.chat.startuserchating',$userData->user_uuid) }}">
                                        @else
                                            <a href="{{ route('coach.chat.startgroupchating',$chat->chat_uuid) }}">
                                        @endif
                                        <div class="media">
                                            <figure class="pro-pic">
                                                <img class="media-object " src="{{ $chat->chat_type == 1 ? ($userData->user_image)  ? $userData->user_image : asset('images/noimage.jpg') : asset('images/groupuser.jpg') }}" alt="">
                                            </figure>
                                            <div class="media-body">
                                                <span class="name">{{ $chat->chat_type == 1 ? $userData->name : $chat->group_name}}</span>
                                                <span class="badge badge-outline status"></span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li>No Record Found</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-4 col-sm-12">
            <div class="card profile-header upcoming-event-bx">
                <div class="body">
                    <div class="header p-0">
                        <h2>Upcoming Events</h2>
                        <hr>
                    </div>

                    @if($events->count() > 0)
                        @foreach($events as $event_key => $event_val)
                            @php
                                $event_attendants = $event_val->event_attendants->where('attendant_type', 'A')->pluck('email')->toArray();
                                $event_attendants = implode(", ",$event_attendants);
                            @endphp
                            <div class="timeline-item green" date-is="">
                            <!-- <span class="e-dot-c" style="background-color:{{$event_val->color_code}};"></span>-->
                                <h5>{{ $event_val->title ?? '' }}</h5>
                                <div class="msg">
                                    <p>{{ $event_val->description ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                    <div class="text-right">
                        <a class="text-link" href="{{ route('coach.event.list') }}">View All</a>
                    </div>
                </div>
            </div>
            <div class="card profile-header upcoming-event-bx">
                <div class="body">
                    <div class="header p-0">
                        <h2>Running Sessions</h2>
                        <hr>
                    </div>
                    @if($sessions->count() > 0)
                        @foreach($sessions as $session_key => $session_val)
                            <div class="timeline-item green">
                                @if($session_val->coach_id == auth()->id())
                                    <h5> Session With <strong>{{ $session_val->athelete_user->name ?? '' }}</strong></h5>
                                @else
                                    <h5> Session With <strong> {{ $session_val->coach_user->name ?? '' }}</strong></h5>
                                @endif

                            </div>
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                    <div class="text-right">
                        <a class="text-link" href="{{ route('coach.sessionrequest.index') }}">View All</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
     <?php /*
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>Latest Uploaded Video</h2>
                    <ul class="header-dropdown">
                        <li>
                            <a href="{!! route('coach.video.index') !!}" class="text-link">View All</a>
                        </li>
                    </ul>
                </div>
                <div class="body">
                    <div class="row clearfix file_manager">
                        @if($videos->count() > 0)
                            @foreach($videos as $key => $video)
                                <div class="col-lg-3 col-md-3 col-sm-12">
                                    <div class="card">
                                        <div class="file">
                                                <div class="hover">
                                                    <a href="{!! $video->video_folder_path !!}" class="btn btn-icon btn-primary" download> <i class="fa fa-download"></i> </a>
                                                </div>
                                                <a href="#videoPopup" class="popupVideoLink video-play-button" data-video-url="{!! $video->video_folder_path !!}" target="_blank">
                                                    <div class="image video-bx">
                                                        <video class="video_thumbnail" >
                                                            <source src="{!! $video->video_folder_path !!}#t=0.1">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                        <figure class="video-play-icon"><img src="{!! asset('images/video-icon.svg') !!}"></figure>
                                                    </div>
                                                </a>
                                                <div class="file-name">
                                                    <p class="m-b-5 text-muted">{{ $video->title ?? '' }}</p>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="no-record-message">No record found</p>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>Latest Uploaded Images</h2>
                    <ul class="header-dropdown">
                        <li>
                            <a href="{!! route('coach.photo.index') !!}" class="text-link">View All</a>
                        </li>
                    </ul>
                </div>
                <div class="body">
                    <div class="row clearfix file_manager">
                        @if($photos->count() > 0)
                            @foreach($photos as $key => $photo)
                                <div class="col-lg-3 col-md-3 col-sm-12">
                                    <div class="card">
                                        <div class="file">
                                                <div class="hover">
                                                    <a href="{!! $photo->video_folder_path !!}" class="btn btn-icon btn-primary" download> <i class="fa fa-download"></i> </a>
                                                </div>
                                                <div class="image">
                                                        <img src="{!! $photo->video_folder_path !!}" alt="img" class="img-fluid image-click">
                                                </div>
                                                <div class="file-name">
                                                    <p class="m-b-5 text-muted">{{ $photo->title ?? '' }}</p>
                                                </div>
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                        @else
                            <p class="no-record-message">No record found</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    */?>
        <div class="modal fade" id="edit-video" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="title" id="defaultModalLabel">Edit Video Contact</h6>
                    </div>
                    <div class="modal-body">
                        <div class="row clearfix">
                            <div class="col-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="First Name">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Last Name">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <input type="number" class="form-control" placeholder="Phone Number">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Enter Address">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <input type="file" class="form-control-file" id="exampleInputFile" aria-describedby="fileHelp">
                                    <small id="fileHelp" class="form-text text-muted">This is some placeholder block-level help text for the above input. It's a bit lighter and easily wraps to a new line.</small> </div>
                                <hr>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Facebook">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Twitter">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="Linkedin">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <input type="text" class="form-control" placeholder="instagram">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary">Add</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">CLOSE</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="videoPopup" class="mfp-hide embed-responsive embed-responsive-21by9 magni-popup" >

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
        @stop
        @section('blade-page-script')
            <script src="{{ asset('assets/vendor/LightboxGallery/scripts.js') }}"></script>
            <script type="text/javascript">

                $(document).ready(function() {
                    $('.popupVideoLink').magnificPopup({
                        type:'inline',
                        midClick: true, // Allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source in href.
                        callbacks: {
                            close: function(){
                                $('#videoPopup').html('');
                            }
                        }
                    });
                });

                $(document).on('click','.video-play-button',function(e){
                    let url = $(this).data('video-url');
                    var iframe = document.createElement('iframe');
                    iframe.setAttribute('src', url);
                    document.getElementById('videoPopup').appendChild(iframe);
                })

                $(document).on('click','.image-click',function(e){
                    $("#image-popup").html('')
                    $('#myModal').modal('show');
                    let url = $(this).attr('src');
                    var image = document.createElement('img');
                    image.setAttribute('src', url);
                    document.getElementById('image-popup').appendChild(image);
                })
            </script>
@stop