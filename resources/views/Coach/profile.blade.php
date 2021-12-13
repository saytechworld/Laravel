@extends('Coach.layout.master')
@section('title', 'User Profile')
@section('parentPageTitle', 'Pages')


@section('content')
@php 
use Carbon\Carbon; 
use App\Models\Country;
use App\Models\Game;
use App\Models\Language;
use Illuminate\Support\Facades\Storage;

$country_arr = Country::where('status',1)->get();

$stripe_country_arr = Country::whereRaw("(status = 1 AND stripe_enabled = 1 AND currency_code IS NOT NULL AND ISO_code IS NOT NULL)")->get();

$game_arr = Game::with(['game_skills'])->where('status',1)->get();
$user_selected_game = $loginuser->coach_games()->groupBy('game_id')->pluck('game_id')->toArray();
$user_selected_skill = $loginuser->coach_games_skills()->groupBy('skill_id')->pluck('skill_id')->toArray();

$language_arr = Language::where('status',1)->get();
$user_selected_language = $loginuser->user_spoken_languages()->pluck('language_id')->toArray();

@endphp
<div class="row clearfix">

    <div class="col-lg-12">
        <div class="card user_profile_update_sec">
            <div class="body">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#Settings">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#gameskills">Disciplines And Experience</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#PaymentDetail">Payment Detail</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#changepassword">Change Password</a></li>
                    <li class="nav-item"><span class="btn btn-danger copy-text-btn delete-profile d-p-btn">Delete Account</span></li> 
                    <?php /*
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#payOut">Payout</a></li>
                     <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#billings">Billings</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#preferences">Preferences</a></li>
                    <span class="btn btn-danger copy-text-btn delete-profile">Delete Account</span>
                    */ ?>
                </ul>                
            </div>
            <div class="tab-content">
                
                <div class="tab-pane active setting_sec" id="Settings">
                    {!! Form::open(array('url' => route('coach.updateprofile'),'method' => 'POST', 'id' => 'coach-updateprofile', 'files' => "true")) !!}
                    <div class="body">
                        <div class="row">
                        <div class="col-sm-6 col-md-4 col-lg-4">

                            <div class="media-left">
                                <figure class="profile-pic user_image_cropiee">
                                    @if($loginuser->user_image)
                                        <img src="{!! $loginuser->user_image !!}" class="user-photo media-object img-responsive image-click" alt="User" id ="profile_user_image">
                                    @else
                                        <img src="{!! asset('images/noimage.jpg') !!}" class="user-photo media-object img-responsive" alt="User" id ="profile_user_image">
                                    @endif
                                </figure>
                                <div class="crop_imag_save">

                                </div>
                                <div class="form-group">
                                        <label for="privacy" class="control-label private-profile">Private Profile</label>
                                        <div class="toggle-switch">
                                            <label class="switch">
                                                {!! Form::checkbox('privacy', 2, $loginuser->privacy == 2 ? true : false, ['class' => "form-control"]) !!}
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                </div>
                                <div class="share-link">
                                    <input type="text" class="form-control" readonly value="{!! asset('coachlist/'. $loginuser->username) !!}" id="profile-link">
                                    <a href="#" class="copy-text-btn" onclick="copyProfileLink()">Copy</a>
                                </div>
                            </div>

                            <div class="media photo">
                                <div class="media-body">
                                    <label class="btn btn-default-dark cb-photo-edit-btn" id="btn-upload-photo"></label>
                                    <input type="file" id="filePhoto" class="sr-only" name="user_image" data-allowed-file-extensions="jpg jpeg png" accept=".png, .jpg, .jpeg">
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-8 col-lg-8">
                            <div class="bx-shade">
                                <div class="row">
                                    <div class="col-md-6">
                                      <div class="form-group">
                                            <label for="name" class="control-label">Full Name</label>
                                            {!! Form::text('name',auth()->user()->name,['class' => "form-control", 'placeholder' => "Name", 'data-rule-required' => "true", 'data-rule-maxlength' => "50"  ]) !!}
                                        </div>
                                        <div class="form-group">
                                            <label for="gender" class="control-label">Gender</label>
                                            <div>
                                                <label  class="fancy-radio">
                                                    {!! Form::radio('user_details[gender]','M', !empty($loginuser->user_details->gender) && $loginuser->user_details->gender == 'M' ?  true : false, ['data-rule-required' => "true", 'class' => 'gender_selection']) !!}
                                                    <span><i></i>Male</span>
                                                </label>
                                                <label  class="fancy-radio">
                                                    {!! Form::radio('user_details[gender]','F', !empty($loginuser->user_details->gender) && $loginuser->user_details->gender == 'F' ?  true : false,['data-rule-required' => "true", 'class' => 'gender_selection'] ) !!}
                                                    <span><i></i>Female</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="dob" class="control-label">Date of birth</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text calendar"><i class="icon-calendar"></i></span>
                                                </div>
                                                {!! Form::text('user_details[dob]',!empty($loginuser->user_details->dob) ? Carbon::parse($loginuser->user_details->dob)->format('m/d/Y') :  null,['class' => "form-control user_birthday", 'placeholder' => "Birthdate",  'data-provide' => "datepicker", 'data-date-autoclose' => "true", 'data-rule-required' => "true"]) !!}
                                            </div>
                                        </div> 
                                    </div>
                                    <div class="col-md-6">
                                       <div class="about-textarea">
                                            <label>About User</label>
                                            {!! Form::textarea('user_details[about]',!empty($loginuser->user_details->about) ? $loginuser->user_details->about : null,['class' => "form-control", 'placeholder' => "About", 'data-rule-required' => "true",  'data-rule-maxlength' => "500"  ]) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>    
                        </div>
                        </div>

                    </div>

                    <div class="body">
                        
                        <div class="bx-shade">
                            <div class="row clearfix">
                                <div class="col-lg-6 col-md-12">


                                    <div class="form-group">
                                        <label for="country" class="control-label">Country</label>
                                        <select class="form-control" name="user_details[country_id]" id="user_country" data-rule-required = "true">
                                            <option value="">Select Country</option>
                                            @if($country_arr->count() > 0)
                                                @foreach($country_arr as $country_arr_key => $country_arr_val)
                                                <option value="{{ $country_arr_val->id }}" @if(!empty($loginuser->user_details->country_id) && $loginuser->user_details->country_id == $country_arr_val->id ) selected @endif>{{ $country_arr_val->title }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                        <label for="state" class="control-label">State</label>
                                        <select class="form-control" name="user_details[state_id]" id="user_state">
                                            <option value="">Select State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                        <label for="city" class="control-label">City</label>
                                        <select class="form-control" name="user_details[city_id]" id="user_city">
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                </div>   


                                    <?php /*<div class="form-group">
                                        <label for="zip" class="control-label">Zip Code</label>
                                        {!! Form::text('zip_code',!empty($loginuser->user_details->zip_code->zip_code) ? $loginuser->user_details->zip_code->zip_code : null,['class' => "form-control", 'id'=>"zip_code", 'placeholder' => "Zip Code", 'data-rule-required'=>"true", 'data-rule-zip_code_validate' => "true"]) !!}
                                    </div> */?>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                        <label for="address_line_1" class="control-label">Address line 1</label>
                                        {!! Form::text('user_details[address_line_1]',!empty($loginuser->user_details->address_line_1) ? $loginuser->user_details->address_line_1 : null,['class' => "form-control", 'placeholder' => "Address Line 1"]) !!}
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                        <label for="address_line_2" class="control-label">Address line 2</label>
                                        {!! Form::text('user_details[address_line_2]', !empty($loginuser->user_details->address_line_2) ? $loginuser->user_details->address_line_2 : null, ['class' => "form-control", 'placeholder' => "Address Line 2"]) !!}
                                    </div>

                                </div>
                            </div>
                        </div>    
                    </div>

                    <div class="body">
                        <div class="bx-shade">
                            <div class="row clearfix">
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                         <label for="username" class="control-label">Username</label>
                                        <input type="text" class="form-control" value="{!! auth()->user()->username !!}" disabled placeholder="Username">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="form-group">
                                         <label for="email" class="control-label">Email</label>
                                        {!! Form::email('email',auth()->user()->email,['class' => "form-control", 'id' => "email", 'placeholder' => "Email", 'autocomplete' => "off"]) !!}
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">    
                                    <div class="form-group">
                                        <label for="mobile" class="control-label">Phone</label>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <select class="form-control select_mobile_code" name="user_details[mobile_code_id]" id="mobile_code" data-rule-required="true">
                                                    <option value=""> Area Code </option>
                                                    @if($country_arr->count() > 0)
                                                        @foreach($country_arr as $country_arr_key => $country_arr_val)
                                                            <option value="{{ $country_arr_val->id }}" @if(!empty($loginuser->user_details->mobile_code_id) && $loginuser->user_details->mobile_code_id == $country_arr_val->id ) selected @endif>{{ $country_arr_val->phone_code }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-md-9">
                                                {!! Form::text('user_details[mobile]', !empty($loginuser->user_details->mobile) ? $loginuser->user_details->mobile : null, ['class' => "form-control", 'id' => "mobile", 'placeholder' => "Phone Number", 'data-rule-required' => "true", 'data-rule-mobile_number_validate' => "true"]) !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>    
                        <div class="update_btn_sec">
                         <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                         <a href="#" class="btn btn-default">Cancel</a>
                        </div> 
                    </div>
                       

                    {!! Form::close() !!}
                </div>

                <div class="tab-pane gameskill_sec" id="gameskills">

                   {!! Form::open(array('url' => route('coach.updategameskill'),'method' => 'POST', 'id' => 'coach-updategameskill')) !!}
                    <div class="body">
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-12">
                                <div class="bx-shade">
                                    <div class="form-group">
                                        <label for="game" class="control-label">Sport</label>
                                        <select class="form-control" name="select_games[]" id="select_games" data-placeholder="Select a Sport" data-rule-required="true" multiple="multiple">
                                            @foreach($game_arr as $game_arr_key => $game_arr_val)
                                                <option value="{{ $game_arr_val->id }}">{{ $game_arr_val->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <div class="user_selected_game">
                                            <ul class="user_selected_game_list">

                                            </ul>
                                        </div>
                                    </div>



                                    <div class="form-group">
                                        <label for="skill" class="control-label">Discipline</label>
                                        <div class="add-skill-list">

                                        </div>
                                    </div>


                                    <div class="form-group">
                                        <label for="experience" class="control-label">Experience (Years.)</label>
                                        {!! Form::text('user_experience', !empty($loginuser->user_details->experience) ? $loginuser->user_details->experience : null, ['class' => "form-control", 'placeholder' => "Experience", 'data-rule-required' => "true", 'data-rule-user_experience_validate' => "true",'data-rule-min' => "0", 'data-rule-max' => "99"]) !!}
                                    </div>

                                    <div class="form-group">
                                        <label for="language" class="control-label">Language</label>
                                        <select class="form-control" name="select_languages[]" id="select_languages" data-placeholder="Select please language"  data-rule-required="true" multiple>
                                            @foreach($language_arr as $language_arr_key => $language_arr_val)
                                                <option value="{{ $language_arr_val->id }}" @if(in_array($language_arr_val->id, $user_selected_language)) selected @endif >{{ $language_arr_val->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="update_btn_sec">
                            <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                            <a href="#" class="btn btn-default">Cancel</a>
                        </div>  
                    </div>
                    {!! Form::close() !!}
                </div>

                <div class="tab-pane payment_detail" id="PaymentDetail">

                    <?php 
                    $stripe = Stripe::make(env('STRIPE_SECRET'));
                    $stripe->setApiKey(env('STRIPE_SECRET'));
                    $stripe_cards = array();
                    if(!empty(auth()->user()->stripe_id)){
                      $stripe_cards = $stripe->cards()->all(auth()->user()->stripe_id); 
                    }
                    ?>
                    <div class="body">
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-12">
                                
                                <div class="bx-shade">
                                    <h6>Added Cards</h6>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="table-responsive">
                                                <table class="table" id="cardList">
                                                    <thead class="thead-dark">
                                                    <tr>
                                                        <th>Card list</th>
                                                        <th></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @if(count($stripe_cards) > 0)
                                                        @foreach($stripe_cards['data'] as $stripe_card_key => $stripe_card_val)
                                                            <tr class= "user_card_element" id="card_element_{{$stripe_card_val['id']}}">
                                                                <td>••••{{$stripe_card_val['last4']}} </td>
                                                                <td class="text-right"> <span class="btn btn-sm btn-outline-danger m-r-10 delete_user_card" id="{{$stripe_card_val['id']}}"><i class="icon-trash"></i> </span></td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                                                    @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>    
                            </div>
                        </div>
                    </div>


                    {!! Form::open(array('url' => route('coach.updatepaymentdetail'),'method' => 'POST', 'id' => 'coach-updatepaymentdetail')) !!}
                    <div class="body">
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-12">
                                <div class="bx-shade">
                                <h6>Add New Card</h6>
                                    <div class="form-group">
                                         <label for="card_holder" class="control-label">Card Holder Name</label>
                                        {{ Form::text('card_holder',null,[ 'class' => "form-control card-name", "placeholder" => "Card holder name", "data-rule-required" => "true"]) }}
                                    </div>
                                    <div class="form-group">
                                         <label for="card_number" class="control-label">Card Number</label>
                                        <div class="input-group">
                                            {{ Form::text('card_number',null,[ 'class' => "form-control card-number", "placeholder" => "Card number", "data-rule-required" => "true", 'data-rule-maxlength' => 20]) }}
                                            <div class="input-group-append"> <span class="input-group-text text-muted card_icn_sec"> <i class="fa fa-cc-visa"></i> &nbsp; <i class="fa fa-cc-amex"></i> &nbsp; <i class="fa fa-cc-mastercard"></i> </span> </div>
                                          </div>
                                    </div>
                                    <div class="form-group">
                                         <label for="Expiration" class="control-label">Expiration</label>
                                        <div class="input-group">
                                             {{ Form::text('month',null,[ 'class' => "form-control card-expiry-month", "placeholder" => "MM", "data-rule-required" => "true", 'data-rule-maxlength' => 2, 'data-rule-minlength' => 2]) }}
                                              {{ Form::text('year',null,[ 'class' => "form-control card-expiry-year", "placeholder" => "YYYY", "data-rule-required" => "true", 'data-rule-maxlength' => 4, 'data-rule-minlength' => 4]) }}
                                          </div>
                                    </div>
                                    <div class="form-group">
                                         <label for="Expiration" class="control-label">CVC</label>

                                        {{ Form::text('cvc',null,[ 'class' => "form-control card-cvc", "placeholder" => "ex. 311", "data-rule-required" => "true", 'data-rule-maxlength' => 4]) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="update_btn_sec">
                            <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                            <a href="#" class="btn btn-default">Cancel</a>
                        </div> 
                    </div>
                    {!! Form::close() !!}
                </div>

                 <div class="tab-pane change_pass_sec" id="changepassword">
                    {!! Form::open(array('url' => route('coach.updatepassword'),'method' => 'POST', 'id' => 'coach-changepassword')) !!}
                    <div class="body">
                            <div class="row clearfix">
                                <div class="col-lg-6 col-md-12"> 
                                    <div class="bx-shade">
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
                            </div> 
                        <div class="update_btn_sec">
                            <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                            <a href="#" class="btn btn-default">Cancel</a>
                        </div>    
                    </div>
                    {!! Form::close() !!}
                </div>
                <?php /*
                <div class="tab-pane" id="payOut">
                    {!! Form::open(array('url' => route('coach.bankaccount'),'method' => 'POST', 'id' => 'add_bank_account')) !!}
                    <div class="body">
                        <h6>Account Information</h6>
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label for="country" class="control-label">Country</label>
                                    <select class="form-control" name="stripe_country_id" id="stripe_country_id" data-rule-required = "true">
                                        <option value="">-- Select Country --</option>
                                        @if($stripe_country_arr->count() > 0)

                                            @foreach($stripe_country_arr as $stripe_country_arr_key => $stripe_country_arr_val)
                                                <option value="{{ $stripe_country_arr_val->id }}" @if($stripe_country_arr_val->id == request()->query('stripe_country_id')) selected @endif  >{{ $stripe_country_arr_val->title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="account_number" class="control-label">IBAN</label>
                                    {!! Form::text('IBAN',null,['class' => "form-control", 'placeholder' => "IBAN", 'data-rule-required' => "true" ]) !!}
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                    </div>
                    {!! Form::close() !!}
                </div> */  ?>



            </div>
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
<?php /*
<style type="text/css">
 .add-skill-list ul, .user_selected_game ul {
    display: inline-block;
    margin-left: -10px;
    margin-right: -10px;
    margin-bottom: 20px;
    padding: 0;
}
    .add-skill-list ul li, .user_selected_game ul li{padding-right: 10px;padding-left: 10px;display: inline-block;}
 .skill-tag {
    background-color: #d2d2d2;
    display: inline-block;
    padding: 10px;
    position: relative;
    padding-right: 40px;
    border-radius: 30px;
}
.skill-tag p{margin:0;padding: 0;}
.skill-tag span {
    position: absolute;
    right: 10px;
    top: 8px;
    cursor: pointer;
    background-color: #fff;
    border-radius: 100%;
    height: 24px;
    width: 24px;
    text-align: center;
    font-size: 13px;
    line-height: 24px;
}

</style>


<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.full.min.js" ></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css" rel="Stylesheet"></link>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js" ></script>
*/ ?>
@stop

@section('blade-page-script')
    <script type="text/javascript">

        function copyProfileLink() {
            var copyText = document.getElementById("profile-link");
            copyText.select();
            document.execCommand("copy");
            toastr.success("Copied the link to clipboard");
        }
       $(function() {
            var user_selected_state = "{{ !empty($loginuser->user_details->state_id) ? $loginuser->user_details->state_id : "" }}";
            var user_selected_city = "{{ !empty($loginuser->user_details->city_id) ? $loginuser->user_details->city_id : "" }}";
            // photo upload

           $('.select_mobile_code').select2();
           $(document).off('click', '#btn-upload-photo').on('click', '#btn-upload-photo', function() {
               $(this).siblings('#filePhoto').trigger('click');
           });

           $(document).on('change', '#filePhoto', function () {
               if($(this).val()){
                   var reader = new FileReader();
                   reader.onload = function (e) {
                       let base64Data = e.target.result;
                       //Get type
                       let type = base64Data.split(';')[0].split('/')[1];
                       if($.inArray(type, ['png','jpg','jpeg']) == -1) {
                           toastr.error('invalid file types!');
                           return false;
                       }

                       //Get image size
                       var stringLength = base64Data.length - 'data:image/png;base64,'.length;
                       var sizeInBytes = 4 * Math.ceil((stringLength / 3))*0.5624896334383812;
                       var imageSize=sizeInBytes/1000000;

                       var defaultSize = "{{ env('MESSAGE_IMAGE_SIZE') }}"/1000000;

                       if(imageSize > defaultSize) {
                           toastr.error('File should be less then '+ defaultSize +' MB!');
                           return false;
                       }


                       $('#profile_user_image').remove();
                       if(!$('.user_image_cropiee').data('croppie'))
                       {
                           $('.user_image_cropiee').croppie({
                               enableExif: true,
                               enableOrientation: true,
                               viewport: {
                                   width: 200,
                                   height: 200,
                                   type: 'circle'
                               },
                               boundary: {
                                   width: 250,
                                   height: 250,
                                   margin:0
                               }
                           });
                       }
                       $('.crop_imag_save').html('<div id="SaveCropImage" class="save-crop-img"><button type="button" class="btn btn-success cb-save-profile" id="save-profile-photo">Save</button></div><div id="RotateClockwise" class="btn btn-primary img-rotate-btn" title="Rotate clockwise"><span class="fa fa-rotate-right"></span></div>');
                        $('.user_image_cropiee').croppie('bind', {
                           url: e.target.result,
                           orientation: 1,
                        }).then(function(){
                            /*console.log($(window).width());
                            if($(window).width() < 640){
                               $('.user_image_cropiee').croppie('rotate', -90); 
                            }*/
                        });
                    }
                   reader.readAsDataURL(this.files[0]);
               }
           });


            $(document).off('click','#RotateClockwise').on('click','#RotateClockwise',function(e) {
              $('.user_image_cropiee').croppie('rotate', -90);
            });

           $(document).off('click','#save-profile-photo').on('click','#save-profile-photo',function(e) {
               e.preventDefault();
               if($('.user_image_cropiee').data('croppie'))
               {
                   $('.user_image_cropiee').croppie('result', {
                       type: 'canvas',
                       size: 'viewport'
                   }).then(function (resp) {
                       var csrf_token = "<?php echo csrf_token(); ?>";
                       var ImageFormData = new FormData();
                       ImageFormData.append('_token', csrf_token);
                       ImageFormData.append('image', resp);
                       var IMAGE_POST_URL = "{!! route('coach.updateuserprofileimage') !!}";
                       $.ajax({
                           type: "POST",
                           url : IMAGE_POST_URL,
                           data: ImageFormData,
                           contentType:false,
                           processData:false,
                           cache:false,
                           beforeSend: function() {
                               $('.processing-loader').show();
                           },
                           success: function(result) {
                               $('.processing-loader').hide();
                               if(result.status){
                                   toastr.success(result.message);
                               }else{
                                   toastr.error(result.message);
                               }
                               window.location.reload(true);
                           },
                           error: function(xhr) { // if error occured
                               $('.processing-loader').hide();
                               toastr.error("Error occured.please try again");
                           },
                       });
                   });
               }else{
                   toastr.error('Please select image');
               }
           });

            jQuery.validator.addMethod("mobile_number_validate", function (value, element) {
                var phoneno_pattern = /^\d{0,20}$/;
                if(value.match(phoneno_pattern))
                {
                    return true;
                }else{
                    return false;
                }
            }, "Mobile number not valid.");

            jQuery.validator.addMethod("country_mobile_validate", function (value, element) {
                let IS_SUCCESS = true;
                if($('#mobile_code').val()){
                    var MOBILE_UNIQUE_URL = "{!! route('ajax.user.uniqueuserbymobile',auth()->id()) !!}";
                    $.ajax({
                        type: "POST",
                        url : MOBILE_UNIQUE_URL,
                        data:{
                            mobile: value,
                            mobile_code : $('#mobile_code').val()
                        },
                        async: false, 
                        beforeSend: function() {
                          // setting a timeout

                        },
                        success: function(data) {
                          if(data == true)
                          {
                            IS_SUCCESS = true;
                          }else{
                           IS_SUCCESS = false; 
                          } 
                        },
                        error: function(xhr) { // if error occured
                          console.log("Error occured.please try again");
                        },
                        complete: function() {

                        },
                    });
                }
                return IS_SUCCESS;
            }, "The mobile has already been taken.");

            jQuery.validator.addMethod("zip_code_validate", function (value, element) {
                let IS_SUCCESS = true;
                if($('#user_country').val()){
                    var ZIP_URL = "{!! route('ajax.user.validatezip') !!}";
                    $.ajax({
                        type: "POST",
                        url : ZIP_URL,
                        data:{
                            country_id: $('#user_country').val(),
                            state_id: $('#user_state').val(),
                            city_id: $('#user_city').val(),
                            zip_code : value
                        },
                        async: false,
                        beforeSend: function() {
                          // setting a timeout

                        },
                        success: function(data) {
                          if(data == true)
                          {
                            IS_SUCCESS = true;
                          }else{
                           IS_SUCCESS = false;
                          }
                        },
                        error: function(xhr) { // if error occured
                          console.log("Error occured.please try again");
                        },
                        complete: function() {

                        },
                    });
                } else {
                    toastr.error('Please Select Country');
                    return false;
                }
                return IS_SUCCESS;
            }, "The zip code is not valid.");

             

            /*

             remote: {

                             url: "{!! route('ajax.user.uniqueuserbymobile',auth()->id()) !!}",
                             type: "post",
                             data: {
                                 mobile:  function(){
                                     return $("#mobile").val();
                                 },
                                 mobile_code:  function(){
                                     return $("#mobile_code").val();
                                 }
                             }
                         }
            */



           $('#add_bank_account').validate({
               ignore : [],

               errorPlacement: function(error, element) {
                   if($(element).hasClass('user_birthday')){
                       error.insertAfter(element.closest('div.input-group'));
                   }else{
                       error.insertAfter(element);
                   }
               },
           });
            $('#coach-changepassword').validate();
            $('#coach-updatepaymentdetail').validate({
            	ignore : [],
			    errorPlacement: function(error, element) {
			        if($(element).hasClass('card-name') || $(element).hasClass('card-number') || $(element).hasClass('card-expiry-month') || $(element).hasClass('card-expiry-year') || $(element).hasClass('card-cvc') ){
			          error.insertAfter(element.closest('div.form-group'));
			        }else{
			          error.insertAfter(element);
			        }
			    }
            });

            $(document).off('click', '.delete_user_card').on('click', '.delete_user_card', function() {
                if (confirm('Are You Sure?')) {
                    if($(this).attr('id')){
                        var card = $(this).attr('id');
                        var CARD_DELETE_ROUTE = "{{ route('coach.deleteusercard') }}";
                            $.ajax({
                                type: "POST",
                                url : CARD_DELETE_ROUTE,
                                data:{
                                    card_id: card,
                                    _token : "{{ csrf_token() }}"
                                },
                                async: true, 
                                beforeSend: function() {
                                  $('.processing-loader').show();

                                },
                                success: function(data) {
                                  $('.processing-loader').hide();
                                    if(data.status)
                                    {
                                        toastr.success(data.message);
                                        $('table#cardList tr#card_element_'+card+'').remove();
                                    }else{
                                        toastr.error(data.message);
                                    }
                                },
                                error: function(xhr) { // if error occured
                                  $('.page-loader-wrapper').hide();
                                  toastr.error("Error occured.please try again");
                                },
                                complete: function() {

                                },
                            });
                    }else{
                        toastr.error('Card id not valid.');
                    }
                }
            })

            $('#coach-updateprofile').validate({
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
                        "country_mobile_validate" : true,
                     },
                     "zip_code": {
                         "zip_code_validate" : true,
                     },
                 },
                 messages : {
                     email : {
                         remote : "The email has already been taken."
                     },
                     "user_details[mobile]":{
                         remote : "The mobile has already been taken."
                     },
                     "zip_code":{
                         remote : "The zip code is not valid."
                     },
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


             $(document).on('change','#mobile_code',function () {
                if($('#mobile').val()) {
                    $('#coach-updateprofile').validate().element('#mobile');
                }
             });
             

            jQuery.validator.addMethod("user_experience_validate", function (value, element) {
                var experience_pattern = /^[0-9]\d*(\d{1,2})?$/;
                if(value.match(experience_pattern))
                {
                    return true;
                }else{
                    return false;
                }
            }, "Experience not valid.");

            $('#coach-updategameskill').validate({
                ignore : [],
                errorPlacement: function(error, element) {
                  if($(element).is("#select_games") || $(element).hasClass('game_skill_selected') || $(element).is("#select_languages") ){
                    error.insertAfter(element.siblings('span'));
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
                        var user_state_dropdown = '<option value=""> Select State </option>';
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
                        
                        var user_city_dropdown = '<option value=""> Select City </option>';
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
            function Generator() {};
            Generator.prototype.rand =  Math.floor(Math.random() * 26) + Date.now();

            Generator.prototype.getId = function() {
              return this.rand++;
            };
            var idGen =new Generator();

            var game_arr = new Array();
            @foreach($game_arr as $game_arr_key => $game_arr_val)
                var game_skill_arr = new Array();
                var encrypted_id =  idGen.getId();
                @if($game_arr_val->game_skills->count() > 0)
                    @foreach($game_arr_val->game_skills as $game_skills_key => $game_skills_val)
                        game_skill_arr.push({id : "{{ $game_skills_val->id }}", title : "{{ $game_skills_val->title }}", used:true });
                    @endforeach
                @endif
                game_arr.push({id : "{{ $game_arr_val->id }}", title : "{{ $game_arr_val->title }}", used:true, uuid:encrypted_id, skills: game_skill_arr });
            @endforeach
            

            $('#select_games').select2({
                placeholder: function(){
                    $(this).data('placeholder');
                }
            });

            $('#select_languages').select2({
                placeholder: function(){
                    $(this).data('placeholder');
                }
            });
            

            var user_selected_game = '<?php echo json_encode($user_selected_game); ?>';
            var user_selected_skill = '<?php echo json_encode($user_selected_skill); ?>';
           
            user_selected_game = jQuery.parseJSON(user_selected_game);
            user_selected_skill = jQuery.parseJSON(user_selected_skill);
            var user_selected_game_array = new Array();
            var user_selected_skill_array = new Array();

            if(user_selected_skill.length > 0)
            {
                for(var user_selected_skill_key in user_selected_skill ){
                    user_selected_skill_array[user_selected_skill_key] = user_selected_skill[user_selected_skill_key];
                }
            }

            if(user_selected_game.length > 0)
            {
                for(var user_selected_game_key in user_selected_game ){
                    user_selected_game_array[user_selected_game_key] = user_selected_game[user_selected_game_key];
                }
            }

            user_selected_game_array.forEach(function(user_selected_game_element,user_selected_game_index){
                appendGameSkillSearchBox(user_selected_game_element);
            });
            $('#select_games').select2('destroy').val(user_selected_game_array).select2();
            

            $(document).on('select2:select', '#select_games', function (e) {
                var SelectedGameItem = e.params.data.id;
                appendGameSkillSearchBox(SelectedGameItem);
            }).on('select2:unselect', '#select_games', function (e) {
                console.log( e.params.data.id);
                var SelectedGameItem = e.params.data.id;
                removeGameSkillSearchBox(SelectedGameItem);
            });

             $(document).off('click','.remove-selected-game').on('click','.remove-selected-game',function(e){
                e.preventDefault();
                var selected_game_list = $(this).closest('li.selected-game-title');
                var game_removable_id = $(selected_game_list).data('user-selected-game');
                removeGameSkillSearchBox(game_removable_id);
                var select_games_val = $('#select_games').val();
                var filter_selected_game_result = select_games_val.filter(function(select_games_val_elem){
                   return select_games_val_elem != game_removable_id; 
                });
                $('#select_games').select2('destroy').val(filter_selected_game_result).select2();
            });


            function appendGameSkillSearchBox(game_id)
            {
                var filtered_game_arr = game_arr.filter(function(game_arr_obj) {
                                      return game_arr_obj['id'] == game_id;
                                    });
                if(filtered_game_arr.length > 0){
                    if(filtered_game_arr[0]['used']){
                        var game_skill_select2='<select name="game_skill['+game_id+'][skill_id][]" multiple="true" class="game_skill_selected" data-skill-selection="'+filtered_game_arr[0]['uuid']+'" data-rule-required="true">';
                        var selected_skill_li = '';
                        $.each(filtered_game_arr[0]['skills'],function(game_skiil_arr_index, game_skill_arr_element){
                            if ( user_selected_skill_array.length > 0 &&  $.inArray(parseInt(game_skill_arr_element.id), user_selected_skill_array) != -1)
                            {
                                game_skill_select2+='<option value="'+game_skill_arr_element.id+'" selected>'+game_skill_arr_element.title+'</option>';
                                selected_skill_li+='<li class="game-skill-title" data-skill-id="'+game_skill_arr_element.id+'"><div class="skill-tag"><p>'+game_skill_arr_element.title+'</p> <span class="remove-game-skill-title"><i class="icon-close"></i></span></div></li>';
                            }else{
                                game_skill_select2+='<option value="'+game_skill_arr_element.id+'"   >'+game_skill_arr_element.title+'</option>';
                            }
                        });
                        game_skill_select2+='</select>';
                        $('.user_selected_game ul.user_selected_game_list').append('<li class="selected-game-title" data-selected-game="'+filtered_game_arr[0]['uuid']+'" data-user-selected-game="'+game_id+'"><div class="skill-tag"><p>'+filtered_game_arr[0]['title']+'</p> <span class="remove-selected-game"><i class="icon-close"></i></span></div></li>');
                        var skill_game_html = '<div class="select-game-skill" id="'+filtered_game_arr[0]['uuid']+'" data-selected-game-skill="'+filtered_game_arr[0]['uuid']+'"><input type="hidden" name="game_skill['+game_id+'][game_id]" value="'+game_id+'"/><div class="form-group"><label>You have selected this Sport: '+filtered_game_arr[0]['title']+'</label>'+game_skill_select2+'</div><ul class="skill_list_item" data-skill-list="'+filtered_game_arr[0]['uuid']+'">'+selected_skill_li+'</ul></div>';

                        $('.add-skill-list').append(skill_game_html);
                         $('.game_skill_selected').select2({
                            multiple: true,
                            allowClear: false,
                            placeholder: 'Please select discipline',
                            serach:true,
                        });
                        

                        game_arr.forEach(function(game_arr_element,game_arr_index){
                            if(game_arr[game_arr_index]['id'] == filtered_game_arr[0]['id']){
                              game_arr[game_arr_index]['used'] = false;
                            }
                        });
                    }else{
                        toastr.error('This game is already selected.');
                        return false;
                    }
                }else{
                    toastr.error('This game not available in database.');
                    return false;
                }
            }

            function removeGameSkillSearchBox(game_id)
            {
                var filtered_game_arr = game_arr.filter(function(game_arr_obj) {
                                      return game_arr_obj['id'] == game_id;
                                    }); 
                if(filtered_game_arr.length > 0){
                    $('.user_selected_game ul.user_selected_game_list li.selected-game-title[data-selected-game='+filtered_game_arr[0]['uuid']+']').remove();
                    $('.add-skill-list .select-game-skill[data-selected-game-skill='+filtered_game_arr[0]['uuid']+']').remove();
                    game_arr.forEach(function(game_arr_element,game_arr_index){
                        if(game_arr[game_arr_index]['id'] == filtered_game_arr[0]['id']){
                          game_arr[game_arr_index]['used'] = true;
                        }
                    });
                }else{
                    toastr.error('This game not available in database.');
                    return false;
                }

            }

            $(document).on('select2:select', '.game_skill_selected', function (e) {
                //Gets the last selected item
                var lastSelectedItem = e.params.data.id;
                 //Gets parent
                var closest_target = $(this).closest('div.select-game-skill');
                var closest_target_id = $(closest_target).attr('id');
                $('#'+closest_target_id+' ul.skill_list_item').append('<li class="game-skill-title" data-skill-id="'+lastSelectedItem+'"><div class="skill-tag"><p>'+e.params.data.text+'</p> <span class="remove-game-skill-title"><i class="icon-close"></i></span></div></li>');
            }).on('select2:unselect', '.game_skill_selected', function (e) {
                //Gets the last selected item
                var lastSelectedItem = e.params.data.id;
                 //Gets parent
                var closest_target = $(this).closest('div.select-game-skill');
                var closest_target_id = $(closest_target).attr('id');
                $('#'+closest_target_id+' ul.skill_list_item li.game-skill-title[data-skill-id='+lastSelectedItem+']').remove();
            });
           
            $(document).off('click','.remove-game-skill-title').on('click','.remove-game-skill-title',function(e){
                e.preventDefault();
                var game_skill_ul = $(this).closest('ul.skill_list_item');
                var game_skill_li = $(this).closest('li.game-skill-title');
                var skill_id = $(game_skill_li).data('skill-id');
                var game_id = $(game_skill_ul).data('skill-list');
                var selected_val = $('.game_skill_selected[data-skill-selection='+game_id+']').val();

                var filter_selected_result = selected_val.filter(function(elem){
                   return elem != skill_id; 
                });
                $('.game_skill_selected[data-skill-selection='+game_id+']').select2('destroy').val(filter_selected_result).select2();
                $(game_skill_li).remove();
            });

           $('.select_user_data').select2();

           $(document).on('click','.image-click',function(e){
               $("#image-popup").html('')
               $('#myModal').modal('show');
               let url = $(this).attr('src');
               var image = document.createElement('img');
               image.setAttribute('src', url);
               document.getElementById('image-popup').appendChild(image);
           });

           $(document).off('click','.delete-profile').on('click','.delete-profile', function(e){
               swal({
                   title: "Are you sure?",
                   icon: "warning",
                   buttons: true,
                   dangerMode: true,
               })
               .then((willDelete) => {
                   if (willDelete) {
                       var DELETE_ROUTE = "{{ route('coach.deleteuser') }}";
                       $.ajax({
                           type: "POST",
                           url : DELETE_ROUTE,
                           data:{
                               _token : "{{ csrf_token() }}"
                           },
                           async: true,
                           beforeSend: function() {
                               $('.processing-loader').show();

                           },
                           success: function(data) {
                               $('.processing-loader').hide();
                               if(data.status) {
                                   toastr.success(data.message);
                                   window.location.reload();
                               }else{
                                   toastr.error(data.message);
                               }
                           },
                           error: function(xhr) { // if error occured
                               $('.page-loader-wrapper').hide();
                               toastr.error("Error occured.please try again");
                           },
                           complete: function() {

                           },
                       });
                   }
               });
           });
        }); 
    </script>
    <style type="text/css">
        .select2-container{
            width: 100%!important;
        }
        .image-click {
            cursor: pointer;
        }
    </style>
@stop
