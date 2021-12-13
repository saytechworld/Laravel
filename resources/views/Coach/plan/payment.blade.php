@extends('Coach.layout.master')
@section('title', 'Payment')
@section('parentPageTitle', 'Payment')

<?php 

$stripe = Stripe::make(env('STRIPE_SECRET'));
$stripe->setApiKey(env('STRIPE_SECRET'));
$cards = array();
if(!empty(auth()->user()->stripe_id)){
  $cards = $stripe->cards()->all(auth()->user()->stripe_id); 
}
?>

@section('content')
<div class="row clearfix">
  <div class="col-12">
    <div class="card">
      <div class="header">
        <h2>Select Payment Method</h2>
      </div>

      <div class="body">
        <div class="col-12">
          <div class="payment_error"></div>  
        </div>

        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active show" data-toggle="tab" href="#Home-withicon"><i class="fa fa-credit-card"></i> Credit Card </a></li>
         <?php /*<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#Profile-withicon"><i class="fa fa-paypal"></i> Paypal</a></li> */ ?>
        </ul>
        <div class="tab-content">
          <div class="tab-pane show active" id="Home-withicon">
            {!! Form::open(array('url' => route('coach.plan.payment.store',['plan_id'=> encrypt($plan->id),'plan_price_id'=> encrypt($plan->id)]),'method' => 'POST', 'autocomplete'=> 'off', 'files' => "true", 'data-cc-on-file' => "false", 'data-stripe-publishable-key' => "{{ env('STRIPE_KEY') }}", 'id' => "payment-stripe-form")) !!}

            <div class="row clearfix">
              <div class="col-lg-12">
                <div class="row">
                  <div class="col-sm-12">
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                        <thead>
                        <thead>
                        <tr>
                          <th>Validity</th>
                          <th>Price</th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr>
                              <td>{{$plan_price->validity}} Month</td>
                              <td>{{$plan_price->price}} <i class="fa fa-eur"></i></td>
                            </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row clearfix">
              <div class="col-lg-6">
                <div class="row">
                  <div class="col-sm-12">
                    <div class="table-responsive">
                      <table class="table">
                        <thead class="thead-dark">
                        <tr>
                          <th class="text-left"></th>
                          <th>Card list</th>
                          <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(count($cards) > 0)
                        @foreach($cards['data'] as $card)
                        <tr>
                          <td class="text-left"><input type="radio" name="card" class="card_id card_info_selection" value="{{$card['id']}}" onchange="handleCardChange('S', '{{$card['id']}}')" data-rule-required="true"> </td>
                          <td>••••{{$card['last4']}} </td>
                        </tr>
                        @endforeach
                        @endif
                        <tr>
                          <td class="text-left"><input type="radio" name="card" onchange="handleCardChange('N')" data-rule-required="true" class="card_info_selection"></td>
                          <td>New Card </td>
                          <td class="text-right"><div class="card_info_error"></div></td>
                        </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="new_card">

                </div>
              </div>

              <div class="col-lg-6">
                <div class="row">
                  <div class="col-12">
                    <div class="table-responsive">
                      <table class="table">
                        <thead class="thead-dark">
                          <tr>
                            <th>Plan Description</th>
                            <th class="text-right">Amount</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                            $service_tax = (env('SERVICE_TAX') / 100) * $plan_price->price;
                            $total_price = $plan_price->price + $service_tax;
                          ?>
                          <tr>
                            <td class="text-right"><p>Plan Amount:</p>
                              <p>Service Tax:</p>
                            </td>
                            <td class="text-right"><p><strong class="plan-amount">{!! $plan_price->price !!} <i class="fa fa-eur"></i></strong></p>
                              <p><strong class="plan-tax">{{ round($service_tax, 2) }} <i class="fa fa-eur"></i></strong></p>
                            </td>
                          </tr>
                          <tr>
                            <td class="text-right"><h6>Total:</h6></td>
                            <td class="text-danger text-right"><h6 class="plan-total">{!! round($total_price,2) !!} <i class="fa fa-eur"></i></h6></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="text-right"><button class="subscribe btn btn-primary" type="submit">Make Payment</button></div>             
              </div>
            </div>
            {!! Form::close() !!} 
          </div>
          <div class="tab-pane" id="Profile-withicon">
            <p><strong>Note:</strong> After payment via PayPal's secure checkout, we will send you a link to download your files. PayPal accepts</p>
            {!! Form::open(array('url' => route('coach.plan.payment.paypal',['plan_id'=> encrypt($plan->id),'plan_price_id'=> encrypt($plan->id)]),'method' => 'POST', 'autocomplete'=> 'off', 'data-cc-on-file' => "false", 'id' => "payment-paypal-form")) !!}
            <div class="row clearfix">
              <div class="col-lg-12">
                <div class="row">
                  <div class="col-sm-12">
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                        <thead>
                        <thead>
                        <tr>
                          <th>Validity</th>
                          <th>Price</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                          <td>{{$plan_price->validity}} Month</td>
                          <td>{{$plan_price->price}}  <i class="fa fa-eur"></i></td>
                        </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-12">
                <div class="table-responsive">
                  <table class="table">
                    <thead class="thead-dark">
                    <tr>
                      <th>Plan Description</th>
                      <th class="text-right">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $service_tax = (env('SERVICE_TAX') / 100) * $plan_price->price;
                    $total_price = $plan_price->price + $service_tax;
                    ?>
                    <tr>
                      <td class="col-md-9">Plan Amount</td>
                      <td class="col-md-3 text-right">{!! $plan_price->price !!} <i class="fa fa-eur"></i></td>
                    </tr>
                    <tr>
                      <td class="col-md-9">Service Tax</td>
                      <td class="col-md-3 text-right">{{ round($service_tax, 2) }} <i class="fa fa-eur"></i></td>
                    </tr>
                    <tr>
                      <td class="text-right"><h6>Total:</h6></td>
                      <td class="text-danger text-right"><h6>{!! round($total_price,2) !!} <i class="fa fa-eur"></i></h6></td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="text-right">
              <input class="subscribe btn btn-primary" type="submit" value="Checkout with PayPal">
            </div>
            {!! Form::close() !!}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@stop 
