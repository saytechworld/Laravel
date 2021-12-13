@extends('Backend.layout.master')
@section('title', 'FAQ')
@section('parentPageTitle', 'FAQ')
@section('content')
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>FAQ</h2>
                    <ul class="header-dropdown">
                        <li>
                  <a href="{!! route('admin.system.faq.create',['page' => request()->input('page')]) !!}" class="btn btn-outline-primary">Create</a>
                  </li>
                    </ul>
                </div>
                <div class="body">
                    <div class="table-responsive">
                        <table class="table table-hover m-b-0 c_list">
                            <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @if($faq->count() > 0)
                                    @foreach($faq as $key => $data)
                                        <tr>
                                            <td>{!! ++$i !!}</td>
                                            <td>{{ $data->question }}</td>
                                            <td>{{ substr($data->answer,0,100) }}</td>
                                            <td>
                                                <a href="{{ route('admin.system.faq.edit',['faq' => $data->id, 'page' => request()->input('page')]) }}" class="btn btn-info"><i class="fa fa-edit"></i></a>
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
                        {{ $faq->appends(request()->except('page'))->links()  }} 
                  </div>
                </div>
            </div>
        </div>
    </div>
@stop
@section('blade-page-script')
@stop