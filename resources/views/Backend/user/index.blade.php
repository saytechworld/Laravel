@extends('Backend.layout.master')
@section('title', $type)
@section('parentPageTitle', $type)
@section('content')
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>{{$type}}</h2>
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
                                    <th>User Name</th>
                                    <th>Email</th>
                                    @if($type == 'Users')
                                        <th>Role</th>
                                        <th>Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if($users->count() > 0)
                                    @foreach($users as $key => $data)
                                        <tr>
                                            <td>{!! ++$i !!}</td>
                                            <td>{!! $data->name !!}</td>
                                            <td>{!! $data->username !!}</td>
                                            <td> {!! $data->email !!} </td>
                                            @if($type == 'Users')
                                                <td> {!! ucfirst($data->role_type) !!} </td>
                                                <td>
                                                    @if($data->deleted_status == 0)
                                                        {!! Form::model($data, ['method' => 'DELETE','route' => ['admin.access.user.destroy', $data->id], 'id' => 'delete-account' ]) !!}
                                                            <input type="submit" class="btn btn-danger" value="Delete"/>
                                                        {!! Form::close() !!}
                                                    @else
                                                        <a href="{{ route('admin.access.user.restore', $data->id) }}" class="btn btn-success">Restore</a>
                                                    @endif
                                                </td>
                                            @endif
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
                            {{ $users->appends(request()->except('page'))->links()  }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop
@section('blade-page-script')
    <script type="text/javascript">
        $("#delete-account").submit(function(e) {
           e.preventDefault();
            swal({
                title: "Are you sure?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then((willDelete) => {
                    if (willDelete) {
                        $("#delete-account").get(0).submit();
                    }
                });
        });
    </script>
@stop