@extends('Coach.layout.master')
@section('title', 'Coaches')
@section('parentPageTitle', 'Coaches')
@section('content')
<?php
use App\ Models\ Language;
use App\ Models\ Game;
$languages = Language::where( 'status', 1 )->get();
$games = Game::with( [ 'game_skills' => function ( $query ) {
  $query->where( 'status', 1 );
} ] )->whereHas( 'game_skills', function ( $query ) {
  $query->where( 'status', 1 );
} )->where( 'status', 1 )->get();

$games_arr = array();
$games_arr[ 'all' ] = 'Select sport';
$languages_arr = array();
$languages_arr[ 'all' ] = 'Select language';

foreach ( $games as $games_arr_key => $games_arr_value ) {
  $games_arr[ $games_arr_value->id ] = $games_arr_value->title;
}
foreach ( $languages as $languages_arr_key => $languages_arr_value ) {
  $languages_arr[ $languages_arr_value->id ] = $languages_arr_value->title;
}

//echo "<pre>"; print_r($games_arr); exit;
?>
<div class="s-filter coach_filter_section">
  <div class="row clearfix">
    <div class="col-lg-3 col-md-4 col-sm-12">
      <div class="card planned_task lft-f-sec coach_filter_elements">
        <div class="header">
          <h2>Filters</h2>
          <ul class="header-dropdown">
            <a href="{!! route('coach.coachlist') !!}" class="reset-btn btn btn-danger">Reset</a>
          </ul>
        </div>
        <div class="body p-t-0 p-b-0"> {!! Form::open(['method' => 'GET','route' => ['coach.coachlist'], 'class' => 'filter-coach-form', 'role' => 'form', 'id' => 'basic-form']) !!}
          <div class="filter-bx">
            <label class="mb-3">Select Sport</label>
            <div class="input-group">
              <div class="ts-select"> {{ Form::select('games', $games_arr, request()->query('games'), ['class' => "form-control show-tick ms select2 filter_game seach_coach",  'data-placeholder' => "Select Sports"]) }} </div>
            </div>
            <div class="m-t-20">
              <div class="selected_game_skill"> </div>
            </div>
          </div>
		  <div class="filter-bx">
            <label class="mb-3">Search</label>
            <div class="input-group"> {{ Form::text('name', request()->query('name'), ['class' => "form-control search-by-name",  'placeholder' => "Search name"]) }}
              <button type="submit" class="search-button" style="display: {{!empty(request()->query('name')) ? 'block' : 'none'}}"><i class="fa fa-search"></i></button>
            </div>
          </div>
          <div class="filter-bx">
            <label class="mb-3">Languages</label>
            <div class="input-group">
              <div class="ts-select"> {{ Form::select('languages', $languages_arr, request()->query('languages'), ['class' => "form-control show-tick ms select2 seach_coach",  'data-placeholder' => "Select Langauge"]) }} </div>
            </div>
          </div>
          
          <div class="filter-bx">
            <label class="mb-3">Experience</label>
            <div class="form-group">
              <div id="nouislider_range_experience"></div>
              <div class="m-t-20 yrs-f-value"><strong>Value: </strong><span class="js-nouislider-value-experience"></span>
                <input type="hidden" name="min_experience" id="min_experience" class="min_experience" value="{{ request()->query('min_experience') }}">
                <input type="hidden" name="max_experience"  id="max_experience" class="max_experience" value="{{ request()->query('max_experience') }}">
              </div>
            </div>
          </div>
          {!! Form::close() !!} </div>
      </div>
    </div>
    <div class="col-lg-9 col-md-8 col-sm-12">
      <div class="card rit-f-sec">
        <div class="header">
          <h2>Coaches </h2>
          <span>(Showing {{ $coaches->total() }} Coaches)</span>
          <ul class="header-dropdown">
            {{--<li class="dropdown"> <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Sort By</a>
              <ul class="dropdown-menu dropdown-menu-right">
                <li><a href="javascript:void(0);">Action</a></li>
                <li><a href="javascript:void(0);">Another Action</a></li>
                <li><a href="javascript:void(0);">Something else</a></li>
              </ul>
            </li>--}}
          </ul>
        </div>
        <div class="body team-section coach-list-section">
          <div class="row clearfix">
            <?php
            /*
                     <div class="col-lg-3 col-md-6 col-sm-12">
                       <div class="card">
                         <div class="body text-center">
                           <div class="p-t-65 p-b-65">
                             <h6>Add New Contact</h6>
                             <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#addcontact"><i class="fa fa-plus-circle"></i></button>
                           </div>
                         </div>
                       </div>
                     </div>
                     */
            ?>
            @if($coaches->count() > 0 )
            @foreach($coaches as $coach_key => $coach_val)
            @php
            $coach_games = $coach_val->coach_games()->groupBy('title')->pluck('title')->toArray();
            
            $coach_skills = $coach_val->coach_games_skills()->take(2)->pluck('title')->toArray();
            @endphp
                
                 <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="card c-b-box">
                        <div class="body text-center">
                          <div class="profile-image" data-percent="75"> <img src=" {{ !empty($coach_val->user_image) ? $coach_val->user_image : asset('images/noimage.jpg') }}" alt="user" class="rounded-circle image-click"/> </div>
                          <span class="c-b-name"><a href="{{ route('coach.coachdetail',$coach_val->username) }}"> {{ ucfirst($coach_val->name) }}</a></span>
                          <div class="game-list">
                            @if($coach_val->privacy == 1)
                              @foreach($coach_games as $coach_game) <span class="badge badge-default">{{ $coach_game ?? '' }}</span> @endforeach
                            @endif
                          </div>
                          @if($coach_val->privacy == 1)
                            <p>Experience: {{ $coach_val->user_details->experience  ?? '0' }} Years</p>
                          @endif
                          <div class="b-c-btns m-t-20 m-t-20">
                            <ul class="cmn-ul-list">
                              <li><a href="{{ route('coach.coachdetail',$coach_val->username) }}"class="btn btn-outline-primary  gray-btn"><span>Profile</span></a></li>

                              <li><a href="{{ route('coach.chat.startuserchating',$coach_val->user_uuid) }}"  class="btn btn-outline-primary  gray-btn"><span>Message</span></a></li>

                            </ul>
                          </div>
                          <div class="c-p-skills">
                            @if($coach_val->privacy == 1)
                              @foreach($coach_skills as $coach_skill) <span class="badge badge-purple">{{ $coach_skill ?? '' }}</span> @endforeach
                            @endif
                          </div>
                        </div>
                   </div>
                 </div>    
                  
                  
                  
                
                
                
                
                  
            @endforeach
            @endif </div>
          <div class="row">
            <div class="col-12">
              <div class="pagi-center"> {{ $coaches->appends(request()->except('page'))->links()  }} </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Default Size -->
