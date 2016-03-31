$(function() {
  $('body').on('submit', 'form.ajax-form', function(event) {
    var $form = $(this);
    return ajaxRequestForObj(this, $form.attr('action'), $form.serialize());
  });

  $("body").on("submit", "form[data-sync]", function(event) {
    var $form = $(this);
    if ($form.attr('pending') === 'true') {
      event.preventDefault();
      return false;
    }
    
     $form.block();
    // handle before callback
    var before_callback = callEvalFun($form.attr('data-before'));
    if (before_callback !== false && typeof before_callback !== 'undefined') {
      var status = before_callback(this);
      if (status === false) {
        event.preventDefault();
        $form.unblock();
        return false;
      }else if(-1 === status){
        //return directly, jump the codes
        return false;
      }
    }
    $form.attr('pending', 'true');
    if($form.attr('target') && $("#"+$form.attr('target')).attr('action')){
      $form.attr('action', $("#"+$form.attr('target')).attr('action'));
    }
    $form.submit();
  });

  $('body').on('click', '.ajax-link', function(event) {
    event.preventDefault();
    ajaxRequestForObj(this, $(this).attr('href'));
    return false;
  });
});

function ajaxRequestForObj(obj, url, params) {
    var $obj = $(obj);
    params = params ? params : '';

    if ($obj.attr('pending') == 'true') {
      return false;
    }
    // used for ajax send message histories.
    var showBlock = $obj.data('block') ? $obj.data('block') : true;
    var $blockTarget = $obj.data('blockTarget') ? $($obj.data('blockTarget')) : false;
    if (!$blockTarget || $blockTarget.length == 0) {
        $blockTarget = $obj;
    }
    // show loading overlay
    showBlock && $blockTarget.block();
    if ($obj.closest($obj.attr('data-target')).length) {
      var $target = $obj.closest($obj.attr('data-target'));
    } else {
      var $target = $($obj.attr('data-target'));
    }

    if ($target.length == 0) {
      $target = $obj;
    }
    // handle before callback
    var before_callback = callEvalFun($obj.attr('data-before'));
    if (before_callback != false && typeof before_callback != 'undefined') {
      var status = before_callback(this, $target[0]);
      if (status == false) {
        // show loading overlay
        showBlock && $blockTarget.unblock();
        return false;
      }
    }
    $obj.attr('pending', 'true');

    var dataType = typeof $obj.attr('data-type') == 'undefined' ? 'html' : $obj.attr('data-type');
    var isReplaceHtml = $obj.attr('data-replace') != '0' && $obj.attr('data-replace') != 'false';

    $.ajax({
      type: $obj.attr('method'),
      url: url,
      data: params,
      dataType: dataType,
      success: function(data, status) {
        // add to target if is html.
        if ($target.length && dataType == 'html' && isReplaceHtml) {
          $target.html(data);
        }

        // handle after callback
        var after_callback = callEvalFun($obj.attr('data-after'));
        if (after_callback != false && typeof after_callback != 'undefined') {
          after_callback($obj[0], $target[0], data);
        }
      },
      complete: function(xhr, status) {
        $obj.attr('pending', 'false');
        showBlock && $blockTarget.unblock();
      }
    });
    
    // handle end of function
    var end_callback = callEvalFun($obj.attr('data-end'));
    if(end_callback != false && typeof end_callback != 'undefined'){
      end_callback($obj[0], $target[0]);
    }
        
    return false;
}
