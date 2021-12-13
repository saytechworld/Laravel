<html>
    <head>
        <title>Video Conference</title>
        <meta charset="utf-8" />
        <link type="text/css" rel="stylesheet" href="https://source.zoom.us/1.7.9/css/bootstrap.css" />
        <link type="text/css" rel="stylesheet" href="https://source.zoom.us/1.7.9/css/react-select.css" />
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    </head>
    <body>
        <script src="https://source.zoom.us/1.7.9/lib/vendor/react.min.js"></script>
        <script src="https://source.zoom.us/1.7.9/lib/vendor/react-dom.min.js"></script>
        <script src="https://source.zoom.us/1.7.9/lib/vendor/redux.min.js"></script>
        <script src="https://source.zoom.us/1.7.9/lib/vendor/redux-thunk.min.js"></script>
        <script src="https://source.zoom.us/1.7.9/lib/vendor/jquery.min.js"></script>
        <script src="https://source.zoom.us/1.7.9/lib/vendor/lodash.min.js"></script>
        <script src="https://source.zoom.us/zoom-meeting-1.7.9.min.js"></script>
        <script src="{{ asset('js/zoom/tool.js') }}"></script>
        <script src="{{ asset('js/zoom/vconsole.min.js') }}"></script>
        @include('Coach.chat.script')
    </body>
</html>
