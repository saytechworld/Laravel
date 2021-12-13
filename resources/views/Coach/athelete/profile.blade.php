@extends('Coach.layout.master')
@section('title', 'User Profile')
@section('parentPageTitle', 'Profile')

@section('content')
@php

$user_games = $user->athelete_games()->groupBy('title')->pluck('title')->toArray();

$user_languages = $user->user_spoken_languages()->pluck('title')->toArray();

$user_videos = $user->videos()->where('file_type', 1)->where('privacy', 1)->get();
$privacy = $user->privacy;
@endphp
<div class="row clearfix">
    <div class="col-lg-4 col-md-12">
        <div class="card profile-header">
            <div class="header">
                <h2>About Me</h2>
                @if($privacy == 2)
                    <ul class="header-dropdown">
                        <li>
                            <span class="badge badge-primary">Private</span>
                        </li>
                    </ul>
                @endif
            </div>
            <div class="body text-center">
                <div class="profile-image">
                    <img src="{{ ($user->user_image) ? $user->user_image : asset('images/noimage.jpg') }}" alt="user" class="rounded-circle image-click"/>
                </div>
                <div class="user-name">
                    <h4 class="m-b-0"><strong> {{ $user->name ?? '' }} </strong></h4>
                     </div>
                <div class="m-t-15">
                    <a href="{{ route('coach.chat.startuserchating',$user->user_uuid) }}" class="btn btn-outline-secondary m-b-10">Chat with Athlete</a>
                </div>
                @if($privacy == 1)
                    <div class="profile-info">
                    <p>{{ $user->user_details->about ?? '' }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Info</h2>
            </div>
            @if($privacy == 1)
            <div class="body"> <small class="text-muted">Address: </small>
                <p> {{$user->user_details->country->title  ?? ''}} </p>
                <?php /* <div>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1923731.7533500232!2d-120.39098936853455!3d37.63767091877441!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x80859a6d00690021%3A0x4a501367f076adff!2sSan+Francisco%2C+CA%2C+USA!5e0!3m2!1sen!2sin!4v1522391841133" width="100%" height="150" frameborder="0" style="border:0" allowfullscreen></iframe>
                </div> 
                <hr>
                <small class="text-muted">Experience: </small>
                <p class="m-b-0">{{ $user->user_details->experience  ?? '0'}} Years</p>
                */ ?>
                <hr>
                <small class="text-muted">Gender: </small>
                @if($user->user_details)
                    <p class="m-b-0">{{ $user->user_details->gender == 'F' ? 'Female' : ($user->user_details->gender == 'M' ? 'Male' : '') }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4 col-md-12">
        <div class="card right-box">
            <div class="header">
                <h2>Sports</h2>
            </div>
            @if($privacy == 1)
                <div class="body">
                    <ul class="list-unstyled categories-clouds m-b-0">
                        @foreach($user_games as $user_game)
                            <li><a href="javascript:void(0);">{{ $user_game }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="card right-box">
            <div class="header">
                <h2>Language</h2>
            </div>
            @if($privacy == 1)
                <div class="body">
                    <ul class="list-unstyled categories-clouds m-b-0">
                        @foreach($user_languages as $user_language)
                            <li><a href="javascript:void(0);">{{ $user_language }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="col-12">
        <div class="card file_manager messenger_video_sec">
            <div class="header">
                <h2>Videos</h2>
            </div>
            @if($privacy == 1)
                <div class="body">
                <div class="row clearfix">
                    @foreach($user_videos as $video)
                        @php $video_detail = explode('.',$video->file_name); @endphp
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="card c-b-box">
                                <div class="file">
                                    <div class="hover">
                                        <div class="chat-option">
                                            <div class="dropdown">
                                                <button class="dropdown-toggle dropdown-open" data-toggle="dropdown"></button>
                                                <div class="dropdown-menu">
                                                    <a href="{{ route('frontend.downloadfile',['type'=>'U', 'id'=> $video->id] ) }}" class="dropdown-item"> Download </a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                    <a href="#videoPopup" class="popupVideoLink video-play-button" data-video-url="{!! $video->aws_video_folder_path !!}" target="_blank">

                                    <div class="image video-bx">
                                            <video class="video_thumbnail">
                                                <source src="{!! $video->aws_video_folder_path !!}#t=0.1" type="video/{{$video_detail[1]}}">
                                                Your browser does not support the video tag.
                                            </video>
                                                <figure class="video-play-icon"><img src="{!! asset('images/video-icon.svg') !!}"></figure>

                                        </div>
                                    </a>
                                        <div class="file-name"> <strong class="m-b-5 text-muted">{!! $video->title !!}</strong>
                                            <p>{!! $video->description !!}</p>
                                            <span class="date text-muted">Tag: {!! $video->video_tags()->count() > 0 ? implode(', ', $video->video_tags()->pluck('title')->toArray()) : ""  !!}</span> </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
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
    <link rel="stylesheet" href="{{asset('assets/css/blog.css') }}"/>
    <script type="text/javascript">
        $(document).on('click','.image-click',function(e){
            $("#image-popup").html('')
            $('#myModal').modal('show');
            let url = $(this).attr('src');
            var image = document.createElement('img');
            image.setAttribute('src', url);
            document.getElementById('image-popup').appendChild(image);
        });
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

        $(document).on('click','.video-play-button',function(e) {
            let url = $(this).data('video-url');
            var iframe = document.createElement('iframe');
            iframe.setAttribute('src', url);
            document.getElementById('videoPopup').appendChild(iframe);
        });
    </script>
@stop
