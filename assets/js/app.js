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

// backend
let backend = axios.create({
    baseURL: '/',
    headers: {
        token: user.token,
    }
});
// добавляем перехватчики для обработки стандартных ошибок и логирования
backend.interceptors.response.use(function (response) {
    if(response.data.error) {
        console.log("api error: " + response.data.error);
    } else {
        console.log("api call result:");
        console.log(response.data.result);
        return response.data.result;
    }
}, function (error) {
    console.log("server error: " + error);
});


new Vue({
    el: '#app',
    data: {
        currentNode: {
            path: "/",
            children: []
        },
        upload: {
            type: "public",
            path: "/",
            file: null,
        },
        previewFile: null
    },
    // загрузка корневой директории на старте
    created: function () {
        this.syncCurrent()
    },
    methods: {
        syncCurrent: function() {
            this.previewFile = null;
            this.upload.path = this.currentNode.path;

            let params = new FormData();
            params.append('path', this.currentNode.path);
            backend
                .post(api.list, params)
                .then(result => {
                    this.currentNode.children = result;
                });
        },
        toParentNode: function() {
            // убираем последнюю часть из пути
            let parts = this.currentNode.path.split('/');
            parts.length-=1;

            let newPath = parts.join('/');
            if (newPath) {
                this.currentNode.path = parts.join('/');
            } else {
                this.currentNode.path = '/';
            }
            this.syncCurrent();
        },
        toChildNode: function(node){
            // это файл
            if (node.url) {
                this.preview(node);
                return;
            }

            if (this.currentNode.path === '/') {
                this.currentNode.path += node.name;
            } else {
                this.currentNode.path += '/' + node.name;
            }
            this.syncCurrent();
        },
        prepareFile: function(fileList) {
            this.upload.file = fileList[0];
        },
        sendFile: function () {
            let params = new FormData();
            params.append('path', this.upload.path);
            params.append('access_type', this.upload.type);
            params.append('file', this.upload.file);
            console.log(params);

            backend
                .post(api.create, params)
                .then(result => {
                    // файл создан!
                })
        },
        preview: function(file){
            this.previewFile = file;
        },
    },
    filters: {
        tsToUTC: function(ts) {
            let newDate = new Date();
            newDate.setTime(ts * 1000);
            return newDate.toUTCString();
        },
        sizeForHumans: function(size) {
            var i = Math.floor( Math.log(size) / Math.log(1024) );
            return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
        }
    }
});
