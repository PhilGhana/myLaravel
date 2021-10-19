
@section('header')
<style>
    #login {
        margin: 10px 0;
        padding: 10px;
        border-bottom: 1px solid #000;
    }
</style>
<div id="login">
    <form @submit.prevent="doLogin" v-if="!isLogin">
        <div>
            <label for="">Account</label>
            <input type="text" v-model="input.account">
        </div>
        <div>
            <label for="">Password</label>
            <input type="password" v-model="input.password">
        </div>
        <button>login</button>
    </form>
    <div v-else  >
        <span v-html="`${user.account} Welcome`"></span>
        <span v-html="`, ${user.token}`"></span>
        <a href="#" @click.prevent="doLogout">Logout</a>
    </div>
</div>

<script>
    var head = new Vue({
        el: '#login',
        data: {
            input: {
                account: '',
                password: '',
            },
            user: {
                id: null,
                account: null,
                name: null,
                token: null,
            }
        },
        computed: {
            isLogin () {
                return this.user.account;
            }
        },
        mounted () {
            this.init();
        },
        methods: {
            init () {
                $.ajax('/api/public/init').then(res => {
                    if (res.refresh.user) {
                        let {id, account, name, token} = res.refresh.user;
                        this.user.id = id;
                        this.user.account = account;
                        this.user.name = name;
                        this.user.token = token;
                        this.$emit('onlogin');
                    }
                });
            },
            doLogin () {
                $.ajax('/api/public/login', {
                    type: 'post',
                    data: this.input,
                }).then(res => {
                    let {id, account, name, token} = res.refresh.user;
                    this.user.id = id;
                    this.user.account = account;
                    this.user.name = name;
                    this.user.token = token;
                    this.$emit('onlogin');
                }).fail(res => alert(res.responseText));
            },
            doLogout () {
                $.ajax('/api/public/logout').then(() => {
                    this.user.id = 0;
                    this.user.account = '';
                    this.user.name = '';
                    this.user.token = '';
                }).fail(res => alert(res.responseText));
            }

        }
    })

</script>
@endsection
