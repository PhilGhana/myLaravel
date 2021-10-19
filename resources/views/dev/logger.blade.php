@extends('dev/index')

@section('content')
    <style>
        .active {
            background: green;
        }
        .table thead th {
            text-align: center;
            font-size: 13px;
            padding: 5px;
        }
    </style>
    <div id="logger">
        <form @submit.prevent="reload">
            <div class="form-row">
                <div class="col-md-2">
                    <label >ID</label>
                    <input class="form-control" v-model="input.id">
                </div>
                <div class="col-md-2">
                    <label >Time Start</label>
                    <input class="form-control" v-model="input.startTime" placeholder="2018-12-12">
                </div>
                <div class="col-md-2">
                    <label >Time End</label>
                    <input class="form-control" v-model="input.endTime" placeholder="2018-12-12 23:59:59">
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <div>
                        <button class="btn">查詢</button>
                    </div>
                </div>
            </div>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th class="align-middle">ID</th>
                    <th class="align-middle">Time</th>
                    <th class="align-middle">Status Code</th>
                    <th class="align-middle">Message</th>
                </tr>
            </thead>
            <tr v-for="row in rows">
                <td v-text="row.id"></td>
                <td v-text="row.createdAt"></td>
                <td v-text="row.statusCode"></td>
                <td>
                    <a href="#" @click.prevent="showDetail(row)" v-text="row.messageText"></a>
                </td>
                <td></td>
            </tr>
        </table>

        <!-- Modal -->
        <div class="modal fade" id="detail" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel" v-text="detail.className"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    </div>
                    <div class="modal-body">
                        <p v-text="`URL: ${detail.url}`"></p>
                        <p v-text="`class: ${detail.className}`"></p>
                        <p v-text="`file: ${detail.file}`"></p>
                        <p v-text="detail.line"></p>
                        <textarea name="" class="form-control" :value="detail.message" disabled></textarea>
                        <fieldset >
                            <legend>Traces</legend>
                            <div class="list-group" style="height: 600px; overflow: auto">
                                <div class="list-group-item" v-for="trace in detail.traces">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1" v-text="`${trace.class}${trace.type}${trace.func}`"></h5>
                                        <small v-text="`Line.${trace.line || ''}`"></small>
                                    </div>
                                    <p class="mb-1" v-text="`FIle: ${trace.file || ''}`"></p>
                                    <p>
                                        Params:
                                        <small v-text="trace.args"></small>
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                    </div>
                    <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.logger = new Vue({
            el: '#logger',
            data: {
                rows: [],
                input: {
                    id: '',
                    startTime: '',
                    endTime: '',
                },
                detail: {}
            },
            computed: {
            },
            mounted() {
                this.reload();

                this.$detail = $('#detail').modal('hide');
            },
            methods: {
                reload () {
                    let data = {...this.input};
                    $.ajax('/api/dev/logger', {data})
                        .then(res => {
                            this.rows = res.rows.map(row => {
                                row.messageText = row.message.length > 50
                                    ? (row.message.substr(0, 45) + '...')
                                    : row.message;
                                return row;
                            });
                        })
                        .fail(res => console.error(res.responseText));
                },

                showDetail (row) {
                    $.ajax(`/api/dev/logger/${row.id}`)
                        .then(res => {
                            if (res && res.id) {
                                this.detail = res;
                                this.$detail.modal('show');
                            }
                        })
                        .fail(res => console.error(res.responseText));
                }


            }

        });

    </script>
@endsection