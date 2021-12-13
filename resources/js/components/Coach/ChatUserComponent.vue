<template>
  <div>
    <div class="row"> 
      <div class="col-lg-3">
        <div id="plist" class="people-list">
          <div class="input-group search-section">
            <div class="input-group-prepend"> <span class="input-group-text search-btn-icn"><i class="icon-magnifier"></i></span> </div>
            <input type="text" class="form-control search-input" v-model="chat_user_search" placeholder="Search...">
          </div>
          <div class="chatting_user_list" v-if="ChatFilteredList.length > 0">
            <ul class="list-unstyled chat-list mb-0">
                <li :class="chatuser.chat_uuid ==  active_el  ? 'clearfix chat-user-click active' : 'clearfix chat-user-click'" v-for="chatuser in ChatFilteredList" >
                  <div :key="chatuser.chat_uuid"  @click="openuserchat(chatuser.chat_uuid)">
                  <figure class="pro-pic">
                    <img v-if="chatuser.chat_type == 1" :src="(login_user_detail.id != chatuser.one_user_id ? (chatuser.one_users.user_image ? chatuser.one_users.user_image : no_image_url) : (chatuser.two_users.user_image ? chatuser.two_users.user_image : no_image_url))" alt="avatar" />
                    <img v-else :src="group_image_url" alt="avatar" />
                  </figure>
                  <div class="about">
                    <div class="name">{{ chatuser.chat_type == 2 ? chatuser.group_name : login_user_detail.id != chatuser.one_user_id ? chatuser.one_users.name :  chatuser.two_users.name  }}</div>
                    <div class="status" v-if="chatuser.chat_messages_count  > 0"> <i class="fa fa-circle offline"></i> {{chatuser.chat_messages_count}} </div>
                  </div>
                  </div>
                  <div class="chat-option">
                    <div class="dropdown">
                      <button data-toggle="dropdown" class="dropdown-toggle dropdown-open" @click="openDropdown(chatuser.id)"></button>
                      <div :id="'open_dropdown_' + chatuser.id" class="dropdown-menu">
                        <a href="#" class="dropdown-item" v-on:click="deleteCompleteChat(chatuser.chat_uuid, chatuser.chat_type == 2 ? chatuser.group_name : login_user_detail.id != chatuser.one_user_id ? chatuser.one_users.name :  chatuser.two_users.name)">Delete</a>
                      </div>
                    </div>
                  </div>
                </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-lg-9">
        <component :is="currentComp" :key="selected_chat_uuid" :selected_chat_dt="selected_chat_dt" :selected_chat_uuid="selected_chat_uuid" :login_user_detail="login_user_detail" :selected_chat_user="selected_chat_user"></component>
      </div>
    </div>
    <div class="modal add_event_category_modal delete-chat-popup" id="deleteAllChatPopup" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="title" id="selfDelete">Delete Chat</h4>
          </div>
          <div class="modal-body">
            <div class="c-b-box">
              <div class="body">
                <p>Are you sure you want to delete the chat with <strong>{{ deleteUserName }}</strong></p>
                <div class="form-group">
                  <input class="d-c-checkbox" type="checkbox" id="checkbox" v-model="mediaChecked">
                  <label for="checkbox">Also delete media</label>
                </div>
                <div class="d-c-btns">
                <ul class="cmn-ul-list">
                  <li><button type="button" class="btn btn-simple" data-dismiss="modal">Cancel</button></li>
                  <li><button class="btn btn-outline-primary" v-on:click="confirmDelete()">Delete Chat</button></li>
                </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>




