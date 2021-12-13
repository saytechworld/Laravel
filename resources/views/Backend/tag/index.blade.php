@extends('Backend.layout.master')
@section('title', 'Tag')
@section('parentPageTitle', 'Tag')
@section('content')
 <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>Tag</h2>
                    <ul class="header-dropdown">
                        <li>
                  <a href="{!! route('admin.system.tag.create',['page' => request()->input('page')]) !!}" class="btn btn-outline-primary">Create</a>
                  </li>
                    </ul>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0 c_list">
                            <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Title</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @if($tags->count() > 0)
                                    @foreach($tags as $key => $data)
                                        <tr>
                                            <td>{!! ++$i !!}</td>
                                            <td>{{ $data->title }}</td>
                                            <td>
                                                <a href="{{ route('admin.system.tag.edit',['tag' => $data->id, 'page' => request()->input('page')]) }}" class="btn btn-info"><i class="fa fa-edit"></i></a>
                                            </td>
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
                        {{ $tags->appends(request()->except('page'))->links()  }} 
                  </div>
                </div>
            </div>
        </div>
    </div>
@endsection