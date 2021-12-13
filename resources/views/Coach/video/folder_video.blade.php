@extends('Coach.layout.master')
@section('title', 'Videos')
@section('parentPageTitle', 'Videos')
@section('content')
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card messenger_video_sec">
                <div class="header dbl-btns">
                    <h2>{{ ucfirst($user_folder->title) }} Folder Video archive</h2>
                    <ul class="header-dropdown">
                        <li>
                            <a href="{!! route('coach.video.userfolder.create',$user_folder->slug) !!}" class="btn btn-outline-primary"><img src="{!! asset('images/upload.svg') !!}" ><br><span>Upload Video</span> </a>
                        </li>
                    </ul>
                </div>

                <div class="body">

                    <div class="f-frm-sec search_video_frm">
                      <ul class="cmn-ul-list contact_folder_search_sec">
                        {!! Form::open(array('url' => route('coach.video.userfolder',$user_folder->slug),'method' => 'GET', 'files' => "true")) !!}
                        <li class="select_privacy_sec">
                            <div class="input-group">
                                @php
                                    $privacy_arr = ['private' => 'Private', 'public'=>'Public']
                                @endphp
                                {{ Form::select('privacy', $privacy_arr, request()->query('privacy'), ['class' => "form-control",  'placeholder' => "Select Privacy"]) }}
                            </div>
                           
                        </li>
                        <li class="search_name_sec">
                         <div class="input-group">
                                {{ Form::text('name', request()->query('name'), ['class' => "form-control",  'placeholder' => "Search Name"]) }}
                            </div>
                            
                        </li>
                        <li class="search_btn_sec">
                            <button type="submit" class="btn btn-primary btn-block search-button"><i class="fa fa-search"></i></button>
                        </li>
                        {!! Form::close() !!}
                          
                          </ul>
                    
                    </div>
                   <div class="row clearfix file_manager">
                       @if($videos->count() > 0)
                            @foreach($videos as $video_key => $video_val)
                             @php $video_detail = explode('.',$video_val->file_name); @endphp
                                <div class="col-lg-3 col-md-3 col-sm-12">
                                    <div class="card">
                                        <div class="file">
                                                <div class="hover">
                                                    <div class="chat-option">
                                                        <div class="dropdown">
                                                            <button class="dropdown-toggle dropdown-open" data-toggle="dropdown"></button>
                                                            <div class="dropdown-menu">
                                                                 @if(!empty($video_val->user_folders->slug))
                                                                    @if($video_val->user_folders->slug == $user_folder->slug)
                                                                        <a href="{{ route('coach.video.userfolder.edit',['userfolder' => $video_val->user_folders->slug, 'id' => $video_val->id]) }}" class="dropdown-item"> Edit </a>
                                                                    @else
                                                                        <a href="{{ route('coach.video.userfolder.edit',['userfolder' => $video_val->user_folders->slug, 'id' => $video_val->id]) }}" target="_blank" class="btn btn-icon btn-secondary"> Edit </a>
                                                                    @endif
                                                                @else
                                                                    <a href="{{  route('coach.video.edit',['id' => $video_val->id]) }}" target="_blank" class="btn btn-icon btn-secondary"> Edit </a>
                                                                @endif
                                                                <a href="{{ route('frontend.downloadfile',['type'=>'U', 'id'=> $video_val->id] ) }}" class=" dropdown-item"> Download </a>
                                                                <a class="dropdown-item redirect_coach_video_delete" data-video-delete-id="{!! $video_val->id !!}" href="#" >Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                                <a href="#videoPopup" class="popupVideoLink video-play-button" data-video-url="{!! $video_val->aws_video_folder_path !!}" target="_blank">
                                                    <div class="image video-bx">
                                                        <video class="video_thumbnail">
                                                            <source src="{!! $video_val->aws_video_folder_path !!}#t=0.1" type="video/{{$video_detail[1]}}">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                        <figure class="video-play-icon"><img src="{!! asset('images/video-icon.svg') !!}"></figure>
                                                    </div>
                                                </a>
                                                <div class="file-name">
                                                    <p>Title: <strong>{!! $video_val->title !!}</strong></p>
                                                    <p>Privacy: <strong>{{ $video_val->privacy == 1 ? 'Public' : 'Private' }}</strong></p>
                                                    <p>Description : {!! $video_val->description !!}</p>
                                                    <p>Tag: {!! $video_val->video_tags()->count() > 0 ? implode(', ', $video_val->video_tags()->pluck('title')->toArray()) : ""  !!}</p>
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

    <div id="videoPopup" class="mfp-hide embed-responsive embed-responsive-21by9 magni-popup" >

    </div>
@stop
@section('blade-page-script')
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
        $(function() {
            $(document).on('click','.redirect_coach_video_delete',function(e){
                e.preventDefault();
                var video_id = $(this).data('video-delete-id');
                if (confirm('Are You Sure?')){
                    var url = '{{ route("coach.video.delete", ":id") }}';
                    url = url.replace(':id', video_id);
                    document.location.href = url;
                }
            });

            $(document).on('click','.video-play-button',function(e){
                let url = $(this).data('video-url');
                var iframe = document.createElement('iframe');
                iframe.setAttribute('src', url);
                document.getElementById('videoPopup').appendChild(iframe);
            })
        });

        


    </script>

@stop