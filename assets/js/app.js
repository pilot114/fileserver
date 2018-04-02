import Vue from 'vue';
import axios from 'axios';
import 'bootstrap';

Vue.config.devtools = true;

// пока хардкод
let user = {
    name: 'portal',
    password: '1234',
    secret: '1234',
    token: '1234',
};

let api = {
    'create': '/api/v1/file/create',
    'delete': '/api/v1/file/delete',
    'get'   : '/api/v1/file/get',
    'list'  : '/api/v1/file/list',
    'setAccessType': '/api/v1/file/setAccessType'
};

let server = axios.create({
    baseURL: 'http://fileserver.local/',
    headers: {
        token: user.token,
    }
});
// добавляем перехватчики для обработки стандартных ошибок и логгирования
server.interceptors.response.use(function (response) {
    if(response.data.error) {
        alert(response.data.error);
    } else {
        console.log(response.data.result);
        return response.data.result;
    }
}, function (error) {
    console.log(error);
});


new Vue({
    el: '#app',
    data: {
        currentNode: null,
        parentNode: null
    },
    created: function () {
        server
            .post(api.list)
            .then(result => {
                this.currentNode = {
                    name: '/',
                    children: result.map(item => {
                        item.name = item.path;
                        return item;
                    })
                };
            })
    },
    methods: {
        enterNode: function(node){
            console.log('path:' + node.path);

            // application/x-www-form-urlencoded - как в формах
            let params = new URLSearchParams();
            params.append('path', node.path);

            server
                .post(api.list, params)
                .then(result => {
                    this.currentNode = {
                        name: node.path,
                        children: result.map(item => {
                            item.name = item.path;
                            return item;
                        })
                    };
                })
        },
    }
});

