@extends('Backend.layout.master')
@section('title', 'Static Pages')
@section('parentPageTitle', 'Static Pages')
@section('content')
    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card">
                <div class="header">
                    <h2>Static Pages</h2>
                    <ul class="header-dropdown">
                        <li>
                  <a href="{!! route('admin.system.staticpage.create',['page' => request()->input('page')]) !!}" class="btn btn-outline-primary">Create</a>
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
                                <th>Image</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($staticpages as $key => $static_page)
                                    <tr>
                                        <td>{!! ++$i !!}</td>
                                        <td>
                                            {{ $static_page->title ?? '' }}
                                        </td>
                                        <td>
                                            @if(!empty($static_page->featured_image) &&  file_exists(public_path('images/staticpages/'.$static_page->featured_image)))
                                                <img src="{!! env('Aws_URL').'staticpages/'.$static_page->featured_image!!}" width="320" height="200">
                                            @else
                                                <img src="{!! asset('images/defaultimage.jpg') !!}" width="320" height="200">
                                            @endif
                                        </td>
                                        <td>
                                            {!! substr(strip_tags($static_page->description),0,300) !!}
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.system.staticpage.edit',['staticpage' =>  $static_page->id, 'page' => request()->input('page') ]) }}" class="btn btn-info"><i class="fa fa-edit"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination pull-right">
                        {{ $staticpages->appends(request()->except('page'))->links()  }} 
                  </div>
                </div>
            </div>
        </div>
    </div>
@stop
@section('blade-page-script')
@stop