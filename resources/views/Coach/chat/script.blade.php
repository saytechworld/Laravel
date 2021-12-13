<script type="text/javascript">
    (function () {
        var testTool = window.testTool;
        // get meeting args from url
        var tmpArgs = testTool.parseQuery();
        var meetingConfig = {
            apiKey: "{{ env('ZOOM_API_KEY') }}",
            meetingNumber: "{{ $input['mn'] }}",
            userName: (function () {
                if ( "{{ $input['name'] }}") {
                    try {
                        return testTool.b64DecodeUnicode("{{ $input['name'] }}");
                    } catch (e) {
                        return "{{ $input['name'] }}";
                    }
                }
                return (
                    "CDN#" +
                    tmpArgs.version +
                    "#" +
                    testTool.detectOS() +
                    "#" +
                    testTool.getBrowserInfo()
                );
            })(),
            passWord: "{{ $input['pwd'] }}",
            leaveUrl: "{{ route('coach.meeting.leave', request()->route('chat_uuid')) }}",
            role: parseInt({{$input['role']}}, 10),
            userEmail: (function () {
                try {
                    return testTool.b64DecodeUnicode("{{ $input['email'] ?? '' }}");
                } catch (e) {
                    return "{{ $input['email'] ?? '' }}";
                }
            })(),
            lang: "{{ $input['lang'] }}",
            signature: "{{ $input['signature'] }}",
            china: "{{ $input['china'] === "1" }}",
        };


        // a tool use debug mobile device
        if (testTool.isMobileDevice()) {
            vConsole = new VConsole();
        }

        // it's option if you want to change the WebSDK dependency link resources. setZoomJSLib must be run at first
        // ZoomMtg.setZoomJSLib("https://source.zoom.us/1.7.9/lib", "/av"); // CDN version defaul
        if (meetingConfig.china)
            ZoomMtg.setZoomJSLib("https://jssdk.zoomus.cn/1.7.9/lib", "/av"); // china cdn option
        ZoomMtg.preLoadWasm();
        ZoomMtg.prepareJssdk();
        function beginJoin(signature) {
            ZoomMtg.init({
                leaveUrl: meetingConfig.leaveUrl,
                webEndpoint: meetingConfig.webEndpoint,
                isSupportChat: false,
                screenShare:false,
                isSupportAV: true,
                meetingInfo: [
                    'topic',
                    'host',
                ],
                success: function () {
                    $.i18n.reload(meetingConfig.lang);
                    ZoomMtg.join({
                        meetingNumber: meetingConfig.meetingNumber,
                        userName: meetingConfig.userName,
                        signature: signature,
                        apiKey: meetingConfig.apiKey,
                        userEmail: meetingConfig.userEmail,
                        passWord: meetingConfig.passWord,
                        success: function (res) {
                            ZoomMtg.getAttendeeslist({});
                            ZoomMtg.getCurrentUser({
                                success: function (res) {
                                    console.log("success getCurrentUser", res.result.currentUser);
                                },
                            });
                        },
                        error: function (res) {
                            console.log(res);
                        },
                    });
                },
                error: function (res) {
                    console.log(res);
                },
            });
        }

        beginJoin(meetingConfig.signature);
    })();

</script>