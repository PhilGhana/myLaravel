@extends('dev/index')
@section('header')
@endsection
@section('content')
<style>
    [v-cloak] {
        opacity: 0
    }

    .input-label-component label {
        display: block;
        cursor: pointer;
    }
</style>
<div id="app" v-cloak>
    <div class="row">
        <div class="col-6">
            <div class="row">
                <div class="form-group col-4">
                    <label>關鍵字</label>
                    <input class="form-control" v-model="query.keyword" @change="reload(1)" />
                </div>
                <div class="form-group col-4">
                    <label>平台</label>
                    <select class="form-control" v-model="query.pid" :class="{'is-invalid': invalids.pid}" @change="reload(1)">
                        <option :value="0" v-text="'請選擇'"></option>
                        <option :value="opt.value" v-for="opt in platformOptions" :key="opt.value" v-text="opt.label"></option>
                    </select>
                    <div class="invalid-tooltip" v-text="invalids.pid"></div>
                </div>
                <div class="form-group col-4">
                    <label>類型</label>
                    <select class="form-control" v-model="query.type" :class="{'is-invalid': invalids.type }" @change="reload(1)">
                        <option value="" v-text="'請選擇'"></option>
                        <option :value="opt.value" v-for="opt in typeOptions" :key="opt.value" v-text="opt.label"></option>
                    </select>
                    <div class="invalid-tooltip" v-text="invalids.type"></div>
                </div>

            </div>
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>code</th>
                        <th>codeMobile</th>
                        <th>name</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in rows" :key="i">
                        <td>
                            <input-label v-model="row.code" @change="editGame(row, 'code')" required />
                        </td>
                        <td>
                            <input-label v-model="row.codeMobile" @change="editGame(row, 'codeMobile')" />
                        </td>
                        <td>
                            <input-label v-model="row.name" @change="editGame(row, 'name')" required />
                        </td>
                    </tr>
                </tbody>
            </table>
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center">
                    <li class="page-item" v-for="p in pages" :key="p" :class="{active: p === paginate.page}" @click.prevent="reload(p)">
                        <a class="page-link" href="#" v-html="p"></a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="col-6">
            <div class="row">
                <div class="form-group col-4">
                    <label>遊戲代碼</label>
                    <input type="text" class="form-control" :class="{'is-invalid': invalids.code}" v-model="form.code" :disabled="!canAdd" @keydown.enter="addGame">
                    <div class="invalid-tooltip" v-text="invalids.code"></div>
                </div>
                <div class="form-group col-4">
                    <label>Mobile 代碼</label>
                    <input type="text" class="form-control" :class="{'is-invalid': invalids.codeMobile}" v-model="form.codeMobile" :disabled="!canAdd" @keydown.enter="addGame">
                    <div class="invalid-tooltip" v-text="invalids.codeMobile"></div>
                </div>
                <div class="form-group col-4">
                    <label>遊戲名稱</label>
                    <input type="text" class="form-control" :class="{'is-invalid': invalids.name}" v-model="form.name" :disabled="!canAdd" @keydown.enter="addGame">
                    <div class="invalid-tooltip" v-text="invalids.name"></div>
                </div>
            </div>
            <div class="list-group">
                <div class="list-group item" v-for="queue in updateQueues" :key="queue.id">
                    <div v-if="queue.loading" class="alert alert-warning">
                        <span class="col-2"> 【 Saving ... 】 </span>
                        <span v-html="queue.content"></span>
                    </div>
                    <div v-else-if="queue.done" class="alert alert-success">
                        <span class="col-2"> 【 Finish 】 </span>
                        <span v-html="queue.content"></span>
                    </div>
                    <div v-else class="alert alert-danger">
                        <span class="col-2"> 【 Fail 】 </span>
                        <span v-html="queue.content"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="input-label">
    <div class="input-label-component">
        <label v-show="!editMode" @click="editMode = true" v-text="value || 'no-setting'"></label>
        <div class="form-group col" v-show="editMode">
            <input ref="input" type="text" class="form-control" :class="{'is-invalid': invalid}" :value="value" @change="onChange" @blur="onChange" />
            <div class="invalid-tooltip" v-text="invalid"></div>
        </div>
    </div>
