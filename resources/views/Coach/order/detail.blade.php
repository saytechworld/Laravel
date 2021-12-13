@extends('Coach.layout.master')
@section('title', 'Invoice Details ')
@section('parentPageTitle', 'Invoice Details ')


@section('content')

<div class="row clearfix">
    <div class="col-lg-12">
        <div class="card">
            <div class="header">
                <h2>Single Invoice</h2>
                <ul class="header-dropdown">
                    {{--<li class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"></a>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li><a href="javascript:void(0);">Print Invoices</a></li>
                            <li role="presentation" class="divider"></li>
                            <li><a href="javascript:void(0);">Export to XLS</a></li>
                            <li><a href="javascript:void(0);">Export to CSV</a></li>
                            <li><a href="javascript:void(0);">Export to XML</a></li>
                        </ul>
                    </li>--}}
                </ul>
            </div>
            <div class="body" id="print_invoice">
                <h3>Invoice Details : <strong class="text-primary">{{ $order->order_uuid ?? '' }}</strong></h3>
                
                <div class="row clearfix">
                    <div class="col-md-6 col-sm-6">
                        <address>
                            <strong>{{$order->user->name ?? ''}}</strong><br>
                            {{ $order->user->user_details->address_line_1  ?? ''}}
                            {{$order->user->user_details->address_line_2  ?? ''}}<br>
                            {{$order->user->user_details->city->title  ?? ''}}
                            {{$order->user->user_details->state->title  ?? ''}}
                            {{$order->user->user_details->country->title  ?? ''}}<br>
                            <abbr title="Phone">P:</abbr> {{ $order->user->user_details->mobile ?? '' }}
                        </address>
                    </div>
                    <div class="col-md-6 col-sm-6 text-right">
                        <p class="m-b-0"><strong>Order Date: </strong> {{ $order->created_at_date ?? '' }}</p>
                        <p class="m-b-0"><strong>Order Status: </strong> {!!  $order->status == '1' ? '<span class="badge badge-success m-b-0">Paid</span>' : '<span class="badge badge-success m-b-0">Pending</span>' !!}</p>
                        <p><strong>Order ID: </strong> {{ $order->order_uuid ?? '' }}</p>
                    </div>
                </div>
                <div class="row clearfix">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>{{ $order->order_type == '1' ? 'Session Request' : $order->plan->title}}</td>
                                        <td>1</td>
                                        <td>{{ $order->total_price ?? ''}} €</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row clearfix">
                            <div class="col-md-6">

                            </div>
                            <div class="col-md-6 text-right">
                                <p class="m-b-0"><b>Sub-total:</b> {{ $order->price ?? ''}} €</p>
                                <p class="m-b-0"><b>Platform Fees:</b> {{ $order->transaction_fees ?? ''}} €</p>
                                <p class="m-b-0"><b>Service Tax:</b> {{ $order->service_tax ?? ''}} €</p>
                                <h3 class="m-b-0 m-t-10">{{ $order->total_price ?? ''}} €</h3>
                            </div>
                        </div>
            </div>
            <div class="hidden-print col-md-12 text-right">
                <hr>
                <button class="btn btn-outline-secondary" onclick="printPageArea()"><i class="icon-printer"></i></button>
            </div>
        </div>
    </div>
</div>
@stop
@section('blade-page-script')
    <script type="text/javascript">
        function printPageArea(){
            var printContents = document.getElementById('print_invoice').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
        }
    </script>
@stop
