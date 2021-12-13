<template>
<div class="default_chat_page">

 <div class="jumbotron text-center"> 
 
 <div class="body text-center">
     <div class="profile-image"><img :src="login_user_detail.user_image ? login_user_detail.user_image : no_image_url" class="rounded-circle" alt="avatar" /> </div>
        <div class="m-t-30">
            <h4 class="m-b-0"><strong>Welcome, </strong> {{ login_user_detail.name }}</h4>
        </div>
        <div class="m-t-15">          
          <span class="badge">Start a Chat with Coach</span>
        </div>
      </div>
  </div>
 </div>
</template>


<script>
import Vue from 'vue';
    export default {
        props: ['login_user_detail'],
        data: function() {
          return {
            link_url : Vue.http.options.BASE_URL,
            no_image_url :  Vue.http.options.BASE_URL+'/images/noimage.jpg',
          } 
        },
        }
</script>