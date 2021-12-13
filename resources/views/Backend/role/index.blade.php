@extends('Backend.layout.master')
@section('title', 'Roles')
@section('parentPageTitle', 'Roles')
@section('content')
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>Roles</h2>
                    <ul class="header-dropdown">
                    </ul>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0 c_list">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($roles->count() > 0)
                                    @foreach($roles as $key => $data)
                                        <tr>
                                            <td>{!! ++$i !!}</td>
                                            <td>{!! $data->name !!}</td>
                                            <td>{!! $data->description !!}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pagination pull-right">
                            {{ $roles->appends(request()->except('page'))->links()  }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection