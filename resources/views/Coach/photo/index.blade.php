@extends('Coach.layout.master')
@section('title', 'My Photos')
@section('parentPageTitle', 'My Photos')
@section('content')
    <div class="f-frm-sec search_video_frm">
        <ul class="cmn-ul-list">
            {!! Form::open(array('url' => route('coach.photo.index'),'method' => 'GET', 'files' => "true")) !!}
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
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header dbl-btns">
                    <h2>Photo Folders</h2>
                     <ul class="header-dropdown">
                         <li>
                             <a href="{!! route('coach.photo.create') !!}" class="btn btn-outline-primary"><img src="{!! asset('images/upload.svg') !!}" ><br><span>Upload Photo</span></a>
                         </li>
                         <li>
                            <a href="#" type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#createfolder"><img src="{!! asset('images/create-folder.svg') !!}" ><br>
                            <span>Create Folder</span></a>
                        </li>
                    </ul>
                </div>
                <div class="body">
                    <div class="row clearfix file_manager">
                        @if($userfolders->count() > 0)
                            @foreach($userfolders as $user_folder_key => $user_folder_val)
                                <div class="col-lg-3 col-md-3 col-sm-12 folder_row_{{ $user_folder_val->id }}">
                                    <div class="card folder-bx">
                                        <div class="file file-folder">
                                            <a href="{{ route('coach.photo.userfolder', $user_folder_val->slug) }}">
                                                <div class="file-icon">
                                                    <i class="fa fa-folder" aria-hidden="true"></i>
                                                </div>
                                                <div class="file-name">
                                                    <span class="date text-muted">{!! $user_folder_val->title !!}</span>
                                                </div>
                                                <div class="hover">
                                                    <button type="button" data-edit-id="{!! $user_folder_val->id !!}" data-title="{!! $user_folder_val->title !!}" class="btn btn-icon btn-secondary folder_edit_modal redirect_folder_delete"> <i class="icon-pencil"></i> </button>
                                                    <button type="button" data-delete-id="{!! $user_folder_val->id !!}" class="btn btn-icon btn-secondary redirect_coach_folder_delete redirect_folder_delete"> <i class="icon-trash"></i> </button>
                                                </div>
                                            </a>
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
            <div class="card messenger_video_sec">
                <div class="header">
                    <h2>My Photos</h2>
                    <ul class="header-dropdown">

                    </ul>
                </div>
                <div class="body">
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
                                                                <a class="dropdown-item"  href="{{ route('frontend.downloadfile',['type'=>'U', 'id'=> $video_val->id] ) }}">Download</a>
                                                                <a class="dropdown-item redirect_athelete_video_edit" data-video-edit-id="{!! $video_val->id !!}" > edit </a>
                                                                <a class="dropdown-item redirect_coach_photo_delete" data-photo-delete-id="{!! $video_val->id !!}" href="#"  >Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                               <!--<div class="hover">
                                                    <button type="button" data-video-edit-id="{!! $video_val->id !!}" class="btn btn-icon btn-secondary redirect_athelete_video_edit"> <i class="icon-note"></i> </button>
                                                    <button type="button" data-photo-delete-id="{!! $video_val->id !!}" class="btn btn-icon btn-secondary redirect_coach_photo_delete"> <i class="icon-trash"></i> </button>
                                                    <a href="{!! $video_val->video_folder_path !!}" class="btn btn-icon btn-primary" download> <i class="fa fa-download"></i> </a>
                                                </div>-->
                                                <div class="image video-bx">
                                                    <img class="image-click" src="{!! $video_val->aws_video_folder_path !!}">
                                                </div>
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


    <div class="modal fade image-view-popup " id="myModal" role="dialog">
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

    <div class="modal fade add_event_category_modal" id="createfolder" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open(array('url' => route('frontend.folder.create'),'method' => 'POST', 'id' => 'create_photo_folder', 'files' => "true")) !!}


                <div class="modal-body">
                    
                    <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>

                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" placeholder="Folder Name" name="title" id="folder_name" data-rule-required="true" data-rule-maxlength="150">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>


    <div class="modal fade add_event_category_modal" id="updateFolder" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open(array('url' => route('frontend.folder.update'),'method' => 'POST', 'id' => 'update_photo_folder', 'files' => "true")) !!}

                <div class="modal-body">

                    <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                    <input name="folder_id" type="hidden" class="form-control update_folder_id">
                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control update_folder_name" placeholder="Folder Name" name="title" data-rule-required="true" data-rule-maxlength="150">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@stop
