<style>
/*.rs-action-group: {text-align: right;}*/
</style>
<section id="alert"></section>
<div class="rs-action-group">
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addReplSetModal">Add Replica Set</button>
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scanReplSetModal">Scan Replica Set in LAN</button>
</div>
<p></p>
<div class="rs-panel-group" data-after="callback_after_load_replset">
<?php
if (!empty($rs_list)) {
    // code...
    foreach ($rs_list as $rs) {
        echo $this->element('../MongoReplSet/add', array('replset_status' => $rs));
    }
}
?>
</div>
<!-- /sheepIt Form -->
 <!-- <div class="panel panel-default">
     <div class="panel-heading">ReplSetName: rs0</div>
     <div class="panel-body">
        <button class="btn btn-primary pull-right">Add Member</button>
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
                <tr>
                    <th scope="row">1</th>
                    <td>10.1.10.102</td>
                    <td>Primary</td>
                    <td>Success</td>
                    <td>
                        <button class="btn btn-primary">Set Primary</button>
                        <button class="btn btn-danger">Remove</button>
</td>
                </tr>
            </tbody>
        </table>
     </div>
 </div> -->
<?php echo $this->element('modal_add_replset');?>
<?php echo $this->element('modal_init_replset');?>
<?php echo $this->element('modal_add_member');?>
<?php echo $this->element('modal_remove_member');?>
<?php echo $this->element('modal_scan_rs');?>
<?php echo $this->element('modal_remove_replset');?>
<?php echo $this->element('modal_reconfig_replset');?>
<?php echo $this->element('modal_clear_data');?>
<!-- /.modal -->

<?php
$this->Html->script("common", array("block" => 'script'));
$this->Html->script("jquery.ajax-form", array("block" => 'script'));
$this->Html->script("jquery.blockUI", array("block" => 'script'));
$this->Html->script("jquery.sheepItPlugin-1.1.1", array("block" => 'script'));
$this->Html->script("mongo_replset", array("block" => 'script'));
?>
