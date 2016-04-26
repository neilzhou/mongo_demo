$(document).ready(function(){
    $('.saveReplSetChanges').on('click', function(e){
        var $modal = $(this).closest('.modal');
        $('form', $modal).submit();
    });

    $('[data-toggle="tooltip"]').tooltip();

    $('#enable_selection').on('change', function(e){
        if($(this).is(":checked")) {
            $('#addMemberModal .select-member').show();
            $('#addMemberModal .type-member').hide();
        } else {
            $('#addMemberModal .select-member').hide();
            $('#addMemberModal .type-member').show();
        }
    });

    $('body').on('click', 'button.clear-data', function(e){
        var $this = $(this);
        var $panel = $this.closest('.panel');
        var $td = $this.closest('tr');
        var id = $panel.data('id');

        var rs_name = $panel.data('rsName');
        var ip_port = $('.ip-port-td', $td).text();
        var ip_port_array = ip_port.split(':');
        var ip = $.trim(ip_port_array[0]);
        var port = $.trim(ip_port_array[1]);

        $('#clearDataModal form').attr('action', '/mongo_repl_set/clear_data/' + id);
        $('#clearDataModal form').data('id', id);
        $('#clearDataModal #modal_clear_data_host').html(ip);
        $('#clearDataModal #modal_clear_data_port').html(port);
        $('#clearDataModal #clear_data_host').val(ip);
        $('#clearDataModal #clear_data_port').val(port);
        $('#clearDataModal').modal('show');
    });

    $('body').on('click', 'button.remove-replset', function(e){
        var $this = $(this);
        var $panel = $this.closest('.panel');
        var id = $panel.data('id');
        var rs_name = $panel.data('rsName');

        $('#removeRsModal form').attr('action', '/mongo_repl_set/delete/' + id);
        $('#removeRsModal form').data('id', id);
        $('#removeRsModal #modal_member_rs_name').html(rs_name);
        $('#removeRsModal').modal('show');
    });

    $('body').on('click', '#MongoReplSetScanLan', function(e){
        var $this = $(this);
        if (this.checked) {
            $('#ScanFromHost').attr('disabled', true);
            $('#ScanToHost').attr('disabled', true);
            $('#ScanPort').attr('disabled', true);
        } else {
            $('#ScanFromHost').attr('disabled', false);
            $('#ScanToHost').attr('disabled', false);
            $('#ScanPort').attr('disabled', false);
        }
    });

    var initSheepIt = false;
    $('#addReplSetModal').on('show.bs.modal', function(e){
        if (initSheepIt == false) {
            initSheepIt = true;
            $('#addReplSetModal #sheepItForm').sheepIt({
                separator: '',
                allowRemoveLast: false,
                allowRemoveCurrent: true, 
                allowAdd: true,
                minFormsCount: 1,
                maxFormsCount: 7,
                iniFormsCount: 1
            });
        }
    });

    $('body').on('rs-panel-group:ajax-load', '.rs-panel-group', function(e){
        var $this = $(this);
        var url = '/mongo_repl_set/index';

        ajaxRequestForObj(this, url);
    });
    $('body').on('rs-member:ajax-load', '.panel-wrapper', function(e){
        var $this = $(this);
        var id = $this.data('id');
        var url = '/mongo_repl_set/view/' + id;

        ajaxRequestForObj(this, url);
    });

    $('body').on('click', '.reconfig-replset', function(e){
    
        var $this = $(this);
        var $panel = $this.closest('.panel');
        var id = $panel.data('id');

        var url = '/mongo_repl_set/reconfig/' + id;
        ajaxRequestForObj(this, url);
    });

    $('body').on('click', '.remove-member', function(e){
        var $this = $(this);
        var $panel = $this.closest('.panel');
        var $td = $this.closest('tr');
        var id = $panel.data('id');

        var rs_name = $panel.data('rsName');
        var ip_port = $('.ip-port-td', $td).text();
        var ip_port_array = ip_port.split(':');
        var ip = $.trim(ip_port_array[0]);
        var port = $.trim(ip_port_array[1]);

        $('#removeMemberModal #modal_member_rs_name').html(rs_name);
        $('#removeMemberModal #modal_member_ip').html(ip);
        $('#removeMemberModal #modal_member_port').html(port);
        $('#removeMemberModal #removeHost').val(ip);
        $('#removeMemberModal #removePort').val(port);
        $('#removeMemberModal form').attr('action', '/replset_member/delete/'+id);
        $('#removeMemberModal form').data('id', id);
        $('#removeMemberModal').modal('show');
    });
    $('body').on('click', '.add-member', function(e){
        var $this = $(this);
        var $panel = $this.closest('.panel');
        var id = $panel.data('id');
        var rs_name = $panel.data('rsName');

        $('#addMemberModal #modal_member_rs_name').html(rs_name);
        $('#addMemberModal form').attr('action', '/replset_member/add/'+id);
        $('#addMemberModal form').data('id', id);
        $('#addMemberModal').modal('show');
    });
});