@section('blade-page-script')
    <script type="text/javascript">
        $(function() {
            $(document).on('click','.redirect_athelete_video_edit',function(e){
                e.preventDefault();
                var video_id = $(this).data('video-edit-id');
                var video_edit_url = "{!! route('coach.photo.edit',['id' => ':id']) !!}";
                video_edit_url = video_edit_url.replace(':id',video_id);
                window.location.href  = video_edit_url;
            });

            $(document).on('click','.redirect_coach_photo_delete',function(e){
                e.preventDefault();
                var video_id = $(this).data('photo-delete-id');
                if (confirm('Are You Sure?')){
                    var url = '{{ route("coach.photo.delete", ":id") }}';
                    url = url.replace(':id', video_id);
                    document.location.href = url;
                }
            });

            $(document).on('click','.folder_edit_modal',function(e){
                e.preventDefault();
                var folder_id = $(this).data('edit-id');
                $('#updateFolder').modal();
                $('.update_folder_name').val($(this).data('title'));
                $('.update_folder_id').val(folder_id);
            });

            $(document).on('click','.redirect_coach_folder_delete',function(e){
                e.preventDefault();
                var folder_id = $(this).data('delete-id');
                if (confirm('Are You Sure?')){
                    var deleteUrl = '{{ route("coach.photo.userfolder.delete", ":id") }}';
                    deleteUrl = deleteUrl.replace(':id', folder_id);
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
                                $('.folder_row_'+folder_id).remove();
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
            });

            $(document).on('click','.image-click',function(e){
                $("#image-popup").html('')
                $('#myModal').modal('show');
                let url = $(this).attr('src');
                var image = document.createElement('img');
                image.setAttribute('src', url);
                document.getElementById('image-popup').appendChild(image);
            })
        });


        $(function() {

            $.validator.addMethod('regex', function(value, element, param) {
                var re = new RegExp(param);
                return this.optional(element) || !re.test(value);
            },"Please enter valid format.");



            $('#create_photo_folder').validate({
                ignore : [],
                rules: {
                    title: {
                        remote: {
                            url: "{!! route('ajax.folder.uniquefolder',2) !!}",
                            type: "post",
                            data: {
                                title:  function(){
                                   return $("#folder_name").val();
                                }
                            }
                        },
                        regex:'[!@#$%^’&*(),.?\\":{}|<>]',
                    },
                },
                messages : {
                    title : {
                       remote : "The folder has already been taken.",
                       regex : "Allow alphabets only",
                    },
                },
                submitHandler: function (form) {
                    var folder_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}"+'&folder_type=2';
                    $.ajax({
                        type: "POST",
                        url : "{{ route('frontend.folder.create') }}",
                        data : folder_form_data,
                        async: true,
                        beforeSend: function() {
                            $('.processing-loader').show();
                        },
                        success: function(data) {
                            $('.processing-loader').hide();
                            if(data.status){
                                toastr.success(data.message);
                                window.location.reload(true);
                            }else{
                                toastr.error(data.message);
                            }
                        },
                        error: function(xhr) { // if error occured
                            $('.processing-loader').hide();
                            toastr.error("Error occured.please try again");
                        },
                    });
                }
            });

            $('#update_photo_folder').validate({
                ignore : [],
                rules: {
                    title: {
                        remote: {
                            url: "{!! route('ajax.folder.uniquefolder',2) !!}",
                            type: "post",
                            data: {
                                title:  function(){
                                   return $(".update_folder_name").val();
                                },
                                id:  function(){
                                    return $(".update_folder_id").val();
                                }
                            }
                        },
                        regex:'[!@#$%^’&*(),.?\\":{}|<>]',
                    },
                },
                messages : {
                    title : {
                       remote : "The folder has already been taken.",
                       regex : "Allow alphabets only",
                    },
                },
                submitHandler: function (form) {
                    var folder_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}"+'&folder_type=2';
                    $.ajax({
                        type: "POST",
                        url : "{{ route('frontend.folder.update') }}",
                        data : folder_form_data,
                        async: true,
                        beforeSend: function() {
                            $('.processing-loader').show();
                        },
                        success: function(data) {
                            $('.processing-loader').hide();
                            if(data.status){
                                toastr.success(data.message);
                                window.location.reload(true);
                            }else{
                                toastr.error(data.message);
                            }
                        },
                        error: function(xhr) { // if error occured
                            $('.processing-loader').hide();
                            toastr.error("Error occured.please try again");
                        },
                    });
                }
            });
        });

    </script>

@stop