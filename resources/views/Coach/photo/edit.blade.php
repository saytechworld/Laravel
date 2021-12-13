@extends('Coach.layout.master')
@section('title', 'Edit Photo')
@section('parentPageTitle', 'Edit Photo')
@section('content')
<?php
use App\Models\Tag;
use App\Models\UserFolder;

$tags = Tag::where('status',1)->pluck('title','id');
$video_tags = $video->video_tags()->pluck('tag_id');
$video_file_detail = explode('.',$video->file_name);

$photo_folder_arr = array();
if($photo_folders->count() > 0)
{
    $photo_folder_arr = $photo_folders->pluck('title','id')->toArray();
}

?>
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card upload_video_frm">
            <div class="header">
                <h2>Edit Photo</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                    {!! Form::model($video, ['method' => 'PATCH','route' => ['coach.photo.update', $video->id], 'id' => 'video-upload', 'files' => "true" ]) !!}
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
                            @if(count($photo_folder_arr) > 0)
                                @foreach($photo_folder_arr as $photo_folder_arr_key => $photo_folder_arr_val)
                                <option value="{{ $photo_folder_arr_key }}" @if($video->user_folder_id == $photo_folder_arr_key) selected @endif >{{ $photo_folder_arr_val }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="mb-3">Choose Photo Privacy Option</label>
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
                        <label for="name" class="control-label">Photo</label>
                        <input type="file" class="dropify dropify_file_upload" data-max-file-size="200M" name="file_name" data-show-remove="false" data-rule-required = "false" data-allowed-file-extensions="jpg jpeg png" accept=".png, .jpg, .jpeg">
                    </div>
                    <div class="form-group">
                        <img src="{!! $video->aws_video_folder_path !!}" type="video/{{$video_file_detail[1]}}" width="320" height="200">
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
    $(function() {
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
    $('.select_video_tag').select2();
    $('.select_folder').select2({
        placeholder : 'Select folder',
        allowClear: true,
    });
});
</script>

@stop