<div class="modal fade" id="addcontact" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="title" id="defaultModalLabel">Add Contact</h6>
      </div>
      <div class="modal-body">
        <div class="row clearfix">
          <div class="col-6">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="First Name">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Last Name">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <input type="number" class="form-control" placeholder="Phone Number">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Enter Address">
            </div>
          </div>
          <div class="col-12">
            <div class="form-group">
              <input type="file" class="form-control-file" id="exampleInputFile" aria-describedby="fileHelp">
              <small id="fileHelp" class="form-text text-muted">This is some placeholder block-level help text for the above input. It's a bit lighter and easily wraps to a new line.</small> </div>
            <hr>
          </div>
          <div class="col-6">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Facebook">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Twitter">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="Linkedin">
            </div>
          </div>
          <div class="col-6">
            <div class="form-group">
              <input type="text" class="form-control" placeholder="instagram">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary">Add</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">CLOSE</button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade image-view-popup" id="myModal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-body">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        <figure class="upload-img-view" id="image-popup"> <img src=""> </figure>
      </div>
    </div>
  </div>
</div>
@stop 

@section('blade-page-script') 
<script type="text/javascript">
  /* $(document).on('click','.image-click',function(e){
     $("#image-popup").html('')
     $('#myModal').modal('show');
     let url = $(this).attr('src');
     var image = document.createElement('img');
     image.setAttribute('src', url);
     document.getElementById('image-popup').appendChild(image);
   });*/
