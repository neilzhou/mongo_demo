var Utils = {
  /**
   *  @brief get the html template for bootstrap alert 
   *  @author Neil.zhou created at 20140722
   *  @param [in] msg html message.
   *  @param [in] type success|info|warning|danger, default to success
   *  @return html
   *  
   */
  alert: {
    html: function(msg, type){
      var type = type ? type : 'success';
      return '<div class="alert alert-'+type+' alert-dismissible fade in" role="alert">'
              + '<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>'
              + msg
              + '</div>'
    }
  },
  /**
   *  @brief add verify status for form input|checkbox...
   *  @author Neil.zhou created at 20140722
   *  @param [object] parent, wrap obj of verify status, if null, then use source's parent
   *  @param [object] object where put the message after, eg. input|span.help-block|checkbox...
   *  @param [string] type success|warning|error
   *  @param [string] helpMsg html for error notice message.
   *  @return void
   */
  formVerifyStatus: {
    add: function(parent, source, type, helpMsg){
      type = type ? type : 'success';
      var statusClass = 'has-' + type;
      var feedbackClass = 'has-feedback';
      var $parent = parent ? $(parent) : $(source).parent();
      $parent.removeClass('has-success has-error has-warning has-feedback')
        .addClass(statusClass).addClass(feedbackClass);
      
      var glyphicon = 'ok';
      switch (type) {
        case 'warning' :
          glyphicon = 'warning-sign';
          break;
        case 'error':
          glyphicon = 'remove';
          break;
      }
      
      if(helpMsg){
        if($('.verify-help', $parent).length) {
          $('.verify-help', $parent).html(helpMsg);
          $('.verify-help', $parent).show();
        } else {
          $(source).after('<span class="help-block verify-help">'+helpMsg+'</span>');
        }
      } else {
        $('.verify-help', $parent).hide();
      }
      
      if ($('.form-control-feedback', $parent).length) {
        // do nothing
        $('.form-control-feedback', $parent).removeClass().addClass('glyphicon glyphicon-'+glyphicon+' form-control-feedback');
      } else {
        $(source).after('<span class="glyphicon glyphicon-'+glyphicon+' form-control-feedback"></span>');
      }
    },
    /**
     *  @brief remove verify status for form input|checkbox...
     *  @author Neil.zhou created at 20140722
     *  @param [object] parent, wrap obj of verify status, if null, then use source's parent
     *  @param [object] object where put the message after, eg. input|span.help-block|checkbox...
     */
    remove: function (parent, source){
      var $parent = parent ? $(parent) : $(source).parent();
      $parent.removeClass('has-success has-warning has-error has-feedback');
      $('.verify-help', $parent).remove();
      $('.form-control-feedback', $parent).remove();
    }
  }
};