<div class="modal fade event_view_modal" id="event_view_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
           <div class="modal-body event-detail-modal">
                <div class="ed-de-cl-btn">
                    @if($event->user_id == auth()->id())
                    <a href="#" class="all-event-add-btn" onclick="editEventData('{{$event->id}}')"><span class=" icon-pencil"></span></a>
                    @else
                        @if($event->self_attendant->status == 0)
                            <a href="#" class="all-event-add-btn" onclick="eventAction('{{$event->id}}', 1)"><span class="fa fa-check"></span></a>
                            <a href="#" class="all-event-add-btn" onclick="eventAction('{{$event->id}}', 0)"><span class="fa fa-times"></span></a>
                        @else
                            <a href="#" onclick="deleteEventData({{$event->id}},{{$event->user_id}})"><span class="icon-trash"></span></a>
                        @endif
                    @endif
                        <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                </div>
                <div class="event-view-content">
                    <div class="view-left-side-sec">
                        <span class="use-color"></span>
                    </div>
                    <div class="view-right-side-sec">
                        <div class="view-detail-section">
                            <strong>{{$event->title ?? ''}}</strong>
                            <p>{{$event->event_created_date_time ?? ''}}</p>
                        </div>
                    </div>
                </div>
                <div class="event-view-content">
                    <div class="view-left-side-sec">
                        <span class="icon-users"></span>
                    </div>
                    <div class="view-right-side-sec">
                        <div class="view-detail-section">
                            @if($event->user_id == auth()->id())
                             <?php
                                $awaiting_count = $event->pending_event_attendants->count();
                                $accepted_count = $event->accepted_event_attendants->count();
                                $rejected_count = $event->reject_event_attendants->count();
                            ?>
                            <p>{{ $event->event_attendants->count() - 1 }} Guest</p>
                            <p>{{ $accepted_count }} yes, {{ $awaiting_count }} awaiting, {{ $rejected_count }} rejected</p>
                            @foreach($event->event_attendants as $event_attendant_key => $event_attendant_val)
                                @if($event_attendant_val->attendant_type == 'A')
                                <div class="user-id">
                                    <span>{{ $event_attendant_val->users->name }}</span>
                                </div>
                                @endif
                            @endforeach
                            @else
                            <p>By</p>
                            <div class="user-id">
                                <span>{{ $event->event_creators->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="event-view-content">
                    <div class="view-left-side-sec">
                        <span class=" fa-paragraph"></span>
                    </div>
                    <div class="view-right-side-sec">
                        <div class="view-detail-section">
                            <p>{{ $event->description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>