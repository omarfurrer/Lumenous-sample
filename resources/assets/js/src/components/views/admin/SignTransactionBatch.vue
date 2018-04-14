<template>
    <section class="content">
        <div class="row center-block">
            <h1 class="text-center">Transaction Batch {{ transactionBatch.timestamp }}</h1>
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="box box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title">Use your private key to sign</h3>
                        </div>
                        <!-- /.box-header -->
                        <!-- form start -->
                        <form class="form-horizontal">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-2 control-label">Private Key</label>

                                    <div class="col-sm-10">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-fw fa-key"></i></span>
                                            <input placeholder="Private Key" name="privateKey" type="text" class="form-control" v-model="privateKey">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.box-body -->
                            <div class="box-footer">
                                <button type="submit" v-on:click.prevent="sign(transactionBatch.id,privateKey)" class="btn btn-info pull-right">Sign</button>
                            </div>
                            <!-- /.box-footer -->
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
</template>
<script>
    import config from '../../../config'
            import api from '../../../api';


    export default {
        name: 'SignTransactionBatch',
        data() {
            return {
                url: '/transactions/batches',
                transactionBatch: null,
                privateKey: null,
                error: null
            }
        },
        methods: {
            getTransactionBatch(id) {
                api.request('get', config.adminURI + this.url + '/' + id)
                        .then(response => {
                            if (response.status !== 200) {
                                this.error = response.statusText
                                return
                            }
                            this.transactionBatch = response.data.transactionBatch
                        })
                        .catch(error => {
                            // Request failed.
                            console.log('error', error.response)
                            this.error = error.response.statusText
                        })
            },
            sign(id, privateKey) {
                api.request('post', config.adminURI + this.url + '/' + id + '/sign', {private_key: privateKey})
                        .then(response => {
                            if (response.status !== 200) {
                                this.error = response.statusText
                                return
                            }
                            console.log(response);
                        })
                        .catch(error => {
                            // Request failed.
                            console.log('error', error.response)
                            this.error = error.response.statusText
                        })
            }
        },
        mounted() {
            console.log(this.$route.params.id);
            this.getTransactionBatch(this.$route.params.id);
        }
    }
</script>

<style>

</style>
