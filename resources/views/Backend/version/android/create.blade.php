@extends('Backend.layout.master')
@section('title', 'Create Android Version')
@section('parentPageTitle', 'Android Version')
@section('content')
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Create Android Version</h2>
            </div>
            <div class="body">
                <div class="col-lg-12 col-md-12">
                   {!! Form::open(array('url' => route('admin.system.version.android.store'),'method' => 'POST', 'id' => "create_version")) !!}

                     {!! Form::hidden('page', request()->input('page')) !!}
                    <div class="form-group">
                        <label for="name" class="control-label">Version</label>
                        {!! Form::text('version',null,['class' => "form-control", 'placeholder' => "Version", 'data-rule-required' => "true", 'data-rule-maxlength' => "50", 'id' => 'version'  ]) !!}
                    </div>

                    <div class="form-group">
                        <label class="mb-3">Mandatory Update</label>
                        <div class="input-group">
                            <label class="fancy-radio">
                                {{ Form::radio('status', '1' , true) }}
                                <span><i></i> Yes</span> </label>
                        </div>
                        <div class="input-group">
                            <label class="fancy-radio">
                                {{ Form::radio('status', '0' , false) }}
                                <span><i></i> no</span>
                            </label>
                        </div>
                    </div>
                
                    <button type="submit" class="btn btn-primary">Create</button>

                </div>
            </div>


        </div>
    </div>

</div>
@stop
@section('blade-page-script')
<script type="text/javascript">
    $(function() {
    
    $('#create_version').validate({
        ignore : []
    });
});
</script>

@stop