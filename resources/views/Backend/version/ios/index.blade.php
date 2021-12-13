@extends('Backend.layout.master')
@section('title', 'IOS Versions')
@section('parentPageTitle', 'IOS Versions')
@section('content')
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>IOS Versions</h2>
                    <ul class="header-dropdown">
                        <li>
                  <a href="{!! route('admin.system.version.ios.create',['page' => request()->input('page')]) !!}" class="btn btn-outline-primary">Create</a>
                  </li>
                    </ul>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0 c_list">
                            <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Version</th>
                                <th>Update Mandatory</th>
                            </tr>
                            </thead>
                            <tbody>
                                @if($ios_versions->count() > 0)
                                    @foreach($ios_versions as $key => $data)
                                        <tr>
                                            <td>{!! ++$i !!}</td>
                                            <td>{{ $data->version }}</td>
                                            <td>{{ $data->status ? 'Yes' : 'No'  }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                 <tr>
                                    <td colspan="5">
                                        No record found.
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination pull-right">
                        {{ $ios_versions->appends(request()->except('page'))->links()  }}
                  </div>
                </div>
            </div>
        </div>
    </div>
@stop
@section('blade-page-script')
@stop