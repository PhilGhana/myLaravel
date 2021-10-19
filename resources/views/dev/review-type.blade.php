@extends('dev/index')

@section('content')
    <style>
        .active {
            background: green;
        }
    </style>
    <div id="review-type">
        <button @click="reload">Reload</button>
        <table class="table">
            <tr>
                <th >name</th>
                <th >key</th>
                <th >code</th>
                <th >enabled</th>
                <th >limit</th>
                <th></th>
            </tr>
            <tr v-for="row in rows">
                <td v-html="row.name"></td>
                <td v-html="row.key"></td>
                <td v-html="row.enabled"></td>
                <td v-html="row.order"></td>
                <td v-html="row.limit"></td>
                <td>
                    <button @click="load(row)">Edit</button>
                </td>
            </tr>
        </table>
        <fieldset>
            <legend>Platform</legend>
            <div class="row">
                <div class="cell">
                    <input @change="addMember" />
                    <div v-for="account in members" @click="removeMember(account)">
                        <span v-html="account"></span>
                    </div>
                </div>
                <form class="col" @submit.prevent>
                    <div>Key: <input v-model="input.key"></div>
                    <div>Name: <input v-model="input.name"></div>
                    <div>Type: <input v-model="input.type"></div>
                    <div>Code: <input v-model="input.code"></div>
                    <div>MemberPrefix: <input v-model="input.memberPrefix"></div>
                    <div>paramter: <input v-model="input.paramter"></div>
                    <div>enabled: <input v-model="input.enabled"></div>
                    <div>limit: <input v-model="input.limit"></div>
                    <div>order: <input v-model="input.order"></div>
                    <div>image: <input type="file" ref="image"></div>
                    <button @click="add">add</button>
                    <button @click="edit">edit</button>
                </form>
            </div>
        </fieldset>
    </div>

    <script>
        window.review = new Vue({
            el: '#review-type',
            data: {
                rows: [],
                members: [],
                input: {
                    key: '',
                    name: '',
                    type: 'ddf',
                    code: '3200',
                    memberPrefix: 'i88',
                    paramter: '{}',
                    enabled: 1,
                    limit: 0,
                    order: 20
                }
            },
            computed: {
                memberItems () {
                    let items = this.members.map(o => Object.assign({active: false}, o));
                    this.activeMembers.forEach(m => {
                        let find = items.find(o => o.id === m.id);
                        if (find) {
                            find.active = true;
                        } else {
                            items.push(Object.assign({}, m, {active: true}));
                        }
                    });
                    return items;
                }
            },
            mounted() {
                this.reload();
            },
            methods: {

                reload () {
                    $.ajax('/api/review-type/all').then(res => {
                        this.rows = res.data;
                    });

                },
                load (row) {
                    this.input.id = row.id;
                    this.input.key = row.key;
                    this.input.name = row.name;
                    this.input.type = row.type;
                    this.input.code = row.code;
                    this.input.memberPrefix = row.memberPrefix;
                    this.input.paramter = row.paramter;
                    this.input.enabled = row.enabled;
                    this.input.limit = row.limit;
                    this.input.order = row.order;
                    this.members = row.members.filter(acc => acc);
                },
                formData() {
                    let formData = new FormData();
                    Object.keys(this.input).forEach(key => {
                        formData.append(key, this.input[key]);
                    });
                    if (Number(this.input.limit)) {
                        let members = this.members.slice();
                        members.forEach(acc => {
                            formData.append('members[]', acc);
                        });
                    }

                    let img = this.$refs.image;
                    if (img.files.length) {
                        formData.append('image', img.files[0]);
                    }
                    return formData;
                },
                add () {
                    $.ajax('/api/game-platform/add', {
                        type: 'post',
                        dataType: 'json',
                        data: this.formData(),
                        processData: false,  // tell jQuery not to process the data
                        contentType: false   // tell jQuery not to set contentType
                    }).done(res => {
                        console.info('success');
                        this.reload();
                    }).fail(res => console.error(res));
                },
                edit () {
                    let members = this.members.slice();
                    $.ajax('/api/game-platform/edit', {
                        type: 'post',
                        dataType: 'json',
                        data: this.formData(),
                        processData: false,  // tell jQuery not to process the data
                        contentType: false   // tell jQuery not to set contentType
                    }).done(res => {
                        console.info('success');
                        this.reload();
                    }).fail(res => console.error(res));
                },
                addMember (event) {
                    this.members.push(event.target.value);
                    event.target.value = '';
                },
                removeMember(acc) {
                    this.members = this.members.filter(m => m !== acc);
                }
            }
        });

    </script>
@endsection