<template>
  <div class="row">
    <div class="col-lg-12">
      <div class="chat">
        <div class="chat-header clearfix">
          <div class="row">
            <div class="col-4">
                <a href="javascript:void(0);" @click="viewInfo()"> <img :src="link_url+'/images/groupuser.jpg'" alt="avatar" />
                  <div class="chat-about">
                    <h6 class="m-b-0">{{selected_chat_dt.group_name}}</h6>
                  </div>
                </a>
            </div>
              <div class="col-8">

            </div>
          </div>
        </div>
        <div class="chat-history user_messages" v-chat-scroll="{always: false, smooth: true}" v-on:scroll="scrollFunction">
         
          <ul class="m-b-0">
            <li class="clearfix" v-for="user_message in user_messages" >
                <div class="msg_date_sec" v-if="user_message.dateValue != 0">
                    <span>{{user_message.message_created_date }}</span>
                </div>
              <div v-if="user_message.user_id !=login_user_detail.id">
                <div class="message my-message">
                    <span class="sender-name">{{ user_message.senders.name }}</span>
                    <div v-if="user_message.message_type == 2" class="all-chat-msg-pos img-timing">
                      <img :src="user_message.aws_file_url" alt="avatar" />
                      <span class="timing">{{ user_message.message_created_time }}</span>
                    </div>
                    <div v-if="user_message.message_type == 3" class="all-chat-msg-pos img-timing">
                      <video width="320" height="200" controls>
                        <source :src="user_message.aws_file_url" type="video/mp4">
                        Your browser does not support the video tag.
                      </video>
                       <span class="timing">{{ user_message.message_created_time }}</span>
                    </div>
                  <div v-if="user_message.message_type == 12" class="all-chat-msg-pos document_box black-chat">
                    <a :href="link_url+'/downloadfile/D/'+ user_message.id">
                    <div class="document_sec">
                      <p class="doc_file_name">{{ user_message.thumbnail }}</p>
                      <div class="document-file">
                        <i class="fa-file-o"></i> File
                      </div>
                      <a :href="link_url+'/downloadfile/D/'+ user_message.id" class="download-document">Download</a>
                    </div>
                    </a>
                    <span class="timing">{{ user_message.message_created_time }}</span>
                  </div>
                    <div v-if="user_message.message_type == 1" class="all-chat-msg-pos">
                      <pre v-linkified>{{ user_message.message }}</pre>
                       <span class="timing">{{ user_message.message_created_time }}</span>
                    </div>
                  <div class="chat-option">
                    <div class="dropdown" v-if="user_message.message_type == 1 || user_message.message_type == 2 || user_message.message_type == 3 || user_message.message_type == 12">
                        <button class="dropdown-toggle dropdown-open" data-toggle="dropdown" @click="openDropdown(user_message.id)"></button>
                        <div class="dropdown-menu" :id="'open_dropdown_' + user_message.id">
                            <a v-if=" user_message.message_type == 2 || user_message.message_type == 3" class="dropdown-item" href="#"  @click="openMediaFolderPopup(user_message.id)">Save</a>
                            <a :href="link_url+'/downloadfile/M/'+ user_message.id" v-if=" user_message.message_type == 2 || user_message.message_type == 3" class="dropdown-item">Download</a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#otherDeletePopup"  @click="delete_message_id = user_message.id">Delete</a>
                        </div>
                    </div>
                </div>
                    
                </div>
              </div>
              <div v-else>
                <div class="message other-message float-right">
                    <div v-if="user_message.message_type == 2" class="all-chat-msg-pos img-timing">
                      <img :src="user_message.aws_file_url" alt="avatar" />
                        <div v-if="user_message.sent_status == false" class="loading_icon" >
                            <img :src="loading_icon_url" />
                        </div>
                        <span class="timing">
                            {{ user_message.message_created_time }}
                            <i class="message-status" v-if="user_message.sent_status == true"><img :src="double_tick_icon_url" style="height: 11px;"></i>
                            <i class="message-status" v-else><img :src="watch_icon_url" style="height: 11px;"></i>
                        </span>
                    </div>
                    <div v-if="user_message.message_type == 3" class="all-chat-msg-pos img-timing">
                      <video width="320" height="200" controls>
                        <source :src="user_message.aws_file_url" type="video/mp4">
                        Your browser does not support the video tag.
                      </video>
                        <div v-if="user_message.sent_status == false" class="loading_icon" >
                            <img :src="loading_icon_url" />
                        </div>
                        <span class="timing">
                            {{ user_message.message_created_time }}
                            <i class="message-status" v-if="user_message.sent_status == true"><img :src="double_tick_icon_url" style="height: 11px;"></i>
                            <i class="message-status" v-else><img :src="watch_icon_url" style="height: 11px;"></i>
                        </span>
                    </div>
                  <div v-if="user_message.message_type == 12" class="all-chat-msg-pos document_box black-chat">
                    <a :href="link_url+'/downloadfile/D/'+ user_message.id">
                    <div class="document_sec">
                      <p class="doc_file_name">{{ user_message.thumbnail }}</p>
                      <div class="document-file">
                        <i class="fa-file-o"></i> File
                      </div>
                      <a :href="link_url+'/downloadfile/D/'+ user_message.id" class="download-document">Download</a>
                    </div>
                    </a>
                    <span class="timing">
                        {{ user_message.message_created_time }}
                        <i class="message-status" v-if="user_message.sent_status == true"><img :src="double_tick_icon_url" style="height: 11px;"></i>
                        <i class="message-status" v-else><img :src="watch_icon_url" style="height: 11px;"></i>
                    </span>
                  </div>
                    <div v-if="user_message.message_type == 1" class="all-chat-msg-pos">
                      <pre v-linkified>{{ user_message.message }}</pre>
                        <span class="timing">
                            {{ user_message.message_created_time }}
                            <i class="message-status" v-if="user_message.sent_status == true"><img :src="double_tick_icon_url" style="height: 11px;"></i>
                            <i class="message-status" v-else><img :src="watch_icon_url" style="height: 11px;"></i>
                        </span>
                    </div>
                  <div class="chat-option">
                    <div class="dropdown" v-if="user_message.message_type == 1 || user_message.message_type == 2 || user_message.message_type == 3 || user_message.message_type == 12">
                        <button class="dropdown-toggle dropdown-open" data-toggle="dropdown" @click="openDropdown(user_message.id)"></button>
                        <div class="dropdown-menu" :id="'open_dropdown_' + user_message.id">
                            <a v-if=" user_message.message_type == 2 || user_message.message_type == 3" class="dropdown-item" href="#"  @click="openMediaFolderPopup(user_message.id)">Save</a>
                            <a :href="link_url+'/downloadfile/M/'+ user_message.id" v-if=" user_message.message_type == 2 || user_message.message_type == 3" class="dropdown-item">Download</a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#selfDeletePopup"  @click="delete_message_id = user_message.id">Delete</a>
                        </div>
                    </div>
                    </div>
                    
                </div>
              </div>
            </li>
          </ul>
        </div>

        <template v-if="selected_chat_user.status == 1" >
        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="SubmitMessage"  >
          <div class="chat-message clearfix">
            <div class="input-group mb-0">
                <ul class="media-archive-button">
                    <li>
                        <button data-toggle="modal" data-target="#openMediaPopup" @click="media_type = 1" class="btn btn-outline-secondary"><i class="fa-file-image-o"></i></button>
                        <button data-toggle="modal" data-target="#openMediaPopup" @click="setUpPlupload()"  class="btn btn-outline-primary"><i class=" fa-file-video-o"></i></button>
                        <button  @click="$refs.message_file_document.click()" class="btn btn-outline-primary"><i class="fa-file-o"></i></button>
                    </li>
                </ul>
              <button class="input-group-prepend chat-msg-send-btn"> <span class="input-group-text"><img :src="link_url+'/images/send.svg'" /></span></button>
              <textarea
                      type="text" class="form-control chat-send-message" placeholder="Type a message"  v-model="message" name="message"
                      @keydown.enter.exact.prevent
                      @keydown.enter.shift.exact="newline"
                      @keyup.enter.exact="SubmitMessage"
              > </textarea>
            </div>
          </div>
        </form>
        </template>
          <template v-if="selected_chat_user.status == 2" >
              <p class="user_denied_account">You are removed from this group.</p>
          </template>
          <template v-if="selected_chat_user.status == 3" >
              <p class="user_denied_account">You are no longer participant of this group.</p>
          </template>

      </div>
    </div>
    <div class="col-lg-4">
      <input type="file" style="display: none" class="message_file_image" data-max-file-size="200M" name="message_file_image" accept=".png, .jpg, .jpeg" ref="message_file_image" v-on:change="message_file_sharing('I',$event)" >
      <input type="file" style="display: none" class="message_file_video" data-max-file-size="200M" name="message_file_video" accept=".mp4,.webm,.hdv,.flv,.avi,.wmv,.mov" ref="message_file_video" v-on:change="message_file_sharing('V',$event)" >
      <input type="file" style="display: none" class="message_file_document"  data-max-file-size="200M" name="message_file_document" accept=".doc,.docx,.pdf,.txt" ref="message_file_document" v-on:change="message_file_sharing('D',$event)" >
    </div>

    <div class="modal upload-m-popup openMediaPopup" id="openMediaPopup" tabindex="-1" role="dialog" ref="mediaModal">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
              <h4 class="title" id="openMediaData">Select File</h4>
              <a class="close-btn" data-dismiss="modal"><i class="icon-close"></i></a>
            </div>
            <div class="modal-body">
              <ul class="nav nav-tabs-new2">
                <li class="nav-item inlineblock"><a class="nav-link active" data-toggle="tab" href="#fromMedia" aria-expanded="true"> <i class="icon-folder-alt"></i> Select From Media </a></li>
                <li class="nav-item inlineblock"><a class="nav-link" data-toggle="tab" href="#upload" aria-expanded="true"><i class="icon-cloud-upload"></i> Upload</a></li>
              </ul>
              <div class="tab-content">
              
                <div role="tabpanel" class="tab-pane in active" id="fromMedia" aria-expanded="true">
                    <div class="row clearfix" v-if="media_type == 1">
                        <div class="col-lg-12">
                            <div class="header">
                                <h2>Folders</h2>
                                <ul class="header-dropdown">
                                
                                </ul>
                            </div>
                            <div class="folder-list">
                                <div class="row clearfix file_manager">
                                    <div class="col-lg-4 col-md-4 col-sm-12" v-for="photoFolder in photo_folders">
                                        <div class="radiobtn">
                                            <span  :class="'selected_photo_folder selected_photos_folder_' + photoFolder.id" v-on:click="changeImageFolder(photoFolder.id)">
                                                <i class="fa fa-folder" aria-hidden="true"></i> {{ photoFolder.title }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" v-if="media_type == 1">
                      <div class="col-sm-4 col-md-4 col-lg-4" v-for="photo in photo_archive" >
                        <div class="thumbnail">
                          <div class="hover">
                            <a :href="link_url+'/downloadfile/U/'+ photo.id" class="btn btn-outline-primary"><i class="fa fa-download"></i></a>
                          </div>
                          <img :src="photo.aws_video_folder_path" alt="avatar" v-on:click="SubmitMessageFileText(photo.id, 'MI', photo.aws_video_folder_path)" />
                        </div>
                      </div>
                    </div>

                    <div class="row clearfix" v-if="media_type == 2">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="header">
                                    <h2>Folders</h2>
                                    <ul class="header-dropdown">
                                    </ul>
                                </div>
                                <div class="body">
                                    <div class="row clearfix file_manager">
                                        <div class="col-lg-4 col-md-4 col-sm-12" v-for="videoFolder in video_folders">
                                            <div class="radiobtn">
                                                <span  :class="'selected_video_folder selected_videos_folder_'+videoFolder.id" v-on:click="changeVideoFolder(videoFolder.id)">
                                                    <i class="fa fa-folder" aria-hidden="true"></i> {{ videoFolder.title }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="row" v-if="media_type == 2">
                        <template>
                            <div class="col-sm-4 col-md-4 col-lg-4" v-for="video in video_archive" v-if="renderComponent" >
                                <div class="thumbnail">
                                    <div class="hover">
                                        <a :href="link_url+'/downloadfile/U/'+ video.id" class="btn btn-outline-primary"><i class="fa fa-download"></i></a>
                                    </div>
                                    <video class="video_thumbnail" v-on:click="SubmitMessageFileText(video.id, 'MV', video.aws_video_folder_path)">
                                        <source :src="video.aws_video_folder_path+'#t=0.1'" >
                                    </video>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <div role="tabpanel" class="tab-pane" id="upload" aria-expanded="true">
                  <div class="row clearfix">
                    <div class="col-md-12">
                    <div class="upload-media-bx">
                      <button class="btn upload-m-btn" @click="$refs.message_file_image.click()" v-if="media_type == 1"><i class="icon-picture"></i><br> Upload Image</button>
                        <template v-if="media_type == 2">
                            <div id="container">
                                <button class="btn upload-m-btn"  id="pickfiles" ><i class="icon-camcorder"></i> <br>Upload Video</button>
                            </div>
                            <br />
                            <div id="filelist">Your browser doesn't have Flash, Silverlight or HTML5 support.</div>
                            <br />
                            <pre id="console"></pre>
                            <div class="upload-progress-bar">
                                <div class="progress">
                                    <div class="bar"></div >
                                    <div class="percent">0%</div >
                                </div>
                            </div>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
                
              </div>
            </div>           
        </div>
      </div>
    </div>

    <div class="modal add_event_category_modal  group-info-modal" id="view_info" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="title" id="info">Group Info</h4>
            <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
          </div>
          <div class="modal-body">
            <div class="msg-group-modify-sec">
                  <div class="edit-group-name">
                      <h5 class="title">{{selected_chat_dt.group_name}}</h5>
                      <span v-if="this.admin == 1"  class="group-name-edit-btn" data-dismiss="modal" data-toggle="modal" data-target="#edit_group_name"><i class="fa fa-pencil"></i></span>
                  </div>
              <div class="exit-group-btn"> 
                    <button v-if="this.admin == 1" class="btn btn-success" @click="addParticipant">Add Participant</button>
                <button v-if="this.admin != 1 && selected_chat_user.status == 1" class="btn btn-danger" @click="exitGroup">Exit Group</button>
                </div>
                </div>
            <div class="row">            
              
                <div class="col-md-4"  v-for="user in group_users" >
                  <div class="card c-b-box">
                    <div class="body text-center">
                      <div class="profile-image" data-percent="75">
                        <img :src="user.users.user_image ? user.users.user_image : no_image_url" alt="avatar">
                      </div>
                      <span class="c-b-name"><a href="#"> {{ user.users.name }}</a></span>
                        <span class="c-b-name" v-if="user.admin == 1">Admin</span>
                      <p v-if="user.users.role_type != 'athlete'">Experience: {{ user.users.user_details ? user.users.user_details.experience : '0' }} Years</p>
                      <ul class="cmn-ul-list">
                        <li><a :href="link_url+'/coach/athlete/profileDetail/'+user.users.username" class="btn btn-outline-primary"><i class="fa fa-user"></i> <span>Profile</span></a></li>
                          <li><a href="javascript:void(0)" v-if="admin == 1 && user.users.id != login_user_detail.id" class="btn btn-outline-primary"  @click="removeFromGroup(user.users.id)"><i class="fa fa-user"></i> <span>Remove</span></a></li>
                      </ul>
                    </div>
                  </div>    
                </div>
              </div>
         
            
          </div>
        </div>
      </div>
    </div>

      <div class="modal add_event_category_modal" id="edit_group_name" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h4 class="title" id="group_name">Edit Group Name</h4>
                      <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                  </div>
                  <form method="POST" v-on:submit.prevent="editGroupName">
                      <div class="modal-body">
                          <div class="form-group group_user_list">
                              <label for="user">Group Name</label>
                              <div class="form-line">
                                  <input type="text" class="form-control" v-model="group_name">
                              </div>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <input type="submit" class="btn btn-primary" value="Update">
                      </div>
                  </form>
              </div>
          </div>
      </div>

      <div class="modal add_event_category_modal" id="add_participant" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h4 class="title" id="paricipant">Add Participant</h4>
                      <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                  </div>
                  <form method="POST" v-on:submit.prevent="addMember">
                      <div class="modal-body">
                          <div class="form-group group_user_list">
                              <label for="user">Members</label>
                              <div class="form-line">
                                  <select name="member[]" v-model="members_id"  class="form-control group_member" multiple required>
                                      <option v-for="user in new_users" :value="user.id">{{user.name}}</option>
                                  </select>
                              </div>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <input type="submit" class="btn btn-primary" value="Add">
                      </div>
                  </form>
              </div>
          </div>
      </div>



      <div class="modal add_event_category_modal" id="selfDeletePopup" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h4 class="title" id="selfDelete">Delete message?</h4>
                  </div>
                  <div class="modal-body">
                      <div class="c-b-box">
                          <div class="body text-center">
                          <ul class="cmn-ul-list">
                          <li><button class="btn btn-outline-primary" v-on:click="DeleteMessage('S', 'E')">Delete For Everyone</button></li>
                          <li><button class="btn btn-outline-primary" v-on:click="DeleteMessage('S', 'M')">Delete For Me</button></li>
                          <li><button type="button" class="btn btn-simple" data-dismiss="modal">CLOSE</button></li>
                          </ul>                             
                          </div>
                      </div>
                  </div>                  
              </div>
          </div>
      </div>
      <div class="modal add_event_category_modal" id="otherDeletePopup" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h4 class="title" id="otherDelete">Delete message?</h4>
                  </div>
                  <div class="modal-body">
                      <div class="c-b-box">
                          <div class="body text-center">
                           <ul class="cmn-ul-list">
                          <li><p><button class="btn btn-outline-primary" v-on:click="DeleteMessage('O', 'M')">Delete For Me</button></p></li>
                          <li><p><button type="button" class="btn btn-simple" data-dismiss="modal">CLOSE</button></p></li>
                          </ul>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      <div class="modal" id="moveToFolder" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="folder_ajax_modal">

              </div>
          </div>
      </div>
  </div>

</template>
<script>
  import Vue from 'vue';
  import VueChatScroll from 'vue-chat-scroll';
  import linkify from 'vue-linkify'

  Vue.use(VueChatScroll);
  Vue.directive('linkified', linkify)
  const firebase = require('../../firebaseConfig.js');
  
  export default {
    props: ['selected_chat_uuid', 'login_user_detail', 'selected_chat_user', 'selected_chat_dt'],
    data: function () {
      return {

        user_messages: [],
        photo_archive: [],
        video_archive: [],
        photo_folders: [],
        video_folders: [],
        members_id: [],
        media_folder_check : '',
        media_type : '',
        group_users: [],
        new_users : [],
          admin:0,
        link_url: Vue.http.options.BASE_URL,
        aws_link_url : Vue.http.options.AWS_BASE_URL,
        no_image_url: Vue.http.options.BASE_URL + '/images/noimage.jpg',
        double_tick_icon_url: Vue.http.options.BASE_URL + '/images/double-tick-indicator.png',
        watch_icon_url: Vue.http.options.BASE_URL + '/images/ic_message_watch.png',
        loading_icon_url: Vue.http.options.BASE_URL + '/images/file-loader.gif',
        tempUrl: '',
        message: '',
        chat_ref: firebase.firebase_db.ref('chats/'),
        delete_user_ref: firebase.firebase_db.ref('user_references/'),
        group_ref: firebase.firebase_db.ref('group_chat/'),
          group_name_ref: firebase.firebase_db.ref('group_name/'),
        MESSAGE_IMAGE_SIZE: Vue.http.options.MESSAGE_IMAGE_SIZE,
        MESSAGE_VIDEO_SIZE: Vue.http.options.MESSAGE_VIDEO_SIZE,
        group_name: this.selected_chat_dt.group_name,
        loading: false,
          scrollPage : 1,
          paginate : true,
          lastDate: null,
          renderComponent: true,
      }
    },

    created: function () {
      this.fetchUserChats();
      this.fetchMediaArchive();
      this.chat_ref.on('value', (snapshot) => {
        snapshot.forEach((doc) => {
          let item = doc.val();
          item.key = doc.key;
          if (this.selected_chat_dt.id == item.chat_id && this.selected_chat_user.status == 1) {

              let messages = this.user_messages;
              if (item.delete_everyone) {
                  var result = $.grep(messages, function(e){
                      return e.id != item.id;
                  });
                  this.user_messages = result;
              } else if(item.group_delete_message && item.group_delete_message.indexOf(this.login_user_detail.id) != -1) {
                  var result = $.grep(messages, function(e){
                      return e.id != item.id;
                  });
                  this.user_messages = result;
              } else if(!item.delete_everyone && !item.group_delete_message) {
                  if (this.lastDate != item.message_created_date) {
                      this.lastDate = item.message_created_date;
                      item.dateValue = item.message_created_date;
                  } else {
                      item.dateValue = 0;
                  }

                  let messages = this.user_messages;
                  if(item.user_id == this.login_user_detail.id) {
                      var index = messages.findIndex(message => message.message_sent_uuid === item.message_sent_uuid);
                      if (index >= 0) {
                          messages.splice(index, 1, item);
                          var currentRef = firebase.firebase_db.ref('chats/' + doc.key);
                          currentRef.remove();
                          return false;
                      }
                  }

                  this.user_messages.push(item);
              }
          }
          var currentRef = firebase.firebase_db.ref('chats/' + doc.key);
          currentRef.remove();
        });
      });

        this.group_ref.on('value', (group_ref_snapshot) => {
            group_ref_snapshot.forEach((group_ref_doc) => {
                let group_ref_item = group_ref_doc.val();
                group_ref_item.key = group_ref_doc.key;
                if (this.selected_chat_user.id == group_ref_item.id) {
                    this.selected_chat_user.status = group_ref_item.status;
                }
                let currentRequestRef = firebase.firebase_db.ref('group_chat/' + group_ref_doc.key);
                currentRequestRef.remove();
            });
        });

        this.group_name_ref.on('value', (group_name_ref_snapshot) => {
            group_name_ref_snapshot.forEach((group_name_ref_doc) => {
                let group_name_ref_item = group_name_ref_doc.val();
                group_name_ref_item.key = group_name_ref_doc.key;
                if (this.selected_chat_dt.id == group_name_ref_item.id) {
                    this.selected_chat_dt.group_name = group_name_ref_item.group_name;
                    this.group_name =  group_name_ref_item.group_name;
                }
                let currentRequestRef = firebase.firebase_db.ref('group_name/' + group_name_ref_doc.key);
                currentRequestRef.remove();
            });
        });

    },

    computed: {

    },
    mounted() {
        this.setUpPlupload();
    },


    methods: {

      newline() {
        this.value = `${this.value}\n`;
      },

        openDropdown:function(id) {
            $('#open_dropdown_'+id+'').toggleClass('show')
        },

        scrollFunction:function() {
            if($(".chat-history").scrollTop() == 0) {
                if (this.paginate) {
                    this.fetchUserChats();
                }
            }
        },
        changeImageFolder:function(folderId) {
          $('.processing-loader').show();
          $(".selected_photo_folder").not($('.selected_photos_folder_'+ folderId)).removeClass('selected');
          if($('.selected_photos_folder_'+ folderId).hasClass('selected')){
            $('.selected_photos_folder_'+ folderId).removeClass('selected');
            this.fetchMediaArchive();
            this.media_folder_check = '';
              $('.processing-loader').hide();
          }else{
            $('.selected_photos_folder_'+ folderId).addClass('selected');
            let router_url = Vue.http.options.BASE_URL + '/coach/folderMediaArchive';
            axios.post(router_url, {folder_id: folderId}).then(res => {
                $('.processing-loader').hide();
                if (res.status == 200) {
                    if (res.data.status) {
                        this.photo_archive = [];
                        this.photo_archive = res.data.data.result;
                    } else {
                        this.photo_archive = [];
                    }
                }
            }).catch(err => {
                console.log(err);
            });
          }
          
        },

        viewInfo:function() {
          let vm = this;
          $('.processing-loader').show();
        let router_url = Vue.http.options.BASE_URL + '/coach/fetchgroupinfo';
        axios.post(router_url, {chatting_id: this.selected_chat_uuid}).then(res => {
          if (res.status == 200) {
              $('.processing-loader').hide();
            if (res.data.status) {
                this.group_users = [];
                this.group_users = res.data.data.result.group_user.active_group_users;
                this.admin = res.data.data.result.admin;
                $('#view_info').modal();
            } else {
                this.group_users = [];
            }
          }
        }).catch(err => {
          console.log(err);
        });
          
        },

        setUpPlupload() {
            let vm = this;
            vm.media_type = 2;
            var bar = $('.bar');
            var percent = $('.percent');

            var uploader = new plupload.Uploader({
                runtimes : 'gears,html5,flash,silverlight,browserplus',
                browse_button : 'pickfiles', // you can pass in id...
                container: document.getElementById('container'), // ... or DOM Element itself
                url : Vue.http.options.BASE_URL+'/ajax/video/store',
                chunk_size: '30mb',
                max_retries: 3,
                max_file_count: 1,
                //multi_selection: false,

                flash_swf_url : '/plupload/js/plupload.flash.swf',
                silverlight_xap_url : '/plupload/js/plupload.silverlight.xap',

                filters : {
                    max_file_size : '400mb',
                    mime_types: [
                        {title : "Video files", extensions : "mp4,avi,webm,hdv,flv,wmv,mov"},
                    ],
                },

                init: {
                    PostInit: function() {
                        document.getElementById('filelist').innerHTML = '';
                        bar.width('');
                        percent.html('');
                    },

                    FilesAdded: function(up, files) {
                        plupload.each(files, function(file) {
                            document.getElementById('filelist').innerHTML = '';
                            if (up.files.length > 1) {
                                up.removeFile(up.files[0]);
                            }
                            document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
                            uploader.start();
                            uploader.disableBrowse(true);
                        });
                    },

                    UploadProgress: function(up, file) {
                        if (file.percent == 100) {
                            file.percent = 99;
                        }
                        document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
                        var percentVal = file.percent + '%';
                        bar.width(percentVal);
                        percent.html(percentVal);
                    },

                    FileUploaded: function(up, file, info) {
                        let res = JSON.parse(info.response);
                        if (res.status) {
                            percent.html('Complete');
                            vm.tempUrl = vm.aws_link_url+'chunk_videos/'+res.data.file_name;
                            vm.SubmitMessageFileText(res.data.file_name, 'V');
                        }
                    },

                    FilesRemoved: function(up, files) {

                        // Called when files are removed from queue
                    },

                    Error: function(up, err) {
                        document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
                    }
                }
            });

            uploader.init();
        },

        addParticipant:function() {
            let vm = this;
            $('#view_info').modal('hide');
            $('.processing-loader').show();
            let router_url = Vue.http.options.BASE_URL + '/coach/fetchnewuser';
            axios.post(router_url, {chatting_id: this.selected_chat_uuid}).then(res => {
                if (res.status == 200) {
                    $('.processing-loader').hide();
                    if (res.data.status) {
                        this.new_users = [];
                        this.new_users = res.data.data.result.new_user;
                        $('#add_participant').modal();
                    } else {
                        this.new_users = [];
                    }
                }
            }).catch(err => {
                console.log(err);
            });

        },

        changeVideoFolder:function(folderId) {
          $('.processing-loader').show();
          this.renderComponent = false;
          $(".selected_video_folder").not($('.selected_videos_folder_'+ folderId)).removeClass('selected');
          if($('.selected_videos_folder_'+ folderId).hasClass('selected')){
            $('.selected_videos_folder_'+ folderId).removeClass('selected');
            this.fetchMediaArchive();
            this.media_folder_check = '';
              $('.processing-loader').hide();
          }else{
            $('.selected_videos_folder_'+ folderId).addClass('selected');
            let router_url = Vue.http.options.BASE_URL + '/coach/folderMediaArchive';
            axios.post(router_url, {folder_id: folderId}).then(res => {
                $('.processing-loader').hide();
                this.renderComponent = true;
                if (res.status == 200) {
                    if (res.data.status) {
                        this.video_archive = [];
                        this.video_archive = res.data.data.result;
                    } else {
                        this.video_archive = [];
                    }
                }
            }).catch(err => {
                console.log(err);
            });
          }
        },

        fetchUserChats: function () {
        let vm = this;
          $('.processing-loader').show();
        let router_url = Vue.http.options.BASE_URL + '/coach/fetchgroupmessage?page='+this.scrollPage;
        axios.post(router_url, {chatting_id: this.selected_chat_uuid}).then(res => {
          if (res.status == 200) {
              $('.processing-loader').hide();
            if (res.data.status) {
                let data = res.data.data.result.data;

                data.forEach(function(item){
                    vm.user_messages.unshift(item);
                });
                if (this.scrollPage > 1) {
                    let scrollHeight = $(".chat-history").height()/2;
                    $(".chat-history").animate({ scrollTop: scrollHeight });
                }
                if(res.data.data.result.last_page == this.scrollPage) {
                    this.paginate = false;
                }
                this.scrollPage++;
                vm.lastDate = null;
                vm.user_messages.forEach(function(item){
                    if (vm.lastDate != item.message_created_date) {
                        vm.lastDate = item.message_created_date;
                        item.dateValue = item.message_created_date;
                    } else {
                        item.dateValue = 0;
                    }
                });
            }
          }
        }).catch(err => {
          console.log(err);
        });
      },

        fetchMediaArchive: function () {
        let vm = this;
        let router_url = Vue.http.options.BASE_URL + '/coach/media_archive';
        axios.get(router_url).then(res => {
            this.renderComponent = true;
          if (res.status == 200) {
            if (res.data.status) {
              this.photo_archive = res.data.data.result.photo;
              this.video_archive = res.data.data.result.video;
              this.photo_folders = res.data.data.result.photo_folder;
              this.video_folders = res.data.data.result.video_folder;
            } else {
              this.photo_archive = [];
              this.video_archive = [];
                this.photo_folders = [];
                this.video_folders = [];
            }
          }
        }).catch(err => {
          console.log(err);
        });
      },

      SubmitMessage(event) {
        let vm = this;
        if (this.message) {
          this.SubmitMessageFileText(this.message, 'N');
          this.message = '';
          //event.target.reset();
        }
      },


      message_file_sharing: function (file_type, event) {
        if (file_type == 'I' || file_type == 'V' || file_type == 'D') {
          let file = event.target.files[0];
          this.tempUrl = URL.createObjectURL(event.target.files[0]);
          if (file) {
            if (file_type == 'I') {
              if (!/\.(jpe?g|png|gif)$/i.test(file.name)) {
                toastr.error("File extension not valid.");
                return false;
              }
              if (file.size > this.MESSAGE_IMAGE_SIZE) {
                toastr.error("'File too big (> 10MB)'");
                return false;
              } else {
                this.SubmitMessageFileText(file, file_type);
              }
            }
            if (file_type == 'D') {
              if (!/\.(doc|docx|pdf|txt)$/i.test(file.name)) {
                toastr.error("File extension not valid.");
                return false;
              }
              if (file.size > this.MESSAGE_VIDEO_SIZE) {
                toastr.error("'File too big (> 200MB)'");
                return false;
              } else {
                this.SubmitMessageFileText(file, file_type);
              }
            }
            if (file_type == 'V') {
              if (!/\.(mp4|avi|webm|hdv|flv|wmv|mov)$/i.test(file.name)) {
                toastr.error("File extension not valid.");
                return false;
              }
              if (file.size > this.MESSAGE_VIDEO_SIZE) {
                toastr.error("'File too big (> 200MB)'");
                return false;
              } else {
                this.SubmitMessageFileText(file, file_type);
              }
            }
          }
        } else {
          toastr.error("File extension not valid.");
        }

      },

      SubmitMessageFileText: function (message_input, message_type, url) {
          if(url) {
              this.tempUrl = url;
          }
          let message_sent_uuid = Math.random().toString(16).slice(2);
        if (message_type == 'I' || message_type == 'V' || message_type == 'D' || message_type == 'MI' || message_type == 'MV'){
            let user_message_type = 3
            if (message_type == 'I' || message_type == 'MI') {
                user_message_type = 2;
            }
            if (message_type == 'D') {
              user_message_type = 12;
            }
            this.user_messages.push({'message':message_input.name,'thumbnail':message_input.name, 'aws_file_url':this.tempUrl, 'message_type':user_message_type,'user_id': this.login_user_detail.id,'dateValue':0,'sent_status':false,'message_sent_uuid':message_sent_uuid});
            $('#openMediaPopup').modal('hide');
            this.RemoveModalBackDrop();
        } else {
            this.user_messages.push({'message':message_input, 'message_type':1,'user_id': this.login_user_detail.id,'dateValue':0,'sent_status':false,'message_sent_uuid':message_sent_uuid});
        }
        let send_message_url = Vue.http.options.BASE_URL + '/coach/sendgroupmessage';
        let formData = new FormData();
        formData.append('message_file', message_input);
        formData.append('message_type', message_type);
        formData.append('message_sent_uuid', message_sent_uuid);
        formData.append('chatting_id', this.selected_chat_uuid);
        axios.post(send_message_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
          if (res.status == 200) {
           
            $('.processing-loader').hide();
            if (res.data.status) {
             
              $('#openMediaPopup').modal('hide');
              this.RemoveModalBackDrop();
              let message_data = res.data.data.result;
              let newData = this.chat_ref.push();
              newData.set(message_data);
            }else{
              toastr.error(res.data.message);
              return false;
            }
          }
        }).catch((error) => {
          console.log(error);
          $('.processing-loader').hide();
        });
      },

     DeleteMessage : function (message_user, delete_type) {
        let vm = this;
        $('.processing-loader').show();
        let update_request_url = Vue.http.options.BASE_URL + '/coach/messagedelete';
        let formData = new FormData();
        formData.append('message_id', this.delete_message_id);
        formData.append('message_user', message_user);
        formData.append('delete_type', delete_type);
        formData.append('chatting_id', this.selected_chat_uuid);
         formData.append('chat_type', 'G');
        axios.post(update_request_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
            if (res.status == 200) {
                $('.processing-loader').hide();
                if (res.data.status) {
                    if (message_user == 'S') {
                        $('#selfDeletePopup').modal().hide();
                    } else {
                        $('#otherDeletePopup').modal().hide();
                    }
                    this.RemoveModalBackDrop();
                    $('.theme-cyan').removeClass('modal-open');
                    let message_data = res.data.data.result;
                    let newData = this.chat_ref.push();
                    newData.set(message_data);
                }else{
                    toastr.error(res.data.message);
                }
            }
        }).catch((error) => {
            console.log(error);
        });

    },

     openMediaFolderPopup : function (message_id) {
        let vm = this;
        let update_request_url = Vue.http.options.BASE_URL + '/coach/mediafolderpopup';
        let formData = new FormData();
        formData.append('message_id', message_id);
        axios.post(update_request_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
            if (res.status == 200) {
                if (res.data.status) {
                    $('.folder_ajax_modal').html(res.data.data.result);
                    $('#moveToFolder').modal();
                }else{
                    toastr.error(res.data.message);
                }
            }
        }).catch((error) => {
            console.log(error);
        });
    },

        addMember:function()
        {
            let vm = this;
            $('.processing-loader').show();
            let router_url = Vue.http.options.BASE_URL + '/coach/addparticipant';
            axios.post(router_url, {chatting_id: this.selected_chat_uuid, member: this.members_id}).then(res => {
                if (res.status == 200) {
                    $('.processing-loader').hide();
                    if (res.data.status) {
                        toastr.success(res.data.message);
                        $('#add_participant').modal('hide');
                        this.RemoveModalBackDrop();
                    } else {
                        toastr.error(res.data.message);
                    }
                }
            }).catch(err => {
                console.log(err);
            });
        },

        removeFromGroup:function(user_id)
        {
            let vm = this;
            $('.processing-loader').show();
            let router_url = Vue.http.options.BASE_URL + '/coach/removefromgroup';
            axios.post(router_url, {chatting_id: this.selected_chat_uuid, user_id: user_id}).then(res => {
                if (res.status == 200) {
                    $('.processing-loader').hide();
                    if (res.data.status) {
                        toastr.success(res.data.message);
                        this.group_users.splice(this.group_users.findIndex(function(i){
                            return i.id === res.data.data.result.id;
                        }), 1);
                    } else {
                        toastr.error(res.data.message);
                    }
                }
            }).catch(err => {
                console.log(err);
            });
        },

        exitGroup:function(user_id)
        {
            let vm = this;
            $('.processing-loader').show();
            let router_url = Vue.http.options.BASE_URL + '/coach/exitgroup';
            axios.post(router_url, {chatting_id: this.selected_chat_uuid, user_id: user_id}).then(res => {
                if (res.status == 200) {
                    $('.processing-loader').hide();
                    if (res.data.status) {
                        toastr.success(res.data.message);
                        this.selected_chat_user.status = 3;
                        this.group_users.splice(this.group_users.findIndex(function(i){
                            return i.id === res.data.data.result.id;
                        }), 1);
                        $('#view_info').modal('hide');
                        this.removeBackdrop();
                    } else {
                        toastr.error(res.data.message);
                    }
                }
            }).catch(err => {
                console.log(err);
            });
        },

        editGroupName:function(user_id)
        {
            let vm = this;
            if(this.group_name.length > 50) {
                toastr.error('Group name should be less then 50');
                return false;
            }
            $('.processing-loader').show();
            let router_url = Vue.http.options.BASE_URL + '/coach/changegroupname';
            axios.post(router_url, {chatting_id: this.selected_chat_uuid, name: this.group_name}).then(res => {
                if (res.status == 200) {
                    $('.processing-loader').hide();
                    if (res.data.status) {
                        toastr.success(res.data.message);
                        this.selected_chat_dt.group_name = res.data.data.result.group_name;
                        $('#edit_group_name').modal('hide');
                        this.RemoveModalBackDrop();
                    } else {
                        toastr.error(res.data.message);
                    }
                }
            }).catch(err => {
                console.log(err);
            });
        },

      RemoveModalBackDrop:function()
      {
        $('.modal-backdrop').remove();
      }
    }

  }
</script>

