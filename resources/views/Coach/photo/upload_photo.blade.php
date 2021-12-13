@extends('Coach.layout.master')
@section('title', 'Photo Upload')
@section('parentPageTitle', 'Photo')
@section('content')
<?php
use App\Models\Tag;
$tags = Tag::where('status',1)->pluck('title','id');
?>
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card upload_video_frm">
            <div class="header">
                <h2>Upload Photo</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                    {!! Form::open(array('url' => route('coach.photo.userfolder.store',$photo_folders->slug),'method' => 'POST', 'id' => 'photo-upload', 'files' => "true")) !!}
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
                        <input type="file" class="dropify dropify_file_upload" data-max-file-size="200M" name="file_name" data-show-remove="false" data-rule-required = "true" data-allowed-file-extensions="jpg jpeg png" accept=".png, .jpg, .jpeg">
                    </div>                
                    <button type="submit" class="btn btn-primary">Upload</button>
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
    $('#photo-upload').validate({
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
});
</script>

@stop

