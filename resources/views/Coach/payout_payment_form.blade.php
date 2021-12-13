@extends('Coach.layout.master')
@section('title', 'Withdrawal ')
@section('parentPageTitle', 'Pages')


@section('content')
@php
use App\Models\Country;
use Stripe as StripePhp;

StripePhp\Stripe::setApiKey(env('STRIPE_SECRET'));

if (!empty($loginuser->stripe_account_id)) {

    $account = StripePhp\Account::retrieve(
        $loginuser->stripe_account_id
    );
};


$stripe_country_arr = Country::whereRaw("(status = 1 AND stripe_enabled = 1 AND currency_code IS NOT NULL AND ISO_code IS NOT NULL)")->pluck('title', 'ISO_code');
@endphp

@if(isset($account) && !empty($account))
    <div class="body b_border_frm">
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">

                <div class="header">
                    <h6>Account</h6>
                    <ul class="header-dropdown remaining-bal-sec">
                        <strong>Withdrawable Balance</strong> : {{ $loginuser->remaining_balance }} €
                        @if($loginuser->non_withdrawable_amount > 0)
                            <p style="color: #ff0000">Some funds are on the way. The amount is added in your balance in 7 days after completion of session.</p>
                        @endif
                        @if($account['individual']['verification']['status'] == 'verified')
                            <button type="button" class="btn btn-info btn-lg withdraw-btn" id="withdraw_request" data-tggle="modal" data-target="#withdrawalAmount">Withdraw Request</button>
                        @endif
                    </ul>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <tr class= "user_card_element">
                                        <td>******{{ $account['external_accounts']['data'][0]['last4'] }} </td>
                                        <td>{{ $account['individual']['verification']['status'] }} </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if($account['individual']['verification']['status'] == 'unverified')
        {!! Form::open(array('url' => route('coach.bankaccount.update'),'method' => 'POST', 'id' => 'bank_account_update' ,'files' => 'true')) !!}
        <div class="body b_border_frm">
            <h6>Update Verification Detail</h6>
            <div class="row clearfix">
                <div class="col-lg-6 col-md-12">
                    <div class="form-group">
                        <label for="additional_document_front" class="control-label">Additional Document Front <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="A document showing address, either a passport, local ID card, or utility bill from a well known utility company." ></span></label>
                        <input type="file" name="additional_document_front" class="form-control" data-rule-required="true" accept=".png, .jpg, .jpeg">
                    </div>

                    <div class="form-group">
                        <label for="additional_document_back" class="control-label">Additional Document Back <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="A document showing address, either a passport, local ID card, or utility bill from a well known utility company." ></span></label>
                        <input type="file" name="additional_document_back" class="form-control" data-rule-required="true" accept=".png, .jpg, .jpeg">
                    </div>
                </div>

                <div class="col-lg-6 col-md-12">

                    <div class="form-group">
                        <label for="document_front" class="control-label">Photo ID Front <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="Acceptable document vary by country although passport scan is always acceptable and preferred.  of both the front and back are usually required for government-issued IDs and driver licenses. Files need to be JPEGs or PNGs smaller then 10 MB. We can't verify PDFs . Files should be in color, be rotate with the image right-side up, and have al information clearly legible" ></span></label>
                        <input type="file" name="document_front" class="form-control" data-rule-required="true" accept=".png, .jpg, .jpeg">
                    </div>

                    <div class="form-group">
                        <label for="document_back" class="control-label">Photo ID Back <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="Acceptable document vary by country although passport scan is always acceptable and preferred.  of both the front and back are usually required for government-issued IDs and driver licenses. Files need to be JPEGs or PNGs smaller then 10 MB. We can't verify PDFs . Files should be in color, be rotate with the image right-side up, and have al information clearly legible" ></span></label>
                        <input type="file" name="document_back" class="form-control" data-rule-required="true" accept=".png, .jpg, .jpeg">
                    </div>
                </div>
            </div>
            <!-- Tips content -->
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
        {!! Form::close() !!}

    @else
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12">
                <h6>Withdrawal History</h6>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Transaction Id</th>
                                    <th>Transfer Id</th>
                                    <th>Amount</th>
                                    <th>Created At</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($loginuser->withdrawalAmount as $withdrawal)
                                    <tr>
                                        <td>{{ $withdrawal->transaction_id }} </td>
                                        <td>{{ $withdrawal->transfer_id }} </td>
                                        <td>{{ $withdrawal->amount }} </td>
                                        <td>{{ $withdrawal->created_at }} </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endif
