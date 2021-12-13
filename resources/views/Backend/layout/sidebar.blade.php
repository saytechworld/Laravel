<div id="left-sidebar" class="sidebar">
    <div class="sidebar-scroll">
        <div class="user-account">
            <figure class="pro-pic">
                <img src="{{  !empty(auth()->user()->user_details->image) && file_exists(public_path('images/users/'.auth()->user()->user_details->image)) ? asset('images/users/'.auth()->user()->user_details->image) : asset('images/noimage.jpg') }}" class="rounded-circle user-photo image-click" alt="User Profile Picture">
            </figure>
            <div class="dropdown">
                <span>Welcome,</span>
                <a href="javascript:void(0);" class="dropdown-toggle user-name" data-toggle="dropdown"><strong>{!! auth()->user()->name !!}</strong></a>
                <ul class="dropdown-menu dropdown-menu-right account">
                    <li><a href="{{route('admin.profile')}}"><i class="icon-user"></i>My Profile</a></li>
                    {{--<li><a href="{{route('app.inbox')}}"><i class="icon-envelope-open"></i>Messages</a></li>
                    <li><a href="javascript:void(0);"><i class="icon-settings"></i>Settings</a></li>--}}
                    <li class="divider"></li>
                    <li><a href="{{route('frontend.auth.logout')}}"><i class="icon-power"></i>Logout</a></li>
                </ul>
            </div>
        </div>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#menu">Menu</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#Chat"><i class="icon-book-open"></i></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#setting"><i class="icon-settings"></i></a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#question"><i class="icon-question"></i></a></li>                
        </ul>
            
        <!-- Tab panes -->
        <div class="tab-content p-l-0 p-r-0">
            <div class="tab-pane active" id="menu">
                <nav id="left-sidebar-nav" class="sidebar-nav">
                    <ul id="main-menu" class="metismenu">


                        <li>
                            <a href="{!! route('admin.dashboard') !!}"><i class="icon-home"></i> <span>Dashboard</span></a>
                        </li>
                        <li class="{{ Request::segment(2) === 'access' ? 'active' : null }}">
                            <a href="#Users" class="has-arrow"><i class="icon-user"></i> <span>Users</span></a>
                            <ul>
                                <li class="{{ Request::segment(3) === 'user' ? 'active' : null }}"><a href="{{route('admin.access.user.index')}}">Users</a> </li>
                                <li class="{{ Request::segment(2) === 'role' ? 'active' : null }}"><a href="{{route('admin.access.role.index')}}">Roles</a></li>
                                <li class="{{ Request::segment(2) === 'athelete' ? 'active' : null }}"><a href="{{route('admin.access.athelete.index')}}">Athlete</a></li>
                                <li class="{{ Request::segment(2) === 'coach' ? 'active' : null }}"><a href="{{route('admin.access.coach.index')}}">Coach</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::segment(2) === 'system' ? 'active' : null }}">
                            <a href="#Pages" class="has-arrow"><i class="icon-docs"></i> <span>Master</span></a>
                            <ul>
                                <li class="{{ Request::segment(3) === 'staticpage' ? 'active' : null }}"><a href="{{route('admin.system.staticpage.index')}}">Static Page</a> </li>
                                <li class="{{ Request::segment(3) === 'faq' ? 'active' : null }}"><a href="{{route('admin.system.faq.index')}}">FAQ</a></li>
                                <li class="{{ Request::segment(3) === 'tag' ? 'active' : null }}"><a href="{{route('admin.system.tag.index')}}">Tag</a></li>
                            </ul>
                        </li>
                        <li class="{{ Request::segment(2) === 'system' ? 'active' : null }}">
                            <a href="#version" class="has-arrow"><i class="icon-docs"></i> <span>App Versions</span></a>
                            <ul>
                                <li class="{{ Request::segment(2) === 'version' ? 'active' : null }}"><a href="{{route('admin.system.version.android.index')}}">Android Versions</a> </li>
                                <li class="{{ Request::segment(2) === 'version' ? 'active' : null }}"><a href="{{route('admin.system.version.ios.index')}}">IOS Versions</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="tab-pane p-l-15 p-r-15" id="Chat">
                <form>
                    <div class="input-group m-b-20">
                        <div class="input-group-prepend">
                            <span class="input-group-text" ><i class="icon-magnifier"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Search...">
                    </div>
                </form>
                <ul class="right_chat list-unstyled">
                    <li class="online">
                        <a href="javascript:void(0);">
                            <div class="media">
                                <img class="media-object " src="{{ asset('assets/img/xs/avatar4.jpg') }}" alt="">
                                <div class="media-body">
                                    <span class="name">Chris Fox</span>
                                    <span class="message">Designer, Blogger</span>
                                    <span class="badge badge-outline status"></span>
                                </div>
                            </div>
                        </a>                            
                    </li>
                    <li class="online">
                        <a href="javascript:void(0);">
                            <div class="media">
                                <img class="media-object " src="{{ asset('assets/img/xs/avatar5.jpg') }}" alt="">
                                <div class="media-body">
                                    <span class="name">Joge Lucky</span>
                                    <span class="message">Java Developer</span>
                                    <span class="badge badge-outline status"></span>
                                </div>
                            </div>
                        </a>                            
                    </li>
                    <li class="offline">
                        <a href="javascript:void(0);">
                            <div class="media">
                                <img class="media-object " src="{{ asset('assets/img/xs/avatar2.jpg') }}" alt="">
                                <div class="media-body">
                                    <span class="name">Isabella</span>
                                    <span class="message">CEO, Thememakker</span>
                                    <span class="badge badge-outline status"></span>
                                </div>
                            </div>
                        </a>                            
                    </li>
                    <li class="offline">
                        <a href="javascript:void(0);">
                            <div class="media">
                                <img class="media-object " src="{{ asset('assets/img/xs/avatar1.jpg') }}" alt="">
                                <div class="media-body">
                                    <span class="name">Folisise Chosielie</span>
                                    <span class="message">Art director, Movie Cut</span>
                                    <span class="badge badge-outline status"></span>
                                </div>
                            </div>
                        </a>                            
                    </li>
                    <li class="online">
                        <a href="javascript:void(0);">
                            <div class="media">
                                <img class="media-object " src="{{ asset('assets/img/xs/avatar3.jpg') }}" alt="">
                                <div class="media-body">
                                    <span class="name">Alexander</span>
                                    <span class="message">Writter, Mag Editor</span>
                                    <span class="badge badge-outline status"></span>
                                </div>
                            </div>
                        </a>                            
                    </li>                        
                </ul>
            </div>
            <div class="tab-pane p-l-15 p-r-15" id="setting">
                <h6>Choose Skin</h6>
                <ul class="choose-skin list-unstyled">
                    <li data-theme="purple">
                        <div class="purple"></div>
                        <span>Purple</span>
                    </li>                   
                    <li data-theme="blue">
                        <div class="blue"></div>
                        <span>Blue</span>
                    </li>
                    <li data-theme="cyan" class="active">
                        <div class="cyan"></div>
                        <span>Cyan</span>
                    </li>
                    <li data-theme="green">
                        <div class="green"></div>
                        <span>Green</span>
                    </li>
                    <li data-theme="orange">
                        <div class="orange"></div>
                        <span>Orange</span>
                    </li>
                    <li data-theme="blush">
                        <div class="blush"></div>
                        <span>Blush</span>
                    </li>
                </ul>
                <hr>
                <h6>General Settings</h6>
                <ul class="setting-list list-unstyled">
                    <li>
                        <label class="fancy-checkbox">
                            <input type="checkbox" name="checkbox">
                            <span>Report Panel Usag</span>
                        </label>
                    </li>
                    <li>
                        <label class="fancy-checkbox">
                            <input type="checkbox" name="checkbox" checked>
                            <span>Email Redirect</span>
                        </label>
                    </li>
                    <li>
                        <label class="fancy-checkbox">
                            <input type="checkbox" name="checkbox" checked>
                            <span>Notifications</span>
                        </label>                      
                    </li>
                    <li>
                        <label class="fancy-checkbox">
                            <input type="checkbox" name="checkbox">
                            <span>Auto Updates</span>
                        </label>
                    </li>
                    <li>
                        <label class="fancy-checkbox">
                            <input type="checkbox" name="checkbox">
                            <span>Offline</span>
                        </label>
                    </li>
                    <li>
                        <label class="fancy-checkbox">
                            <input type="checkbox" name="checkbox">
                            <span>Location Permission</span>
                        </label>
                    </li>
                </ul>
            </div>
            <div class="tab-pane p-l-15 p-r-15" id="question">
                <form>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" ><i class="icon-magnifier"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Search...">
                    </div>
                </form>
                <ul class="list-unstyled question">
                    <li class="menu-heading">HOW-TO</li>
                    <li><a href="javascript:void(0);">How to Create Campaign</a></li>
                    <li><a href="javascript:void(0);">Boost Your Sales</a></li>
                    <li><a href="javascript:void(0);">Website Analytics</a></li>
                    <li class="menu-heading">ACCOUNT</li>
                    <li><a href="javascript:void(0);">Cearet New Account</a></li>
                    <li><a href="javascript:void(0);">Change Password?</a></li>
                    <li><a href="javascript:void(0);">Privacy &amp; Policy</a></li>
                    <li class="menu-heading">BILLING</li>
                    <li><a href="javascript:void(0);">Payment info</a></li>
                    <li><a href="javascript:void(0);">Auto-Renewal</a></li>                        
                    <li class="menu-button m-t-30">
                        <a href="javascript:void(0);" class="btn btn-primary"><i class="icon-question"></i> Need Help?</a>
                    </li>
                </ul>
            </div>                
        </div>          
    </div>
</div>
