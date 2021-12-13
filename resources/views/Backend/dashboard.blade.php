@extends('Backend.layout.master')
@section('title', 'Dashboard')
@section('parentPageTitle', 'Dashboard')


@section('content')


<?php
    use App\Models\User;
    use App\Models\SessionRequest;
    use App\Models\Withdrawal;

    $users = User::whereHas('roles',function($query){
        $query->whereIn('role_id', [3,4]);
    })->get();


    $total_balance = SessionRequest::where('status', 7)
        ->sum('session_price');

    $withdrawalAmount = Withdrawal::sum('amount');

    $remainingAmount = $total_balance-$withdrawalAmount;

?>

<div class="row clearfix">
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card overflowhidden number-chart">
            <div class="body">
                <div class="number">
                    <h6>Total Balance</h6>
                    <span>{{ $total_balance }}</span>
                </div>
            </div>
            <div class="sparkline" data-type="line" data-spot-Radius="0" data-offset="90" data-width="100%" data-height="50px"
            data-line-Width="1" data-line-Color="#f79647" data-fill-Color="#fac091">1,4,1,3,7,1</div>
        </div>  
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card overflowhidden number-chart">
            <div class="body">
                <div class="number">
                    <h6>Withdrawal Amount</h6>
                    <span>{{$withdrawalAmount}}</span>
                </div>
            </div>
            <div class="sparkline" data-type="line" data-spot-Radius="0" data-offset="90" data-width="100%" data-height="50px"
                 data-line-Width="1" data-line-Color="#4aacc5" data-fill-Color="#92cddc">1,4,1,3,7,1</div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card overflowhidden number-chart">
            <div class="body">
                <div class="number">
                    <h6>Remaining Balance</h6>
                    <span>{{$remainingAmount}}</span>
                </div>
            </div>
            <div class="sparkline" data-type="line" data-spot-Radius="0" data-offset="90" data-width="100%" data-height="50px"
            data-line-Width="1" data-line-Color="#604a7b" data-fill-Color="#a092b0">1,4,1,3,7,1</div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card overflowhidden number-chart">
            <div class="body">
                <div class="number">
                    <h6>Users</h6>
                    <span>{{ $users->count() }}</span>
                </div>
            </div>
            <div class="sparkline" data-type="line" data-spot-Radius="0" data-offset="90" data-width="100%" data-height="50px"
            data-line-Width="1" data-line-Color="#4f81bc" data-fill-Color="#95b3d7">1,4,1,3,7,1</div>
        </div>
    </div>
</div>

@stop
