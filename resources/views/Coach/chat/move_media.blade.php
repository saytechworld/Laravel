<?php
use App\Models\Tag;
$tags = Tag::where('status',1)->pluck('title','id');
$folder_arr = array();
if($folders->count() > 0)
{
    $folder_arr = $folders->pluck('title','id')->toArray();
}


//echo "<pre>"; print_r($tags); exit;
?>
<div class="modal-content add_event_category_modal">
    {!! Form::open(array('url' => route('coach.chat.movefile'),'method' => 'POST', 'id' => 'media-upload', 'files' => "true")) !!}
        <div class="modal-body">
            <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
            <div class="form-group">
                <div class="form-line">
                    <label for="name" class="control-label">Title</label>
                    {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50"  ]) !!}
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="form-line">
                    <label for="name" class="control-label">Description</label>
                    {!! Form::textarea('description',null,['class' => "form-control", 'placeholder' => "Description", 'data-rule-required' => "false", 'data-rule-maxlength' => "1000"  ]) !!}
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="form-line">
                    <label for="name" class="control-label">Tags</label>
                    {!! Form::select('video_tag[]',$tags, null,['class' => "form-control select_video_tag", 'data-placeholder' => "Select tag", 'data-rule-required' => "false", 'multiple' => "true" ]) !!}
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="form-line">
                    <label for="name" class="control-label">Folders</label>
                    <select class="form-control select_folder" data-placeholder="Select folder" data-rule-required="false" name="user_folder_id">
                        <option value="">Select folder</option>
                        @if(count($folder_arr) > 0)
                            @foreach($folder_arr as $photo_folder_arr_key => $photo_folder_arr_val)
                                <option value="{{ $photo_folder_arr_key }}">{{ $photo_folder_arr_val }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <div class="form-line">
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
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary modal-add-cat-event-btn">Save</button>
        </div>
    {!! Form::close() !!}
</div>
<script type="text/javascript">
    $(function() {
        $('#media-upload').validate({
            ignore : [],
            errorPlacement: function(error, element) {
                if($(element).hasClass('select_video_tag')){
                    error.insertAfter(element.siblings('.select2-container'));
                }else{
                    error.insertAfter(element);
                }
            },

            submitHandler: function (form) {
                let url = $('#media-upload').attr('action');
                var event_form_data = $(form).serialize() + '&_token=' + "{!! csrf_token() !!}" + '&file_id=' + "{{ $file_id  }}";
                $.ajax({
                    type: "POST",
                    url: url,
                    data: event_form_data,
                    async: true,
                    beforeSend: function () {
                        $('.processing-loader').show();
                    },
                    success: function (data) {
                        $('.processing-loader').hide();
                        if (data.status) {
                            toastr.success(data.message);
                            $('#moveToFolder').modal('hide');
                        } else {
                            toastr.error(data.message);
                        }
                    },
                    error: function (xhr) { // if error occured
                        toastr.error("Error occured.please try again");
                    },
                });
            }
        });
        $('.select_video_tag').select2();
        $('.select_folder').select2({
            placeholder : 'Select folder',
            allowClear: true,
        });
    });
</script>
