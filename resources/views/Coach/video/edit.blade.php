@extends('Coach.layout.master')
@section('title', 'Edit Video')
@section('parentPageTitle', 'Edit Video')
@section('content')
<?php
use App\Models\Tag;
use App\Models\UserFolder;
$tags = Tag::where('status',1)->pluck('title','id');
$video_tags = $video->video_tags()->pluck('tag_id');
$video_file_detail = explode('.',$video->file_name);

$video_folder_arr = array();
if($video_folders->count() > 0)
{
    $video_folder_arr = $video_folders->pluck('title','id')->toArray();
}

$parent_directory = UserFolder::whereNull('user_folder_id')->where('user_id',auth()->id())->value('slug');
if(!empty($video->user_folders->slug)){
    $uploaded_file_path =   $parent_directory.'/videos/'.$video->user_folders->slug.'/'.$video->file_name;
}else{
    $uploaded_file_path =   $parent_directory.'/videos/'.$video->file_name;
}

?>
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card upload_video_frm">
            <div class="header">
                <h2>Edit video</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                    {!! Form::model($video, ['method' => 'PATCH','route' => ['coach.video.update', $video->id], 'id' => 'video-upload', 'files' => "true" ]) !!}
                    <div class="form-group">
                        <label for="name" class="control-label">Title</label>                                                
                        {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50"  ]) !!}
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Description</label>                                                
                        {!! Form::textarea('description',null,['class' => "form-control", 'placeholder' => "Description", 'data-rule-required' => "false", 'data-rule-maxlength' => "1000"  ]) !!}
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Tags</label>                                                
                        {!! Form::select('video_tag[]',$tags, $video_tags,['class' => "form-control select_video_tag", 'data-placeholder' => "Select tag", 'data-rule-required' => "false", 'multiple' => "true" ]) !!}
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Folders</label>  
                        <select class="form-control select_folder" data-placeholder="Select folder" data-rule-required="false" name="user_folder_id">
                            <option value="">Select folder</option>
                            @if(count($video_folder_arr) > 0)
                                @foreach($video_folder_arr as $video_folder_arr_key => $video_folder_arr_val)
                                <option value="{{ $video_folder_arr_key }}" @if($video->user_folder_id == $video_folder_arr_key) selected @endif >{{ $video_folder_arr_val }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="mb-3">Choose Video Privacy Option</label>
                        <div class="input-group">
                            <label class="fancy-radio">
                                {{ Form::radio('privacy', '1' , true) }}
                                <span><i></i> Public</span> </label>
                        </div>
                        <div class="input-group">
                            <label class="fancy-radio">
                                {{ Form::radio('privacy', '0' , false) }}
                                <span><i></i> Private</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Video</label>
                        <div id="container">
                            <input type="text" id="pickfiles" class="dropify dropify_file_upload" name="file_name" data-rule-required = "false" tabindex="-1" autocomplete="off">
                        </div>
                        <br />
                        <div id="filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
                        <br />
                        <button id="start-upload" class="btn btn-success" style="display: none;">Start upload</button>
                        <pre id="console"></pre>
                    </div>
                    <div class="form-group">
                        <video width="320" height="200" controls>
                        <source src="{!! $video->aws_video_folder_path !!}" type="video/{{$video_file_detail[1]}}">
                        Your browser does not support the video tag.
                        </video>
                    </div>
                    <div class="upload-progress-bar">
                        <div class="progress">
                            <div class="bar"></div >
                            <div class="percent">0%</div >
                        </div>
                    </div>
                
                    <button type="submit" class="btn btn-primary">Update</button>
                    {!! Form::close() !!}
                </div>
            </div>

        </div>
    </div>

</div>
@stop
@section('blade-page-script')
<script type="text/javascript">

    $(document).ready(function () {

        let upload = false;

        var bar = $('.bar');
        var percent = $('.percent');

        var uploader = new plupload.Uploader({
            runtimes : 'gears,html5,flash,silverlight,browserplus',
            browse_button : 'pickfiles', // you can pass in id...
            container: document.getElementById('container'), // ... or DOM Element itself

            url : "{{ route('ajax.video.store') }}",
            chunk_size: '30mb',
            max_retries: 3,
            max_file_count: 1,
            //multi_selection: false,

            flash_swf_url : '/plupload/js/plupload.flash.swf',
            silverlight_xap_url : '/plupload/js/plupload.silverlight.xap',

            filters : {
                max_file_size : '400mb',
                mime_types: [
                    {title : "Video files", extensions : "mp4,avi,webm,hdv,flv,wmv,mov"},
                ],
            },

            init: {
                PostInit: function() {
                    document.getElementById('filelist').innerHTML = '';
                    document.getElementById('start-upload').onclick = function() {
                        uploader.start();
                        uploader.disableBrowse(true);
                        upload = true;
                        $('#start-upload').hide();
                        return false;
                    };
                },

                FilesAdded: function(up, files) {
                    plupload.each(files, function(file) {
                        document.getElementById('filelist').innerHTML = '';
                        if (up.files.length > 1) {
                            up.removeFile(up.files[0]);
                        }
                        document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
                        $('#start-upload').show();
                    });
                },

                UploadProgress: function(up, file) {

                    if (file.percent == 100) {
                        file.percent = 99;
                    }

                    document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '';
                    var percentVal = file.percent + '%';
                    bar.width(percentVal);
                    percent.html(percentVal);
                },

                FileUploaded: function(up, file, info) {
                    let res = JSON.parse(info.response);
                    if (res.status) {
                        $('.dropify_file_upload').val(res.data.file_name);
                        percent.html('Complete');
                        upload = false;
                    }
                },

                FilesRemoved: function(up, files) {

                    // Called when files are removed from queue
                },

                Error: function(up, err) {
                    document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
                }
            }
        });

        uploader.init();

        $('.dropify').dropify();
        var drEvent = $('#dropify-event').dropify();
        $('#video-upload').validate({
            ignore : [],
            errorPlacement: function(error, element) {
                if($(element).hasClass('dropify_file_upload')){
                    error.insertAfter(element.closest('div.dropify-wrapper'));
                }else if($(element).hasClass('select_video_tag')){
                    error.insertAfter(element.siblings('.select2-container'));
                }else{
                    error.insertAfter(element);
                }
            },
        });

        $('#video-upload').ajaxForm({
            beforeSubmit: function () {
                if (upload) {
                    toastr.error('Video upload process is running');
                    return false;
                }
                return $('#video-upload').valid(); // TRUE when form is valid, FALSE will cancel submit
            },
            beforeSend: function() {
                $('.processing-loader').show();
            },
            success: function() {
                $('.processing-loader').hide();
            },
            complete: function(xhr) {
                if(xhr.responseJSON.status) {
                    window.location.href = xhr.responseJSON.route;
                } else  {
                    toastr.error(xhr.responseJSON.message);
                }
            }
        });

        $('.select_video_tag').select2();
        $('.select_folder').select2({
            placeholder : 'Select folder',
            allowClear: true,
        });
    });
</script>

@stop

