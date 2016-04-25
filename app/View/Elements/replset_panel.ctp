<?php
$panel_class = 'default';
$init_replset = empty($replset_status['init_replset']) ? false : true;
$rs_name = empty($replset_status['rs_name']) ? '': $replset_status['rs_name'];
$rs_members = empty($replset_status['data']) ? array(): $replset_status['data'];

if ($rs_members) {
    if (!$replset_status['success']) {
        $panel_class = $init_replset ? 'warning' : 'danger';
        $error_message = $init_replset ? 'Does not have a vallid replica set config. please click Init ReplSet Button to send replica set config to members.' : $replset_status['message'];
?>
<div class="alert alert-<?php echo $panel_class; ?>" role="alsert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
<strong><?php echo ucfirst($panel_class); ?>!</strong> <?php echo $error_message; ?>
</div>
<?php
    }
?>
<div class="panel panel-<?php echo $panel_class; ?>" data-id="<?php echo $replset_status['id']; ?>" data-rs-name="<?php echo $rs_name; ?>">
 <div class="panel-heading">ReplSetName: <?php echo $rs_name; ?>

<button type="button" class="close remove-replset"><span >&times;</span></button>
 </div>
     <div class="panel-body">
        <button class="btn btn-primary pull-right add-member">Add Member</button>
        <button class="btn btn-primary pull-right reconfig-replset" data-after="callback_after_load_modal_reconfig" data-target="#reconfigReplsetModal .modal-body" data-block-target=".panel[data-id='<?php echo $replset_status['id']; ?>']" style="margin-right: 10px;">Reconfig Replia Set</button>
<?php 
    if($init_replset && empty($replset_status['success'])):
?>
<button class="btn btn-primary pull-right init-replset ajax-link" data-target=".init-replset-body" data-block-target=".panel-wrapper[data-id='<?php echo $replset_status['id']; ?>']" href="/replset_init_modal/view/<?php echo $replset_status['id']; ?>"  data-after="callback_after_init_replset_modal" style="margin-right: 10px;">Init ReplSet</button>
<?php
endif;
?>
        <div class="clearfix"></div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>IP Address</th>
                    <th>Member Status</th>
                    <th>Connect Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
<?php 
$index = 1;
App::uses('MongoReplsetStatus', 'Lib');
foreach($rs_members as $m):
    $tr_class = empty($m['success']) ? (!empty($m['code']) && MongoReplsetStatus::canBeInit($m['code']) ? 'warning' : 'danger') : '';
?>
            <tr class="<?php echo $tr_class; ?>">
                <th scope="row"><?php echo $index;?></th>
                <td class="ip-port-td"><?php echo $m['host']. ':' . $m['port']; ?></td>
                <td><?php 
if($m['success']):
    echo $m['rs_status']; 
else:
?>
    <abbr data-toggle="tooltip" data-placement="bottom" title="<?php echo $m['message']; ?>"><?php echo $m['rs_status']; ?></abbr>
<?php
endif;
?></td>
                <td><?php echo $m['conn_status']; ?></td>
                    <td>
                        <!-- <button class="btn btn-primary">Set Primary</button> -->
<?php if(strcasecmp($m['rs_status'], 'primary')) : ?>
                        <button class="btn btn-danger remove-member">Remove</button>
<?php endif; ?>
<?php if (strcasecmp($m['conn_status'], 'failed') || strcasecmp($m['rs_status'], 'unknown')) {
?>

                        <button class="btn btn-danger clear-data">Clear data</button>
<?php
}
?>
</td>
                </tr>
<?php
$index ++;
endforeach;
?>
            </tbody>
        </table>
     </div>
 </div>
<?php
}
?>
