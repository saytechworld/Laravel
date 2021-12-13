<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"> <!-- Favicon-->
        <title>@yield('title') - {{ config('app.name') }}</title>
        <meta name="description" content="@yield('meta_description', config('app.name'))">
        <meta name="author" content="@yield('meta_author', config('app.name'))">
        @yield('meta')
        <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/font-awesome.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/jvectormap/jquery-jvectormap-2.0.3.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/morrisjs/morris.min.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/nouislider/nouislider.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/vendor/toastr/toastr.min.css') }}"/>


        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.4/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css" rel="Stylesheet">

        <link rel="stylesheet" href="{{ asset('assets/vendor/dropify/css/dropify.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('assets/css/chatapp.css') }}"/>

        
        <!-- Custom Css -->


        <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

        <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/css/color_skins.css') }}">
        @if (Request::segment(2) === 'system' )
        <link rel="stylesheet" href="{{ asset('assets/vendor/summernote/dist/summernote.css') }}"/>
        @endif


        @if (trim($__env->yieldContent('page-styles')))
            @yield('page-styles')
        @endif
    </head>
    <?php 
        $setting = !empty($_GET['theme']) ? $_GET['theme'] : '';
        $theme = "theme-cyan";
        $menu = "";
        if ($setting == 'p') {
            $theme = "theme-purple";
        } else if ($setting == 'b') {
            $theme = "theme-blue";
        } else if ($setting == 'g') {
            $theme = "theme-green";
        } else if ($setting == 'o') {
            $theme = "theme-orange";
        } else if ($setting == 'bl') {
            $theme = "theme-blush";
        } else {
             $theme = "theme-cyan";
        }
    ?>
    <body class="theme-cyan <?= $theme ?>">
        <!-- Page Loader -->
        <div class="page-loader-wrapper">
            <div class="loader">
                <div class="m-t-30"><img src="{!! asset('assets/img/logo-icon.svg') !!}" width="48" height="48" alt="Lucid"></div>
                <p>Please wait...</p>        
            </div>
        </div>
        <div id="wrapper">
            @include('Backend.layout.navbar')
            @include('Backend.layout.sidebar')
            <div id="main-content">
                <div class="container-fluid">
                    <div class="block-header">
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12">                        
                                <h2><a href="javascript:void(0);" class="btn btn-xs btn-link btn-toggle-fullwidth"><i class="fa fa-arrow-left"></i></a> @yield('title')</h2>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12"> 
                                @include('Common.includes.error')
                            </div>
                        </div>
                    </div>

                    @yield('content')
                </div>
            </div>
        </div>
        <!-- Scripts -->
        <script type="text/javascript">
          window.Laravel = <?php echo json_encode([
                  'csrfToken' => csrf_token(),
                  'BASE_URL'  => chop(url('/'), '/public'),
                  'API_URL'   => chop(url('/'), '/public').'/api',
          ]); ?>
        </script>
         @yield('blade-page-vue-script')
        <script src="{{ asset('assets/bundles/libscripts.bundle.js') }}"></script>    
        <script src="{{ asset('assets/bundles/vendorscripts.bundle.js') }}"></script>
        <script src="{{ asset('assets/bundles/morrisscripts.bundle.js') }}"></script><!-- Morris Plugin Js -->
        <script src="{{ asset('assets/bundles/jvectormap.bundle.js') }}"></script> <!-- JVectorMap Plugin Js -->
        <script src="{{ asset('assets/bundles/knob.bundle.js') }}"></script>

        <script src="{{ asset('assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/nouislider/nouislider.js') }}"></script>
        <script src="{{ asset('assets/vendor/dropify/js/dropify.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/LightboxGallery/mauGallery.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/LightboxGallery/scripts.js') }}"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.full.min.js" ></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js" ></script>
        <script src="{{ asset('assets/vendor/toastr/toastr.js') }}"></script>
        {!! HTML::script('js/jquery.validate.min.js') !!}
        <script src="{{ asset('assets/bundles/mainscripts.bundle.js') }}"></script>
        <script src="{{ asset('assets/vendor/sweetalert/sweetalert.min.js') }}"></script>
        @if (Request::segment(2) === 'system' )
        <script src="{{ asset('assets/vendor/summernote/dist/summernote.js') }}"></script>
        @endif
        @yield('blade-page-script')
    </body>
</html>
