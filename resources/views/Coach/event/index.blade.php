@extends('Coach.layout.master')
@section('title', 'Agenda')
@section('parentPageTitle', 'Agenda')
@section('content')
    <?php
    use Carbon\Carbon;
    ?>
    <div class="row clearfix">
        <div class="col-lg-8">
            <div class="card">
                <div class="body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                 <div class="body event-section">
                    <div class="header p-0">
                        <h2>Upcoming Events</h2>
                        <button type="button" class="btn btn-block add-event-btn event-semi-btn">
                            <img src="{!! asset('images/add-event.svg') !!}" ><br>
                            <span>Add Event</span>
                        </button>
                    </div>


                    @if($user_events->count() > 0)
                        @foreach($user_events->slice(0, 3) as $event_key => $event_val)
                            @php
                                $event_attendants = $event_val->event_attendants->where('attendant_type', 'A');
                               $user_name = [];
                               foreach ($event_attendants as $event_attendant) {
                                   array_push($user_name,$event_attendant->users->name);
                               }
                               $event_attendants = implode(", ",$user_name);
                            @endphp
                                 <div class="timeline-item up_to_toggle">
                                     <span class="event_view_dt" style="cursor: pointer;" onclick="fetchEventDetail({{$event_val->id}})">
                                        <div class="timeline-item green" date-is="{{ $event_val->title ?? '' }}">
                                            <div class="event-date-sec">
                                                <strong>{{ $event_val->event_created_date ?? '' }}</strong>
                                                <p>{{ $event_val->event_created_month ?? '' }}</p>
                                            </div>
                                            <span class="e-dot-c" style="background-color:{{$event_val->color_code}};"></span>
                                            <h5>{{ $event_val->event_datetime ?? '' }}</h5>

                                            @if($event_val->user_id == auth()->id())
                                                @php
                                                    $event_val['attendent'] = $event_val->event_attendants()->where('attendant_type', 'A')->pluck('user_id')->toArray();
                                                    $event_val['event_datetime'] = Carbon::parse($event_val->event_datetime)->format('Y/m/d H:i');
                                                    $event_val['end_datetime'] = $event_val->end_datetime ? Carbon::parse($event_val->end_datetime)->format('Y/m/d H:i') : '';
                                                @endphp




                                            @else
                                                <span> By : <strong>{{ $event_val->event_creators->name }}</strong></span>
                                            @endif
                                        </div>
                                     </span>     
                                      <div class="dropdown">
                                            <button data-toggle="dropdown" class="dropdown-toggle dropdown-open"><i class="fa-angle-down"></i></button> 
                                            <div id="open_dropdown_84" class="dropdown-menu">
                                                <div class="event-ed-btns">
                                                    <a href="#" class="all-event-add-btn" onclick="fetchEventDetail({{$event_val->id}})"><!--<i class="icon-note"></i>--> View</a>
                                                     @if($event_val->user_id == auth()->id())
                                                    <a href="#" class="all-event-add-btn" onclick="editEventData({{$event_val->id}})"><!--<i class="icon-note"></i>--> Edit</a>
                                                    @endif
                                                    <a href="#" onclick="deleteEventData({{$event_val->id}},{{$event_val->user_id}})" class="all-event-add-btn"><!--<i class="icon-trash"></i>--> Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                </div>     
                                 
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                        
                    <div class="text-right m-3">
                        <a class="btn btn-primary event-see-all-btn" href="{{ route('coach.event.list') }}">See All</a>
                    </div>

                </div>
                 <div class="body event-section category-section">
                    <div class="header p-0">
                        <h2>Categories</h2>                        
                        <button type="button" class="btn btn-block event-semi-btn add-cate-btn" data-toggle="modal" data-target="#addCategory">
                            <img src="{!! asset('images/add-category.svg') !!}" ><br>
                            <span>Add Category</span>
                        </button>
                    </div>

                    @if($categories->count() > 0)
                        @foreach($categories->slice(0, 3) as $category_key => $category_val)
                            <div class="timeline-item green category_row_{{ $category_val->id }}" date-is="{{ $category_val->title ?? '' }}">
                                
                                <span class="e-dot-c" style="background-color:{{$category_val->color_code}};"></span>
                                <div class="dropdown">
                                        <button data-toggle="dropdown" class="dropdown-toggle dropdown-open"><i class="fa-angle-down"></i></button> 
                                        <div id="open_dropdown_84" class="dropdown-menu">
                                            <div class="event-ed-btns">
                                                <a href="#" class="all-event-add-btn" onclick="editCategoryData({{$category_val->id}})"><!--<i class="icon-note"></i>--> Edit</a>
                                                <a href="#" onclick="deleteCategoryData({{$category_val->id}})" class="all-event-add-btn"><!--<i class="icon-trash"></i>--> Delete</a>
                                            </div>    
                                        </div>
                                    </div>
                                
                            </div>
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                    <div class="text-right m-3">
                        <a class="btn btn-primary event-see-all-btn" href="{{ route('coach.category.index') }}">See All</a>
                    </div>

                </div>
            </div>
            
        </div>

        
    </div>

    <div class="modal fade add_event_category_modal" id="addevent"  role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open(array('url' => route('coach.event.store'),'method' => 'POST', 'id' => 'create_event', 'files' => "true")) !!}

                <div class="modal-body">
                    <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type='text' class="form-control add_title" placeholder="Add Title" id="addtitle" name="title" data-rule-required="true" autocomplete = "off" />
                                    </div>
                                </div>
                            </div>    
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type='text' class="form-control datetimepicker-start" placeholder="Start Time" id="datetimepicker" name="event_date" data-rule-required="true" autocomplete = "off" />
                                    </div>

                                </div>
                            </div>   
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type='text' class="form-control datetimepicker-end" placeholder="End Time" name="end_datetime" autocomplete = "off" />
                                    </div>
                                </div>
                            </div>    

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="title">Description</label>
                                    <div class="form-line">
                                        <textarea class="form-control no-resize" placeholder="Event Description..." name="description"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Users</label>
                                    <div class="form-line">
                                        {{ Form::select('attendant[]',$users,null,['class' => "form-control no-resize event_attendant", 'data-placeholder' => "Select Attendant", 'data-rule-required' => "false", "multiple" => "true"]) }}
                                    </div>
                                </div>
                            </div>    
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Teams</label>
                                    <div class="form-line">
                                        <select name="team[]" class="form-control no-resize create_event_team" data-placeholder="Select Team" placeholder="Select Team" data-rule-required ="false" multiple>
                                            <option value="">Select Team</option>
                                            @foreach($team as $team_key => $team_val)
                                                @if($team_val->team_users_count > 0)
                                                <option value="{{ $team_val->id }}">{{ $team_val->title }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>    
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Category</label>
                                    <div class="form-line">
                                        <select name="category_id" class="form-control no-resize event_category" data-placeholder="Select Category" data-rule-required ="false">
                                            <option value="0">Select Category</option>
                                            @foreach($categories as $category_key => $category)
                                                <option value="{{ $category->id }}" data-id="{{$category}}">{{ $category->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="col-md-6 event-color-main-div" style="display: none;">
                                <div class="form-group">
                                    <label class="control-label">Event Color</label>
                                    <ul class="cmn-ul-list color_element selected_default_color_element">
                                        
                                    </ul>
                                    <ul class="cmn-ul-list category_color" style="display: none;">

                                    </ul>
                                </div>
                            </div>    
                        </div>
                    </div>    

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary modal-add-cat-event-btn">Add Event</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    <div class="edit_Event_Modal">
    </div>

    <div class="view_Event_Modal">
    </div>

    <div class="modal fade add_event_category_modal" id="addCategory" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::open(array('url' => route('coach.category.store'),'method' => 'POST', 'id' => 'create_category', 'files' => "true")) !!}


                <div class="modal-body">
                    <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>

                    <div class="form-group">
                        <div class="form-line">
                            <input type="text" class="form-control" placeholder="Category Title" name="title" data-rule-required="true" data-rule-maxlength="150">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Select Event Color</label>
                        <ul class="cmn-ul-list">
                            @foreach ($event_colors as $event_color)
                                @if($event_color->color_sort == 2)
                                    <li>
                                        <label class="event-ck-color">
                                            <input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_color->id}}">
                                            <span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary modal-add-cat-event-btn">Add Category</button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

    
    <div class="modal fade add_event_category_modal" id="editCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="categoryModal">

            </div>
        </div>
    </div>
