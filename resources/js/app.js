
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
Vue.use(require('vue-resource'));

var token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
  Vue.http.headers.common['X-CSRF-TOKEN'] = token.content;
 //  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
  console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}
/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('example-component', require('./components/ExampleComponent.vue').default);

Vue.component('athelete-chatuser-component', require('./components/Athelete/ChatUserComponent.vue').default);
Vue.component('athelete-chatmessage-component', require('./components/Athelete/ChatMessageComponent.vue').default);
Vue.component('athelete-chatpage-component', require('./components/Athelete/ChatPageComponent.vue').default);
Vue.component('athelete-groupchatmessage-component', require('./components/Athelete/GroupChatMessageComponent.vue').default);


Vue.component('coach-chatuser-component', require('./components/Coach/ChatUserComponent.vue').default);
Vue.component('coach-chatmessage-component', require('./components/Coach/ChatMessageComponent.vue').default);
Vue.component('coach-groupchatmessage-component', require('./components/Coach/GroupChatMessageComponent.vue').default);
Vue.component('coach-chatpage-component', require('./components/Coach/ChatPageComponent.vue').default);



Vue.http.options.BASE_URL = Laravel.BASE_URL;
Vue.http.options.AWS_BASE_URL = Laravel.AWS_BASE_URL;
Vue.http.options.API_URL = Laravel.API_URL;

Vue.http.options.STRIPE_KEY = Laravel.STRIPE_KEY;
Vue.http.options.SERVICE_TAX = Laravel.SERVICE_TAX;
Vue.http.options.TRANSACTION_FEES = Laravel.TRANSACTION_FEES;

Vue.http.options.MESSAGE_IMAGE_SIZE = Laravel.MESSAGE_IMAGE_SIZE;
Vue.http.options.MESSAGE_VIDEO_SIZE = Laravel.MESSAGE_VIDEO_SIZE;
Vue.http.options.paypal_sandbox = Laravel.paypal_sandbox;
Vue.http.options.paypal_production = Laravel.paypal_production;
Vue.http.options.paypal_env = Laravel.paypal_env;

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#manage-vue'
});