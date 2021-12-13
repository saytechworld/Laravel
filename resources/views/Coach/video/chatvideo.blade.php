@extends('Coach.layout.master')
@section('title', 'Messenger Videos')
@section('parentPageTitle', 'Messenger Videos')
@section('content')
<div class="row clearfix">
        <div class="col-lg-12">
            <div class="card messenger_video_sec">
                <div class="header">
                    <h2>Messenger Video</h2>
                </div>
                <div class="body">
                    <div class="f-frm-sec search_video_frm">
                        <ul class="cmn-ul-list contact_folder_search_sec">
                            {!! Form::open(array('url' => route('coach.video.chatvideo'),'method' => 'GET', 'files' => "true")) !!}
                            <li class="search_name_sec">
                             <div class="input-group">
                                    {{ Form::text('name', request()->query('name'), ['class' => "form-control",  'placeholder' => "Search By File, User Name...."]) }}
                                </div>
                            </li>
                            <li class="search_btn_sec">
                                <button type="submit" class="btn btn-primary btn-block search-button"><i class="fa fa-search"></i></button>
                            </li>
                            {!! Form::close() !!}
                        </ul>
                    </div>


                    <div class="row clearfix file_manager">
                        @if($userChats->count() > 0)
                            @foreach($userChats as $chat_video_key => $chat_video_val)
                            @php $chat_video_detail = explode('.',$chat_video_val->message); @endphp
                                <div class="col-lg-3 col-md-3 col-sm-12 media_row_{{ $chat_video_val->id }}">
                                    <div class="card">
                                        <div class="file">
                                                <div class="hover">
                                                    <div class="chat-option">
                                                        <div class="dropdown">
                                                            <button class="dropdown-toggle dropdown-open" data-toggle="dropdown"></button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" href="#"  onclick="openMediaFolderPopup({{ $chat_video_val->id }})">Save</a>
                                                                <a class="dropdown-item" href="{{ route('frontend.downloadfile',['type'=>'M', 'id'=> $chat_video_val->id] ) }}"> Download </a>
                                                                <a class="dropdown-item" href="#" onclick="deleteChatData({{ $chat_video_val->id }})" >Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <a href="#videoPopup" class="popupVideoLink" target="_blank">
                                                    <div class="image video-bx video-play-button" data-video-url="{!! env('AWS_URL').'messages/'.$chat_video_val->message !!}">
                                                        <video class="video_thumbnail">
                                                            <source src="{!! env('AWS_URL').'messages/'.$chat_video_val->message !!}" type="video/{{$chat_video_detail[1]}}">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                        <figure class="video-play-icon"><img src="{!! asset('images/video-icon.svg') !!}"></figure>
                                                    </div>
                                                </a>
                                                <div class="file-name">
                                                    <span class="date text-muted">By: {!! $chat_video_val->senders->name !!}</span>
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
<div class="modal" id="moveToFolder" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="folder_ajax_modal">

        </div>
    </div>
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

            $(document).on('click','.video-play-button',function(e){
                let url = $(this).data('video-url');
                var iframe = document.createElement('iframe');
                iframe.setAttribute('src', url);
                document.getElementById('videoPopup').appendChild(iframe);
            });
        });
        function openMediaFolderPopup(id) {
            $('.folder_ajax_modal').html('');
            let url = "{{ route('coach.chat.mediafolderpopup') }}";

            $.ajax({
                type: "POST",
                url: url,
                data:{
                    _token:"{{ csrf_token() }}",
                    message_id:id
                },
                async: false,
                beforeSend: function () {
                    //
                },
                success: function (res) {
                    console.log(res)
                    if (res.status) {
                        $('.folder_ajax_modal').html(res.data.result);
                        $('#moveToFolder').modal();
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        }

        function deleteChatData(id) {
            if (confirm('Are You Sure?')){
                var deleteUrl = '{{ route("coach.chat_media.delete", ":id") }}';
                deleteUrl = deleteUrl.replace(':id', id);
                $.ajax({
                    type: "Delete",
                    url: deleteUrl,
                    data:{
                        _token:"{{ csrf_token() }}"
                    },
                    async: true,
                    beforeSend: function () {
                        $('.processing-loader').show();
                    },
                    success: function (data) {
                        $('.processing-loader').hide();
                        if (data.status) {
                            toastr.success(data.message);
                            $('.media_row_'+id).remove();
                        } else {
                            toastr.error(data.message);
                        }
                    },
                    error: function (xhr) { // if error occured
                        $('.processing-loader').hide();
                        toastr.error("Error occured.please try again");
                    },
                });
            }
        }

    </script>

@stop