@stop
@section('blade-page-script')
    <script type="text/javascript">
        let update_event_id = '';

        function deleteCategoryData(category_id) {
            if (confirm('Are You Sure?')){
                var deleteUrl = '{{ route("coach.category.delete",':id') }}';
                deleteUrl = deleteUrl.replace(':id', category_id);
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
                            $('.category_row_'+category_id).remove();
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
                        $('#event_view_modal').modal('hide');
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

                        <?php /*
                        $(".event_category").on('change', function () {
                            $('.category_color').html('');
                            if($(this).val() > 0) {
                                let detail = $(this).find(':selected').data('id');
                                $('.category_color').html('<li>\n' +
                                    '                                    <label class="event-ck-color">\n' +
                                    '                                    <input type="radio" name="event_color" class="event_color" data-rule-required="true" checked value="'+detail.color_id+'">\n' +
                                    '                                    <span class="checkmark" style="background-color: '+ detail.color_code+'"></span> </label>\n' +
                                    '                                </li>')
                                $('.color_element').hide();
                                $('.selected_editable_color_element').html('');
                                $('.category_color').show();
                            } else {
                                $(".event_category").val('');
                                $('.category_color').hide();
                                var EditableSelectEventColor = appendEditableEventColor();
                                $('.selected_editable_color_element').html(EditableSelectEventColor);
                                $('.selected_editable_color_element').show();
                            }
                        });
                        */ ?>

                        $('#editEventModal').modal();
                        reinitialiseFormValidation("#update_user_event");
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        }

        function fetchEventDetail(event_id)
        {
            $('.view_Event_Modal').html('');
            let url = "{{ route('coach.event.detail',['event'=>':id']) }}";
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
                        $('.view_Event_Modal').html(data.data.result);
                        $('#event_view_modal').modal();
                    } else {
                        toastr.error(data.message);
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
                    if ($('#update_category_id').val() == 0) {
                        $('#update_category_id').val('');
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

        function editCategoryData(id) {
            $('.categoryModal').html('');
            let url = "{{ route('coach.category.edit',['category'=>':id']) }}";
            url = url.replace(':id', id);

            $.ajax({

                type: "GET",
                url: url,
                async: false,
                beforeSend: function () {
                    //
                },
                success: function (data) {
                    if (data.status) {
                        $('.categoryModal').html(data.data.result);
                        $('#editCategoryModal').modal();
                        reinitialiseCategoryFormValidation("#update_category");
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        };

        function reinitialiseCategoryFormValidation(elementSelector) {
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
                    var event_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}";

                    let url = $('#update_category').attr('action');

                    $.ajax({

                        type: "POST",
                        url : url,
                        data : event_form_data,
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
        };

        function deleteEventData(event_id, user_id) {
            if (confirm('Are You Sure?')){
                var url = '{{ route("coach.event.delete", ":event_id") }}';
                url = url.replace(':event_id', event_id);
                document.location.href = url;
            }
        }

        function eventAction(event_id, action) {
            if (confirm('Are You Sure?')){
                var url = '{{ route("coach.event.action", ['event'=>":id",'action'=>":action"]) }}';
                url = url.replace(':id', event_id);
                url = url.replace(':action', action);
                document.location.href = url;
            }
        }

        function appendEditableEventColor(){
            var SelectedEditableEventColor = "";
            @foreach ($event_colors as $event_edit_color)
                @if($event_color->id == 2)
                SelectedEditableEventColor+='<li><label class="event-ck-color"><input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_edit_color->id}}" checked><span class="checkmark" style="background-color: {{$event_edit_color->color_code}}"></span> </label></li>';
                @endif
            @endforeach   
            return SelectedEditableEventColor; 
        }

        $(function() {

            function appendEventColor(){
                var SelectedEventColor = "";
                @foreach ($event_colors as $event_color)
                    @if($event_color->id == 2)
                    SelectedEventColor+='<li><label class="event-ck-color"><input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_color->id}}" checked><span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label></li>';
                    @endif
                @endforeach   
                return SelectedEventColor; 
            }

            

            //  open model 
            $(document).on('click','.add-event-btn',function(event){
                event.preventDefault();
                var EventColor = appendEventColor();
                $('.selected_default_color_element').html(EventColor);
                $('#addevent').modal('show');
            });

            $(".datetimepicker-start").datetimepicker({
                format: 'Y/m/d H:m',
                minDate: new Date(),
            })

            $(".datetimepicker-end").datetimepicker({
                format: 'Y/m/d H:m',
                 minDate: new Date(),
            });

            $(".datetimepicker-start").on('change', function () {
                $(".datetimepicker-end").datetimepicker({
                    format: 'Y/m/d H:m',
                    minDate: $(this).val()
                });
            });

            $(".datetimepicker-end").on('blur', function () {
                if($(".datetimepicker-end").val()) {
                    $(".datetimepicker-start").datetimepicker({
                        format:'Y/m/d H:m',
                        maxDate: $(this).val()
                    })
                }
            });

            $('.event_attendant').select2({
                "width": "100%"
            });
            $('.event_category').select2({
                "width": "100%",
                allowClear: true,
                placeholder: 'Select Category'
            });

            $('.create_event_team').select2({
                dropdownAutoWidth: true,
                multiple: true,
                width: '100%',
                height: '30px',
                placeholder: "Select test",
                allowClear: true
            });

            $('#create_event').validate({

                ignore: [],
                errorPlacement: function (error, element) {
                    if ($(element).hasClass('event_color')) {
                        error.insertAfter(element.closest('div.form-group'));
                    } else {
                        error.insertAfter(element);
                    }
                },

                submitHandler: function (form) {
                    $('.processing-loader').show();
                    if ($('.event_category').val() == 0) {
                        $('.event_category').val('');
                    }
                    var event_form_data = $(form).serialize() + '&_token=' + "{!! csrf_token() !!}";
                    $.ajax({

                        type: "POST",
                        url: "{{ route('coach.event.store') }}",
                        data: event_form_data,
                        async: true,
                        beforeSend: function () {
                            //
                        },
                        success: function (data) {
                            $('.processing-loader').hide();
                            if (data.status) {
                                toastr.success(data.message);
                                window.location.reload(true);
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

            $('#create_category').validate({

                ignore: [],
                errorPlacement: function (error, element) {
                    if ($(element).hasClass('event_color')) {
                        error.insertAfter(element.closest('div.form-group'));
                    } else {
                        error.insertAfter(element);
                    }
                },

                submitHandler: function (form) {
                    var category_form_data = $(form).serialize() + '&_token=' + "{!! csrf_token() !!}";
                    $.ajax({

                        type: "POST",
                        url: "{{ route('coach.category.store') }}",
                        data: category_form_data,
                        async: true,
                        beforeSend: function () {
                            $('.processing-loader').show();
                        },
                        success: function (data) {
                            $('.processing-loader').hide();
                            if (data.status) {
                                toastr.success(data.message);
                                window.location.reload(true);
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

            $(document).on('change',  ".event_category", function () {
                $('.category_color').html('');
                if($(this).val() > 0) {
                    let detail = $(this).find(':selected').data('id');
                    $('.category_color').html('<li>\n' +
                        '                                    <label class="event-ck-color">\n' +
                        '                                    <input type="radio" name="event_color" class="event_color" data-rule-required="true" checked value="'+detail.color_id+'">\n' +
                        '                                    <span class="checkmark" style="background-color: '+ detail.color_code+'"></span> </label>\n' +
                        '                                </li>')
                    $('.selected_default_color_element').html('');
                    $('.selected_default_color_element').hide();
                    $('.category_color').show();
                    $('.event-color-main-div').show();
                } else {
                    $(".event_category").val('');
                    $('.category_color').hide();
                    $('.event-color-main-div').hide();
                    var EventColor = appendEventColor();
                    $('.selected_default_color_element').html(EventColor);
                    $('.selected_default_color_element').show();
                }
            });

            var user_events = new Array();
            var defaultEventDate = "{{ Carbon::now()->format('Y-m-d') }}";
                    @if($user_events->count() > 0)
                    @foreach($user_events as $user_event_key => $user_event_val)
                    @php
                        $attendent = $user_event_val->event_attendants()->where('attendant_type', 'A')->pluck('user_id')->toArray();
                        $user_event_val->end_datetime ? $end_date = Carbon::parse($user_event_val->end_datetime)->format('Y/m/d H:i') : $end_date = null;
                    @endphp
            user_events.push({
                id: "{{ $user_event_val->id }}",
                title: "{{ $user_event_val->title }}",
                start: "{{ Carbon::parse($user_event_val->event_datetime)->format('Y-m-d') }}",
                end: "{{ Carbon::parse($user_event_val->end_datetime)->format('Y-m-d') }} 23:59:59",
                backgroundColor: "{{$user_event_val->color_code}}",
            });
            @endforeach
                defaultEventDate = "{{ Carbon::parse($user_events[0]->event_datetime)->format('Y-m-d') }}";
            @endif
            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,timelineCustom'
                },
                defaultDate: defaultEventDate,
                editable: true,
                displayEventTime: false,
                droppable: false, // this allows things to be dropped onto the calendar
                /*drop: function() {
                    // is the "remove after drop" checkbox checked?
                    if ($('#drop-remove').is(':checked')) {
                        // if so, remove the element from the "Draggable Events" list
                        $(this).remove();
                    }
                },*/
                eventClick: function (calEvent, jsEvent, view) {
                    fetchEventDetail(calEvent.id);
                },
                schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
                eventLimit: true, // allow "more" link when too many events
                events: user_events,
                fixedWeekCount: false,
                contentHeight: 650,
                views: {
                    timelineCustom: {
                        type: 'timeline',
                        buttonText: 'Year',
                        dateIncrement: { years: 1 },
                        slotDuration: { months: 1 },
                        visibleRange: function (currentDate) {
                            return {
                                start: currentDate.clone().startOf('year'),
                                end: currentDate.clone().endOf("year")+1
                            };
                        }
                    }
                }
            });
        })

        $('.modal').on('hidden.bs.modal', function(e) {
            $(this)
                .find("input[type=text],textarea,select")
                .val('')
                .end()
                .find("input[type=checkbox], input[type=radio]")
                .prop("checked", "")
                .end();

            $('.event_category').select2({
                "width" : "100%",
                allowClear: true,
                placeholder: 'Select Category'
            });
            $('.create_event_team').select2({
                dropdownAutoWidth: true,
                multiple: true,
                width: '100%',
                height: '30px',
                placeholder: "Select test",
                allowClear: true
            });
            $('.event_attendant').select2({
                "width": "100%"
            });
            $('.category_color').hide();
            $('.event-color-main-div').hide();
            $('.color_element').show();
        }) ;

    </script>
@stop