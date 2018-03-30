import Vue from 'vue';
import 'jquery';
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
        tree: {
            name: 'My Tree',
            children: [
                { name: 'hello' },
                { name: 'wat' },
                {
                    name: 'child folder',
                    children: [
                        {
                            name: 'child folder',
                            children: [
                                { name: 'hello' },
                                { name: 'wat' }
                            ]
                        },
                        { name: 'hello' },
                        { name: 'wat' },
                        {
                            name: 'child folder',
                            children: [
                                { name: 'hello' },
                                { name: 'wat' }
                            ]
                        }
                    ]
                }
            ]
        }
    }
});

