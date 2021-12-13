@extends('Coach.layout.master')
@section('title', 'Subscription')
@section('parentPageTitle', 'Subscription')
@section('content')
<div class="row clearfix">

  @if($plans->count() > 0 )  
  @foreach($plans as $plans_key => $plans_val)
  <div class="col-lg-4 col-md-12">
    <div class="card pricing2">
      <div class="body">
        <div class="pricing-plan"> <img src="{{ asset('assets/img/paper-plane.png')  }}" alt="" class="pricing-img">
          <h2 class="pricing-header">{!! $plans_val->title !!}</h2>
          <ul class="pricing-features">
            <li>{!! $plans_val->description !!}</li>
          </ul>
          <div class="pricing">
            <select name="plan_price_id" class="form-control" id="plan_price_id_{{$plans_val->id}}">
                @foreach($plans_val->planPrice as $key => $plan_price)
                  <?php
                  $service_tax = (env('SERVICE_TAX') / 100) * $plan_price->price;
                  $total_price = $plan_price->price + $service_tax;
                  if ($plan_price->validity == 1) {
                    $validity = 'Month';
                  } else if ($plan_price->validity == 12) {
                    $validity = 'Year';
                  }else if ($plan_price->validity > 12) {
                    $validity = $plan_price->validity/12 .'Month';
                  }else {
                    $validity = $plan_price->validity .'Month';
                  }
                  ?>
                    <option value="{{ encrypt($plan_price->id) }}">{{ round($total_price,2) }}/{{$validity}} €</option>
                @endforeach
            </select>
            <span style="color: #f1666a;">*inclusive tax</span>
          </div>
          @if($plan['buy_plan'] == 1)
            <button class="btn btn-outline-primary m-t-30" data-id="{{ encrypt($plans_val->id) }}" id="buy_plan_{{$plans_val->id}}" onclick="bookPlan('{{$plans_val->id}}')">Book Now</button>
          @else
            @if($plan['plan_id'] == $plans_val->id)
              <button class="btn btn-outline-danger" disabled>Current Plan</button>
            @endif
          @endif
        </div>
      </div>
    </div>
  </div>
  @endforeach
  @endif
</div>
@stop
@section('blade-page-script')
  <script type="text/javascript">
    function bookPlan(id) {
        let price_id = $('#plan_price_id_'+id).val();
        let plan_id = $('#buy_plan_'+id).attr('data-id');
        if(price_id && plan_id) {
          var url = '{{ route('coach.plan.payment.show',['plan_id'=> ":plan_id",'plan_price_id'=> ":plan_price_id"]) }}';
          url = url.replace(':plan_id', plan_id);
          url = url.replace(':plan_price_id', price_id);
          document.location.href = url;
        } else {
          toastr.error("Price Not Available")
        }

    }
  </script>
  @endsection