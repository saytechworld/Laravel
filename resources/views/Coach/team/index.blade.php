 @extends('Coach.layout.master')
@section('title', 'Team')
@section('parentPageTitle', 'Team')
@section('content')
    <style>
        .user_dropdown_image {
            height: 25px;
            width: 25px;
            border-radius: 13px;
        }
    </style>
<div class="team-list">
    <div class="row clearfix">
       <?php /*  <div class="col-lg-8">
            <div class="card">
                <div class="body">
                    <div class="row clearfix">
                        @if(count($users) > 0 )
                            @foreach($users as $athelete_key => $athelete_val)
                                @php
                                    $athelete_games = $athelete_val->athelete_games()->groupBy('title')->pluck('title')->toArray();
                                @endphp
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="card c-b-box">
                                        <div class="body text-center">
                                            <div class="profile-image" data-percent="75"> <img src=" {{ !empty($athelete_val->user_details->image) && file_exists(public_path('images/users/'.$athelete_val->user_details->image)) ? asset('images/users/'.$athelete_val->user_details->image) : asset('images/noimage.jpg') }}" alt="user" class="rounded-circle image-click"/> </div>
                                            <span class="c-b-name">{{ ucfirst($athelete_val->name) }}</span>
                                            <div class="game-list">
                                                @if($athelete_val->privacy == 1)
                                                    @foreach($athelete_games as $athelete_game)
                                                        <span class="badge badge-default">{{ $athelete_game ?? '' }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                            @if($athelete_val->privacy == 1)
                                                <p>Experience: {{ $athelete_val->user_details->experience  ?? '0' }} Years</p>
                                            @endif
                                            <div class="b-c-btns m-t-20 m-t-20">
                                                <ul class="cmn-ul-list">
                                                    <li><a href="{{ route('coach.athelete.profileDetail',$athelete_val->username) }}"class="btn btn-outline-primary"><i class="fa fa-user"></i> <span>Profile</span></a></li>
                                                    <li><a href="{{ route('coach.chat.startuserchating',$athelete_val->user_uuid) }}"  class="btn btn-outline-primary"><i class="fa fa-comments-o"></i> <span>Message</span></a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div> */ ?>
        <div class="col-lg-12">
            <div class="card">
               
            </div>
            <div class="card profile-header upcoming-event-bx">
                <div class="body team-section">
                    <div class="header p-0">
                        <h2>Teams</h2>
                        
                        <button type="button" class="btn btn-block add-tm-btn tm-semi-btn" data-toggle="modal" data-target="#createTeam"><img src="{!! asset('images/add-event.svg') !!}" ><br>
                            <span>Add New Team</span></button>
                    </div>

                    @if($teams->count() > 0)
                        @foreach($teams as $team_key => $team)
                            <div class="team_timeline_item_row timeline-item green team_row_{{ $team->id }}" date-is="{{ $team->created_at ?? '' }}">
                                <span class="e-dot-c" style="background-color:#57b53f"></span><!--
                                <a href="{{route('coach.team.show', $team->id)}}" type="button">--> 
                                    <h5>{{ $team->title ?? '' }}</h5>
                                    <div class="clearfix">
                                        
                                        <div id="team-owl-demo_{{ $team->id }}" class="owl-carousel owl-theme team-detail-slider">
                                          @if($team->team_users->count() > 0 )
                                          @foreach($team->team_users as $athelete_key => $athelete_val)
                                          @php
                                            $athelete_games = $athelete_val->athelete_games()->groupBy('title')->pluck('title')->toArray();
                                          @endphp
                                          <div class="item">
                                              <div class="card c-b-box">
                                                <div class="body text-center">
                                                  <div class="profile-image" data-percent="75">
                                                    <img src=" {{ ($athelete_val->user_image) ? $athelete_val->user_image : asset('images/noimage.jpg') }}" alt="user" class="rounded-circle image-click">
                                                  </div>
                                                  <span class="c-b-name">{{ ucfirst($athelete_val->name) }}</span>
                                               @if($athelete_val->pivot->status == 0)
                                                    <p> Status : <strong>Pending</strong> </p>
                                                @elseif($athelete_val->pivot->status == 1)
                                                    <p> Status : <strong>Accepted</strong> </p>
                                                @else
                                                    <p> Status : <strong>Rejected</strong> </p>
                                                @endif
                                                <div class="game-list">
                                                  @if($athelete_val->privacy == 1)
                                                    @foreach($athelete_games as $athelete_game)
                                                      <span class="badge badge-default">{{ $athelete_game ?? '' }}</span>
                                                    @endforeach
                                                  @endif
                                                </div>
                                                 @if($athelete_val->privacy == 1 && $athelete_val->role_type == 'coach')
                                                      <p>Experience: {{ $athelete_val->user_details->experience  ?? '0' }} Years</p>
                                                  @endif
                                                  <div class="b-c-btns m-t-20 m-t-20">
                                                    <ul class="cmn-ul-list">

                                                      <li><a href="{{ route('coach.athelete.profileDetail',$athelete_val->username) }}" class="btn btn-outline-primary gray-btn"><span>Profile</span></a></li>
                                                      <li><a href="{{ route('coach.chat.startuserchating',$athelete_val->user_uuid) }}" class="btn btn-outline-primary gray-btn"><span>Message</span></a></li>
                                                    </ul>
                                                  </div>
                                                </div>
                                              </div>
                                          </div>
                                          @endforeach
                                           @endif                              
                                        </div>
                                    
                                    </div><!--
                                </a>-->
                                <div class="event-ed-btns">
                                    <li><a href="{{ route('coach.team.chat', encrypt($team->id)) }}" class="btn btn-block add-event-btn tm-semi-btn" onclick="teamChat({{$team->id}})">
                                        <img src="{!! asset('images/add-event.svg') !!}" ><br>
                                        <span>Open Chat</span></a>
                                    </li>
                                    <li><button type="button" class="btn btn-block add-event-btn tm-semi-btn" onclick="AddEventTeam({{$team->id}})">
                                        <img src="{!! asset('images/add-event.svg') !!}" ><br>
                                        <span>Add Event</span></button>
                                    </li>
                                    <li><button type="button" class="btn btn-block add-event-btn tm-semi-btn" onclick="editTeamData({{$team->id}})" >
                                        <img src="{!! asset('images/edit.svg') !!}" ><br>
                                        <span>Edit</span></button>
                                    </li> 
                                    <li><button type="button" class="btn btn-block add-event-btn tm-semi-btn" onclick="deleteTeamData({{$team->id}})">
                                        <img src="{!! asset('images/delete.svg') !!}" ><br>
                                        <span>Delete</span></button>
                                    </li>
                                    <div class="dropdown">
                                        <button data-toggle="dropdown" class="dropdown-toggle dropdown-open" aria-expanded="false"><i class="fa-angle-down"></i></button> 
                                        <div id="open_dropdown_84" class="dropdown-menu" x-placement="bottom-start" style="top: 80%; position: absolute; transform: translate3d(-41px, 32px, 0px); left: 0px; will-change: transform;">
                                            <div class="event-ed-btns">
                                                <a href="#" class="btn btn-block all-event-add-btn" data-toggle="modal" data-target="#addevent">Add Event</a>
                                                <a href="#" class="all-event-add-btn" onclick="editTeamData({{$team->id}})" > Edit</a>
                                                <a href="#" onclick="deleteEventData(8,3)" class="all-event-add-btn" onclick="deleteTeamData({{$team->id}})"> Delete</a>
                                            </div>    
                                        </div>
                                    </div>
                                    
                                </div>
                                
                            </div>
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
 <div class="modal fade createTeamPopup add_event_category_modal" id="createTeam" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             {!! Form::open(array('url' => route('coach.team.store'),'method' => 'POST', 'id' => 'create_team', 'files' => "true")) !!}
             <div class="modal-body">
                 <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                 <div class="form-group">
                     <label for="title">Title</label>
                     <div class="form-line">
                         {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50"  ]) !!}
                     </div>
                 </div>
                 <div class="form-group">
                     <label for="user">Members</label>
                     <div class="form-line">
                         <select name="member[]" class="form-control team_member" multiple>
                             @foreach($users as $user)
                                 @if(($user->user_image))
                                     @php $image = $user->user_image  @endphp
                                 @else
                                     @php $image = asset('images/noimage.jpg')  @endphp
                                 @endif
                                 <option value="{{$user->id}}" data-image="{!! $image !!}">{{$user->name}}</option>
                             @endforeach
                         </select>
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


 <div class="modal fade createTeamPopup add_event_category_modal" id="editTeam" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
         <div class="edit_team">

         </div>
     </div>
 </div>

<div class="modal fade image-view-popup" id="myModal" role="dialog">
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

    <div class="modal fade add_event_category_modal" id="addevent" tabindex="-1" role="dialog">
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
                                        <input type="text" class="form-control add_title" placeholder="Event Title" name="title" data-rule-required="true" autocomplete = "off" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type='text' class="form-control datetimepicker-start" placeholder="Start Date Time" id="datetimepicker" name="event_date" data-rule-required="true" autocomplete = "off" />
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="form-line">
                                        <input type='text' class="form-control datetimepicker-end" placeholder="End Date Time" name="end_datetime" autocomplete = "off" />
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


                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Event Color</label>
                                    <ul class="cmn-ul-list color_element selected_default_color_element">
                                        @foreach ($event_colors as $event_color)
                                          @if($event_color->id == 2)
                                            <li>
                                                <label class="event-ck-color">
                                                    <input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_color->id}}" checked="checked">
                                                    <span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label>
                                            </li>
                                            @endif
                                        @endforeach
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