function callback_after_scan_replset(form, target, resp) {

    $('.alert', $(form)).remove();
    var $firstGroup = $(form).find('.form-group').first();
    if (resp.json.success) {
        $('#scanReplSetModal').modal('hide');
        $('.rs-panel-group').trigger('rs-panel-group:ajax-load');
    } else {
        $firstGroup.before(Utils.alert.html(resp.json.message, 'danger'));
    }
}

function callback_after_load_replset(obj, target, resp) {
    $('[data-toggle="tooltip"]', $(obj)).tooltip();
}

function callback_after_add_replset(form, target, resp){

    $('.alert', $(form)).remove();
    var $firstGroup = $(form).find('.form-group').first();
    if (resp.json.success) {
        var id = $(form).data('id');
        $('#addReplSetModal').modal('hide')
        $('.rs-panel-group').trigger('rs-panel-group:ajax-load');
    } else {
        $firstGroup.before(Utils.alert.html(resp.json.message, 'danger'));
    }
}

function callback_after_init_replset_modal(form, target, resp){
    var $initReplsetModal = $(target).closest('.modal');
    $initReplsetModal.modal('show');

}

function callback_after_init_replset(form, target, resp) {
    $('.alert', $(form)).remove();
    var $firstGroup = $(form).find('.form-group').first();
    if (resp.json.success) {
        var id = $(form).data('id');
        $('#initReplSetModal').modal('hide');
        $('.panel-wrapper[data-id="'+id+'"]').trigger('rs-member:ajax-load');
    } else {
        $firstGroup.before(Utils.alert.html(resp.json.message, 'danger'));
    }
}

function callback_after_add_member(form, target, resp) {
    $('.alert', $(form)).remove();
    var $firstGroup = $(form).find('.form-group').first();
    if (resp.json.success) {
        $firstGroup.before(Utils.alert.html(resp.json.message));
        var id = $(form).data('id');
        $('#addMemberModal').modal('hide');
        $('#removeMemberModal').modal('hide');
        $('.panel-wrapper[data-id="'+id+'"]').trigger('rs-member:ajax-load');
    } else {
        $firstGroup.before(Utils.alert.html(resp.json.message, 'danger'));
    }
}

function callback_after_remove_rs(form, target, resp) {
    $('#alert').html('');
    if (resp.json.success) {
        $('#alert').html(Utils.alert.html(resp.json.message));
    } else {
        $('#alert').html(Utils.alert.html(resp.json.message, 'danger'));
    }
    var $form = $(form);
    var id = $form.data('id');
    $('#removeRsModal').modal('hide');
    $('.panel-wrapper[data-id="'+id+'"]').remove();
}

function callback_after_load_modal_reconfig(form, target, resp) {
    $('#reconfigReplsetModal').modal('show');
}

function callback_after_reconfig_replset(form, target, resp) {
    $('.alert', $(form)).remove();
    var $firstGroup = $(form).find('.form-group').first();
    if (resp.json.success) {
        $firstGroup.before(Utils.alert.html(resp.json.message));
        var id = $(form).data('id');
        $('#reconfigReplsetModal').modal('hide');
        $('.panel-wrapper[data-id="'+id+'"]').trigger('rs-member:ajax-load');
    } else {
        $firstGroup.before(Utils.alert.html(resp.json.message, 'danger'));
    }
}

function callback_after_clear_data(form, target, resp) {
    $('.alert', $(form)).remove();
    var $firstGroup = $(form).find('.form-group').first();
    if (resp.json.success) {
        $firstGroup.before(Utils.alert.html(resp.json.message));
        var id = $(form).data('id');
        $('#clearDataModal').modal('hide');
        $('.panel-wrapper[data-id="'+id+'"]').trigger('rs-member:ajax-load');
    } else {
        $firstGroup.before(Utils.alert.html(resp.json.message, 'danger'));
    }
}
