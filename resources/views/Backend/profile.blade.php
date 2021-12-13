@extends('Backend.layout.master')
@section('title', 'User Profile')
@section('parentPageTitle', 'Pages')


@section('content')
@php 
use Carbon\Carbon; 
use App\Models\Country;

$country_arr = Country::where('status',1)->get();

@endphp
<div class="row clearfix">

    <div class="col-lg-12">
        <div class="card">
            <div class="body">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#changepassword">Change Password</a></li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane active" id="changepassword">
                    {!! Form::open(array('url' => route('admin.updatepassword'),'method' => 'POST', 'id' => 'admin-changepassword')) !!}
                    <div class="body">
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-12">
                                <?php /* <h6>Change Password</h6> */ ?>
                                <div class="form-group">
                                     <label for="current_password" class="control-label">Current password</label>
                                    {{  Form::password('current_password',['class' => "form-control", 'placeholder' => "Current Password", 'data-rule-required' => "true", 'data-rule-minlength' => "6", 'data-rule-maxlength' => "12" ]) }}
                                </div>
                                <div class="form-group">
                                     <label for="new_password" class="control-label">New password</label>
                                    {!! Form::password('password',[ 'class' => 'form-control', 'id' => "password", 'placeholder' => "New Password", 'data-rule-required' => "true", 'data-rule-minlength' => "6", 'data-rule-maxlength' => "12" ]) !!}
                                </div>
                                <div class="form-group">
                                     <label for="confirm_password" class="control-label">Confirm password</label>
                                    {!! Form::password("password_confirmation", ["class" => "form-control", "placeholder" => "Confirm Password", "data-rule-required" => "true", "data-rule-minlength" => 6, "data-rule-maxlength" => 12, "data-rule-equalTo" => "#password",  "data-msg-equalTo" => "Confirm password does not match." ]) !!}
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                        <a href="#" class="btn btn-default">Cancel</a>
                    </div>
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
            var user_selected_state = "{{ !empty($loginuser->user_details->state_id) ? $loginuser->user_details->state_id : "" }}";
            var user_selected_city = "{{ !empty($loginuser->user_details->city_id) ? $loginuser->user_details->city_id : "" }}";

             $('.select_languages').select2();
            // photo upload
            $(document).off('click', '#btn-upload-photo').on('click', '#btn-upload-photo', function() {
                $(this).siblings('#filePhoto').trigger('click');
            });
                function readURL(input) {
                    if (input.files && input.files[0]) {

                        var reader = new FileReader();

                        var extension = input.files[0].name.substring(input.files[0].name.lastIndexOf('.'));

                        var validFileType = ".jpg , .png";
                        if (validFileType.toLowerCase().indexOf(extension) < 0) {
                            alert("please select valid file type. The supported file types are .jpg , .png");
                            return false;
                        }

                        reader.onload = function (e) {
                            $('#profile_user_image').attr('src', e.target.result);
                        }
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                $(document).on('change', '#filePhoto', function () {
                    readURL(this);
                });


            jQuery.validator.addMethod("mobile_number_validate", function (value, element) {
                var phoneno_pattern = /^\d{7,10}$/;
                if(value.match(phoneno_pattern))
                {
                    return true;
                }else{
                    return false;
                }
            }, "Mobile number not valid.");


            $('#admin-changepassword').validate();
             $('#admin-updateprofile').validate({
                ignore : [],
                 rules: {
                     email: {
                         remote: {
                             url: "{!! route('ajax.user.uniqueuser',auth()->id()) !!}",
                             type: "post",
                             data: {
                                 email:  function(){
                                     return $("#email").val();
                                 }
                             }
                         }
                     },
                     "user_details[mobile]": {
                         remote: {
                             url: "{!! route('ajax.user.uniqueuserbymobile',auth()->id()) !!}",
                             type: "post",
                             data: {
                                 mobile:  function(){
                                     return $("#mobile").val();
                                 }
                             }
                         }
                     },
                 },
                 messages : {
                     email : {
                         remote : "The email has already been taken."
                     },
                     "user_details[mobile]":{
                         remote : "The mobile has already been taken."
                     }
                 },
                errorPlacement: function(error, element) {
                  if($(element).hasClass('gender_selection')){
                    error.insertAfter(element.closest('div'));
                  }else if($(element).hasClass('user_birthday')){
                    error.insertAfter(element.closest('div.input-group'));
                  }else{
                    error.insertAfter(element);
                  }
                },
            });

            var country_id = $('#user_country').val();
            $(document).on('change','#user_country',function(){
                country_id = $(this).val();
                getCountryState(country_id);
            });
            getCountryState(country_id);
            

            function getCountryState(country_id)
            {
                var STATE_URL = "{!! route('ajax.state.fetchstatebycountryid') !!}";
                $.ajax({
                    type: "POST",
                    url : STATE_URL,
                    data:{
                        country_id: country_id,
                    },
                    async: false, 
                    beforeSend: function() {
                      // setting a timeout

                    },
                    success: function(data) {
                        var user_state_dropdown = '<option value="">-- Select State --</option>';
                        if(data.status){
                            var country_state = data.data.result;
                            $.each(country_state,function(state_key, state_val){
                                if(user_selected_state == state_val.id){
                                    user_state_dropdown+='<option value="'+state_val.id+'" selected>'+state_val.title+'</option>'; 
                                }else{
                                   user_state_dropdown+='<option value="'+state_val.id+'">'+state_val.title+'</option>'; 
                                } 
                            })  
                        }
                        $('#user_state').html(user_state_dropdown);
                        state_id = $('#user_state').val();
                        getCountryStateCity(state_id);
                    },
                    error: function(xhr) { // if error occured
                      console.log("Error occured.please try again");
                    },
                    complete: function() {

                    },
                });
            }
            $(document).on('change','#user_state',function(){
                state_id = $(this).val();
                getCountryStateCity(state_id);
            });

            var state_id = $('#user_state').val();
            getCountryStateCity(state_id);
            function getCountryStateCity(state_id)
            {
                var STATE_URL = "{!! route('ajax.state.fetchstatecity') !!}";
                $.ajax({
                    type: "POST",
                    url : STATE_URL,
                    data:{
                        state_id: state_id,
                    },
                    async: false, 
                    beforeSend: function() {
                      // setting a timeout

                    },
                    success: function(data) {
                        
                        var user_city_dropdown = '<option value="">-- Select City --</option>';
                        if(data.status){
                            var country_state_city = data.data.result;
                            $.each(country_state_city,function(city_key, city_val){
                                if(user_selected_city == city_val.id){
                                    user_city_dropdown+='<option value="'+city_val.id+'" selected>'+city_val.title+'</option>';
                                }else{
                                    user_city_dropdown+='<option value="'+city_val.id+'">'+city_val.title+'</option>';
                                }
                            })  
                        }
                        $('#user_city').html(user_city_dropdown);
                    },
                    error: function(xhr) { // if error occured
                      console.log("Error occured.please try again");
                    },
                    complete: function() {

                    },
                });
            }
            
        }); 
    </script>
    <style type="text/css">
        .select2-container{
            width: 100%!important;
        }
    </style>
@stop
