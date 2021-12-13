<?php
$member = $team->team_users->pluck('id')->toArray();
?>
<div class="modal-content">
{!! Form::model($team, ['method' => 'PATCH','route' => ['coach.team.update', $team->id], 'id' => 'update_team', 'files' => "true" ]) !!}


 <div class="modal-body">
     
     <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
     <div class="form-group">
         <label for="title">Title</label>
         <div class="form-line">
             {!! Form::text('title',null,['class' => "form-control", 'placeholder' => "Title", 'data-rule-required' => "true", 'data-rule-maxlength' => "50"  ]) !!}
         </div>
     </div>
     <div class="form-group">
         <label for="user">Members</label>
         <div class="form-line">
             <select name="member[]" class="form-control edit_team_member" multiple>
                 @foreach($users as $user)
                     @if($user->userimage)
                         @php $image = $user->userimage  @endphp
                     @else
                         @php $image = asset('images/noimage.jpg')  @endphp
                     @endif
                     <option value="{{$user->id}}" @if(in_array($user->id, $member)) ? selected @endif data-image="{!! $image !!}">{{$user->name}}</option>
                 @endforeach
             </select>
         </div>
         </div>
     </div>
       
<div class="modal-footer">
     <button type="submit" class="btn btn-primary">Update</button>
 </div>
 {!! Form::close() !!}
     
 </div>
<script>
    $('.edit_team_member').select2({
        "width" : "100%",
        templateResult: addUserPic,
        templateSelection: addUserPic
    });

    function addUserPic (opt) {
        if (!opt.id) {
            return opt.text;
        }
        var optimage = $(opt.element).data('image');
        if(!optimage){
            return opt.text;
        } else {
            var $opt = $(
                '<span class="userName"><img src="' + optimage + '" class="user_dropdown_image" /> ' + $(opt.element).text() + '</span>'
            );
            return $opt;
        }
    };
</script>