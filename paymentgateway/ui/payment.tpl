{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/mpesa_c2b">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('M-Pesa C2B Payment Gateway')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Shortcode</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_c2b_shortcode" name="mpesa_c2b_shortcode"
                                value="{$_c['mpesa_c2b_shortcode']}" required>
                            <small class="form-text text-muted">Your M-Pesa C2B Shortcode</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Passkey</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_c2b_passkey" name="mpesa_c2b_passkey"
                                value="{$_c['mpesa_c2b_passkey']}" required>
                            <small class="form-text text-muted">M-Pesa C2B Passkey from Daraja Portal</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_c2b_consumer_key" name="mpesa_c2b_consumer_key"
                                value="{$_c['mpesa_c2b_consumer_key']}" required>
                            <small class="form-text text-muted">M-Pesa API Consumer Key</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Secret</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="mpesa_c2b_consumer_secret" name="mpesa_c2b_consumer_secret"
                                value="{$_c['mpesa_c2b_consumer_secret']}" required>
                            <small class="form-text text-muted">M-Pesa API Consumer Secret</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Environment</label>
                        <div class="col-md-6">
                            <select class="form-control" name="mpesa_c2b_environment">
                                <option value="sandbox" {if $_c['mpesa_c2b_environment'] == 'sandbox'}selected{/if}>Sandbox</option>
                                <option value="live" {if $_c['mpesa_c2b_environment'] == 'live'}selected{/if}>Live</option>
                            </select>
                            <small class="form-text text-muted">Select Sandbox for testing, Live for production</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light"
                                type="submit">{Lang::T('Save Change')}</button>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>M-Pesa C2B Setup Instructions:</strong>
                        <ol>
                            <li>Register for M-Pesa Daraja API at <a href="https://developer.safaricom.co.ke" target="_blank">https://developer.safaricom.co.ke</a></li>
                            <li>Create a C2B application and get your Consumer Key and Secret</li>
                            <li>Set your C2B Shortcode and Passkey</li>
                            <li>Configure the callback URL in your M-Pesa app: <code>{$_url}callback/mpesa_c2b</code></li>
                            <li>Test with Sandbox environment first</li>
                        </ol>
                    </div>

                    <small class="form-text text-muted">{Lang::T('Set Telegram Bot to get any error and notification')}</small>
                </div>
            </div>

        </div>
    </div>
</form>
{include file="sections/footer.tpl"}