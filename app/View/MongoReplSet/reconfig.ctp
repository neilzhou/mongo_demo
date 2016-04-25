<?php 
echo $this->Form->create('MongoReplSet', array(
    'inputDefaults' => array(
        'div' => 'form-group',
        'label' => array(
            'class' => 'col col-md-3 control-label'
        ),
        'wrapInput' => 'col col-md-9',
        'class' => 'form-control'
    ),
    'class' => 'form-horizontal ajax-form',
    'data-target' => '#reconfigReplsetModal .modal.body',
    'data-type' => 'json',
    'data-after' => 'callback_after_reconfig_replset',
    'data-id' => $replset['MongoReplSet']['_id'],
    'type' => 'post',
    'url' => array(
        'controller' => 'MongoReplSet',
        'action' => 'reconfig',
        $replset['MongoReplSet']['_id']
    )
));
?>
<div class="form-group">
    <label for="MongoReplSetRsName" class="col col-md-3 control-label">Replset Name:</label>
    <div class="col col-md-9 control-label" style="text-align: left;"><strong><em id="modal_member_rs_name"><?php echo $replset['MongoReplSet']['rs_name']; ?></em></strong></div>
</div>
<legend>Members:</legend>
<?php 
$is_force = false;
foreach ($replset['MongoReplSet']['members'] as $key=>$member) :

    $changed_ip = empty($member['changed_ip']) ? '' : $member['changed_ip'];
    $changed_port = empty($member['changed_port']) ? '' : $member['changed_port'];
    $is_changed = $changed_ip;
    $is_force = !$replset_status;//$is_force ? $is_force : $is_changed;
echo $this->Form->hidden("MongoReplSet.Members.$key._id", array('value' => empty($member['_id']) ? 0 : $member['_id']));
    if($is_changed) echo "<p class='text-center text-warning'>The mongo instance has been changed from {$member['host']}:{$member['port']} to $changed_ip:$changed_port</p>";

echo $this->Form->input("MongoReplSet.Members.$key.host", array(
    'label' => array(
        'text' => 'IP Address:'
    ),
    'div' => $is_changed ? 'form-group has-warning' : 'form-group',
    //'placeholder' => 'IP Address'
    'placeholder' => '127.0.0.1',
    'value' => $is_changed ? $changed_ip : $member['host']
));
?>
<?php echo $this->Form->input("MongoReplSet.Members.$key.port", array(
    'label' => array(
        'text' => 'Port:'
    ),
    //'placeholder' => 'Port'
    'div' => $is_changed ? 'form-group has-warning' : 'form-group',
    'placeholder' => '27017',
    'value' => $is_changed ? $changed_port : $member['port']
));
?>
<hr class="col col-md-8 col-md-offset-3">
<?php
endforeach;
?>
<?php echo $this->Form->input('force', array(
    'label' => array(
        'text' => 'Forcing the reconfiguration',
        'class' => null,
    ),
            'type' => 'checkbox',
            'wrapInput' => 'col col-md-9 col-md-offset-3',
            'afterInput' => '<span class="help-block"><small>The force option forces a new configuration onto the member. Use this procedure only to recover from catastrophic interruptions.</small></span>',
            'checked' => $is_force,
			'class' => false
		)); ?>
<?php
echo $this->Form->end();
?>