@section('blade-page-script')
<script src="https://js.stripe.com/v2/"></script>

<script type="text/javascript">
  Stripe.setPublishableKey("{{ env('STRIPE_KEY') }}");
  function handleCardChange(type, id=null) {
    let html = '<div class="row">\n' +
            '                  <div class="col-sm-12 col-md-6 col-lg-6">\n' +
            '                    <div class="form-group">\n' +
            '                      <label for="username">Full name (on the card)</label>\n' +
            '                      <input type="text" name="card_holder" class="form-control card-name" placeholder="Card holder name" data-rule-required="true">\n' +
            '                    </div>\n' +
            '                  </div>\n' +
            '                  <div class="col-sm-12 col-md-6 col-lg-6">\n' +
            '                    <div class="form-group">\n' +
            '                      <label for="cardNumber">Card number</label>\n' +
            '                      <div class="input-group">\n' +
            '                        <input type="text" name="card_number" class="form-control card-number" placeholder="Card number" data-rule-required="true" data-rule-maxlength="20">\n' +
            '                        <div class="input-group-append"> <span class="input-group-text text-muted"> <i class="fa fa-cc-visa"></i> &nbsp; <i class="fa fa-cc-amex"></i> &nbsp; <i class="fa fa-cc-mastercard"></i> </span> </div>\n' +
            '                      </div>\n' +
            '                    </div>\n' +
            '                  </div>\n' +
            '                  <div class="col-sm-8 col-md-8 col-lg-8">\n' +
            '                    <div class="form-group">\n' +
            '                      <label><span>Expiration</span> </label>\n' +
            '                      <div class="input-group">\n' +
            '                        <input type="text" name="month" class="form-control card-expiry-month" placeholder="MM" data-rule-required="true" data-rule-maxlength="2" data-rule-minlength="2">\n' +
            '                        <input type="text" name="year" class="form-control card-expiry-year" placeholder="YYYY" data-rule-required="true" data-rule-maxlength="4" data-rule-minlength="4">\n' +
            '                      </div>\n' +
            '                    </div>\n' +
            '                  </div>\n' +
            '                  <div class="col-sm-4 col-md-4 col-lg-4">\n' +
            '                    <div class="form-group">\n' +
            '                      <label data-toggle="tooltip" title="" data-original-title="3 digits code on back side of the card">CVV <i class="fa fa-question-circle"></i></label>\n' +
            '                      <input type="text" name="cvc" class="form-control card-cvc" placeholder="ex. 311" data-rule-required="true" data-rule-maxlength="4">\n' +
            '                    </div>\n' +
            '                  </div>\n' +
            '<div class="col-6"><div class="form-group"><div class="input-group"><div class="fancy-checkbox"><label><input type="checkbox" name="saved_card" value="1" checked="checked"><span>Save this card</span></label></div></div></div></div></div>';

    $('.new_card').html('');
    if (type == 'S') {
    } else {
      $('.new_card').html(html)
    }
  };

  $(document).ready(function(){

    $('#payment-stripe-form').validate({
      ignore : [],
      errorPlacement: function(error, element) {
        if($(element).hasClass('card-name') || $(element).hasClass('card-number') || $(element).hasClass('card-expiry-month') || $(element).hasClass('card-expiry-year') || $(element).hasClass('card-cvc') ){
          error.insertAfter(element.closest('div.form-group'));
        }else if($(element).hasClass('card_info_selection')){
          error.insertAfter(element.closest('tbody'));
        }else{
          error.insertAfter(element);
        }
      },
      submitHandler: function(form) {
        var checkedValue = $(".card_id:checked").val();
        if(checkedValue) {
          var formCard$ = $("#payment-stripe-form");
          formCard$.append("<input type='hidden' name='card_id' value='" + checkedValue + "' />");
          formCard$.append("<input type='hidden' name='type' value='S' />");
          formCard$.get(0).submit();
        } else {
          Stripe.createToken({
            number: $('.card-number').val(),
            cvc: $('.card-cvc').val(),
            exp_month: $('.card-expiry-month').val(),
            exp_year: $('.card-expiry-year').val()
          }, stripeResponseHandler);
        }
      }
    })
    
    // Callback to handle the response from stripe
    function stripeResponseHandler(status, response) {
      if (response.error) {
        console.log(response);
        // Display the errors on the form
        $(".payment_error").html('<label class="error">'+response.error.message+'</label>');
      } else {
        $(".payment_error").html('');
        var form$ = $("#payment-stripe-form");
        // Get token id
        var token = response.id;
        // Insert the token into the form
        form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
        form$.append("<input type='hidden' name='type' value='N' />");
        // Submit form to the server
        form$.get(0).submit();
      }
    }


  })
</script>
@endsection