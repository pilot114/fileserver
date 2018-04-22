import Vue from 'vue';
import axios from 'axios';
import 'bootstrap';

Vue.config.devtools = true;

// пока хардкод
let user = {
    name: 'portal',
    token: '94a08da1fecbb6e8b46990538c7b50b2',
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


let app = new Vue({
    el: '#app',
    data: {
        // текущая директория
        currentDir: {
            path: "/",
            children: []
        },
        // загружаемый файл
        upload: {
            type: "public",
            path: "/",
            file: null,
            filename: null
        },
        // информация по выбранному файлу (name, size и т.д.)
        previewFile: null
    },
    // загрузка корневой директории на старте
    created: function () {
        this.syncCurrentDir()
    },
    methods: {
        syncCurrentDir: function() {
            this.previewFile = null;
            this.upload.path = this.currentDir.path;

            let params = new FormData();
            params.append('path', this.currentDir.path);
            backend
                .post(api.list, params)
                .then(result => {
                    this.currentDir.children = result;
                });
        },
        toParentNode: function() {
            // убираем последнюю часть из пути
            let parts = this.currentDir.path.split('/');
            parts.length-=1;

            let newPath = parts.join('/');
            if (newPath) {
                this.currentDir.path = parts.join('/');
            } else {
                this.currentDir.path = '/';
            }
            this.syncCurrentDir();
        },
        toChildNode: function(node){
            // это файл
            if (node.url) {
                this.preview(node);
                return;
            }

            if (this.currentDir.path === '/') {
                this.currentDir.path += node.name;
            } else {
                this.currentDir.path += '/' + node.name;
            }
            this.syncCurrentDir();
        },

        prepareFile: function(fileList) {
            this.upload.file = fileList[0];
            this.upload.filename = fileList[0].name;
        },
        sendFile: function () {
            let params = new FormData();
            params.append('path', this.upload.path);
            params.append('access_type', this.upload.type);
            params.append('file', this.upload.file);

            backend
                .post(api.create, params)
                .then(result => {
                    // в результате нужно получить мета инфу
                    // файл создан, обновляем UI
                    app.currentDir.children.push(result);
                })
        },
        deleteFile: function() {
            let params = new FormData();
            params.append('path', this.currentDir.path);
            params.append('filename', this.previewFile.name);

            backend
                .post(api.delete, params)
                .then(result => {
                    // файл удален, обновляем UI
                    app.currentDir.children = app.currentDir.children.filter(child => {
                        return child.name != app.previewFile.name;
                    });
                    app.previewFile = null;
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
