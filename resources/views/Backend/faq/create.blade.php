@extends('Backend.layout.master')
@section('title', 'Create FAQ')
@section('parentPageTitle', 'Create FAQ')
@section('content')
<div class="row clearfix">
    <div class="col-lg-12 col-md-12">
        <div class="card">
            <div class="header">
                <h2>Create FAQ</h2>
            </div>
            <div class="body">
                <div class="col-lg-12 col-md-12">
                   {!! Form::open(array('url' => route('admin.system.faq.store'),'method' => 'POST', 'files' => "true", 'id' => "create_faq")) !!}

                     {!! Form::hidden('page', request()->input('page')) !!}
                    <div class="form-group">
                        <label for="name" class="control-label">Question</label>                                                
                        {!! Form::textarea('question',null,['class' => "form-control", 'placeholder' => "Question", 'data-rule-required' => "true", 'rows' => 4, 'cols' => 4]) !!}
                    </div>

                    <div class="form-group">
                        <label for="name" class="control-label">Answer</label>                                                
                        {!! Form::textarea('answer',null,['class' => "form-control", 'placeholder' => "Answer", 'data-rule-required' => "true", 'rows' => 4, 'cols' => 4]) !!}
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
    
    $('#create_faq').validate({
        ignore : []
    });
});
</script>

@stop