import * as firebase from 'firebase';
//import * as firebase from 'firebase/database';

// Your web app's Firebase configuration
var firebaseConfig = {
	apiKey: "AIzaSyCCtXBMvS-Z6zo5SoojZMnfJiC8ld_MFcQ",
    authDomain: "coachbookdemo.firebaseapp.com",
    databaseURL: "https://coachbookdemo.firebaseio.com",
    projectId: "coachbookdemo",
    storageBucket: "coachbookdemo.appspot.com",
    messagingSenderId: "974966385354",
    appId: "1:974966385354:web:96dbd2e040d7b5df926233",
    measurementId: "G-V983NL4NFE"
};
// Initialize Firebase
firebase.initializeApp(firebaseConfig);

const firebase_db = firebase.database();




export {
	firebase_db,
};