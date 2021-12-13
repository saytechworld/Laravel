<div class="product-tour-screen pt-coach">
  <div class="tour-in-content">
    <div class="cd-nugget-info">
        <figure class="tour-pic">
        <img src="{!! asset('assets/img/product-tour-img.svg') !!}" alt="">
        </figure>
      <h1>Welcome!</h1>
        <p>Thank you for signing up.<br>
        Reference site about Lorem Ipsum, giving information on its origins.
        </p>
        <div class="skip-start-btns">
            <ul class="cmn-ul-list">
                <li><button id="cd-tour-trigger" class="cd-btn">Start tour</button></li>
                <li><a class="cmn-link cd-next-finish" href="#0">Skip</a></li>
            </ul>
        </div>
    </div>
    <ul class="cd-tour-wrapper">
      <li class="cd-single-step pt-notification"> <span>Step 1</span>
        <div class="cd-more-info bottom">
          <h2>Notification</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Modi alias animi molestias in, aperiam.</p>
        </div>
      </li>
      <li class="cd-single-step pt-chat"> <span>Step 2</span>
        <div class="cd-more-info bottom">
          <h2>Chat</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Officia quasi in quisquam.</p>
        </div>
      </li>
      <li class="cd-single-step pt-profile"> <span>Step 3</span>
        <div class="cd-more-info bottom">
          <h2>Profile</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Optio illo non enim ut necessitatibus perspiciatis, dignissimos.</p>
        </div>
      </li>
      <li class="cd-single-step pt-agenda"> <span>Step 4</span>
        <div class="cd-more-info bottom">
          <h2>Agenda</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Optio illo non enim ut necessitatibus perspiciatis, dignissimos.</p>
        </div>
      </li>
      <li class="cd-single-step pt-video-a"> <span>Step 5</span>
        <div class="cd-more-info bottom">
          <h2>Video Archive</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Optio illo non enim ut necessitatibus perspiciatis, dignissimos.</p>
        </div>
      </li>
      <li class="cd-single-step pt-photo-a"> <span>Step 6</span>
        <div class="cd-more-info bottom">
          <h2>Photo Archive</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Optio illo non enim ut necessitatibus perspiciatis, dignissimos.</p>
        </div>
      </li>
      <li class="cd-single-step pt-coaches"> <span>Step 7</span>
        <div class="cd-more-info bottom">
          <h2>Orders</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Optio illo non enim ut necessitatibus perspiciatis, dignissimos.</p>
        </div>
      </li>
      <li class="cd-single-step pt-subscription"> <span>Step 8</span>
        <div class="cd-more-info bottom">
          <h2>Subscription</h2>
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Optio illo non enim ut necessitatibus perspiciatis, dignissimos.</p>
        </div>
      </li>
    </ul>
  </div>
  <div class="cd-app-screen"></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" type="text/javascript"></script>
<script>
  // A $( document ).ready() block.
  $( window ).on( "load", function() {
      var url = "{!! route('ajax.user.first_login') !!}";
      $.ajax({
        type: "GET",
        url : url,

        async: false,
        success: function(data) {
          console.log(data)
        },
        error: function(xhr) { // if error occured
          console.log("Error occured.please try again");
        },
        complete: function() {

        },
      });
  });
</script>