<div class="modal-content">
{!! Form::model($category, ['method' => 'PATCH','route' => ['coach.category.update', $category->id], 'id' => 'update_category', 'files' => "true" ]) !!}
<div class="modal-body">
    <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
    <div class="form-group">
        <div class="form-line">
            <input type="text" class="form-control" placeholder="Category Title" name="title" id="categoryTitle" value="{{$category->title ?? ''}}" data-rule-required="true" data-rule-maxlength="150">
        </div>
    </div>
    <div class="form-group">
        <label class="control-label">Select Event Color</label>
        <ul class="cmn-ul-list">
            @foreach ($event_colors as $event_color)
                @if($event_color->color_sort == 2)
                    <li>
                        <label class="event-ck-color">
                            <input type="radio" name="event_color" class="event_color" data-rule-required="true"  value="{{$event_color->id}}" {{$event_color->id == $category->color_id ? 'checked' : ''}}>
                            <span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-primary modal-add-cat-event-btn">Update Category</button>
</div>
{!! Form::close() !!}
</div>

