@extends('Coach.layout.master')
@section('title', 'Messenger')
@section('parentPageTitle', 'Messenger')
@section('content')
 <style>
        .user_dropdown_image {
            height: 25px;
            width: 25px;
            border-radius: 13px;
        }
</style>
<div class="row clearfix">
  <div class="col-12">
    <div class="add-new-g-btn">Create Group <button type="button" class="btn btn-outline-primary click-open-modal"><span><i class="icon-plus"></i> </span></button></div>
   <div class="c-l-btn"><button class="btn btn-outline-primary open-chat-users">Chat Users </button></div>
    <div class="card chat-app chat-sec">
      <div class="loading_image" style="display: none;" >
       <?php /* <img src="{!! asset('images/loading.gif') !!}"/> */ ?>
      </div>
       <div id="manage-vue">
          <coach-chatuser-component :request_athelete_chat="{{ !empty($request_chat) ? $request_chat : '{}' }}" :login_user="{{ auth()->user() }}"></coach-chatuser-component>
        </div>
    </div>
  </div>

</div>
 <div class="modal fade add_event_category_modal" id="createGroup" tabindex="-1" role="dialog">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             {!! Form::open(array('url' => route('coach.chat.creategroup'),'method' => 'POST', 'id' => 'create_group', 'files' => "true")) !!}
             <div class="modal-body">
                 <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                 <div class="form-group">
                     <label for="title">Title</label>
                     <div class="form-line">
                         {!! Form::text('title',null,['class' => "form-control title-text", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50"  ]) !!}
                     </div>
                 </div>

                 <div class="form-group">
                     <label class="mb-3">Choose Group Type </label>
                     <div class="input-group">
                         <label class="fancy-radio">
                             {{ Form::radio('group_type', '1' , true, ['class' => "group_type_radio team_users_radio"]) }}
                             <span><i></i> Users</span> </label>
                     </div>
                     <div class="input-group">
                         <label class="fancy-radio">
                             {{ Form::radio('group_type', '2' , false, ['class' => "group_type_radio"]) }}
                             <span><i></i> Team</span>
                         </label>
                     </div>
                 </div>

                 <div class="form-group group_user_list">
                     <label for="user">Members</label>
                     <div class="form-line">
                         <select name="member[]" class="form-control team_member" multiple required>
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

                 <div class="form-group group_team_list" style="display: none">
                     <label for="teams">Teams</label>
                     <div class="form-line">
                         <select name="team" class="form-control team_list">
                             <option value="">-- Select --</option>
                             @foreach($teams as $team)
                                 <option value="{{$team->id}}">{{$team->title}}</option>
                             @endforeach
                         </select>
                     </div>
                 </div>
             </div>

             <div class="modal-footer">
                 <input type="submit" class="btn btn-primary" value="Add">
             </div>
             {!! Form::close() !!}
         </div>
     </div>
 </div>
@stop
@section('blade-page-vue-script')
{!! HTML::script('js/app.js') !!}
@endsection

@section('blade-page-script')
    <script type="text/javascript">
        $(function() {
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

            $('.team_list').select2({
                "width": "100%",
            });

            $(document).off('click','.group_type_radio').on('click','.group_type_radio', function(e){
               if($(this).val() == 1) {
                    $('.group_user_list').show();
                    $('.group_team_list').hide();
                   $('.team_member').attr('required', true);
                   $('.team_list').attr('required', false);
               } else {
                   $('.group_user_list').hide();
                   $('.group_team_list').show();
                   $('.team_member').attr('required', false);
                   $('.team_list').attr('required', true);
               }
            });

            $('#create_group').validate({
                ignore: [],

                errorPlacement: function(error, element) {
                    if($(element).hasClass('team_member')){
                        error.insertAfter(element.closest('div.form-group'));
                    } else if($(element).hasClass('team_list')){
                        error.insertAfter(element.closest('div.form-group'));
                    }else{
                        error.insertAfter(element);
                    }
                },

                submitHandler: function (form) {
                    var event_form_data = $(form).serialize() + '&_token=' + "{!! csrf_token() !!}";
                    $.ajax({

                        type: "POST",
                        url: "{{ route('coach.chat.creategroup') }}",
                        data: event_form_data,
                        async: true,
                        beforeSend: function () {
                            $('.processing-loader').show();
                        },
                        success: function (data) {
                            $('.processing-loader').hide();
                            if (data.status) {
                                toastr.success(data.message);
                                $('#createGroup').modal('hide');
                                $('.modal-backdrop').remove();
                                $('.theme-cyan').removeClass('modal-open');
                                $(".team_member").val([]).trigger("change");
                                $(".team_list").val([]).trigger("change");
                                $(".title-text").val('');
                                $('.group_user_list').show();
                                $('.group_team_list').hide();
                                $('.team_member').attr('required', true);
                                $('.team_list').attr('required', false);
                                $( ".team_users_radio" ).prop( "checked", true );
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

            $('#createGroup').on('hidden.bs.modal', function(e) {
                $('.modal-backdrop').remove();
                $('.theme-cyan').removeClass('modal-open');
                $(".team_member").val([]).trigger("change");
                $(".team_list").val([]).trigger("change");
                $(".title-text").val('');
                $('.group_user_list').show();
                $('.group_team_list').hide();
                $('.team_member').attr('required', true);
                $('.team_list').attr('required', false);
                $( ".team_users_radio" ).prop( "checked", true );
            });

            $(document).off('click','.open-chat-users').on('click','.open-chat-users', function(e){
                $(".people-list").toggleClass("c-l-open");
                if( $(".people-list").hasClass("c-l-open")) {
                    $(".open-chat-users").html('Close');
                } else {
                    $(".open-chat-users").html('Chat Users');
                }
            });

            $(document).off('click','.chat-user-click').on('click','.chat-user-click', function(e){
                $(".people-list").removeClass("c-l-open");
                $(".open-chat-users").html('Chat Users');
            });

            $(document).off('click','.click-open-modal').on('click','.click-open-modal', function(e){
                $('#createGroup').modal();
            });

        });
    </script>
@endsection