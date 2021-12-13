@extends('Coach.layout.master')
@section('title', 'Notifications List')
@section('parentPageTitle', 'Notifications')


@section('content')

<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Notifications</h2>
            </div>
            <div class="body">
                @if($notifications->count() > 0)
                    @foreach($notifications as $notification)
                        <a href="javascript:void(0);" class="{{$notification->type == 2 || $notification->type == 3 ? '' : 'read_user_notification' }}" data-notification-uuid="{{ $notification->notification_uuid }}">
                            @if($notification->read_at)
                                <div class="alert alert-secondary" role="alert">{{ $notification->data ?? '' }}</div>
                            @else
                                <div class="alert alert-primary" role="alert">{{ $notification->data ?? '' }}</div>
                            @endif
                        </a>

                        @if(($notification->type == 3 || $notification->type == 2) && $notification->read_at == 0)
                            <div class="notification-action-button">
                                <button class="btn btn-success accept-invitation" data-notification-uuid="{{ $notification->notification_uuid }}">Accept</button>
                                <button class="btn btn-danger reject-invitation" data-notification-uuid="{{ $notification->notification_uuid }}">Reject</button>
                            </div>
                        @endif
                    @endforeach
                @else
                    <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                @endif
                <div class="box-footer">
                    <div class="pagination pull-right">
                        {{ $notifications->appends(request()->except('page'))->links()  }}
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop
