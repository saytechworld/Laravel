<template>
<div class="row">
  <div class="col-lg-12">
    <div class="chat">
      <div class="chat-header clearfix">
        <div class="row">
          <div class="col-4"> <a href="javascript:void(0);" data-toggle="modal" data-target="#view_info" v-if="selected_chat_user.deleted_status != 1"> <img :src="link_url+'/images/noimage.jpg'" alt="avatar" />
            <div class="chat-about">
              <h6 class="m-b-0">{{selected_chat_user.name}}</h6>
            </div>
          </a>

          <a href="javascript:void(0);"  v-else> <img :src="link_url+'/images/noimage.jpg'" alt="avatar" />
            <div class="chat-about">
              <h6 class="m-b-0">{{selected_chat_user.name}}</h6>
            </div>
          </a>

          </div>
            <div class="col-8 text-right">
                <div class="text-right" v-if="selected_chat_user.deleted_status != 1">
                    <ul class="cmn-ul-list">
                        <li v-if="user_session_request.status == 1"><p class="title"> You sent new session request.</p></li>
                        <li v-if="user_session_request.status == 4"><p class="title">Please wait for starting session. </p></li>

                        <template v-if="user_session_request.status == 6">
                            <li><p class="title">Session started. </p></li>
                            <li>
                                <div class="session_btn">
                                    <button class="btn header-all-session-button end-btn semilar-btn" v-on:click="CompleteSession(session_request = user_session_request)"><img :src="link_url+'/images/end-session.svg'" /><br><span class="session-content">End Session</span></button>
                                </div>
                            </li>
                        </template>

                        <template v-if="user_session_request.status == 2">
                            <li><p class="title">The session price is <strong>{{ user_session_request.total_session_price }} €</strong></p></li>
                            <li>
                                <div class="session_btn">
                                    <button class="btn header-all-session-button accept"  data-toggle="modal" data-target="#acceptRequest" @click="session_request = user_session_request"><i class="fa fa-check"></i></button>
                                    <button class="btn header-all-session-button deny" v-on:click="RejectCoachSessionPriceRequest(session_request = user_session_request)"><i class="fa fa-close"></i></button>
                                </div>
                            </li>
                        </template>
                        <li v-if="pending_request == false"><button class="btn header-all-session-button book semilar-btn" @click="SendSessionRequest"><img :src="link_url+'/images/book.svg'" /><br><span class="session-content">Book Session</span></button></li>
                        <li>
                            <button class="btn header-all-session-button report semilar-btn"  data-toggle="modal" data-target="#reportCoach"><img :src="link_url+'/images/report.svg'" /><br><span class="session-content">Report</span></button>
                        </li>
                        <template>
                            <li><a :href="link_url+'/athlete/join_meeting/'+this.selected_chat_uuid" target="_blank" class="btn session-start-btn semilar-btn" ><img :src="link_url+'/images/zoom.svg'" /><br><span class="session-content">ZOOM JOIN</span></a></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>
      </div>
      <div class="chat-history user_messages" v-chat-scroll  v-on:scroll="scrollFunction">
        <ul class="m-b-0">

          <li class="clearfix" v-for="user_message in user_messages" >
              <div class="msg_date_sec" v-if="user_message.dateValue != 0">
                  <span>{{user_message.message_created_date }}</span>
              </div>
           <div v-if="user_message.user_id !=login_user_detail.id">
              <div class="message my-message">
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
                <div v-if="user_message.message_type == 5" class="all-chat-msg-pos">
                  <p>Your session request has been accepted.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 6" class="all-chat-msg-pos">
                  <p>Your session request has been declined.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 9" class="all-chat-msg-pos">
                  <p>Your session has been started.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 10" class="all-chat-msg-pos">
                  <p>Your session has been completed.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 11" class="all-chat-msg-pos">
                  <p>Meeting started. Click on Zoom button to join.</p>
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
                <div v-if="user_message.message_type == 4" class="all-chat-msg-pos">
                   <p>You sent new session request.</p>
                   <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 7" class="all-chat-msg-pos">
                  <p>You have been accepted session price.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 8" class="all-chat-msg-pos">
                  <p>Session price was declined.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 10" class="all-chat-msg-pos">
                  <p>Session completed</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
                </div>
                <div v-if="user_message.message_type == 11" class="all-chat-msg-pos">
                  <p>Meeting started. Click on Zoom button to join.</p>
                  <span class="timing">{{ user_message.message_created_time }}</span>
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
       <template v-if="selected_chat_user.deleted_status != 1">
      <form method="POST" enctype="multipart/form-data" id="messageForm" v-on:submit.prevent="SubmitMessage">
      <div class="chat-message clearfix">
        <div class="input-group mb-0">
            <ul class="media-archive-button">
                <li>
                    <button data-toggle="modal" data-target="#openMediaPopup" @click="media_type = 1" class="btn btn-outline-secondary"><i class=" fa-file-image-o