</template>
<script>
  import chatmessage from '././ChatMessageComponent.vue';
  import groupchatmessage from '././GroupChatMessageComponent.vue';
  import chatpage from '././ChatPageComponent.vue';
  import Vue from "vue";
  const firebase = require('../../firebaseConfig.js');
  export default {
    components: {chatmessage, chatpage, groupchatmessage},
    props: ['login_user','request_athelete_chat'],
    data: function() {
      return {
        chat_user_search : '',
        chatusers : [],
        link_url : Vue.http.options.BASE_URL,
        no_image_url :  Vue.http.options.BASE_URL+'/images/noimage.jpg',
        group_image_url :  Vue.http.options.BASE_URL+'/images/groupuser.jpg',
        currentComp : 'chatpage',
        selected_chat_uuid : '',
        login_user_detail : this.login_user,
        selected_chat_user : '',
        selected_chat_dt : '',
        active_el: '',
        user_chat_ref: firebase.firebase_db.ref('chats/'),
        user_list_ref: firebase.firebase_db.ref('chat_users/'),
        request_athelete_chat_detail : this.request_athelete_chat,
        user_conversations : '',
        deleteChatId: '',
        deleteUserName: '',
        mediaChecked: '',
      }
    },
    computed: {

      ChatFilteredList() {
        return this.chatusers.filter(chatuser_dt => {
          return chatuser_dt.chat_type == 2 ? chatuser_dt.group_name.toLowerCase().includes(this.chat_user_search.toLowerCase()) : this.login_user_detail.id != chatuser_dt.one_user_id ? chatuser_dt.one_users.name.toLowerCase().includes(this.chat_user_search.toLowerCase()) : chatuser_dt.two_users.name.toLowerCase().includes(this.chat_user_search.toLowerCase());
        })
      }
    },

    created: function () {
      this.fetchUsers();
      this.user_chat_ref.on('value', (user_chat_ref_snapshot) => {
        user_chat_ref_snapshot.forEach((user_chat_ref_doc) => {
          let user_chat_ref_item = user_chat_ref_doc.val();
          user_chat_ref_item.key = user_chat_ref_doc.key;
          if (user_chat_ref_item.user_conversations.chat_type == 1) {
            if(this.login_user.id == user_chat_ref_item.user_conversations.one_user_id || this.login_user.id == user_chat_ref_item.user_conversations.two_user_id )
            {
              if(this.currentComp == 'chatmessage' && this.selected_chat_uuid == user_chat_ref_item.user_conversations.chat_uuid){
                this.MakeReadMessage(user_chat_ref_item.user_conversations.chat_uuid,'R',user_chat_ref_item.message_uuid);
              }else{
                this.MakeReadMessage(user_chat_ref_item.user_conversations.chat_uuid, 'U');
              }
            }
          } else {
            const found = user_chat_ref_item.user_conversations.group_users.some(el => el.user_id === this.login_user.id && el.status == 1);
            if (found) {
              if(this.currentComp == 'groupchatmessage' && this.selected_chat_uuid == user_chat_ref_item.user_conversations.chat_uuid){
                this.MakeGroupReadMessage(user_chat_ref_item.user_conversations.chat_uuid,'R',user_chat_ref_item.message_uuid);
              }else{
                this.MakeGroupReadMessage(user_chat_ref_item.user_conversations.chat_uuid, 'U');
              }
            }
          }
        });
      });

      this.user_list_ref.on('value', (user_list_ref_snapshot) => {
        user_list_ref_snapshot.forEach((user_list_ref_doc) => {
          let user_list_ref_item = user_list_ref_doc.val();
          user_list_ref_item.key = user_list_ref_doc.key;
          const found = user_list_ref_item.group_users.some(el => el.user_id === this.login_user.id && el.status == 1);
          const chatExist = this.chatusers.some(el => el.id === user_list_ref_item.id);
          if (found && !chatExist) {
            this.chatusers.unshift(user_list_ref_item);
          }
          var currentRef = firebase.firebase_db.ref('chat_users/' + user_list_ref_doc.key);
          currentRef.remove();
        });
      });
    },
    
    mounted() {
      
    },

    methods:{


      fetchUsers:function(){
        $('.processing-loader').show();
        let vm = this;
        let router_url =Vue.http.options.BASE_URL+'/coach/chatuser';
        axios.get(router_url).then(res=>{
          $('.processing-loader').hide();
          if(res.status==200){
            if(res.data.status){
              this.chatusers = res.data.data.result;
              if(Object.keys(this.request_athelete_chat_detail).length > 0){
                this.openuserchat(this.request_athelete_chat_detail.chat_uuid);
              }
              this.request_athelete_chat_detail = {};
            }else{
              this.chatusers = [];
            }
          }
        }).catch(err=>{
          console.log(err);
        });
      },

      MakeReadMessage:function(user_chat_d, chat_message_type, message_id= ''){
        let vm = this;
        let router_url =Vue.http.options.BASE_URL+'/coach/chat/readmessage';
        let formData = new FormData();
        formData.append('chatting_id', user_chat_d);
        formData.append('type', chat_message_type);
        if(chat_message_type == 'R')
        {
          formData.append('message_id', message_id);
        }
        axios.post(router_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
          if (res.status == 200) {
            if(res.data.status){
              if(res.data.data.unread_message_count > 0)
              {
                $('.unread_message_flag').addClass('unread_message_flag_color');
                $('.unread_message_flag').after('<span class="notification-dot"></span>');
              }else{
                $('.unread_message_flag').removeClass('unread_message_flag_color');
                $(".remove-notification-dot span").remove();
              }
              let user_con = res.data.data.result;
              if(this.chatusers.length > 0){
                let filterChatUserDt =   this.chatusers.filter(chatusers_filter_dt => {
                                  return chatusers_filter_dt.id == user_con.id;
                                });
                if(filterChatUserDt.length == 0)
                {
                  this.chatusers.push(user_con);
                }
              }else{
                this.chatusers.push(user_con);
              }
              this.chatusers.forEach(chatusers_element => {
                if(chatusers_element.id == user_con.id)
                {
                  this.$set(chatusers_element, 'chat_messages_count', user_con.chat_messages_count);
                }
              });
            }
            console.log(res.data.status);
          }
        }).catch((error) => {
          console.log(error);
        });
      },

      MakeGroupReadMessage:function(user_chat_d, chat_message_type, message_id= ''){
        let vm = this;
        let router_url =Vue.http.options.BASE_URL+'/coach/chat/groupreadmessage';
        let formData = new FormData();
        formData.append('chatting_id', user_chat_d);
        formData.append('type', chat_message_type);
        if(chat_message_type == 'R')
        {
          formData.append('message_id', message_id);
        }
        axios.post(router_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
          if (res.status == 200) {
            if(res.data.status){
              if(res.data.data.unread_message_count > 0)
              {
                $('.unread_message_flag').addClass('unread_message_flag_color');
                $('.unread_message_flag').after('<span class="notification-dot"></span>');
              }else{
                $('.unread_message_flag').removeClass('unread_message_flag_color');
                $(".remove-notification-dot span").remove();
              }
              let user_con = res.data.data.result;
              if(this.chatusers.length > 0){
                let filterChatUserDt =   this.chatusers.filter(chatusers_filter_dt => {
                  return chatusers_filter_dt.id == user_con.id;
                });
                if(filterChatUserDt.length == 0)
                {
                  this.chatusers.push(user_con);
                }
              }else{
                this.chatusers.push(user_con);
              }
              this.chatusers.forEach(chatusers_element => {
                if(chatusers_element.id == user_con.id)
                {
                  this.$set(chatusers_element, 'chat_messages_count', user_con.chat_messages_count);
                }
              });
            }
            console.log(res.data.status);
          }
        }).catch((error) => {
          console.log(error);
        });
      },

      deleteCompleteChat:function(chatId, name){
        let vm = this;
        vm.deleteChatId = chatId
        vm.deleteUserName = name
        $('#deleteAllChatPopup').modal();
      },

      confirmDelete: function() {
        let media = this.mediaChecked;
        if (this.mediaChecked) {
          media = 1;
        } else  {
          media = 0;
        }
        $('.processing-loader').show();
        let update_request_url = Vue.http.options.BASE_URL + '/coach/chatdelete';
        let formData = new FormData();
        formData.append('media_delete', media);
        formData.append('chatting_id', this.deleteChatId);
        axios.post(update_request_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
          if (res.status == 200) {
            $('.processing-loader').hide();
            if (res.data.status) {
              $('#deleteAllChatPopup').modal().hide();
              this.RemoveModalBackDrop();
              $('.theme-cyan').removeClass('modal-open');
              let message_data = res.data.data.result;
              this.chatusers = $.grep(this.chatusers, function(e){
                return e.id != message_data.id;
              });
              window.location.reload();
            }else{
              toastr.error(res.data.message);
            }
          }
        }).catch((error) => {
          console.log(error);
        });
      },

      openuserchat:function(el){
        $('.processing-loader').show();
        let vm = this;
        this.active_el = el;
        this.selected_chat_uuid = el;
        let selected_chat = "";
        selected_chat = this.chatusers.filter(chatuser_item => {
          return chatuser_item.chat_uuid == this.selected_chat_uuid;
        });
        if(selected_chat[0].chat_type == 1) {
          this.MakeReadMessage(this.selected_chat_uuid, 'A');
        } else {
          this.MakeGroupReadMessage(this.selected_chat_uuid, 'A');
        }

        let selected_chat_user_id = selected_chat[0].one_user_id != this.login_user_detail.id ? selected_chat[0].one_user_id : selected_chat[0].two_user_id;
        let selected_chat_id = selected_chat[0].chat_uuid;
        let ChatUserFormData = new FormData();
        ChatUserFormData.append('user_id', selected_chat_user_id);
        ChatUserFormData.append('chatting_id', selected_chat_id);
        let router_url =Vue.http.options.BASE_URL+'/coach/chatuserdetail';
        axios.post(router_url, ChatUserFormData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
          if(res.status==200){
            if(res.data.status){
              this.selected_chat_dt = selected_chat[0];
              this.selected_chat_user = res.data.data.result.user;
              if (selected_chat[0].chat_type == 1) {
                vm.currentComp = 'chatmessage';
              } else  {
                vm.currentComp = 'groupchatmessage';
              }
            }else{
              toastr.error(res.data.message);
            }
          }
        }).catch(err=>{
          toastr.error(err);
          console.log(err);
        });
      },
      RemoveModalBackDrop:function()
      {
        $('.modal-backdrop').remove();
      },
      openDropdown:function(id) {
        $('#open_dropdown_'+id+'').toggleClass('show')
      },

    }

  }
</script>