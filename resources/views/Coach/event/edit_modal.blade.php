<?php 
use Carbon\Carbon;
$individual_attendant =  $event->event_attendants()->whereNull('team_id')->where('attendant_type','A')->pluck('user_id')->toArray();
$team_attendant  =  $event->event_attendants()->whereNotNull('team_id')->where('attendant_type','A')->pluck('team_id')->toArray();
?>
@if($event->category_id)
    <style>
        .category_color {
            display: block;
        }

        .color_element {
            display: none;
        }
    </style>
@else
    <style>
        .category_color {
            display: none;
        }

        .color_element {
            display: block;
        }
    </style>
@endif
<div class="modal fade add_event_category_modal" id="editEventModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {!! Form::model($event, ['method' => 'PATCH','route' => ['coach.event.update', $event->id], 'id' => 'update_user_event', 'files' => "true" ]) !!}
                   
                    <div class="modal-body">
                        <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type="text" class="form-control add_title" placeholder="Event Title" id="eventTitle" name="title" data-rule-required="true" data-rule-maxlength="150" value="{{ $event->title }}" autocomplete = "off" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type='text' class="form-control datetimepicker_update" placeholder="Start Date Time" id="datetimepicker_event_date" name="event_date" data-rule-required="true" autocomplete = "off" value="{{ Carbon::parse($event->event_datetime)->format('Y/m/d H:i') }}" />
                                        </div>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="form-line">
                                            <input type='text' class="form-control datetimepicker_update" placeholder="End Date Time" id="datetimepicker_end_date" name="end_datetime" autocomplete = "off" value="{{ $event->end_datetime ? Carbon::parse($event->end_datetime)->format('Y/m/d H:i') : '' }}" />
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="title">Description</label>
                                        <div class="form-line">
                                            <textarea class="form-control no-resize" placeholder="Event Description..." id="eventDescription" name="description">{{ $event->description }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Users</label>
                                        <div class="form-line">
                                            {{ Form::select('attendant[]',$users,$individual_attendant,['class' => "form-control no-resize event_attendant", 'id' => "eventAttenders", 'data-placeholder' => "Select Attendant", 'data-rule-required' => "false", "multiple" => "true"]) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Teams</label>
                                        <div class="form-line">
                                            <select name="team[]" class="form-control no-resize create_event_team" data-placeholder="Select Team" placeholder="Select Team" data-rule-required ="false" multiple>
                                                <option value="">Select Team</option>
                                                @foreach($team as $team_key => $team_val)
                                                    @if($team_val->team_users_count > 0)
                                                        <option value="{{ $team_val->id }}" @if(in_array($team_val->id, $team_attendant)) selected @endif>{{ $team_val->title }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Category</label>
                                        <div class="form-line">
                                            <select name="category_id" class="form-control no-resize event_category" data-placeholder="Select Category" data-rule-required ="false" id="update_category_id">
                                                <option value="0">Select Category</option>
                                                @foreach($categories as $category_key => $category)
                                                    <option value="{{ $category->id }}" data-id="{{$category}}" {{ $category->id == $event->category_id ? 'selected' : ''}}>{{ $category->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-6 event-color-main-div" @if(empty($event->category_id)) style="display: none;" @endif>
                                    <div class="form-group">
                                        <label class="control-label">Event Color</label>
                                        <ul class="cmn-ul-list color_element selected_default_color_element">
                                            @foreach ($event_colors as $event_color)
                                                @if($event_color->id == 2)
                                                <li>
                                                    <label class="event-ck-color">
                                                        <input type="radio" data-rule-required="true" name="event_color" class="event_color" id="event_color_{{$event_color->id}}" value="{{$event_color->id}}"  @if($event->event_color_id == $event_color->id) checked @endif>
                                                        <span class="checkmark" style="background-color: {{$event_color->color_code}}"></span> </label>
                                                </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                        <ul class="cmn-ul-list category_color">
                                            @if($event->category_id)
                                                <li>
                                                    <label class="event-ck-color">
                                                        <input type="radio" data-rule-required="true" name="event_color" class="event_color" id="event_color_{{$event->category->color_id}}" value="{{$event->category->color_id}}"  checked>
                                                        <span class="checkmark" style="background-color: {{$event->category->color_code}}"></span> </label>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                         <button type="submit" class="btn btn-primary modal-add-cat-event-btn">Update Event</button>
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>