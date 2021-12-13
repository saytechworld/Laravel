<?php

$local_live_env = 'local';
//$local_live_env = 'production';

$config_array = array(); 
// Config File for static env variable 
if($local_live_env == 'local'){
    $config_array['STRIPE']['STRIPE_KEY'] = 'pk_test_5TujBnwtq8f7ttcMO9yZ8ebI00vYtPZDyZ';
    $config_array['STRIPE']['STRIPE_SECRET'] = 'sk_test_UA5IZRcxlZiPRAuUyl0FXQG9009b0k2WF0';
    $config_array['AWS_URL']="https://s3.eu-west-3.amazonaws.com/staging-asportcoach/";
    $config_array['AWS_BUCKET']="staging-asportcoach";
    $config_array['paypal_env']="sandbox";
}else{
    $config_array['STRIPE']['STRIPE_KEY'] = 'pk_live_sb2IW0X1MrywwCscAvsv0Ruw00WmgB8uW2';
    $config_array['STRIPE']['STRIPE_SECRET'] = 'sk_live_8MHh3y4TfAbn4T0JbIp29nrV00waAb51Na';
    $config_array['AWS_URL']="https://s3.eu-west-3.amazonaws.com/asportcoach/";
    $config_array['AWS_BUCKET']="asportcoach";
    $config_array['paypal_env']="sandbox";
}
$config_array['MESSAGE_IMAGE_SIZE'] = 10000000;
$config_array['MESSAGE_VIDEO_SIZE'] = 1000000000;
$config_array['FIREBASE_DATABASE'] = "https://coachbookdemo.firebaseio.com/";
$config_array['FIREBASE_CREDENTIALS'] = "coachbookdemo-firebase-adminsdk-uy4vk-056b9f8522.json";
$config_array['SERVICE_TAX']= 17;
$config_array['TRANSACTION_FEES']=0;
$config_array['TRAIL_DAY']=30;
$config_array['PLATFORM_FEES']=10;
$config_array['MINIMUM_PAYMENT']=2;
$config_array['paypal_sandbox']='AS7bVGTQLEIYAV6TlDvXc0fDCJCjOt-jzPuFzUpHVQMYxNYnORtk5HZlojUg0cPEQXt4tdETx2eKhqr7';
$config_array['paypal_production']='';



return $config_array;






