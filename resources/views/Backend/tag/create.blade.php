@extends('Backend.layout.master')
@section('title', 'Create Tag')
@section('parentPageTitle', 'Tag')
@section('content')
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Create Tag</h2>
            </div>
            <div class="body">
                <div class="col-lg-6 col-md-12">
                   {!! Form::open(array('url' => route('admin.system.tag.store'),'method' => 'POST', 'files' => "true", 'id' => "create_tag")) !!}

                     {!! Form::hidden('page', request()->input('page')) !!}
                    <div class="form-group">
                        <label for="name" class="control-label">Title</label>
                        {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50", 'id' => 'title']) !!}
                    </div>

                    <button type="submit" class="btn btn-primary">Create</button>
                    {!! Form::close() !!}
                </div>
            </div>


        </div>
    </div>

</div>
@stop
@section('blade-page-script')

    <script type="text/javascript">
        $('#create_tag').validate({
            ignore : [],
            rules: {
                title: {
                    remote: {
                        url: "{!! route('ajax.tag.uniquetagtitle') !!}",
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
