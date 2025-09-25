{include file="sections/header.tpl"}

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <div class="btn-group pull-right">
                    <a class="btn btn-success btn-xs" title="preview" href="{$base_url}/system/hotspot_login.php" target="_blank">
                        <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span> preview
                    </a>
                    <a class="btn btn-primary btn-xs" title="save" href="{Text::url('services/sync/hotspot')}"
                        onclick="return ask(this, 'This will sync/send hotspot package to Mikrotik?')"><span
                            class="glyphicon glyphicon-refresh" aria-hidden="true"></span> sync</a>
                </div>{Lang::T('Hotspot Plans')}
            </div>
            <form id="site-search" method="post" action="{Text::url('services/hotspot/')}">
                <div class="panel-body">
                    <div class="row row-no-gutters" style="padding: 5px">
                        <div class="col-lg-2">
                            <div class="input-group">
                                <div class="input-group-btn">
                                    <a class="btn btn-danger" title="Clear Search Query"
                                        href="{Text::url('services/hotspot/')}"><span
                                            class="glyphicon glyphicon-remove-circle"></span></a>
                                </div>
                                <input type="text" name="name" class="form-control"
                                    placeholder="{Lang::T('Search by Name')}...">
                            </div>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="type1" name="type1">
                                <option value="">{Lang::T('Prepaid')} &amp; {Lang::T('Postpaid')}</option>
                                <option value="yes" {if $type1 eq 'yes' }selected{/if}>{Lang::T('Prepaid')}</option>
                                <option value="no" {if $type1 eq 'no' }selected{/if}>{Lang::T('Postpaid')}</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="type2" name="type2">
                                <option value="">{Lang::T('Type')}</option>
                                {foreach $type2s as $t}
                                    <option value="{$t}" {if $type2 eq $t }selected{/if}>{Lang::T($t)}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="bandwidth" name="bandwidth">
                                <option value="">Bandwidth</option>
                                {foreach $bws as $b}
                                    <option value="{$b['id']}" {if $bandwidth eq $b['id'] }selected{/if}>
                                        {$b['name_bw']}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="type3" name="type3">
                                <option value="">{Lang::T('Category')}</option>
                                {foreach $type3s as $t}
                                    <option value="{$t}" {if $type3 eq $t }selected{/if}>{$t}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="valid" name="valid">
                                <option value="">{Lang::T('Validity')}</option>
                                {foreach $valids as $v}
                                    <option value="{$v}" {if $valid eq $v }selected{/if}>{$v}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="router" name="router">
                                <option value="">{Lang::T('Location')}</option>
                                {foreach $routers as $r}
                                    <option value="{$r}" {if $router eq $r }selected{/if}>{$r}</option>
                                {/foreach}
                                <option value="radius" {if $router eq 'radius' }selected{/if}>Radius</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="device" name="device">
                                <option value="">{Lang::T('Device')}</option>
                                {foreach $devices as $r}
                                    <option value="{$r}" {if $device eq $r }selected{/if}>{$r}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <select class="form-control" id="status" name="status">
                                <option value="-">{Lang::T('Status')}</option>
                                <option value="1" {if $status eq '1' }selected{/if}>{Lang::T('Active')}</option>
                                <option value="0" {if $status eq '0' }selected{/if}>{Lang::T('Not Active')}</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-xs-8">
                            <button class="btn btn-success btn-block" type="submit"><span
                                    class="fa fa-search"></span></button>
                        </div>
                        <div class="col-lg-1 col-xs-4">
                            <a href="{Text::url('services/add')}" class="btn btn-primary btn-block"
                                title="{Lang::T('New Service Package')}"><i class="ion ion-android-add"></i></a>
                        </div>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <div style="margin-left: 5px; margin-right: 5px;">
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                                <th></th>
                                <th colspan="5" class="text-center">{Lang::T('Internet Package')}</th>
                                <th colspan="2" class="text-center" style="background-color: rgb(246, 244, 244);">
                                    {Lang::T('Limit')}</th>
                                <th colspan="2"></th>
                                <th colspan="2" class="text-center" style="background-color: rgb(243, 241, 172);">
                                    {Lang::T('Expired')}</th>
                                <th colspan="3"></th>
                            </tr>
                            <tr>
                                <th>{Lang::T('Name')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th><a href="{Text::url('bandwidth/list')}">Bandwidth</a></th>
                                <th>{Lang::T('Category')}</th>
                                <th>{Lang::T('Price')}</th>
                                <th>{Lang::T('Validity')}</th>
                                <th style="background-color: rgb(246, 244, 244);">{Lang::T('Time')}</th>
                                <th style="background-color: rgb(246, 244, 244);">{Lang::T('Data')}</th>
                                <th><a href="{Text::url('routers/list')}">{Lang::T('Location')}</a></th>
                                <th>{Lang::T('Device')}</th>
                                <th style="background-color: rgb(243, 241, 172);">{Lang::T('Internet Package')}</th>
                                <th style="background-color: rgb(243, 241, 172);">{Lang::T('Date')}</th>
                                <th>{Lang::T('ID')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $d as $ds}
                                <tr {if $ds['enabled'] !=1}class="danger" title="disabled" {elseif $ds['prepaid'] !='yes'
                                            }class="warning" title="Postpaid" {/if}>
                                    <td class="headcol">{$ds['name_plan']}</td>
                                    <td>{if $ds['prepaid'] == no}<b>Postpaid</b>{else}Prepaid{/if} {$ds['plan_type']}</td>
                                    <td>{$ds['name_bw']}</td>
                                    <td>{$ds['typebp']}</td>
                                    <td>{Lang::moneyFormat($ds['price'])}
                                        {if !empty($ds['price_old'])}
                                            <sup
                                                style="text-decoration: line-through; color: red">{Lang::moneyFormat($ds['price_old'])}</sup>
                                        {/if}
                                    </td>
                                    <td>{$ds['validity']} {$ds['validity_unit']}</td>
                                    <td>{$ds['time_limit']} {$ds['time_unit']}</td>
                                    <td>{$ds['data_limit']} {$ds['data_unit']}</td>
                                    <td>
                                        {if $ds['is_radius']}
                                            <span class="label label-primary">RADIUS</span>
                                        {else}
                                            {if $ds['routers']!=''}
                                                <a href="{Text::url('routers/edit/0&name=')}{$ds['routers']}">{$ds['routers']}</a>
                                            {/if}
                                        {/if}
                                    </td>
                                    <td>{$ds['device']}</td>
                                    <td>{if $ds['plan_expired']}<a
                                            href="{Text::url('services/edit/')}{$ds['plan_expired']}">{Lang::T('Yes')}</a>{else}{Lang::T('No')}
                                        {/if}</td>
                                    <td>{if $ds['prepaid'] == no}{$ds['expired_date']}{/if}</td>
                                    <td>{$ds['id']}</td>
                                    <td>
                                        <a href="{Text::url('services/edit/')}{$ds['id']}"
                                            class="btn btn-info btn-xs">{Lang::T('Edit')}</a>
                                        <a href="{Text::url('services/delete/')}{$ds['id']}" id="{$ds['id']}"
                                            onclick="return ask(this, '{Lang::T('Delete')}?')"
                                            class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-trash"></i></a>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="panel-footer">
                {include file="pagination.tpl"}
                <div class="bs-callout bs-callout-info" id="callout-navbar-role">
                    <h4>{Lang::T('Create expired Internet Package')}</h4>
                    <p>{Lang::T('When customer expired, you can move it to Expired Internet Package')}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}