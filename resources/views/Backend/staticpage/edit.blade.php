@extends('Backend.layout.master')
@section('title', 'Edit Static Page')
@section('parentPageTitle', 'Static Page')
@section('content')
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Edit Static Page</h2>
            </div>
            <div class="body">
                <div class="col-lg-12 col-md-12">
                    {!! Form::model($staticpage, ['method' => 'PATCH','route' => ['admin.system.staticpage.update', $staticpage->id], 'files' => "true", 'id'=>"static_page" ]) !!}
                     {!! Form::hidden('page', request()->input('page')) !!}
                    <div class="form-group">
                        <label for="name" class="control-label">Title</label>
                         {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50", 'id' => 'static_page_title'  ]) !!}
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Image</label>
                        {!! Form::file('image', ['class' => "form-control-file dropify dropify_file_upload", 'data-rule-required' => "false", 'data-max-file-size' => "10M", 'accept' => ".png, .jpg, .jpeg" ,'data-allowed-file-extensions'=>"jpg jpeg png"]) !!}

                        <br>
                        @if(!empty($staticpage->featured_image) &&  Storage::disk('s3')->exists('staticpages/'.$staticpage->featured_image))
                            <img src="{!! env('Aws_URL').'staticpages/'.$staticpage->featured_image!!}" width="320" height="200">
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Description</label>
                         {!! Form::textarea('description',null,['class' => "form-control textarea-description", 'placeholder' => "Description", 'id'=>"description", 'data-rule-required' => "true"]) !!}
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="#" class="btn btn-success" id="preview_html" style="display: {{ empty($staticpage->description) ? 'none' : '' }};">Preview</a>
                    {!! Form::close() !!}
                </div>
            </div>


        </div>
    </div>

</div>
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog cb-homepage" role="document">
        <div class="modal-content">
            <div class="content"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-simple" data-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>
@stop
@section('page-styles')
<style type="text/css">
/*.note-editor .note-insert{
    display: none !important;
}*/
</style>
@endsection
@section('blade-page-script')
    {!! HTML::script('js/editor/ckeditor/ckeditor.js') !!}
    <script type="text/javascript">
        $(document).ready(function(){

            $('#preview_html').on('click', function () {
                $('.content').html($('#description').val());
                $('#previewModal').modal('show');
            });

            $('#description').on('keyup blur', function () {
                if ($('#description').val()) {
                    $('#preview_html').show();
                } else {
                    $('#preview_html').hide();
                }
            })

            CKEDITOR.on('instanceReady', function (ev) {
                ev.editor.dataProcessor.writer.setRules('br',
                {
                    indent: false,
                    breakBeforeOpen: false,
                    breakAfterOpen: false,
                    breakBeforeClose: false,
                    breakAfterClose: false
                });
            });

    


            CKEDITOR.replace('editor'); 
            $('.dropify').dropify();
            $('#static_page').validate({
                ignore : [],
                rules: {
                    title: {
                        remote: {
                            url: "{!! route('ajax.static_page.uniquetitle',$staticpage->id) !!}",
                            type: "post",
                            data: {
                                title:  function(){
                                    return $("#static_page_title").val();
                                }
                            }
                        }
                    },
                },
                messages : {
                    title : {
                        remote : "The title has already been taken."
                    }
                },
                errorPlacement: function(error, element) {
                    if($(element).hasClass('summernote')){
                        error.insertAfter(element.siblings('.note-editor'));
                    }else if($(element).hasClass('dropify_file_upload')){
                        error.insertAfter(element.closest('div.dropify-wrapper'));
                    }else{
                        error.insertAfter(element);
                    }
                },
            });
        })
    </script>

@stop
