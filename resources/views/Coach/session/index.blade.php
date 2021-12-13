@extends('Coach.layout.master')
@section('title', 'Sessions')
@section('parentPageTitle', 'Sessions')


@section('content')
<div class="row clearfix">
  <div class="col-12">
    <div class="card planned_task">
      <div class="header">
        <h2>Sessions</h2>
      </div>
      <div class="body">
        <div class="table-responsive">
          <table class="table table-hover m-b-0">
            <thead class="thead-dark">
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Session ID</th>
                <th>Session Price</th>
                <th>Request By</th>
                <th>Session With</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            @if($sessions->count() > 0)
              @foreach($sessions as $session_key => $session)
                <tr>
                  <td>{!! ++$i !!}</td>
                  <td>{{ $session->start_session_date_time ?? '' }}</td>
                  <td>{{ $session->chat_session_uuid ?? '' }}</td>
                  <td>{{ auth()->id() == $session->athelete_id ? $session->total_session_price : $session->session_price }}</td>
                  <td>{{ $session->request_by == 1 ? $session->athelete_user->name : $session->coach_user->name }}</td>
                  <td>Session With {{ $session->coach_id == auth()->id() ? $session->athelete_user->name : $session->coach_user->name }}</td>
                  <td>{{ ($session->status == 1 ? 'Pending' :
                  ($session->status == 2 ? 'Accept Coach' :
                  ($session->status == 3 ? 'Session Declined' :
                  ($session->status == 4 ? 'Accept Athlete' :
                  ($session->status == 5 ? 'Session Price Declined' :
                  ($session->status == 6 ? 'Start Session' :
                  ($session->status == 7 ? 'Session Complete' :
                   'Session Expired' ))))))) }}</td>
                  <td><a href="{{ route('coach.chat.startuserchating',$session->coach_id == auth()->id() ? $session->athelete_user->user_uuid : $session->coach_user->user_uuid) }}" class="btn btn-outline-secondary">Chat</a></td>
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
                {{ $sessions->appends(request()->except('page'))->links()  }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@stop 