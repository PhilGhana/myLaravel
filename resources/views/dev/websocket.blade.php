@extends('dev/index')

@section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/2.1.1/socket.io.js"></script>
    <div id="web" class="container-fluid">
        <div class="row">
            <div class="col">
                <h2> SocketIO Test </h2>
            </div>
            <div class="cell">
                <small v-if="status === 'connected'">已連線</small>
                <small v-if="status === 'connecting'">連線中</small>
                <small v-if="status === 'disconnected'">
                    <a href="#" class="btn link-btn" @click.prevent="connect(token)"> 斷線 - 重新連線 </a>
                </small>

            </div>

        </div>

        <div class="row">

            <div class="col-3">
                <h4>審核通知</h4>
                <div class="list-group" v-for="review in reviews">
                    <a href="#"
                        class="list-group-item d-flex justify-content-between align-items-center"
                        @click.prevent>
                        <span v-html="review.label"></span>
                        <span class="badge badge-pill badge-primary" v-html="review.badge"></span>
                    </a>
                </div>
            </div>
            <div class="col">

            </div>
        </div>



    </div>

    <script>
        let client;

        window.coupon = new Vue({
            el: '#web',
            data: {
                status: 'disconnected',
                reviews: [
                    {
                        label: '代理存款',
                        badge: 0,
                    },                    {
                        label: '代理提款',
                        badge: 0,
                    },                    {
                        label: '優惠申請',
                        badge: 0,
                    },
                ]
            },
            computed: {
                token () {
                    return head.user.token;
                }
            },
            watch: {
                token (token) {
                    if (token) {
                        this.connect(token);
                    } else {
                        this.disconnect();
                    }
                }
            },
            mounted() {

            },
            methods: {
                disconnect () {
                    client.disconnect();
                },
                connect (token) {

                    client = io.connect('http://127.0.0.1:8080/agent', {
                        secure: true,
                        query: {token}
                    });

                    this.status = 'connecting';

                    client.on('reconnecting', () => {
                        console.info('reconnecting');
                    });


                    client.on('connect', () => {
                        console.info('login-check');
                    });

                    client.on('ConnectSuccess', () => {
                        this.status = 'connected';
                        console.info('Connected');
                    });

                    client.on('ConnectError', message => {

                        console.warn(message);
                    });

                    client.on('SyncReport/Reload', () => {
                        console.info('synced');
                    });
                    client.on('SyncReport/ReloadAll', () => {
                        console.info('reload all ');
                    });

                    client.on('disconnect', () => {
                        this.status = 'disconnected';
                        console.error('disconnected');
                    });
                }

            }

        });

    </script>
@endsection