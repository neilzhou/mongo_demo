<?php 
echo $this->Form->create('ReplsetInitModal', array(
    'inputDefaults' => array(
        'div' => 'form-group',
        //'label' => array(
            //'class' => 'col col-md-3 control-label'
        //),
        //'wrapInput' => 'col col-md-9',
        'wrapInput' => false,
        'class' => 'form-control'
    ),
    'class' => 'form-horizontal ajax-form',
    'data-type' => 'json',
    'data-after' => 'callback_after_init_replset',
    'data-block-target' => '#initReplSetModal .modal-content',
    'data-id' => $id,
    'type' => 'post',
    'url' => array(
        'controller' => 'ReplsetInitModal',
        'action' => 'add',
        $id
    )
));
?>
<?php echo $this->Form->input('MongoReplSet.host', array(
    'label' => array(
        'text' => 'Choose one member as primary:'
    ),
    //'placeholder' => 'Replica Set Name'
    'options' => $members,
));
?>
<?php
echo $this->Form->end();
?>
