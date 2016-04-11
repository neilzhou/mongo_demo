<div class="modal fade" tabindex="0" role="dialog" id="clearDataModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h5 class="modal-title">Clear data</h4>
      </div>
      <div class="modal-body">
<?php 
echo $this->Form->create('MongoReplSet', array(
    'inputDefaults' => array(
        'div' => 'form-group',
        'label' => array(
            'class' => 'col col-md-2 control-label'
        ),
        'wrapInput' => 'col col-md-8',
        'class' => 'form-control'
    ),
    'class' => 'form-horizontal ajax-form',
    'data-target' => '.rs-panel-group',
    'data-type' => 'json',
    'data-after' => 'callback_after_clear_data',
    'type' => 'post',
    'url' => '#void'
));
?>
<div class="form-group">
<p>Are you sure you want to clear the mongodb data?</p>
    <label for="MongoHost" class="col col-md-3 control-label">IP Address:</label>
    <div class="col col-md-9 control-label" style="text-align: left;"><strong><em id="modal_clear_data_host"></em></strong></div>
</div>
<div class="form-group">
    <label for="MongoPort" class="col col-md-3 control-label">Port:</label>
    <div class="col col-md-9 control-label" style="text-align: left;"><strong><em id="modal_clear_data_port"></em></strong></div>
</div>
<?php echo $this->Form->hidden('MongoReplSet.host', array(
    'value' => '127.0.0.1',
    'id' => "clear_data_host"

));
?>
<?php echo $this->Form->hidden('MongoReplSet.port', array(
    'value' => '27017',
    'id' => "clear_data_port"
));
?>
<?php
echo $this->Form->end();
?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary saveReplSetChanges">Clear</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
