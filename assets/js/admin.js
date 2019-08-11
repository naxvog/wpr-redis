(function($){$(function(){
  var evt = 'click.wpr_redis',
      elm = '.notice-dismiss';
  var evt_trigger = function(){
    var type = $( this ).closest( '.notice-wpr-redis' ).data( 'id' );
    $.ajax(
      ajaxurl,
      {
        type: 'POST',
        data: {
          action: 'wpr_redis_notice_handler',
          id: type,
        }
      }
    );
  };
  $( document ).off( evt, elm ).on( evt, elm, evt_trigger );
})})(jQuery);