$(function() {

  var game_arr = new Array();
  var test_arr = new Array();
  @foreach($games as $js_game_key => $js_game_val)
    var game_skill_arr = new Array();
    @foreach($js_game_val->game_skills as $js_game_skills_key => $js_game_skills_val)
      game_skill_arr.push({id: "{{ $js_game_skills_val->id }}", title : "{{ $js_game_skills_val->title }}" });
    @endforeach
    game_arr.push({id:"{{ $js_game_val->id }}", skill: game_skill_arr});
  @endforeach
  @if(!empty(request()->query('skills')))
    @foreach(request()->query('skills') as $query_skill => $query_skill_val )
      test_arr.push('{!!  $query_skill_val  !!}');
    @endforeach
  @endif

  $(document).on('change','.filter_game',function(){
    show_game_skill_data($(this).val());
  })
  show_game_skill_data($('.filter_game').val());
  

  function show_game_skill_data(game_id)
  {
    var filter_selected_game_result = game_arr.filter(function(select_games_val_elem){
      return select_games_val_elem.id == game_id; 
    });
    var show_skill_checkboxs = "";
    if(filter_selected_game_result.length > 0)
    {
      var selected_game_skill = filter_selected_game_result[0]['skill'];
      $(selected_game_skill).each(function(selected_game_skill_key, selected_game_skill_val){
        var skill_checked = "";
        if($.inArray(selected_game_skill_val['id'], test_arr) != -1){
          skill_checked = 'checked';
        }
        show_skill_checkboxs+='<div class="input-group"><div class="fancy-checkbox"><label><input class="seach_coach_skill_checkbox" type="checkbox" name="skills[]" value="'+selected_game_skill_val['id']+'" '+skill_checked+'><span>'+selected_game_skill_val['title']+'</span></label></div></div>';
      });
    }
    $('.selected_game_skill').html(show_skill_checkboxs);
  }


  $(document).on('change','.seach_coach',function(){
    $(this).closest('form.filter-coach-form').submit();
  });

  $(document).on('click','.seach_coach_skill_checkbox',function(){
    $(this).closest('form.filter-coach-form').submit();
  });

  $(document).on('keyup','.search-by-name',function(){
    if($(this).val().length) {
        $('.search-button').show();
    } else {
      $('.search-button').hide();
    }
  });

  var max_exp = "{{ getUserMaxExperience() }}";
  max_exp = parseInt(max_exp);
  var min_experience = "{{ !empty(request()->query('min_experience')) ? request()->query('min_experience') : 0 }}"; 
  var max_experience = "{{ !empty(request()->query('max_experience')) ? request()->query('max_experience') : getUserMaxExperience() }}";
 //Experience Range Example
 var rangeExperienceSlider = document.getElementById('nouislider_range_experience');
 noUiSlider.create(rangeExperienceSlider, {
     start: [min_experience, max_experience],
     connect: true,
     range: {
        'min': 0,
        'max': max_exp
     }
 });
  getNoUISliderExperienceValue(rangeExperienceSlider, false);
  //Get noUISlider Value and write on
  function getNoUISliderExperienceValue(Experienceslider, percentage) {
    Experienceslider.noUiSlider.on('update', function () {
      var experience_range_val = Experienceslider.noUiSlider.get();
      $('.min_experience').val(parseInt(experience_range_val[0]));
      $('.max_experience').val(parseInt(experience_range_val[1]));
      $(Experienceslider).parent().find('span.js-nouislider-value-experience').text(parseInt(experience_range_val[0])+' to '+parseInt(experience_range_val[1])+' ( Years.)');
    });
  }

  $(rangeExperienceSlider)[0].noUiSlider.on('change',function(v,handle){
    $('form.filter-coach-form').submit();
  });

});
</script> 

@endsection