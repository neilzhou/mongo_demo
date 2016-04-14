<div class="modal fade" tabindex="-1" role="dialog" id="addReplSetModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Add Replica Set</h4>
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
    'data-after' => 'callback_after_add_replset',
    'type' => 'post',
    'url' => array(
        'controller' => 'MongoReplSet',
        'action' => 'add'
    )
));
?>
<?php 
/*echo $this->Form->input('rs_name', array(
    'label' => array(
        'text' => 'Name:'
    ),
    //'placeholder' => 'Replica Set Name'
    'placeholder' => 'rs0',
    'value' => 'rs0'
));*/
?>
<legend>Members:</legend>
<div id="sheepItForm">
    <div id="sheepItForm_template">
<?php 
echo $this->Form->input('MongoReplSet.host', array(
        'label' => array(
            'text' => 'IP Address:',
        ),
        'name' => 'MongoReplSet[members][#index#][host]',
        //'placeholder' => 'IP Address'
        'placeholder' => '127.0.0.1',
        'value' => '127.0.0.1'
    ));
    ?>
<?php 
echo $this->Form->input('MongoReplSet.port', array(
        'label' => array(
            'text' => 'Port:'
        ),
        //'placeholder' => 'Port'
        'name' => 'MongoReplSet[members][#index#][port]',
        'placeholder' => '27017',
        'value' => '27017'
    ));
    ?>
    <div class="form-group">
        <div class="col col-md-9 col-md-offset-3">
            <a id="sheepItForm_add" href="javascript:void(0);">
<span class="glyphicon glyphicon-plus" aria-hidden="true"> </span> 
<span class="glyphicon-class">Add member</span>
</a>
            <a id="sheepItForm_remove_current" href="javascript:void(0);" style="color:red;">
<span class="glyphicon glyphicon-remove" aria-hidden="true"> </span> 
<span class="glyphicon-class">Remove</span>
</a>
        </div>
    </div>
    <hr class="col col-md-8 col-md-offset-3">
    </div>
    <div id="sheepItForm_noforms_template">No Found.</div>
</div>
<?php
echo $this->Form->end();
?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary saveReplSetChanges">Save changes</button>
      </div>
    </div>
  </div>
</div>
