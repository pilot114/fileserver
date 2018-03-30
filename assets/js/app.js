import Vue from 'vue';
import $ from 'jquery';
import 'bootstrap';

Vue.config.devtools = true;

let api = {
    'create': '/api/v1/file/create',
    'delete': '/api/v1/file/delete',
    'get'   : '/api/v1/file/get',
    'list'  : '/api/v1/file/list',
    'setAccessType': '/api/v1/file/setAccessType'
};

new Vue({
    el: '#app',
    data: {
    }
});

