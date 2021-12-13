@extends('Coach.layout.master')
@section('title', 'Video Upload')
@section('parentPageTitle', 'Video Upload')
@section('content')
<?php
use App\Models\Tag;
$video_folder_arr = array();
if($video_folders->count() > 0)
{
    $video_folder_arr = $video_folders->pluck('title','id')->toArray();
}
$tags = Tag::where('status',1)->pluck('title','id');
//echo "<pre>"; print_r($tags); exit;
?>
<?php /*
<link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.css" rel="stylesheet" />

<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card upload_video_frm">
            <div class="header">
                <h2>Upload video</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                    {!! Form::open(array('url' => route('coach.video.store'),'method' => 'POST', 'id' => 'video-upload', 'files' => "true")) !!}
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
                        {!! Form::select('video_tag[]',$tags, null,['class' => "form-control select_video_tag", 'data-placeholder' => "Select tag", 'data-rule-required' => "false", 'multiple' => "true" ]) !!}
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Folders</label>  
                        <select class="form-control select_folder" data-placeholder="Select folder" data-rule-required="false" name="user_folder_id">
                            <option value="">Select folder</option>
                            @if(count($video_folder_arr) > 0)
                                @foreach($video_folder_arr as $video_folder_arr_key => $video_folder_arr_val)
                                <option value="{{ $video_folder_arr_key }}">{{ $video_folder_arr_val }}</option>
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
                        <input type="file" class="dropify dropify_file_upload" data-max-file-size="1024M" name="file_name" data-show-remove="false" data-rule-required = "true" data-allowed-file-extensions="mp4 webm hdv flv avi wmv mov" accept=".mp4,.webm,.hdv,.flv,.avi,.wmv,.mov">
                    </div>
                    <div class="upload-progress-bar">
                        <div class="progress">
                            <div class="bar"></div >
                            <div class="percent" id="uploadPercentage">0%</div >
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Upload</button>
                    {!! Form::close() !!}
                </div>
            </div>


        </div>
    </div>

</div>
*/ ?>
<div class="text-center" >
    <div id="resumable-error" style="display: none">
        Resumable not supported
    </div>
    <div id="resumable-drop" style="display: none">
        <p><button id="resumable-browse" data-url="{{ url('upload') }}" >Upload</button> or drop here
        </p>
        <p></p>
    </div>
    <ul id="file-upload-list" class="list-unstyled"  style="display: none">

    </ul>
    <br/>
