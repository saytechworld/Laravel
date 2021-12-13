@extends('Coach.layout.master')
@section('title', 'Event List')
@section('parentPageTitle', 'Events')


@section('content')
    <?php
    use Carbon\Carbon;
    ?>

    <div class="row clearfix">

        <div class="col-lg-12">
            <div class="card upcoming-event-bx">
                <div class="body">
                    @if($events->count() > 0)
                        @foreach($events as $event_key => $event_val)
                            @php
                                $event_attendants = $event_val->event_attendants->where('attendant_type', 'A');
                               $user_name = [];
                               foreach ($event_attendants as $event_attendant) {
                                   array_push($user_name,$event_attendant->users->name);
                               }
                               $event_attendants = implode(", ",$user_name);
                            @endphp
                            <div class="timeline-item green" date-is="{{ $event_val->event_datetime ?? '' }}">
                                <span class="e-dot-c" style="background-color:{{$event_val->color_code}};"></span>
                                <h5>{{ $event_val->title ?? '' }}</h5>
                                <div class="msg">
                                    <p>{{ $event_val->description ?? '' }}</p>
                                </div>
                                @if($event_val->user_id == auth()->id())
                                    <span> Attendants : <strong>{{ $event_attendants }}</strong></span>

                                    <div class="event-ed-btns">
                                        <a href="#" class="btn btn-sm btn-outline-secondary m-r-10" onclick="editEventData({{$event_val->id}})"><i class="icon-note"></i> </a>
                                        <a href="#" onclick="deleteEventData({{$event_val->id }},{{$event_val->user_id }})" class="btn btn-sm btn-outline-danger"><i class="icon-trash"></i> </a>
                                    </div>
                                @else
                                    <div class="event-ed-btns">
                                        <a href="#" onclick="deleteEventData({{$event_val->id }},{{$event_val->user_id }})" class="btn btn-sm btn-outline-danger"><i class="icon-trash"></i> </a>
                                    </div>
                                    <span> By : <strong>{{ $event_val->event_creators->name }}</strong></span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                    <div class="box-footer">
                        <div class="pagination pull-right">
                            {{ $events->appends(request()->except('page'))->links()  }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="edit_Event_Modal">
    </div>
@stop
@section('blade-page-script')
    <script type="text/javascript">
        let update_event_id = '';

        function editEventData(event) {
            fetchEventData(event);
        }

        function fetchEventData(event_id)
        {
            $('.edit_Event_Modal').html('');
            let url = "{{ route('coach.event.event_detail',['event'=>':id']) }}";
            url = url.replace(':id', event_id);
            $.ajax({
                type: "GET",
                url: url,
                async: false,
                beforeSend: function () {
                    //
                },
                success: function (data) {
                    if (data.status) {
                        $('.edit_Event_Modal').html(data.data.result);
                        $('#eventAttenders').select2({
                            "width" : "100%"
                        });

                        $('.event_category').select2({
                            "width" : "100%",
                            allowClear: true,
                        });

                        $('.create_event_team').select2({
                            dropdownAutoWidth: true,
                            multiple: true,
                            width: '100%',
                            height: '30px',
                            placeholder: "Select test",
                            allowClear: true
                        });
                        $(".datetimepicker_update").datetimepicker({
                            format: 'Y/m/d H:m'
                        });

        
                        $('#editEventModal').modal();
                        reinitialiseFormValidation("#update_user_event");
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        }

        function reinitialiseFormValidation(elementSelector) {

            var validator = $(elementSelector).validate({
                ignore : [],
                errorPlacement: function(error, element) {
                    if($(element).hasClass('event_color')){
                        error.insertAfter(element.closest('div.form-group'));
                    }else{
                        error.insertAfter(element);
                    }
                },
                submitHandler: function (form) {
                    $('.processing-loader').show();
                    if ($('.event_category').val() == 0) {
                        $('.event_category').val('');
                    }
                    var event_form_data = $(form).serialize();
                    let url = $('#update_user_event').attr('action');
                    var event_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}";
                    $.ajax({
                        type: "POST",
                        url : url,
                        data : event_form_data,
                        async: true,
                        beforeSend: function() {
                            //
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
        };

        function deleteEventData(event_id, user_id) {
            if (confirm('Are You Sure?')){
                var url = '{{ route("coach.event.delete", ":event_id") }}';
                url = url.replace(':event_id', event_id);
                document.location.href = url;
            }
        }

        $(document).ready(function(){

            $(document).on('change', ".event_category", function () {
                $('.category_color').html('');
                if($(this).val() > 0) {
                    let detail = $(this).find(':selected').data('id');
                    $('.category_color').html('<li>\n' +
                        '                                    <label class="event-ck-color">\n' +
                        '                                    <input type="radio" name="event_color" class="event_color" data-rule-required="true" checked value="'+detail.color_id+';'+ detail.color_code+'">\n' +
                        '                                    <span class="checkmark" style="background-color: '+ detail.color_code+'"></span> </label>\n' +
                        '                                </li>')
                    
                    $('.category_color').show();
                    $('.selected_default_color_element').html('');
                    $('.selected_default_color_element').hide();
                    $('.event-color-main-div').show();
                } else {
                    $(".event_category").val('');
                    $('.category_color').hide();
                    var EventColor = appendEventColor();
                    $('.selected_default_color_element').html(EventColor);
                    $('.selected_default_color_element').show();
                    $('.event-color-main-div').hide();
                }
            });


            function appendEventColor(){
                var SelectedEventColor = "";
                @foreach ($event_colors as $event_color)
                    @if($event_color->id == 2)
                    SelectedEventColor+='<li><label class="event-ck-color"><input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_color->id}}" checked><span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label></li>';
                    @endif
                @endforeach   
                return SelectedEventColor; 
            }
        });

    </script>
@stop
