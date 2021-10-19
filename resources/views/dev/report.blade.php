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
    <div id="coupon">
        <form @submit.prevent="search">
            <div class="form-row">
                <div class="col-md-3">
                    <input type="datetime" class="form-control" v-model="input.startTime">
                </div>
                <span> ~ </span>
                <div class="col-md-3">
                    <input type="datetime" class="form-control" v-model="input.endTime">
                </div>
            </div>
            <div class="form-row">
                <div class="form-check form-check-inline" v-for="opt in typeOptions">
                    <label class="form-check-label">
                        <input type="checkbox" :value="opt.value" v-model="opt.active">
                        <span v-html="opt.label" ></span>
                    </label>
                </div>
            </div>
            <button class="btn">查詢</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">代理</th>
                    <th rowspan="2" class="align-middle">局數</th>
                    <th rowspan="2" class="align-middle">總下注額</th>
                    <th rowspan="2" class="align-middle">會員輸贏</th>
                    <th rowspan="2" class="align-middle">有效下注</th>
                    <th rowspan="2" class="align-middle">會員退水</th>
                    <th rowspan="2" class="align-middle">小費</th>
                    <th rowspan="2" class="align-middle">會員小計</th>
                    <th colspan="5" class="align-center">代理成本</th>
                </tr>
                <tr>
                    <th>代理佔比額度</th>
                    <th>應付退水額(代理)</th>
                    <th>應付退水額(會員)</th>
                    <th>應付紅利(會員)</th>
                    <th>小計</th>
                </tr>
            </thead>
            <tr v-for="row in rows">
                <td>
                    <span v-html="row.agent.account"></span>
                    <span v-html="`(${row.agent.name})`"></span>
                </td>
                <td v-html="row.nums"></td>
                <td v-html="row.betAmount"></td>
                <td v-html="row.resultAmount"></td>
                <td v-html="row.validAmount"></td>
                <td v-html="row.waterAmount"></td>
                <td v-html="row.tip"></td>
                <td v-html="row.subtotal"></td>
                <td v-html="row.fee"></td>
                <td></td>
            </tr>
        </table>
    </div>

    <script>
        window.coupon = new Vue({
            el: '#coupon',
            data: {
                rows: [],
                input: {
                    startTime: moment().format('YYYY-MM-DD'),
                    endTime: moment().format('YYYY-MM-DD 23:59:59'),
                    types: [],
                },
                level: [],
                typeOptions: [],
            },
            computed: {
            },
            watch: {
                typeOptions: {
                    deep: true,
                    handler(opts) {
                        this.input.types = opts.filter(o => o.active).map(o => o.type);
                    }
                }
            },
            mounted() {
                this.reloadTypes();
            },
            methods: {
                reloadTypes () {
                    $.ajax('/api/report/result/platform-options').then(res => {
                        this.typeOptions = res.data.map(o => {
                            return {
                                active: true,
                                value: o.type,
                                label: o.name,
                            };
                        })
                    })
                    .fail(res => console.error(res.responseText));
                },
                search () {
                    let data = {...this.input};
                    data['key'] = 'gs';

                    $.ajax('/api/report/result/organization', {
                        data,
                    }).then(res => {
                        this.rows = res.data;
                    });
                }
            }

        });

    </script>
@endsection