@endsection
@section('blade-page-script')
    <script type="text/javascript">
        $(document).on('click','.image-click',function(e){
            $("#image-popup").html('')
            $('#myModal').modal('show');
            let url = $(this).attr('src');
            var image = document.createElement('img');
            image.setAttribute('src', url);
            document.getElementById('image-popup').appendChild(image);
        });
        $( document ).ready(function() {

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
                $('.color_element').show();
            }) ;

            $('.team_member').select2({
                "width" : "100%",
                templateResult: addUserPic,
                templateSelection: addUserPic
            });

            function addUserPic (opt) {
                if (!opt.id) {
                    return opt.text;
                }
                var optimage = $(opt.element).data('image');
                if(!optimage){
                    return opt.text;
                } else {
                    var $opt = $(
                        '<span class="userName"><img src="' + optimage + '" class="user_dropdown_image" /> ' + $(opt.element).text() + '</span>'
                    );
                    return $opt;
                }
            };

            $('#create_team').validate({
                ignore: [],

                errorPlacement: function(error, element) {
                    if($(element).hasClass('team_member')){
                        error.insertAfter(element.closest('div.form-group'));
                    }else{
                        error.insertAfter(element);
                    }
                },

                submitHandler: function (form) {
                    var event_form_data = $(form).serialize() + '&_token=' + "{!! csrf_token() !!}";
                    $.ajax({

                        type: "POST",
                        url: "{{ route('coach.team.store') }}",
                        data: event_form_data,
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
        });

        function deleteTeamData(team_id) {
            if (confirm('Are You Sure?')){
                var deleteUrl = '{{ route("coach.team.destroy",':id') }}';
                deleteUrl = deleteUrl.replace(':id', team_id);
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
                            $('.team_row_'+team_id).remove();
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

        function editTeamData(id) {
            $('.edit_team').html('');
            let url = "{{ route('coach.team.edit',['team'=>':id']) }}";
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
                        $('.edit_team').html(data.data.result);
                        $('#editTeam').modal();
                        reinitialiseFormValidation("#update_team");
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        };

        function reinitialiseFormValidation(elementSelector) {
            var validator = $(elementSelector).validate({
                ignore: [],

                errorPlacement: function(error, element) {
                    if($(element).hasClass('team_member')){
                        error.insertAfter(element.closest('div.form-group'));
                    }else{
                        error.insertAfter(element);
                    }
                },

                submitHandler: function (form) {
                    let url = $('#update_team').attr('action');
                    var event_form_data = $(form).serialize() + '&_token=' + "{!! csrf_token() !!}";
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
        };

    </script>
	<script>
			 $(document).ready(function() {
				  var owl = $(".team-detail-slider");
				  owl.owlCarousel({

					nav: true,
					margin: 20,
					responsive: {
					  0: {
						items: 1
					  },
					  600: {
						items: 2
					  },
					  960: {
						items: 3
					  },
					  1428: {
						items: 4
					  },
                      1680: {
						items: 5
					  },
					}
				  });

				})
		</script>
    <script type="text/javascript">


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

                        $('.event_category').select2({
                            "width" : "100%",
                            allowClear: true,
                            placeholder: 'Select Category'
                        });

                        $(".event_category").on('change', function () {
                            $('.category_color').html('');
                            if($(this).val()) {
                                let detail = $(this).find(':selected').data('id');
                                $('.category_color').html('<li>\n' +
                                    '                                    <label class="event-ck-color">\n' +
                                    '                                    <input type="radio" name="event_color" class="event_color" data-rule-required="true" checked value="'+detail.color_id+'">\n' +
                                    '                                    <span class="checkmark" style="background-color: '+ detail.color_code+'"></span> </label>\n' +
                                    '                                </li>')
                                $('.color_element').hide();
                                $('.category_color').show();
                            } else {
                                $('.category_color').hide();
                                $('.color_element').show();
                            }
                        });
                        $('#editEventModal').modal();
                        reinitialiseEventFormValidation("#update_user_event");
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        }


        function reinitialiseEventFormValidation(elementSelector) {

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

        let team_id = "";   
        function AddEventTeam(team_tr_id)
        {
        	team_id = "";   
        	team_id = team_tr_id;
        	$('#addevent').modal('show'); 
        }

        $( document ).ready(function() {

          function appendEventColor(){
                var SelectedEventColor = "";
                @foreach ($event_colors as $event_color)
                    @if($event_color->id == 2)
                    SelectedEventColor+='<li><label class="event-ck-color"><input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_color->id}}" checked><span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label></li>';
                    @endif
                @endforeach   
                return SelectedEventColor; 
            }

            $(document).on('click','.image-click',function(e){
                $("#image-popup").html('')
                $('#myModal').modal('show');
                let url = $(this).attr('src');
                var image = document.createElement('img');
                image.setAttribute('src', url);
                document.getElementById('image-popup').appendChild(image);
            });

            $(".datetimepicker-start").datetimepicker({
                format:'Y/m/d H:m',
                minDate: new Date(),
            });

            $(".datetimepicker-end").datetimepicker({
                format:'Y/m/d H:m',
                minDate: new Date(),
            });

            $(".datetimepicker-start").on('change', function () {
                $(".datetimepicker-end").datetimepicker({
                    format:'Y/m/d H:m',
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

            $('.event_category').select2({
                "width": "100%",
                allowClear: true,
                placeholder: 'Select Category'
            });

            $(".event_category").on('change', function () {
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


                } else {
                    $('.category_color').hide();
                    var EventColor = appendEventColor();
                    $('.selected_default_color_element').html(EventColor);
                    $('.selected_default_color_element').show();
                }
            });



            
            



            $('#create_event').validate({

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

                    var coach_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}"+'&team[]='+team_id;
                    $.ajax({

                        type: "POST",
                        url : "{{ route('coach.event.store') }}",
                        data : coach_form_data,
                        async: true,
                        beforeSend: function() {
                        
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
        $('.event_category').select2({
            "width" : "100%",
            allowClear: true,
            placeholder: 'Select Category'
        });

        $(".event_category").on('change', function () {
            $('.category_color').html('');
            if($(this).val() > 0) {
                let detail = $(this).find(':selected').data('id');
                $('.category_color').html('<li>\n' +
                    '                                    <label class="event-ck-color">\n' +
                    '                                    <input type="radio" name="event_color" class="event_color" data-rule-required="true" checked value="'+detail.color_id+';'+ detail.color_code+'">\n' +
                    '                                    <span class="checkmark" style="background-color: '+ detail.color_code+'"></span> </label>\n' +
                    '                                </li>')
                $('.color_element').hide();
                $('.category_color').show();
            } else {
                $('.category_color').hide();
                $('.color_element').show();
            }
        });
    </script>
@stop