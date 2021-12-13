@extends('Coach.layout.master')
@section('title', 'Team Detail')
@section('parentPageTitle', 'Team')
@section('content')
  <?php
  use Carbon\Carbon;
  ?>
<div class="team-details">
  <div class="row clearfix">
    <div class="col-lg-8">
      <div class="card">
        <div class="header">
          <h2>{{ $team->title ?? '' }}</h2>
        </div>
        <div class="body">
          <div class="row clearfix">
            @if($team->team_users->count() > 0 )
              @foreach($team->team_users as $athelete_key => $athelete_val)
                @php
                  $athelete_games = $athelete_val->athelete_games()->groupBy('title')->pluck('title')->toArray();
                @endphp
                <div class="col-lg-4 col-md-6 col-sm-6">
                  <div class="card c-b-box">
                    <div class="body text-center">
                      <div class="profile-image" data-percent="75">
                        <img src=" {{ !empty($athelete_val->user_details->image) && file_exists(public_path('images/users/'.$athelete_val->user_details->image)) ? asset('images/users/'.$athelete_val->user_details->image) : asset('images/noimage.jpg') }}" alt="user" class="rounded-circle image-click"/>
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
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="body">
          <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#addevent">Add New Event</button>
        </div>
      </div>
      <?php /*<div class="card profile-header upcoming-event-bx">
        <div class="body">
          <div class="header p-0">
            <h2>Upcoming Event</h2>
          </div>
          <hr>

          @if($events->count() > 0)
            @foreach($events as $event_key => $event)
              <div class="timeline-item green" date-is="{{ $event->events->event_datetime ?? '' }}">
                <span class="e-dot-c" style="background-color:{{ $event->events->color_code ?? '' }}"></span>
                 <h5>{{ $event->events->title ?? '' }}</h5>
                <div class="event-ed-btns">
                  @php
                    $event->events['event_datetime'] = Carbon::parse($event->events->event_datetime)->format('Y/m/d H:i');
                   $event->events['end_datetime'] = $event->events->end_datetime ? Carbon::parse($event->events->end_datetime)->format('Y/m/d H:i') : '';
                  @endphp
                  <a href="#" class="btn btn-sm btn-outline-secondary m-r-10" onclick="editEventData({{$event->events->id}})"><i class="icon-note"></i> </a>
                </div>
              </div>
            @endforeach
          @else
            <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
          @endif
        </div>
      </div> */ ?>
    </div>
  </div>
</div>
<div class="modal fade" id="addevent" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      {!! Form::open(array('url' => route('coach.event.store'),'method' => 'POST', 'id' => 'create_event', 'files' => "true")) !!}

      <div class="modal-header">
        <h4 class="title" id="defaultModalLabel">Add Event for team : <strong>{{ $team->title ?? '' }}</strong></h4>
      </div>

      <div class="modal-body">

        <div class="form-group">
          <div class="form-line">
            <input type='text' class="form-control datetimepicker-start" placeholder="Start Date Time" id="datetimepicker" name="event_date" data-rule-required="true" autocomplete = "off" />
          </div>

        </div>
        <div class="form-group">
          <div class="form-line">
            <input type='text' class="form-control datetimepicker-end" placeholder="End Date Time" name="end_datetime" autocomplete = "off" />
          </div>
        </div>

        <div class="form-group">
          <div class="form-line">
            <input type="text" class="form-control" placeholder="Event Title" name="title" data-rule-required="true" data-rule-maxlength="150">
          </div>
        </div>

        <div class="form-group">
          <div class="form-line">
            <textarea class="form-control no-resize" placeholder="Event Description..." name="description"></textarea>
          </div>
        </div>

          <div class="form-group">
              <div class="form-line">
                  <select name="category_id" class="form-control no-resize event_category" data-placeholder="Select Category" data-rule-required ="false">
                      <option value="">Select Category</option>
                      @foreach($categories as $category_key => $category)
                          <option value="{{ $category->id }}" data-id="{{$category}}">{{ $category->title }}</option>
                      @endforeach
                  </select>
              </div>
          </div>

          <div class="form-group">
              <label class="control-label">Select Event Color</label>
              <ul class="cmn-ul-list color_element">
                  @foreach ($event_colors as $event_color)
                      <li>
                          <label class="event-ck-color">
                              <input type="radio" name="event_color" class="event_color" data-rule-required="true" value="{{$event_color->id}};{{$event_color->color_code}}">
                              <span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label>
                      </li>
                  @endforeach
              </ul>
              <ul class="cmn-ul-list category_color" style="display: none;">

              </ul>
          </div>

      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Add</button>
        <button type="button" class="btn btn-simple" data-dismiss="modal">CLOSE</button>
      </div>
      {!! Form::close() !!}
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

  <div class="edit_Event_Modal">
    </div>
@endsection

@section('blade-page-script')
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

    $( document ).ready(function() {

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
            if($(this).val()) {
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
          var coach_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}"+'&team[]='+"{{$team->id}}";;
          $.ajax({

            type: "POST",
            url : "{{ route('coach.event.store') }}",
            data : coach_form_data,
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




      
    });
  </script>
@stop