</div>
@stop
@section('blade-page-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>

<script>
        var $ = window.$; // use the global jQuery instance

        var $fileUpload = $('#resumable-browse');
        var $fileUploadDrop = $('#resumable-drop');
        var $uploadList = $("#file-upload-list");

        if ($fileUpload.length > 0 && $fileUploadDrop.length > 0) {
            var resumable = new Resumable({
                // Use chunk size that is smaller than your maximum limit due a resumable issue
                // https://github.com/23/resumable.js/issues/51
                chunkSize: 10000000, // 1MB
                simultaneousUploads: 3,
                testChunks: false,
                throttleProgressCallbacks: 1,
                // Get the url from data-url tag
                target: "{{ route('coach.video.store') }}",
                // Append token to the request - required for web routes
                query:{_token : "{{ csrf_token() }}"}
            });

        // Resumable.js isn't supported, fall back on a different method
            if (!resumable.support) {
                $('#resumable-error').show();
            } else {
                // Show a place for dropping/selecting files
                $fileUploadDrop.show();
                resumable.assignDrop($fileUpload[0]);
                resumable.assignBrowse($fileUploadDrop[0]);

                // Handle file add event
                resumable.on('fileAdded', function (file) {
                    // Show progress pabr
                    $uploadList.show();
                    // Show pause, hide resume
                    $('.resumable-progress .progress-resume-link').hide();
                    $('.resumable-progress .progress-pause-link').show();
                    // Add the file to the list
                    $uploadList.append('<li class="resumable-file-' + file.uniqueIdentifier + '">Uploading <span class="resumable-file-name"></span> <span class="resumable-file-progress"></span>');
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-name').html(file.fileName);
                    // Actually start the upload
                    resumable.upload();
                });
                resumable.on('fileSuccess', function (file, message) {
                    // Reflect that the file upload has completed
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html('(completed)');
                });
                resumable.on('fileError', function (file, message) {
                    // Reflect that the file upload has resulted in error
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html('(file could not be uploaded: ' + message + ')');
                });
                resumable.on('fileProgress', function (file) {
                    // Handle progress for both the file and the overall upload
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html(Math.floor(file.progress() * 100) + '%');
                    $('.progress-bar').css({width: Math.floor(resumable.progress() * 100) + '%'});
                });
            }

        }


</script>

<script type="text/javascript">
    $(function() {
        <?php /*
        Dropzone.prototype.defaultOptions.dictDefaultMessage = "DRAG & DROP FILES HERE TO UPLOAD";

        var myDropzone = new Dropzone(".dropify_file_upload", {
            //autoProcessQueue: false,
            chunking: true,
            method: "POST",
            maxFilesize: 3072, // 3GB
            chunkSize: 10000000, // 10MB
            parallelChunkUploads: true,
            url: "{{ route('coach.video.store') }}",
            headers: {
              'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function (file, response) {
                console.log(file);
                console.log(response);
            },
        });
        */ ?>






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


        var bar = $('.bar');
        var percent = $('.percent');
        var status = $('#status');
        var chunksize = 1000000 // 1MB

        $(document).on('submit', '#video-upload',function(event){
            event.preventDefault();
            if($(this).valid())
            {   
                var $fileUpload  = $('.dropify_file_upload');
                console.log($fileUpload);
                return false;
                let form_data = new FormData(this);
                
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


var chunksize = 1000000 // 1MB
var chunks = math.ceil(chunksize / fileToUpload.fileSize);



for(c = 0; c < chunks; c++) {
    uploadChunk(fileToUpload, c);
}

    <script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>
    <script type="text/javascript">
        var $ = window.$; // use the global jQuery instance

        var $fileUpload = $('#resumable-browse');
        var $fileUploadDrop = $('#resumable-drop');
        var $uploadList = $("#file-upload-list");

        if ($fileUpload.length > 0 && $fileUploadDrop.length > 0) {
            var resumable = new Resumable({
                // Use chunk size that is smaller than your maximum limit due a resumable issue
                // https://github.com/23/resumable.js/issues/51
                chunkSize: 1 * 1024 * 1024, // 1MB
                simultaneousUploads: 3,
                testChunks: false,
                throttleProgressCallbacks: 1,
                // Get the url from data-url tag

                target: "{{ route('coach.video.store') }}",
                // Append token to the request - required for web routes
                query:{_token : "{!! csrf_token() !!}" }
            });

        // Resumable.js isn't supported, fall back on a different method
            if (!resumable.support) {
                $('#resumable-error').show();
            } else {
                // Show a place for dropping/selecting files
                $fileUploadDrop.show();
                resumable.assignDrop($fileUpload[0]);
                resumable.assignBrowse($fileUploadDrop[0]);

                // Handle file add event
                resumable.on('fileAdded', function (file) {
                    // Show progress pabr
                    $uploadList.show();
                    // Show pause, hide resume
                    $('.resumable-progress .progress-resume-link').hide();
                    $('.resumable-progress .progress-pause-link').show();
                    // Add the file to the list
                    $uploadList.append('<li class="resumable-file-' + file.uniqueIdentifier + '">Uploading <span class="resumable-file-name"></span> <span class="resumable-file-progress"></span>');
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-name').html(file.fileName);
                    // Actually start the upload
                    resumable.upload();
                });
                resumable.on('fileSuccess', function (file, message) {
                    // Reflect that the file upload has completed
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html('(completed)');
                });
                resumable.on('fileError', function (file, message) {
                    // Reflect that the file upload has resulted in error
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html('(file could not be uploaded: ' + message + ')');
                });
                resumable.on('fileProgress', function (file) {
                    // Handle progress for both the file and the overall upload
                    $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html(Math.floor(file.progress() * 100) + '%');
                    $('.progress-bar').css({width: Math.floor(resumable.progress() * 100) + '%'});
                });
            }

        }


</script>

    <script type="text/javascript">
    $(function() {

        var $ = window.$; // use the global jQuery instance

        var resumable = new Resumable({
            // Use chunk size that is smaller than your maximum limit due a resumable issue
            // https://github.com/23/resumable.js/issues/51
            chunkSize: 1 * 1024 * 1024, // 1MB
            simultaneousUploads: 3,
            testChunks: false,
            throttleProgressCallbacks: 1,
            // Get the url from data-url tag
            target: "{{ route('coach.video.store') }}",
            // Append token to the request - required for web routes
            query:{_token : "{!! csrf_token() !!}" }
        });

        if (!resumable.support) {
            $('#resumable-error').show();
        } else {
            // Show a place for dropping/selecting files
            $fileUploadDrop.show();
            resumable.assignDrop($fileUpload[0]);
            resumable.assignBrowse($fileUploadDrop[0]);

            // Handle file add event
            resumable.on('fileAdded', function (file) {
                // Show progress pabr
                $uploadList.show();
                // Show pause, hide resume
                $('.resumable-progress .progress-resume-link').hide();
                $('.resumable-progress .progress-pause-link').show();
                // Add the file to the list
                $uploadList.append('<li class="resumable-file-' + file.uniqueIdentifier + '">Uploading <span class="resumable-file-name"></span> <span class="resumable-file-progress"></span>');
                $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-name').html(file.fileName);
                // Actually start the upload
                resumable.upload();
            });
            resumable.on('fileSuccess', function (file, message) {
                // Reflect that the file upload has completed
                // $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html('(completed)');
            });
            resumable.on('fileError', function (file, message) {
                // Reflect that the file upload has resulted in error
                //$('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html('(file could not be uploaded: ' + message + ')');
            });
            resumable.on('fileProgress', function (file) {
                // Handle progress for both the file and the overall upload
                $('.resumable-file-' + file.uniqueIdentifier + ' .resumable-file-progress').html(Math.floor(file.progress() * 100) + '%');
                $('.progress-bar').css({width: Math.floor(resumable.progress() * 100) + '%'});
            });
        }

 $('#video-upload').ajaxForm({

            beforeSubmit: function (event) {
                console.log(event);
                return false;
                //return $('#video-upload').valid(); // TRUE when form is valid, FALSE will cancel submit
            },
            beforeSend: function() {

                status.empty();
                var percentVal = '0%';
                var posterValue = $('input[name=file]').fieldValue();
                bar.width(percentVal)
                percent.html(percentVal);
            },
            uploadProgress: function(event, position, total, percentComplete) {
                var percentVal = percentComplete + '%';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            success: function() {
                var percentVal = 'Wait, Saving';
                bar.width(percentVal)
                percent.html(percentVal);
            },
            complete: function(xhr) {
                if(xhr.responseJSON.status) {
                    window.location.href = xhr.responseJSON.route;
                } else  {
                    toastr.error(xhr.responseJSON.message);
                }
            }
        }); 




 function uploadChunk(fileToUpload, chunk = 0) {
         var xhr = new XMLHttpRequest();
         var uploadStatus = xhr.upload;

         uploadStatus.addEventListener("progress", function (ev) {
                if (ev.lengthComputable) {
                    $("#uploadPercentage").html((ev.loaded / ev.total) * 100 + "%");
                }
            }, false);

         uploadStatus.addEventListener("error", function (ev) {$("#error").html(ev)}, false);
         uploadStatus.addEventListener("load", function (ev) {$("#error").html("APPOSTO!")}, false);

         var start = chunksize*chunk;
         var end = start+(chunksize-1)
         if (end >= fileToUpload.size) {
            end = fileToUpload.size-1;
        }
        /*
         var data = new FormData();
        data.append('user', 'person');
        data.append('pwd', 'password');
        data.append('organization', 'place');
        data.append('requiredkey', 'key');*/


         xhr.open(
                "POST",
                "{{ route('coach.video.store') }}",
                true
         );
         xhr.setRequestHeader("Cache-Control", "no-cache");
         xhr.setRequestHeader("Content-Type", "multipart/form-data");
         xhr.setRequestHeader("X-CSRF-TOKEN", "{!! csrf_token() !!}");
         xhr.setRequestHeader("X-File-Name", fileToUpload.name);
         xhr.setRequestHeader("X-File-Size", fileToUpload.size);
         xhr.setRequestHeader("X-File-Type", fileToUpload.type);
         xhr.setRequestHeader("Content-Range", start+"-"+end+"/"+fileToUpload.size);
         xhr.send(fileToUpload);
    }