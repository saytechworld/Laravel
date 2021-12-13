@extends('Coach.layout.master')
@section('title', 'Event Category')
@section('parentPageTitle', 'Category')


@section('content')

    <div class="row clearfix">

        <div class="col-lg-12">
            <div class="card upcoming-event-bx">
                <div class="body">
                    @if($categories->count() > 0)
                        @foreach($categories as $category_key => $category_val)
                            <div class="timeline-item green" date-is="{{ $category_val->created_at ?? '' }}">
                                <span class="e-dot-c" style="background-color:{{$category_val->color_code}};"></span>
                                <h5>{{ $category_val->title ?? '' }}</h5>

                                <div class="event-ed-btns">
                                    <a href="#" class="btn btn-sm btn-outline-secondary m-r-10" onclick="editCategoryData({{$category_val->id}})"><i class="icon-note"></i> </a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <tr><td colspan="5">{!! trans('No record found') !!}</td></tr>
                    @endif
                    <div class="box-footer">
                        <div class="pagination pull-right">
                            {{ $categories->appends(request()->except('page'))->links()  }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade add_event_category_modal" id="editCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="categoryModal">

            </div>
        </div>
    </div>
@stop
@section('blade-page-script')
    <script type="text/javascript">
        function editCategoryData(id) {
            $('.categoryModal').html('');
            let url = "{{ route('coach.category.edit',['category'=>':id']) }}";
            url = url.replace(':id', id);

            $.ajax({

                type: "GET",
                url: url,
                async: false,
                beforeSend: function () {
                    //
                },
                success: function (data) {
                    if (data.status) {
                        $('.categoryModal').html(data.data.result);
                        $('#editCategoryModal').modal();
                        reinitialiseCategoryFormValidation("#update_category");
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function (xhr) { // if error occured
                    toastr.error("Error occured.please try again");
                },
            });
        };


        function reinitialiseCategoryFormValidation(elementSelector) {
            var validator = $(elementSelector).validate({
                ignore : [],
                errorPlacement: function(error, element) {
                    if($(element).hasClass('event_color')){
                        error.insertAfter(element.closest('div.form-group'));
                    }else{
                        error.insertAfter(element);
                    }
                },

                submitHandler: function (form) {
                    var event_form_data = $(form).serialize()+'&_token='+"{!! csrf_token() !!}";

                    let url = $('#update_category').attr('action');

                    $.ajax({

                        type: "POST",
                        url : url,
                        data : event_form_data,
                        async: true,
                        beforeSend: function() {
                            //
                        },
                        success: function(data) {
                            if(data.status){
                                toastr.success(data.message);
                                window.location.reload(true);
                            }else{
                                toastr.error(data.message);
                            }
                        },
                        error: function(xhr) { // if error occured
                            toastr.error("Error occured.please try again");
                        },
                    });
                }
            });
        };
        


        
    </script>
@stop
