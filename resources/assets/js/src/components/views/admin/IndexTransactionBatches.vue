<template>
    <section class="content">
        <div class="row center-block">
            <h1 class="text-center">{{ transactionBatches.length }} Transaction Batche(s)</h1>
            <div class="row">
                <div class="col-md-12">
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">Transaction Batches</h3>
                        </div>
                        <!-- /.box-header -->
                        <div class="box-body no-padding table-responsive">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th style="width: 10px">#</th>
                                        <th>Time Stamp</th>
                                        <th>Time (UTC)</th>
                                        <th># signers</th>
                                        <th>Submitted</th>
                                        <th></th>
                                    </tr>
                                    <tr v-for="transactionBatch in transactionBatches">
                                        <td>{{ transactionBatch.id }}</td>
                                        <td>{{ transactionBatch.timestamp }}</td>
                                        <td>{{ transactionBatch.created_at | prettyDateTime }}</td>
                                        <td>{{ transactionBatch.signer_count }}</td>
                                        <td>
                                            <i v-if="transactionBatch.submitted" class="fa fa-check text-primary fa-2x"></i>
                                            <i v-if="!transactionBatch.submitted" class="fa fa-close text-danger fa-2x"></i>
                                        </td>
                                        <td>
                                <router-link
                                    v-if="transactionBatch.signedByMe == false"
                                    tag="a"
                                    class="btn btn-block btn-primary"
                                    :to="{path: '/dashboard/admin/transactions/batches/' + transactionBatch.id +'/sign'}"
                                    >Sign</router-link>

                                <a v-if="transactionBatch.signedByMe == true" class="btn btn-block btn-primary" disabled>Signed</a>
                                <i v-if="transactionBatch.signedByMe == null" class="fa fa-spinner fa-spin fa-2x fa-fw text-primary"></i>
                                </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- /.box-body -->
                    </div>

                </div>
            </div>
        </div>
    </section>
</template>
<script>
    import config from '../../../config';
    import api from '../../../api';
    import { mapState } from 'vuex';

    export default {
        name: 'IndexTransactionBatches',
        data() {
            return {
                url: '/transactions/batches',
                transactionBatches: [],
                error: null
            }
        },
        computed: {
            ...mapState([
                    'user'
            ]),
        },
        watch: {
            transactionBatches: function (newValue, oldValue) {
                if (newValue.length != oldValue.length) {
                    var self = this;
                    for (var i = 0; i < newValue.length; i++) {
                        let index = i;
                        this.isTransactionBatchSignedByUser(newValue[index], this.user).then(function (res) {
                            self.$set(self.transactionBatches[index], 'signedByMe', res);
                        });
                    }
                }
            }
        },
        methods: {
            getTransactionBatches() {
                api.request('get', config.adminURI + this.url)
                        .then(response => {
                            if (response.status !== 200) {
                                this.error = response.statusText
                                return
                            }
                            this.transactionBatches = response.data.transactionBatches
                        })
                        .catch(error => {
                            // Request failed.
                            console.log('error', error.response)
                            this.error = error.response.statusText
                        })
            }
            ,
            isTransactionBatchSignedByUser(transactionBatch, user) {
                return api.request('get', config.adminURI + this.url + '/' + transactionBatch.id + '/signed/' + user.id)
                        .then(response => {
                            if (response.status !== 200) {
                                this.error = response.statusText
                                return
                            }
                            return response.data.signed;
                        })
                        .catch(error => {
                            // Request failed.
                            console.log('error', error.response)
                            this.error = error.response.statusText
                        })
            }
        },
        mounted() {
            this.getTransactionBatches();
        }
    }
</script>

<style>

</style>