"></i></button>
                    <button data-toggle="modal" data-target="#openMediaPopup" @click="setUpPlupload()"  class="btn btn-outline-success"><i class=" fa-file-video-o"></i></button>
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
        <template v-else>
          <p class="user_denied_account">This user account has been deleted.</p>
        </template>
    </div>
  </div>

  

  <div class="col-lg-4">

    <input type="file" style="display: none" class="message_file_image" data-max-file-size="200M" name="message_file_image" accept=".png, .jpg, .jpeg" ref="message_file_image" v-on:change="message_file_sharing('I',$event)" >
    <input type="file" style="display: none" class="message_file_video" data-max-file-size="200M" name="message_file_video" accept=".mp4,.webm,.hdv,.flv,.avi,.wmv,.mov" ref="message_file_video" v-on:change="message_file_sharing('V',$event)" >
    <input type="file" style="display: none" class="message_file_document"  data-max-file-size="200M" name="message_file_document" accept=".doc,.docx,.pdf,.txt" ref="message_file_document" v-on:change="message_file_sharing('D',$event)" >

    <div class="modal add_event_category_modal" id="acceptRequest" tabindex="-1" role="dialog" ref="accept_req_modal">
      <div class="modal-dialog " role="document">
        <div class="modal-content">
            <form class="uk-padding">
                <div class="modal-header">
                    <h4 class="title" id="defaultModalLabel">Payment</h4>
                    <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                </div>

                <div class="modal-body">
                    <ul class="nav nav-tabs-new2">
                        <li class="nav-item inlineblock"><a class="nav-link active" data-toggle="tab" href="#creditCard" aria-expanded="true"> Credit Card </a></li>
                        <!--<li class="nav-item inlineblock"><a class="nav-link" data-toggle="tab" href="#paypal" aria-expanded="true"> Paypal</a></li>-->
                    </ul>
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane in active" id="creditCard" aria-expanded="true">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class="thead-dark">
                                            <tr>
                                                <th class="text-left"></th>
                                                <th>Card list</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr v-for="card in cards">
                                                <td class="text-left"><input type="radio" name="card" :value="card.id" v-on:change="handleCardChange('S', card.id)"> </td>
                                                <td>••••{{ card.last4}} </td>
                                            </tr>
                                            <tr>
                                                <td class="text-left"><input type="radio" name="card" v-on:change="handleCardChange('N')"></td>
                                                <td>New Card </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="new_card" style="display:none">
                                <div class="uk-margin uk-text-center">
                                    <p class="stripeError" v-if="stripeError">
                                        {{stripeError}}
                                    </p>
                                </div>
                                <div class="uk-margin uk-text-left">
                                    <label class="uk-form-label" for="Card Name">
                                        Name On Card
                                    </label>
                                    <div class="uk-form-controls">
                                        <div id="card-name">
                                            <input type="text" class="form-control" id="cardname" name="cardname" v-model="cardName">
                                        </div>
                                        <label class="error help-block" v-if="cardNameError">
                                            {{cardNameError}}
                                        </label>
                                    </div>
                                </div>
                                <div class="uk-margin uk-text-left">
                                    <label class="uk-form-label" for="Card Number">
                                        Card Number
                                    </label>
                                    <div class="uk-form-controls">
                                        <div id="card-number" class="uk-input form-control" :class="{ 'uk-form-danger': cardNumberError }"></div>
                                        <label class="error help-block" v-if="cardNumberError">
                                            {{cardNumberError}}
                                        </label>
                                    </div>
                                </div>
                                <div class="uk-grid-small uk-text-left" uk-grid>
                                    <div class="uk-width-1-2@s">
                                        <label class="uk-form-label" for="Card CVC">
                                            Card CVC
                                        </label>
                                        <div class="uk-form-controls">
                                            <div id="card-cvc" class="uk-input form-control" :class="{ 'uk-form-danger': cardCvcError }"></div>
                                            <label class="error help-block" v-if="cardCvcError">
                                                {{cardCvcError}}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="uk-width-1-2@s">
                                        <label class="uk-form-label" for="Expiry Month">
                                            Expiry
                                        </label>
                                        <div class="uk-form-controls">
                                            <div id="card-expiry" class="uk-input form-control" :class="{ 'uk-form-danger': cardExpiryError }"></div>
                                            <label class="error help-block" v-if="cardExpiryError">
                                                {{cardExpiryError}}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-6"><div class="form-group"><div class="input-group"><div class="fancy-checkbox"><label><input type="checkbox" name="saved_card" v-model="saveCardCheck"><span>Save this card</span></label></div></div></div></div>
                                </div><hr/>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class="thead-dark">
                                            <tr>
                                                <th>Session Description</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            <tr>
                                                <td class="text-right"><p>Session Amount:</p>
                                                  <p>Platform Fees:</p>
                                                  <p>Service Tax:</p>
                                                </td>

                                                <td class="text-right">
                                                  <p><strong>{{ session_request.parsing_session_price }} <i class="fa fa-eur"></i></strong></p>
                                                  <p><strong>{{ session_request.session_platform_fees }} <i class="fa fa-eur"></i></strong></p>
                                                  <p><strong>{{ session_request.session_price_vat }} <i class="fa fa-eur"></i></strong></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><h6>Total:</h6></td>
                                                <td class="text-danger text-right">
                                                  <h6>{{ session_request.total_session_price }} <i class="fa fa-eur"></i></h6>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary"  @click.prevent="submitFormToCreateToken(cardType)">
                                    Pay
                                </button>
                            </div>
                        </div>

                        <div role="tabpanel" class="tab-pane" id="paypal" aria-expanded="true">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead class="thead-dark">
                                            <tr>
                                                <th>Plan Description</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            <tr>
                                                <td class="text-right"><p>Plan Amount:</p>
                                                  <p>Platform Fees:</p>
                                                  <p>Service Tax:</p>
                                                </td>

                                                <td class="text-right">
                                                    <p><strong>{{ session_request.session_price }} <i class="fa fa-eur"></i></strong></p>
                                                  <p><strong>{{ session_request.session_platform_fees }} <i class="fa fa-eur"></i></strong></p>
                                                  <p><strong>{{ session_request.session_price_vat }} <i class="fa fa-eur"></i></strong></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-right"><h6>Total:</h6></td>
                                              <td class="text-danger text-right"><h6>{{ session_request.total_session_price }} <i class="fa fa-eur"></i></h6></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <PayPal
                                        :amount= session_amount.toString()
                                        currency="EUR"
                                        :client="credentials"
                                        v-on:payment-authorized="paymentAuthorized"
                                        v-on:payment-completed="paymentCompleted"
                                        v-on:payment-cancelled="paymentCancelled"
                                        env="sandbox"
                                        :commit="commit"
                                        :items="myItems"
                                        :return_url="link_url+'/paypal_payment/session/return'"
                                        :cancel_url="link_url+'/paypal_payment/session/cancel'"
                                >
                                </PayPal>
                                <button type="button" class="btn btn-simple" data-dismiss="modal">CLOSE</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
      </div>
    </div>
      <div class="modal upload-m-popup" id="openMediaPopup" tabindex="-1" role="dialog" ref="mediaModal">
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
                                      <div class="card">
                                          <div class="header">
                                              <h2>Folders</h2>
                                              <ul class="header-dropdown">
                                              </ul>
                                          </div>
                                          <div class="body">
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
      <div class="modal add_event_category_modal" id="reportCoach" tabindex="-1" role="dialog" ref="report_modal">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                  <form  method="post"  v-on:submit.prevent="reportCoach">
                      <div class="modal-header">
                          <h4 class="title" id="report">Report Coach</h4>
                          <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                      </div>
                      <div class="modal-body">
                          <div class="form-group">
                              <div class="form-line">
                                  <input type="text" class="form-control" placeholder="Title" v-model="reportTitle" name="session_price">
                              </div>
                          </div>
                      </div>
                      <div class="modal-body">
                          <div class="form-group">
                              <div class="form-line">
                                  <textarea class="form-control" placeholder="Description" v-model="reportDescription"></textarea>
                              </div>
                          </div>
                      </div>
                      <div class="modal-footer">
                          <button type="submit" class="btn btn-primary">Send</button>
                      </div>
                  </form>
              </div>
          </div>
      </div>
      <div class="modal add_event_category_modal" id="view_info" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <h4 class="title" id="info">View Info</h4>
                      <button type="button" class="btn btn-simple modal-close-btn" data-dismiss="modal"><i class="icon-close"></i></button>
                  </div>
                  <div class="modal-body">
                      <div class="card c-b-box">
                          <div class="body text-center">
                              <div class="profile-image" data-percent="75">
                                  <img :src="selected_chat_user.user_image ? selected_chat_user.user_image : no_image_url" alt="avatar">
                              </div>
                              <span class="c-b-name"><a href="#"> {{ selected_chat_user.name }}</a></span>
                              <p>Experience: {{ selected_chat_user.user_details ? selected_chat_user.user_details.experience : '0' }} Years</p>
                                  <ul class="cmn-ul-list">
                                      <li><a :href="link_url+'/athlete/coach/profileDetail/'+selected_chat_user.username" class="btn btn-outline-primary"><i class="fa fa-user"></i> <span>Profile</span></a></li>
                                  </ul>
                          </div>
                      </div>
                  </div>
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
      <div class="modal add_event_category_modal" id="moveToFolder" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="document">
              <div class="folder_ajax_modal">

              </div>
          </div>
      </div>
  </div>
