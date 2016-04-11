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
foreach ($replset['MongoReplSet']['members'] as $key=>$member) :
echo $this->Form->hidden("MongoReplSet.Members.$key._id", array('value' => empty($member['_id']) ? 0 : $member['_id']));
echo $this->Form->input("MongoReplSet.Members.$key.host", array(
    'label' => array(
        'text' => 'IP Address:'
    ),
    //'placeholder' => 'IP Address'
    'placeholder' => '127.0.0.1',
    'value' => $member['host']
));
?>
<?php echo $this->Form->input("MongoReplSet.Members.$key.port", array(
    'label' => array(
        'text' => 'Port:'
    ),
    //'placeholder' => 'Port'
    'placeholder' => '27017',
    'value' => $member['port']
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
			'class' => false
		)); ?>
<?php
echo $this->Form->end();
?>
