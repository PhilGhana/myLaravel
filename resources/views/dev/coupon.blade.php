@extends('dev/index')

@section('content')
    <style>
        .active {
            background: green;
        }
    </style>
    <div id="coupon">
        <button @click="reload">Reload</button>
        <table class="table">
            <tr>
                <th >name</th>
                <th >key</th>
                <th >code</th>
                <th >order</th>
                <th >limit</th>
                <th></th>
            </tr>
            <tr v-for="row in rows">
                <td v-html="row.name"></td>
                <td v-html="row.key"></td>
                <td v-html="row.code"></td>
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
                    <div>name: <input v-model="input.name" /></div>
                    <div>type: <input v-model="input.type" /></div>
                    <div>platformId: <input v-model="input.platformId" /></div>
                    <div>suitableType: <input v-model="input.suitableType" /></div>
                    <div>bonusType: <input v-model="input.bonusType" /></div>
                    <div>bonusPercent: <input v-model="input.bonusPercent" /></div>
                    <div>bonusAmount: <input v-model="input.bonusAmount" /></div>
                    <div>bonusMax: <input v-model="input.bonusMax" /></div>
                    <div>amountMax: <input v-model="input.amountMax" /></div>
                    <div>amountMin: <input v-model="input.amountMin" /></div>
                    <div>betValidMultiple: <input v-model="input.betValidMultiple" /></div>
                    <div>maxTimesDay: <input v-model="input.maxTimesDay" /></div>
                    <div>maxTimesTotal: <input v-model="input.maxTimesTotal" /></div>
                    <div>startTime: <input v-model="input.startTime" /></div>
                    <div>endTime: <input v-model="input.endTime" /></div>
                    <div>memberRegisterStart: <input v-model="input.memberRegisterStart" /></div>
                    <div>memberRegisterEnd: <input v-model="input.memberRegisterEnd" /></div>
                    <div>content: <input v-model="input.content" /></div>
                    <div>enabled: <input v-model="input.enabled" /></div>
                    <div>remark: <input v-model="input.remark" /></div>
                    <div>image: <input type="file" ref="image"></div>

                    <button @click="add">add</button>
                    <button @click="edit">edit</button>
                </form>
            </div>
        </fieldset>
    </div>

    <script>
        window.coupon = new Vue({
            el: '#coupon',
            data: {
                rows: [],
                members: [],
                input: {
                    name: '',               // * string(50) 優惠名稱
                    type: '',               // * enum(transfer, rescue, free, deposit) 優惠類型
                    platformId: 0,         // * number 遊戲平台 id (game_platform.id), 0 則不限制平台
                    suitableType: 'all',       // enum(all, agent, club-rank) 適用對象
                    bonusType: 'amount',          // enum(percent, amount) 優惠方式 (percent.依額度比例, amount.固定額度)
                    bonusPercent: 0,       // number 優惠額度比例 (%)
                    bonusAmount: 500,        // number 優惠固定額度
                    bonusMax: 0,           // number 產生的額度上限值 (0 不限制)
                    amountMax: 0,          // number 轉點/存款上限額度 (0 不限制)
                    amountMin: 0,          // number 轉點/存款下限額度 (0 不限制)
                    betValidMultiple: 5,   // number 有效投注倍率 (打碼量倍率)
                    maxTimesDay: 0,        // number 每日兌換次數上限 (0 不限制)
                    maxTimesTotal: 0,      // number 總兌換次數上限 ( 0 不限制)
                    startTime: '2018-09-02',          // string 活動起始時間 (YYYY-mm-dd HH:ii:ss)
                    endTime: '2018-09-05',            // string 活動結束時間 (YYYY-mm-dd HH:ii:ss)
                    memberRegisterStart: null,// string 會員註冊時間限制起 (YYYY-mm-dd)
                    memberRegisterEnd: null,  // string 會員註冊時間限制訖 (YYYY-mm-dd)
                    content: '123',            // string 優惠宣傳內容 (html)
                    enabled: 1,            // number 是否啟用 (0.停用, 1.啟用)
                    remark: '123',             // string(50) 備註
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
                    $.ajax('/api/coupon/list', {
                        data: {
                            page: 1,
                            type: 'all',
                            platformId: 0,
                        }
                    }).then(res => {
                        this.rows = res.data.content;
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
                    this.input.name = row.name;
                    this.input.type = row.type;
                    this.input.platformId = row.platformId;
                    this.input.suitableType = row.suitableType;
                    this.input.bonusType = row.bonusType;
                    this.input.bonusPercent = row.bonusPercent;
                    this.input.bonusAmount = row.bonusAmount;
                    this.input.bonusMax = row.bonusMax;
                    this.input.amountMax = row.amountMax;
                    this.input.amountMin = row.amountMin;
                    this.input.betValidMultiple = row.betValidMultiple;
                    this.input.maxTimesDay = row.maxTimesDay;
                    this.input.maxTimesTotal = row.maxTimesTotal;
                    this.input.startTime = row.startTime;
                    this.input.endTime = row.endTime;
                    this.input.memberRegisterStart = row.memberRegisterStart;
                    this.input.memberRegisterEnd = row.memberRegisterEnd;
                    this.input.content = row.content;
                    this.input.enabled = row.enabled;
                    this.input.remark = row.remark;
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
                    $.ajax('/api/coupon/add', {
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
                    $.ajax('/api/coupon/edit', {
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