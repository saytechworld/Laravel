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
                                    <label for="account_holder_name" class="control-label">Full Name</label>
                                    {!! Form::text('account_holder_name',null,['class' => "form-control", 'placeholder' => "Full Name", 'data-rule-required' => "true", 'data-rule-maxlength' => 50 ]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="account_number" class="control-label">IBAN</label>
                                    {!! Form::text('IBAN',null,['class' => "form-control", 'placeholder' => "IBAN", 'data-rule-required' => "true" ]) !!}
                                </div>

                                 <?php /*
                                <div class="form-group">
                                    <label for="name" class="control-label">Routing Number</label>
                                    {!! Form::text('routing_number',null,['class' => "form-control", 'placeholder' => "Routing Number", 'data-rule-required' => "true" ]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="gender" class="control-label">Account Number</label>
                                    {!! Form::text('account_number',null,['class' => "form-control", 'placeholder' => "Amount Number", 'data-rule-required' => "true" ]) !!}
                                </div>

                                <div class="form-group">
                                    <label for="gender" class="control-label">Account Type</label>
                                    <select name="account_type" class="form-control"></select>
                                </div>
                                <div class="form-group">
                                    <label for="gender" class="control-label">First Name</label>
                                    {!! Form::text('fname',null,['class' => "form-control", 'placeholder' => "First Name", 'data-rule-required' => "true" ]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="gender" class="control-label">Last Name</label>
                                    {!! Form::text('lname',null,['class' => "form-control", 'placeholder' => "Last Name", 'data-rule-required' => "true" ]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="dob" class="control-label">Date of birth</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="icon-calendar"></i></span>
                                        </div>
                                        {!! Form::text('dob', null,['class' => "form-control user_birthday", 'placeholder' => "Birthdate",  'data-provide' => "datepicker", 'data-date-autoclose' => "true", 'data-rule-required' => "true"]) !!}
                                    </div>
                                </div>
                                */ ?>
                            </div>
                            <?php /*
                            <div class="col-lg-6 col-md-12">

                                <div class="form-group">
                                    <label for="country" class="control-label">Country</label>
                                    <select class="form-control" name="country_id" id="user_country" data-rule-required = "true">
                                        <option value="">-- Select Country --</option>
                                        @if($country_arr->count() > 0)
                                            @foreach($country_arr as $country_arr_key => $country_arr_val)
                                                <option value="{{ $country_arr_val->id }}" >{{ $country_arr_val->title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="state" class="control-label">State</label>
                                    <select class="form-control" name="state_id" id="user_state">
                                        <option value="">-- Select State --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="city" class="control-label">City</label>
                                    <select class="form-control" name="city_id" id="user_city">
                                        <option value="">-- Select City --</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="address_line_1" class="control-label">Address line 1</label>
                                    {!! Form::text('address_line_1', null,['class' => "form-control", 'placeholder' => "Address Line 1"]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="address_line_2" class="control-label">Address line 2</label>
                                    {!! Form::text('address_line_2',  null, ['class' => "form-control", 'placeholder' => "Address Line 2"]) !!}
                                </div>
                                <div class="form-group">
                                    <label for="zip" class="control-label">Zip Code</label>
                                    {!! Form::text('zip',  null, ['class' => "form-control", 'placeholder' => "Zip Code"]) !!}
                                </div>

                            </div> */ ?>
                        </div>
                        <button type="submit" class="btn btn-primary">Update</button> &nbsp;&nbsp;
                    </div>
                    {!! Form::close() !!}
                </div>