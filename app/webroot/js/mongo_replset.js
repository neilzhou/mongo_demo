$(document).ready(function(){
    $('.saveReplSetChanges').on('click', function(e){
        var $modal = $(this).closest('.modal');
        $('form', $modal).submit();
    });

    $('[data-toggle="tooltip"]').tooltip();

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

function callback_after_scan_replset(obj, target, resp) {

    $('#scanReplSetModal').modal('hide');
    $('.rs-panel-group').trigger('rs-panel-group:ajax-load');
}

function callback_after_load_replset(obj, target, resp) {
    $('[data-toggle="tooltip"]', $(obj)).tooltip();
}

function callback_after_add_replset(form, target, resp){
    $('#addReplSetModal').modal('hide')
    var $target = $(target);
    $target.append(resp);
    var $lastPanel = $target.find('.panel').last();
    if($lastPanel.length){
        $('[data-toggle="tooltip"]', $lastPanel).tooltip();
    } else {
        $('[data-toggle="tooltip"]', $target).tooltip();
    }
}

function callback_after_init_replset_modal(form, target, resp){
    var $initReplsetModal = $(target).closest('.modal');
    $initReplsetModal.modal('show');
}

function callback_after_init_replset(form, target, resp) {
    $('#alert').html('');
    if (resp.json.success) {
        $('#alert').html(Utils.alert.html(resp.json.message));
    } else {
        $('#alert').html(Utils.alert.html(resp.json.message, 'danger'));
    }
    var id = $(form).data('id');
    $('#initReplSetModal').modal('hide');
    $('.panel-wrapper[data-id="'+id+'"]').trigger('rs-member:ajax-load');
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