</div>



</template>
<script>
import Vue from 'vue';
import VueChatScroll from 'vue-chat-scroll';
import PayPal from 'vue-paypal-checkout'
import linkify from 'vue-linkify'

Vue.use(VueChatScroll);
Vue.directive('linkified', linkify)
import VueNativeNotification from 'vue-native-notification';

Vue.use(VueNativeNotification, {
  requestOnNotify: true
});
const firebase = require('../../firebaseConfig.js');

    export default {
        props: ['selected_chat_uuid','login_user_detail','selected_chat_user','selected_chat_dt'],
        data: function() {
          return {
              credentials: {
                  sandbox: Vue.http.options.paypal_sandbox,
                  production: Vue.http.options.paypal_production
              },

              myItems: [],
              commit: true,
              session_amount : 0,
              order_uuid : '',
              card: {
                  cvc: '',
                  number: '',
                  expiry: ''
              },
              //elements
              cardNumber: '',
              cardName: '',
              cardExpiry: '',
              cardCvc: '',
              stripe: null,
              // errors
              stripeError: '',
              cardCvcError: '',
              cardExpiryError: '',
              cardNumberError: '',
              cardNameError: '',
              reportTitle: '',
              saveCardCvc: '',
              cardId: '',
              reportDescription: '',
            pending_request  : false,
            user_messages : [],
              photo_archive: [],
              video_archive: [],
              photo_folders: [],
              video_folders: [],
              cards: [],
              media_type : '',
              media_folder_check : '',
             
            session_request: {},
            sessionRequestData : {},
              cardType: '',
              saveCardCheck : true,
            link_url : Vue.http.options.BASE_URL,
            aws_link_url : Vue.http.options.AWS_BASE_URL,
            no_image_url :  Vue.http.options.BASE_URL+'/images/noimage.jpg',
            double_tick_icon_url: Vue.http.options.BASE_URL + '/images/double-tick-indicator.png',
            watch_icon_url: Vue.http.options.BASE_URL + '/images/ic_message_watch.png',
            loading_icon_url: Vue.http.options.BASE_URL + '/images/file-loader.gif',
            tempUrl: '',
            message : '',
            chat_ref: firebase.firebase_db.ref('chats/'),
            session_requests_ref: firebase.firebase_db.ref('session_requests/'),
            delete_user_ref: firebase.firebase_db.ref('user_references/'),

            MESSAGE_IMAGE_SIZE : Vue.http.options.MESSAGE_IMAGE_SIZE,
            MESSAGE_VIDEO_SIZE : Vue.http.options.MESSAGE_VIDEO_SIZE,
            STRIPE_KEY : Vue.http.options.STRIPE_KEY,
            SERVICE_TAX : Vue.http.options.SERVICE_TAX,
            TRANSACTION_FEES : Vue.http.options.TRANSACTION_FEES,
            user_requests : [],
            user_session_request : {},
            delete_message_id:'',
            scrollPage : 1,
            paginate : true,
            lastDate: null,
            renderComponent: true,
            paypal_env:Vue.http.options.paypal_env,
          }
        },

        components: {
            PayPal
        },

         created: function () {
          this.fetchUserChats();
          this.fetchUserCards();
          this.fetchUserSessionRequests();
          this.fetchMediaArchive();

          this.chat_ref.on('value', (chat_ref_snapshot) => {
            chat_ref_snapshot.forEach((chat_ref_doc) => {
              let chat_ref_item = chat_ref_doc.val();
              chat_ref_item.key = chat_ref_doc.key;
              if (this.selected_chat_dt.id == chat_ref_item.chat_id) {
                  let messages = this.user_messages;
                  if (chat_ref_item.delete_everyone) {
                      var result = $.grep(messages, function(e){
                          return e.id != chat_ref_item.id;
                      });
                      this.user_messages = result;
                  } else if(chat_ref_item.delete_two && chat_ref_item.delete_two == this.login_user_detail.id) {
                      var result = $.grep(messages, function(e){
                          return e.id != chat_ref_item.id;
                      });
                      this.user_messages = result;
                  } else if(chat_ref_item.delete_one && chat_ref_item.delete_one == this.login_user_detail.id) {
                      var result = $.grep(messages, function(e){
                          return e.id != chat_ref_item.id;
                      });
                      this.user_messages = result;
                  }else if(!chat_ref_item.delete_everyone && !chat_ref_item.delete_one && !chat_ref_item.delete_two) {

                      if (this.lastDate != chat_ref_item.message_created_date) {
                          this.lastDate = chat_ref_item.message_created_date;
                          chat_ref_item.dateValue = chat_ref_item.message_created_date;
                      } else {
                          chat_ref_item.dateValue = 0;
                      }

                      let messages = this.user_messages;
                      if(chat_ref_item.user_id == this.login_user_detail.id) {
                          var index = messages.findIndex(message => message.message_sent_uuid === chat_ref_item.message_sent_uuid);
                          if (index >= 0) {
                              messages.splice(index, 1, chat_ref_item);
                              let currentChatRef = firebase.firebase_db.ref('chats/' + chat_ref_doc.key);
                              currentChatRef.remove();
                              return false;
                          }
                      }

                      this.user_messages.push(chat_ref_item);
                  }
              }
              let currentChatRef = firebase.firebase_db.ref('chats/' + chat_ref_doc.key);
              currentChatRef.remove();
            });
          });

          this.session_requests_ref.on('value', (requests_ref_snapshot) => {
            requests_ref_snapshot.forEach((requests_ref_doc) => {
              let requests_ref_item = requests_ref_doc.val()
              requests_ref_item.key = requests_ref_doc.key
              if (this.selected_chat_dt.id == requests_ref_item.chat_id) {
                this.checkPendingApppendSession(2,requests_ref_item);
              }
              let currentRequestRef = firebase.firebase_db.ref('session_requests/' + requests_ref_doc.key);
              currentRequestRef.remove();
            });
          });

             this.delete_user_ref.on('value', (requests_ref_snapshot) => {
                 requests_ref_snapshot.forEach((requests_ref_doc) => {
                     let requests_ref_item = requests_ref_doc.val();
                     requests_ref_item.key = requests_ref_doc.key;
                     if (this.selected_chat_user.user_uuid == requests_ref_item.user_uuid) {
                         this.selected_chat_user.deleted_status = requests_ref_item.deleted_status;
                     }
                     let currentRequestRef = firebase.firebase_db.ref('user_references/' + requests_ref_doc.key);
                     currentRequestRef.remove();
                 });
             });

        },

        computed: {
         
        },
        mounted() {
            this.setUpStripe();
            this.setUpPlupload();
            $(this.$refs.report_modal).on('hidden.bs.modal', this.resetReportFormData);
            $(this.$refs.accept_req_modal).on('hidden.bs.modal', this.resetAcceptFormData);
            $(this.$refs.mediaModal).on('hidden.bs.modal', this.resetMediaModal);
        },


      methods:{

          newline() {
              this.value = `${this.value}\n`;
          },
          resetReportFormData() {
             this.reportTitle = '';
             this.reportDescription = '';
          },
          resetAcceptFormData() {
              this.reset();
              $('.card_cvv').hide();
              $('.new_card').hide();
              this.cardType = '';
              this.cardId = '';
              this.saveCardCheck = true,
              $('.modal')
                  .find("input[type=radio]")
                  .prop("checked", "")
                  .end();
          },

          resetMediaModal() {
              $('.modal')
                  .find("input[type=radio]")
                  .prop("checked", "")
                  .end();
              this.fetchMediaArchive();
              this.media_folder_check = '';
              this.renderComponent = false;
              $(".selected_photo_folder").removeClass('selected');
              $(".selected_video_folder").removeClass('selected');
          },

          paymentAuthorized: function (data) {
              $('.processing-loader').show();
              let send_message_url =Vue.http.options.BASE_URL+'/athlete/payment/paypal';
              let formData = new FormData();
              formData.append('paypal_token', data.paymentToken);
              formData.append('payerId', data.payerID);
              formData.append('session_request_id', this.session_request.chat_session_uuid);
              formData.append('chatting_id', this.selected_chat_uuid);
              axios.post(send_message_url,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then( res => {
                  if(res.status==200){
                      if(res.data.status){
                          this.order_uuid = res.data.data.result.order_uuid;
                      }else {
                          toastr.error(res.data.message);
                      }
                  }
              }).catch((error) => {
                  console.log(error);
              });
          },

          paymentCompleted: function(data) {
              let send_message_url =Vue.http.options.BASE_URL+'/athlete/paymentcomplete/paypal';
              let formData = new FormData();
              formData.append('payment_id', data.id);
              formData.append('order_uuid', this.order_uuid);
              formData.append('session_request_id', this.session_request.chat_session_uuid);
              formData.append('chatting_id', this.selected_chat_uuid);
              axios.post(send_message_url,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then( res => {
                  if(res.status==200){
                      $('.processing-loader').hide();
                      if(res.data.status){
                          $('#acceptRequest').modal().hide();
                          $('.theme-cyan').removeClass('modal-open');
                          this.RemoveModalBackDrop();
                          this.reset();
                          this.cardId = '';
                          this.saveCardCvc = '';
                          let message_data = res.data.data.result.message;
                          let chatRefData = this.chat_ref.push();
                          chatRefData.set(message_data);
                          let session_request_data = res.data.data.result.session_request;
                          let sessionRefData =  this.session_requests_ref.push();
                          sessionRefData.set(session_request_data);
                          this.checkPendingApppendSession(session_request_data);
                          this.fetchUserCards();
                          toastr.success(res.data.message);
                      }else {
                          toastr.error(res.data.message);
                      }
                  }
              }).catch((error) => {
                  console.log(error);
              });
          },
          paymentCancelled: function(data) {
              console.log(data);
          },

          handleCardChange:function(type, id=null) {
              $('.new_card').hide();
              this.cardType = '';
              this.cardId = '';
              this.reset();
              if (type == 'S') {
                  this.cardType = 'S';
                  this.cardId = id;
              } else {
                  $('.new_card').show();
                  this.cardType = 'N';
              }
          },

          openDropdown:function(id) {
              $('#open_dropdown_'+id+'').toggleClass('show')
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
                let router_url = Vue.http.options.BASE_URL + '/athlete/folderMediaArchive';
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
                let router_url = Vue.http.options.BASE_URL + '/athlete/folderMediaArchive';
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

          setUpStripe() {
              if (window.Stripe === undefined) {
                  alert('Stripe V3 library not loaded!');
              } else {
                  const stripe = window.Stripe(this.STRIPE_KEY);
                  this.stripe = stripe;

                  const elements = stripe.elements();
                  this.cardCvc = elements.create('cardCvc');
                  this.cardExpiry = elements.create('cardExpiry');
                  this.cardNumber = elements.create('cardNumber');

                  this.cardCvc.mount('#card-cvc');
                  this.cardExpiry.mount('#card-expiry');
                  this.cardNumber.mount('#card-number');

                  this.listenForErrors();
              }
          },

          listenForErrors() {
              const vm = this;

              this.cardNumber.addEventListener('change', (event) => {
                  vm.toggleError(event);
                  vm.cardNumberError = ''
                  vm.card.number = event.complete ? true : false
              });

              this.cardExpiry.addEventListener('change', (event) => {
                  vm.toggleError(event);
                  vm.cardExpiryError = ''
                  vm.card.expiry = event.complete ? true : false
              });

              this.cardCvc.addEventListener('change', (event) => {
                  vm.toggleError(event);
                  vm.cardCvcError = ''
                  vm.card.cvc = event.complete ? true : false
              });
          },

          toggleError (event) {
              if (event.error) {
                  this.stripeError = event.error.message;
              } else {
                  this.stripeError = '';
              }
          },

          submitFormToCreateToken(type) {
              this.clearCardErrors();
              let valid = true;
              if (!type) {
                  valid = false;
                  alert('please select card');
              }
              if (type == 'N') {
                  if (!this.cardName) {
                      valid = false;
                      this.cardNameError = "Card Name is Required";
                  }
                  if (!this.card.number) {
                      valid = false;
                      this.cardNumberError = "Card Number is Required";
                  }
                  if (!this.card.cvc) {
                      valid = false;
                      this.cardCvcError = "CVC is Required";
                  }
                  if (!this.card.expiry) {
                      valid = false;
                      this.cardExpiryError = "Month is Required";
                  }
                  if (this.stripeError) {
                      valid = false;
                  }
              }
              if (valid) {
                  this.createToken(type)
              }
          },

          createToken(type) {

              if (type == 'N') {
                  this.stripe.createToken(this.cardNumber).then((result) => {
                      if (result.error) {
                          this.stripeError = result.error.message;
                      } else {
                          $('.processing-loader').show();
                          const stripeToken = result.token.id;
                          let saveCard = this.saveCardCheck ? 1 : 0;
                          let send_message_url =Vue.http.options.BASE_URL+'/athlete/payment/post';
                          let formData = new FormData();
                          formData.append('stripeToken', stripeToken);
                          formData.append('cardType', type);
                          formData.append('cardId', '');
                          formData.append('session_request_id', this.session_request.chat_session_uuid);
                          formData.append('chatting_id', this.selected_chat_uuid);
                          formData.append('saveCard', saveCard);
                          axios.post(send_message_url,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then( res => {
                              if(res.status==200){
                                  $('.processing-loader').hide();
                                  if(res.data.status){
                                      $('#acceptRequest').modal().hide();
                                      $('.theme-cyan').removeClass('modal-open');
                                      this.RemoveModalBackDrop();
                                      this.reset();
                                      this.cardId = '';
                                      this.saveCardCvc = '';
                                      let message_data = res.data.data.result.message;
                                      let chatRefData = this.chat_ref.push();
                                      chatRefData.set(message_data);
                                      let session_request_data = res.data.data.result.session_request;
                                      let sessionRefData =  this.session_requests_ref.push();
                                      sessionRefData.set(session_request_data);
                                      this.checkPendingApppendSession(2,session_request_data);
                                      this.fetchUserCards();
                                      toastr.success(res.data.message);
                                  }else {
                                      toastr.error(res.data.message);
                                  }
                              }
                          }).catch((error) => {
                              console.log(error);
                          });
                      }
                  })
              } else {
                  $('.processing-loader').show();
                  const stripeToken = '';
                  let send_message_url =Vue.http.options.BASE_URL+'/athlete/payment/post';
                  let formData = new FormData();
                  formData.append('stripeToken', stripeToken);
                  formData.append('cardType', type);
                  formData.append('cardId', this.cardId);
                  formData.append('session_request_id', this.session_request.chat_session_uuid);
                  formData.append('chatting_id', this.selected_chat_uuid);
                  axios.post(send_message_url,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then( res => {
                      if(res.status==200){
                          $('.processing-loader').hide();
                          if(res.data.status){
                              $('#acceptRequest').modal().hide();
                              $('.theme-cyan').removeClass('modal-open');
                              this.RemoveModalBackDrop();
                              this.reset();
                              let message_data = res.data.data.result.message;
                              let chatRefData = this.chat_ref.push();
                              chatRefData.set(message_data);
                              let session_request_data = res.data.data.result.session_request;
                              let sessionRefData =  this.session_requests_ref.push();
                              sessionRefData.set(session_request_data);
                              this.checkPendingApppendSession(2,session_request_data);
                              toastr.success(res.data.message);
                          }else {
                              toastr.error(res.data.message);
                          }
                      }
                  }).catch((error) => {
                      console.log(error);
                  });
              }

          },

          clearElementsInputs() {
              this.cardCvc.clear()
              this.cardExpiry.clear()
              this.cardNumber.clear()
              this.cardName = ''
          },

          clearCardErrors() {
              this.stripeError = ''
              this.cardCvcError = ''
              this.cardExpiryError = ''
              this.cardNumberError = ''
              this.cardNameError = ''
          },

          reset() {
              this.clearElementsInputs()
              this.clearCardErrors()
          },

          scrollFunction:function() {
              if($(".chat-history").scrollTop() == 0) {
                  if (this.paginate) {
                      this.fetchUserChats();
                  }
              }
          },

          fetchUserChats:function(){
            let vm = this;
              $('.processing-loader').show();
            let router_url =Vue.http.options.BASE_URL+'/athlete/fetchusermessage?page='+this.scrollPage;
            axios.post(router_url,{chatting_id: this.selected_chat_uuid}).then(res=>{
                $('.processing-loader').hide();
              if(res.status==200) {
                  if (res.data.status) {
                      let data = res.data.data.result.data;

                      data.forEach(function(item){
                          vm.user_messages.unshift(item);
                      });

                      if (this.scrollPage > 1) {
                          let scrollHeight = $(".chat-history").height()/2
                          $(".chat-history").animate({ scrollTop: scrollHeight });
                      }

                      if (res.data.data.result.last_page == this.scrollPage) {
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
            }).catch(err=>{
              console.log(err);
            });
          },

          fetchMediaArchive: function () {
              let vm = this;
              let router_url = Vue.http.options.BASE_URL + '/athlete/media_archive';
              axios.get(router_url).then(res => {
                  //$('.processing-loader').hide();
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
                      }
                  }
              }).catch(err => {
                  console.log(err);
              });
          },

          SendSessionRequest:function(){
            let vm = this;
            if(this.pending_request == false){
                $('.processing-loader').show();
                let router_url =Vue.http.options.BASE_URL+'/athlete/sendsessonrequest';
                axios.post(router_url,{chatting_id: this.selected_chat_uuid, sender_id:this.login_user_detail.id, receiver_id:this.selected_chat_user.id }).then(res=>{
                  if(res.status==200){
                      $('.processing-loader').hide();
                  if (res.data.status) {
                      let message_data = res.data.data.result.message;
                      let chatRefData = this.chat_ref.push();
                      chatRefData.set(message_data);
                      let session_request_data = res.data.data.result.session_request;
                      let sessionRefData =  this.session_requests_ref.push();
                      sessionRefData.set(session_request_data);
                      this.checkPendingApppendSession(2,session_request_data);
                      toastr.success(res.data.message);
                    }else{
                      toastr.error(res.data.message);
                      
                    }
                  }
                }).catch(err=>{
                  console.log(err);
                });
            }else{
              toastr.error("You have pending session with this coach.");
            }
          },

          RejectCoachSessionPriceRequest:function(){
            let vm = this;
              $('.processing-loader').show();
            let router_url =Vue.http.options.BASE_URL+'/athlete/updateusersessionrequest';
            let formData = new FormData();
            formData.append('session_request_id', this.session_request.chat_session_uuid);
            formData.append('chatting_id', this.selected_chat_uuid);
            formData.append('request_status', 5);
            axios.post(router_url,formData).then(res=>{
              if (res.status == 200) {
                 $('.processing-loader').hide();
              if (res.data.status) {
                  let message_data = res.data.data.result.message;
                  let chatRefData = this.chat_ref.push();
                  chatRefData.set(message_data);
                  let session_request_data = res.data.data.result.session_request;
                  let sessionRefData =  this.session_requests_ref.push();
                  sessionRefData.set(session_request_data);
                  this.checkPendingApppendSession(2,session_request_data);
                  toastr.success(res.data.message);
                }else{
                  toastr.error(res.data.message);
                }
              }
            }).catch(err=>{
              console.log(err);
            });
          },

          SubmitFormPaySessionPrice(event) {
          let vm = this;
          let send_message_url =Vue.http.options.BASE_URL+'/athlete/payment/post';
          let formData = new FormData();
          formData.append('card_no', this.card_no);
          formData.append('cvvNumber', this.cvvNumber);
          formData.append('ccExpiryMonth', this.ccExpiryMonth);
          formData.append('ccExpiryYear', this.ccExpiryYear);
          formData.append('amount', this.session_request.session_price);
          formData.append('session_request_id', this.session_request.chat_session_uuid);
          formData.append('chatting_id', this.selected_chat_uuid);
          axios.post(send_message_url,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then( res => {
            if(res.status==200){
              if(res.data.status){
                $('#acceptRequest').modal().hide();
                  this.$body.removeClass('modal-open');
                this.RemoveModalBackDrop();

                this.card_no = '';
                this.cvvNumber = '';
                this.ccExpiryMonth = '';
                this.ccExpiryYear = '';
                let message_data = res.data.data.result.message;
                let chatRefData = this.chat_ref.push();
                chatRefData.set(message_data);
                let session_request_data = res.data.data.result.session_request;
                let sessionRefData =  this.session_requests_ref.push();
                sessionRefData.set(session_request_data);
                this.checkPendingApppendSession(2,session_request_data);
                toastr.success(res.data.message);
              }else {
                toastr.error(res.data.message);
              }
            }
          }).catch((error) => {
            console.log(error);
          });
        },

          DeleteMessage : function (message_user, delete_type) {
              let vm = this;
              $('.processing-loader').show();
              let update_request_url = Vue.http.options.BASE_URL + '/athlete/messagedelete';
              let formData = new FormData();
              formData.append('message_id', this.delete_message_id);
              formData.append('message_user', message_user);
              formData.append('delete_type', delete_type);
              formData.append('chatting_id', this.selected_chat_uuid);
              axios.post(update_request_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
                  if (res.status == 200) {
                      $('.processing-loader').hide();
                      if (res.data.status) {
                          if (message_user == 'S') {
                              $('#selfDeletePopup').modal().hide();
                              this.RemoveModalBackDrop();
                          } else {
                              $('#otherDeletePopup').modal().hide();
                              this.RemoveModalBackDrop();
                          }
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
              let update_request_url = Vue.http.options.BASE_URL + '/athlete/mediafolderpopup';
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

          CompleteSession : function () {
              let vm = this;
              $('.processing-loader').show();
              let update_request_url = Vue.http.options.BASE_URL + '/athlete/session_completed';
              let formData = new FormData();
              formData.append('session_request_id', this.session_request.chat_session_uuid);
              formData.append('chatting_id', this.selected_chat_uuid);
              formData.append('request_status', 7);
              axios.post(update_request_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
                  if (res.status == 200) {
                      $('.processing-loader').hide();
                      if (res.data.status) {
                          let message_data = res.data.data.result.message;
                          let chatRefData = this.chat_ref.push();
                          chatRefData.set(message_data);
                          let session_request_data = res.data.data.result.session_request;
                          let sessionRefData =  this.session_requests_ref.push();
                          sessionRefData.set(session_request_data);
                          this.checkPendingApppendSession(session_request_data);
                          toastr.success(res.data.message);
                      }else{
                          toastr.error(res.data.message);
                      }
                  }
              }).catch((error) => {
                  console.log(error);
              });
          },

          fetchUserSessionRequests:function(){
              //$('.processing-loader').show();
            let vm = this;
            let router_url =Vue.http.options.BASE_URL+'/athlete/fetchusersessionrequest';
            axios.post(router_url,{chatting_id: this.selected_chat_uuid}).then(res=>{
              if(res.status==200){
                  //$('.processing-loader').hide();
                if(res.data.status){
                  this.user_requests = res.data.data.result;
                  this.user_session_request = res.data.data.result[res.data.data.result.length-1];

                    this.myItems = [
                        {
                            "name": "Session Request "+this.user_session_request.chat_session_uuid,
                            "description": "Payment for session request.",
                            "quantity": "1",
                            "price": this.user_session_request.total_session_price,
                            "currency": "EUR"
                        },
                    ];
                    this.session_amount = this.user_session_request.total_session_price;

                }else{
                  this.user_requests = [];
                  this.user_session_request = {};
                }
                this.checkPendingApppendSession(1,{});
              }
            }).catch(err=>{
              console.log(err);
            });
          },

          fetchUserCards: function () {
              let vm = this;
              let router_url = Vue.http.options.BASE_URL + '/athlete/fetchusercard';
              axios.get(router_url).then(res => {
                  if (res.status == 200) {
                      if (res.data.status) {
                          this.cards = res.data.data.result;
                      } else {
                          this.cards = [];
                      }
                  }
              }).catch(err => {
                  console.log(err);
              });
          },

          checkPendingApppendSession:function(flag, current_request_data)
          {
              if(flag == 2) {
                  this.user_session_request = current_request_data;
                  if (this.user_session_request.id == current_request_data.id) {
                      this.$set(this.user_session_request, 'status', current_request_data.status);
                      this.$set(this.user_session_request, 'session_price', current_request_data.session_price);
                      this.myItems = [
                          {
                              "name": "Session Request "+current_request_data.chat_session_uuid,
                              "description": "Payment for session request.",
                              "quantity": "1",
                              "price": current_request_data.total_session_price,
                              "currency": "EUR"
                          },
                      ];
                      this.session_amount = current_request_data.total_session_price;
                  }
              }

              if (this.user_session_request.status == 1 || this.user_session_request.status == 2 || this.user_session_request.status == 4 || this.user_session_request.status == 6) {
                  this.pending_request = true;
              } else {
                  this.pending_request = false;
              }
          },

          SubmitMessage(event) {
            let vm = this;
            if(this.message){
              this.SubmitMessageFileText(this.message,'N');
              this.message = '';
              //event.target.reset();
            }
          },

          message_file_sharing:function(file_type, event){
           if(file_type == 'I' || file_type == 'V' || file_type == 'D'){
              let file = event.target.files[0];
               this.tempUrl = URL.createObjectURL(event.target.files[0]);
              if( file){
                if(file_type == 'I'){
                   if ( !/\.(jpe?g|png)$/i.test( file.name ) ) {
                     toastr.error("File extension not valid.");
                     return false;
                   }if(file.size > this.MESSAGE_IMAGE_SIZE ){
                    toastr.error("'File too big (> 10MB)'");
                    return false;
                   }else{
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
                if(file_type == 'V'){
                  if ( !/\.(mp4|avi|webm|hdv|flv|wmv|mov)$/i.test( file.name ) ) {
                     toastr.error("File extension not valid.");
                     return false;
                   }if(file.size > this.MESSAGE_VIDEO_SIZE ){
                    toastr.error("'File too big (> 200MB)'");
                    return false;
                   }else{
                    this.SubmitMessageFileText(file, file_type);
                  }
                }
              }
           }else{
             toastr.error("File extension not valid.");
           }
            
          },


        SubmitMessageFileText:function(message_input, message_type,url){
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

                this.user_messages.push({'message':message_input.name, 'thumbnail':message_input.name, 'aws_file_url':this.tempUrl, 'message_type':user_message_type,'user_id': this.login_user_detail.id,'dateValue':0,'sent_status':false,'message_sent_uuid':message_sent_uuid});
                $('#openMediaPopup').modal('hide');
                this.RemoveModalBackDrop();
            } else {
                this.user_messages.push({'message':message_input, 'message_type':1,'user_id': this.login_user_detail.id,'dateValue':0,'sent_status':false,'message_sent_uuid':message_sent_uuid});
            }
            let send_message_url =Vue.http.options.BASE_URL+'/athlete/sendusermessage';
            let formData = new FormData();
            formData.append('message_file', message_input);
            formData.append('message_type', message_type);
            formData.append('message_sent_uuid', message_sent_uuid);
            formData.append('chatting_id', this.selected_chat_uuid);
            axios.post(send_message_url,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then( res => {
              if(res.status==200){
                  $('.processing-loader').hide();
                if(res.data.status){
                $('#openMediaPopup').modal('hide');
                this.RemoveModalBackDrop();
                  let message_data = res.data.data.result;
                  let newData = this.chat_ref.push();
                  newData.set(message_data);
                }else {
                    toastr.error(res.data.message)
                }
              }
            }).catch((error) => {
              console.log(error);
            });
        },

          reportCoach(event) {
              if (this.reportTitle == '') {
                  toastr.error('Report Title should not be blank');
                  return false;
              }
              if (this.reportDescription == '') {
                  toastr.error('Report Description should not be blank');
                  return false;
              }

              $('.processing-loader').show();
              let report_url = Vue.http.options.BASE_URL + '/athlete/report';
              let formData = new FormData();
              formData.append('title', this.reportTitle);
              formData.append('description', this.reportDescription);
              formData.append('to_user', this.selected_chat_user.id);
              formData.append('from_user', this.login_user_detail.id);
              axios.post(report_url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then(res => {
                  if (res.status == 200) {
                      $('.processing-loader').hide();
                      if (res.data.status) {
                          this.reportTitle = '';
                          this.reportDescription = '';
                          $('#reportCoach').modal('hide');
                          this.RemoveModalBackDrop();
                          toastr.success(res.data.message);
                      }else{
                          $('#reportCoach').modal('hide');
                          this.RemoveModalBackDrop();
                          toastr.error(res.data.message);
                      }
                  }
              }).catch((error) => {
                  console.log(error);
              });
          },


        RemoveModalBackDrop:function()
        {
          $('.modal-backdrop').remove();
        }

      }
    }

</script>

