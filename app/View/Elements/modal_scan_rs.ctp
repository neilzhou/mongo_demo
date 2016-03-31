<div class="modal fade" tabindex="-1" role="dialog" id="scanReplSetModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Scan Replica Set</h4>
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
    'data-after' => 'callback_after_scan_replset',
    'type' => 'post',
    'url' => '/mongo_repl_set/index'
));
?>
<?php echo $this->Form->input('rs_name', array(
    'label' => array(
        'text' => 'Name:'
    ),
    //'placeholder' => 'Replica Set Name'
    'placeholder' => 'rs0',
    'value' => 'rs0'
));
?>
<?php echo $this->Form->input('MongoReplSet.from_host', array(
    'label' => array(
        'text' => 'From IP Address:'
    ),
    //'placeholder' => 'IP Address'
    'placeholder' => '127.0.0.1',
    'value' => '10.107.122.180'
));
?>
<?php echo $this->Form->input('MongoReplSet.to_host', array(
    'label' => array(
        'text' => 'To IP Address:'
    ),
    //'placeholder' => 'IP Address'
    'placeholder' => '127.0.0.1',
    'value' => '10.107.122.186'
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
<?php
echo $this->Form->end();
?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary saveReplSetChanges">Scan</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
