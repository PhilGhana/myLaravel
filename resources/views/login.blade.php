@extends('index')


@section('main')
    <style>
        iframe#view {
            width: 100%;
            border: 1px solid #000;
        }
    </style>
    <div id="app" class="cell">
        <form @submit.prevent="doLogin">
            <div>
                <label for="">Account</label>
                <input type="text" v-model="input.account">
            </div>
            <div>
                <label for="">Password</label>
                <input type="password" v-model="input.password">
            </div>
            <button>login</button>
            <a href="/api/public/logout" target="_blank">Logout</a>
            <label v-html="message" style="font-weight: bold"></label>
        </form>
    </div>
    <script>
        new Vue({
            el: '#app',
            data: {
                input: {
                    account: 'admin',
                    password: 'ivan',
                },
                message: '',
            },
            methods: {
                doLogin () {
                    $.ajax('/api/public/login', {
                        type: 'post',
                        data: this.input,
                    }).then(res => {
                        this.message = 'login success';
                        setTimeout(() => this.message = '', 3000);
                        console.info(res);
                    }).fail(res => {
                        console.error(res);
                        this.message = res.responseJSON || {message: res.responseText};
                        setTimeout(() => this.message = '', 3000);
                    });
                }

            }
        });
    </script>

@endsection