@else
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card b_border_frm">
            <div class="header">
                <h2>Add Bank Account</h2>
            </div>
            <div class="body">
                <div class="col-lg-12 col-md-12">
                    {!! Form::open(array('url' => route('coach.bankaccount'),'method' => 'POST', 'id' => 'add_bank_account' ,'files' => 'true')) !!}
                    <div class="body">
                        <div class="row clearfix">
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label for="account_number" class="control-label">IBAN</label>
                                    {!! Form::text('IBAN',null,['class' => "form-control", 'placeholder' => "IBAN", 'data-rule-required' => "true", 'tabindex' => "1" ]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="country" class="control-label">First Name</label>
                                    {!! Form::text('individual[first_name]',null,['class' => "form-control", 'placeholder' => "First Name", 'data-rule-required' => "true", 'tabindex' => "3" ]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="country" class="control-label">Date Of Birth</label>
                                    {!! Form::text('individual[dob]', null,['class' => "form-control user_birthday", 'placeholder' => "DOB",  'data-provide' => "datepicker", 'data-date-autoclose' => "true", 'data-rule-required' => "true" , 'tabindex' => "5"]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="country" class="control-label">Gender</label>
                                    <select class="form-control" name="individual[gender]" data-rule-required = "true" tabindex="7">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="state" class="control-label">State</label>
                                    {!! Form::text('individual[address][state]',null,['class' => "form-control", 'placeholder' => "State", 'data-rule-required'=>"true" , 'tabindex' => "9"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="zip" class="control-label">Postal Code</label>
                                    {!! Form::text('individual[address][postal_code]',null,['class' => "form-control", 'placeholder' => "Postal Code", 'data-rule-required'=>"true" , 'tabindex' => "11"]) !!}
                                </div>


                                <div class="form-group">
                                    <label for="address_line_2" class="control-label">Address line 2</label>
                                    {!! Form::text('individual[address][line2]', null, ['class' => "form-control", 'placeholder' => "Address Line 2"  , 'tabindex' => "13"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="document_back" class="control-label">Photo ID Back <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="Acceptable document vary by country although passport scan is always acceptable and preferred.  of both the front and back are usually required for government-issued IDs and driver licenses. Files need to be JPEGs or PNGs smaller then 10 MB. We can't verify PDFs . Files should be in color, be rotate with the image right-side up, and have al information clearly legible" ></span></label>
                                    {!! Form::file('document_back', ['class' => "form-control", 'id' => "photo_id_back", 'data-rule-required' => "true", 'accept' => ".png, .jpg, .jpeg" ,'data-allowed-file-extensions'=>"jpg jpeg png" , 'tabindex' => "15"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="additional_document_back" class="control-label">Additional Document Back <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="A document showing address, either a passport, local ID card, or utility bill from a well known utility company." ></span></label>
                                    {!! Form::file('additional_document_back', ['class' => "form-control", 'id' => "additional_doc_back", 'data-rule-required' => "true", 'accept' => ".png, .jpg, .jpeg" ,'data-allowed-file-extensions'=>"jpg jpeg png" , 'tabindex' => "17"]) !!}
                                </div>

                            </div>

                            <div class="col-lg-6 col-md-12">

                                <div class="form-group">
                                    <label for="country" class="control-label">Email</label>
                                    {!! Form::text('individual[email]',null,['class' => "form-control", 'placeholder' => "Email", 'data-rule-required' => "true" , 'tabindex' => "2"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="country" class="control-label">Last Name</label>
                                    {!! Form::text('individual[last_name]',null,['class' => "form-control", 'placeholder' => "Last Name", 'data-rule-required' => "true", 'tabindex' => "4" ]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="country" class="control-label">Phone</label>
                                    {!! Form::text('individual[phone]',null,['class' => "form-control", 'placeholder' => "Phone (Enter phone number with country code)", 'data-rule-required' => "true" , 'tabindex' => "6"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="country" class="control-label">Country</label>
                                        {{ Form::select('individual[address][country]', $stripe_country_arr, request()->query('individual[address][country]'), ['class' => "form-control",  'placeholder' => "Select Country" , 'tabindex' => "8"]) }}
                                </div>

                                <div class="form-group">
                                    <label for="city" class="control-label">City</label>
                                    {!! Form::text('individual[address][city]',null,['class' => "form-control",'placeholder' => "City", 'data-rule-required'=>"true" , 'tabindex' => "10"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="address_line_1" class="control-label">Address line 1</label>
                                    {!! Form::text('individual[address][line1]',null,['class' => "form-control", 'placeholder' => "Address Line 1", 'data-rule-required' => "true" , 'tabindex' => "12"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="document_front" class="control-label">Photo ID Front <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="Acceptable document vary by country although passport scan is always acceptable and preferred.  of both the front and back are usually required for government-issued IDs and driver licenses. Files need to be JPEGs or PNGs smaller then 10 MB. We can't verify PDFs . Files should be in color, be rotate with the image right-side up, and have al information clearly legible" ></span></label>
                                    {!! Form::file('document_front', ['class' => "form-control", 'data-rule-required' => "true", 'accept' => ".png, .jpg, .jpeg" ,'data-allowed-file-extensions'=>"jpg jpeg png",'id'=>"photo_id_front"  , 'tabindex' => "14"]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="additional_document_front" class="control-label">Additional Document Front <span class="glyphicon glyphicon-question-sign append text-info tip" data-toggle="tooltip" data-placement="top" title="A document showing address, either a passport, local ID card, or utility bill from a well known utility company." ></span></label>
                                    {!! Form::file('additional_document_front', ['class' => "form-control", 'id' => "additional_doc_front", 'data-rule-required' => "true", 'accept' => ".png, .jpg, .jpeg" ,'data-allowed-file-extensions'=>"jpg jpeg png" , 'tabindex' => "16"]) !!}
                                </div>

                            </div>
                        </div>
                        <!-- Tips content -->
                        <button type="submit" class="btn btn-primary" tabindex="18">Update</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endif
<div class="modal fade add_event_category_modal" id="withdrawalAmount" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            {!! Form::open(array('url' => route('coach.payout'),'method' => 'POST', 'id' => 'payout_form')) !!}
            <div class="modal-body">
                <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                <div class="form-group">
                    <?php $minimum_amount = config('staging_live_config.MINIMUM_PAYMENT') ?>
                    <div class="form-line">
                        {!! Form::text('amount', null, ['class' => "form-control", 'placeholder' => "Amount", 'data-rule-required' => 'true', 'data-rule-max' => "$loginuser->remaining_balance"]) !!}
                    </div>
                        <p> <strong>Remaining Balance</strong> :{{ $loginuser->remaining_balance }} €</p>
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Withdraw</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
@stop

@section('blade-page-script')
    <script type="text/javascript">
       $(function() {

           var photo_id_front;
           var photo_id_back;
           var additional_doc_front;
           var additional_doc_back;

           $('#payout_form').validate({
               ignore : [],
           });

           $(document).off('click','#withdraw_request').on('click','#withdraw_request', function(e){
               $('#withdrawalAmount').modal();
           });

           $(document).ready(function(){
               $('[data-toggle="tooltip"]').tooltip();
           });

           $(document).on('change','#photo_id_front', function(event){
              compress(event, 1);
           });
           $(document).on('change','#photo_id_back', function(event){
               compress(event, 2);
           });
           $(document).on('change','#additional_doc_front', function(event){
                compress(event, 3);
           });
           $(document).on('change','#additional_doc_back', function(event){
               compress(event, 4);
           });

           function compress(e, variable_name) {
               const fileName = e.target.files[0].name;
               const reader = new FileReader();
               reader.readAsDataURL(e.target.files[0]);
               reader.onload = event => {
                   const img = new Image();
                   img.src = event.target.result;
                   img.onload = () => {
                       const elem = document.createElement('canvas');
                       const width = Math.min(800, img.width);
                       const scaleFactor = width / img.width;
                       elem.width = width;
                       elem.height = img.height * scaleFactor;

                       const ctx = elem.getContext('2d');
                       ctx.drawImage(img, 0, 0, width, img.height * scaleFactor);
                       ctx.canvas.toBlob((blob) => {
                           const file = new File([blob], fileName, {
                               type: 'image/jpeg',
                               lastModified: Date.now()
                           });
                           returnValue(file, variable_name);
                       }, 'image/jpeg', 1);
                   }
               };
           }


           function  returnValue(file, variable_name) {
                if (variable_name == 1) {
                    photo_id_front = file;
                } else if(variable_name == 2) {
                    photo_id_back = file;
                } else if (variable_name == 3) {
                    additional_doc_front = file;
                } else {
                    additional_doc_back = file;
                }
           }

           $('#add_bank_account').validate({
               ignore : [],

               errorPlacement: function(error, element) {
                   if($(element).hasClass('user_birthday')){
                       error.insertAfter(element.closest('div.input-group'));
                   }else{
                       error.insertAfter(element);
                   }
               },
               submitHandler: function (form) {
                   var formData = new FormData(form);
                   formData.append('document_front', photo_id_front);
                   formData.append('document_back', photo_id_back);
                   formData.append('additional_document_front', additional_doc_front);
                   formData.append('additional_document_back', additional_doc_back);

                   $('.processing-loader').show();
                   $.ajax({
                       url :  "{{ route('coach.bankaccount') }}",
                       type : 'POST',
                       data : formData,
                       processData: false,  // tell jQuery not to process the data
                       contentType: false,  // tell jQuery not to set contentType
                       beforeSend: function () {
                           //
                       },
                       success: function (data) {
                           $('.processing-loader').hide();
                           if (data.status) {
                               toastr.success(data.message);
                               window.location.reload();
                           } else {
                               toastr.error(data.message);
                           }
                       },
                       error: function (xhr) { // if error occured
                           $('.processing-loader').hide();
                           toastr.error("Error occurred.please try again");
                       },
                   });
               }
           });
        });
    </script>
@stop
