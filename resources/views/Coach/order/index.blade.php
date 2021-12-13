@extends('Coach.layout.master')
@section('title', 'Orders')
@section('parentPageTitle', 'Orders')


@section('content')
<div class="row clearfix">
  <div class="col-12">
    <div class="card planned_task">
      <div class="header">
        <h2>Orders</h2>
      </div>
      <div class="body">
        <div class="table-responsive">
          <table class="table table-hover m-b-0">
            <thead class="thead-dark">
              <tr>
                <th>#</th>
                <th>Date</th>
                <th data-breakpoints="sm xs">Order ID</th>
                <th>Plan Name</th>
                <th>Quantity</th>
                <th data-breakpoints="xs">Total</th>
                <th data-breakpoints="xs md">Order Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            @if($orders->count() > 0)
              @foreach($orders as $order_key => $order_val)
                <tr>
                  <td>{!! ++$i !!}</td>
                  <td>{{ $order_val->created_at_date ?? '' }}</td>
                  <td>{{ $order_val->order_uuid ?? '' }}</td>
                  <td><h5>{{ $order_val->order_type == '1' ? 'Session Request' : $order_val->plan->title}}</h5></td>
                  <td>01</td>
                  <td>{{ $order_val->total_price ?? '' }}</td>
                  <td>{!!  $order_val->status == '1' ? '<span class="badge badge-success bg-success text-white">Paid</span>' : '<span class="badge badge-warning bg-warning text-white">Pending</span>' !!}</td>
                  <td><a href="{{ route('coach.order.detail', $order_val->id) }}" class="btn btn-outline-secondary">View Order</a></td>
                </tr>
              @endforeach
            @else
              <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
            @endif
            </tbody>
          </table>
        </div>

        <hr>
        <div class="row">
          <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="box-footer">
              <div class="pagination pull-right">
                {{ $orders->appends(request()->except('page'))->links()  }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@stop 