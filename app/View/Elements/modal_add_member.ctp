<div class="modal fade" tabindex="-1" role="dialog" id="addMemberModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Add Member</h4>
      </div>
      <div class="modal-body">
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
    'data-target' => '.rs-panel-group',
    'data-type' => 'json',
    'data-after' => 'callback_after_add_member',
    'type' => 'post',
    'url' => '#void'
));
?>
<div class="form-group">
    <label for="MongoReplSetRsName" class="col col-md-3 control-label">Replset Name:</label>
    <div class="col col-md-9 control-label" style="text-align: left;"><strong><em id="modal_member_rs_name"></em></strong></div>
</div>
<?php echo $this->Form->input('enable_selection', array(
    'label' => array(
        'text' => 'Select one member from Active Timing cluster',
        'class' => null,
    ),
    'type' => 'checkbox',
    'checked' => true,
    'id' => 'enable_selection',
    'wrapInput' => 'col col-md-9 col-md-offset-3',
    'class' => false
)); ?>
<div class="select-member">
<?php 
echo $this->Form->input('MongoReplSet.select_host', array(
    'label' => array(
        'text' => 'Select one member:'
    ),
    'empty' => 'Please select...',
    'options' => $select_options
));
?>
</div>
<div class="type-member" style="display: none;">
<?php echo $this->Form->input('MongoReplSet.host', array(
    'label' => array(
        'text' => 'IP Address:'
    ),
    //'placeholder' => 'IP Address'
    'placeholder' => '127.0.0.1',
    'value' => '127.0.0.1'
));
?>
<?php echo $this->Form->input('MongoReplSet.port', array(
    'label' => array(
        'text' => 'Port:'
    ),
    //'placeholder' => 'Port'
    'placeholder' => '27017',
    'value' => '27017'
));
?>
</div>
<?php
echo $this->Form->end();
?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary saveReplSetChanges">Save changes</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