</template>
<script>
    const InputLabel = {
        model: {
            prop: 'value',
            event: 'change',
        },
        props: {
            value: String,
            required: {
                type: Boolean,
                default: false
            },
        },
        data: () => ({
            editMode: false,
            invalid: '',
        }),
        watch: {
            editMode(bool) {
                if (bool) {
                    this.$nextTick(() => this.$refs.input.select());
                }
            }
        },
        methods: {
            onChange(event) {
                const value = event.target.value;
                this.invalid = '';
                if (value !== this.value) {
                    if (this.required && value === '') {
                        this.invalid = '不得為空值';
                        this.$nextTick(() => this.$refs.input.select());
                        return;
                    }
                    this.$emit('change', event.target.value);
                }
                this.editMode = false;
            }
        },
        template: $('#input-label').html(),
    };



    const app = new Vue({
        el: '#app',
        data: {
            rows: [],
            query: {
                keyword: '',
                pid: 0,
                type: '',
            },
            form: {
                code: '',
                codeMobile: '',
                name: '',
            },
            invalids: {
                pid: '',
                type: '',
                code: '',
                name: '',
            },
            updateQueues: [],
            paginate: {
                page: 1,
                perPage: 15,
                total: 0,
            },
            platformOptions: [],
            typeOptions: [],
        },
        computed: {
            canAdd() {
                const {
                    pid,
                    type
                } = this.query;
                return pid && type && true || false;
            },
            pages() {
                const {
                    perPage,
                    total
                } = this.paginate;
                return new Array(Math.ceil(total / perPage)).fill(0).map((v, i) => (i + 1));
            }
        },
        mounted() {
            this.loadOptions();
        },
        methods: {
            loadOptions() {
                $.ajax('/api/dev/game/options').then(res => {
                    this.platformOptions = res.platforms.map(o => {
                        return {
                            value: o.id,
                            label: `${o.key} - ${o.name}`,
                        };
                    });
                    this.typeOptions = res.types.map(o => {
                        return {
                            value: o.type,
                            label: o.name,
                        };
                    });
                });
            },
            reload(page) {
                const data = {
                    ...this.pagiante,
                    ...this.query,
                    page: page || this.paginate.page,
                };
                $.ajax('/api/dev/game/list', {
                    data
                }).then(o => {
                    this.rows = o.rows;
                    this.paginate.total = Number(o.total);
                    this.paginate.page = Number(o.page);
                    this.paginate.perPage = Number(o.perPage);
                });

            },
            resetInvalids() {
                this.invalids.pid = null;
                this.invalids.type = null;
                this.invalids.code = null;
                this.invalids.name = null;
            },
            isInvalid() {
                this.resetInvalids();
                let invalid = false;
                if (!this.query.pid) {
                    this.invalids.pid = '請選擇平台';
                    invalid = true;
                }
                if (!this.query.type) {
                    this.invalids.type = '請選擇類型';
                    invalid = true;
                }
                if (!this.form.code) {
                    this.invalids.code = '此為必填';
                    invalid = true;
                }
                if (!this.form.name) {
                    this.invalids.code = '此為必填';
                    invalid = true;
                }
                return invalid;

            },
            addGame() {

                if (!this.isInvalid()) {
                    const data = {
                        type: this.query.type,
                        pid: this.query.pid,
                        name: this.form.name,
                        code: this.form.code,
                        codeMobile: this.form.codeMobile,
                    };
                    const promise = $.ajax('/api/dev/game/save', {
                        type: 'post',
                        data
                    });
                    const queue = {
                        id: new Date().getTime(),
                        loading: true,
                        content: `Add Game, Code = ${data.code}, Name = ${data.name}`,
                        done: false,
                        data,
                        promise,
                    }

                    queue.promise.then(() => {
                        queue.done = true;
                        this.reload();
                    }, () => {}).then(() => {
                        queue.loading = false;
                    });

                    this.updateQueues = [
                        queue,
                        ...this.updateQueues,
                    ]
                    this.updateQueues = this.updateQueues.slice(0, 5);
                    this.form.code = '';
                    this.form.name = '';
                }
            },

            editGame(row, attr) {

                if (row[attr] !== '') {
                    const data = {
                        id: row.id,
                        [attr]: row[attr],
                    };
                    const promise = $.ajax(`/api/dev/game/save/${attr}`, {
                        type: 'post',
                        data,
                    });

                    const queue = {
                        id: new Date().getTime(),
                        loading: true,
                        content: `Edit Game, ${attr} = ${data[attr]}`,
                        done: false,
                        data,
                        promise,
                    };

                    queue.promise.then(() => {
                        queue.done = true;
                    }, () => {}).then(() => {
                        queue.loading = false;
                        this.reload();
                    });

                    this.updateQueues = [
                        queue,
                        ...this.updateQueues,
                    ]
                    this.updateQueues = this.updateQueues.slice(0, 5);

                }

            }


        },
        components: {
            'input-label': InputLabel,
        }

    });
</script>
@endsection
