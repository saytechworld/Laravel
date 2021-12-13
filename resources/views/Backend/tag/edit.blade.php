@extends('Backend.layout.master')
@section('title', 'Edit Tag')
@section('parentPageTitle', 'Tag')
@section('content')
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Edit Tag</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                   {!! Form::model($tag, ['method' => 'PATCH','route' => ['admin.system.tag.update', $tag->id], 'files' => "true", 'id'=>"edit_tag" ]) !!}

                     {!! Form::hidden('page', request()->input('page')) !!}
                    <div class="form-group">
                        <label for="name" class="control-label">Title</label>
                        {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50", 'id' => 'title']) !!}
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                    {!! Form::close() !!}
                </div>
            </div>


        </div>
    </div>

</div>
@stop
@section('blade-page-script')

    <script type="text/javascript">
        $('#edit_tag').validate({
            ignore : [],
            rules: {
                title: {
                    remote: {
                        url: "{!! route('ajax.tag.uniquetagtitle',$tag->id) !!}",
                        type: "post",
                        data: {
                            title:  function(){
                                return $("#title").val();
                            }
                        }
                    }
                },
            },
            messages : {
                title : {
                    remote : "The title has already been taken."
                }
            },
        });
    </script>

@stop
