@extends('Coach.layout.master')
@section('title', 'Video Upload')
@section('parentPageTitle', 'Video Upload')
@section('content')
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card upload_video_frm">
            <div class="header">
                <h2>Upload video</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                    {!! Form::open(array('url' => route('coach.video.ajax_store'), 'method' => 'POST', 'id' => 'video-upload', 'files' => "true")) !!}
                        <div class="form-group">
                            <label for="name" class="control-label">Photo</label>
                            <input type="file" class="dropify dropify_file_upload" data-max-file-size="200M" name="file_name" data-show-remove="false" data-rule-required = "true" accept=".mp4">
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    {!! Form::close() !!}
                </div>
            </div>


        </div>
    </div>

</div>
@stop
<script src="https://cdnjs.cloudflare.com/ajax/libs/plupload/3.1.2/plupload.full.min.js"></script>
@section('blade-page-script')
<script>

        $(function () {
            $('.dropify').dropify();

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
                        console.log(xhr.responseJSON);
                    } else  {
                        toastr.error(xhr.responseJSON.message);
                    }
                }
            });
        })

</script>
@stop

