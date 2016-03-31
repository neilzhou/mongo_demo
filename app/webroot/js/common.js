/*
 * replace eval function string to this function. because we use goole compress js file, which will cause eval function error.
 * eg. eval(callback+"(param, obj)") = callEvalFun(callback)(param, obj)
 * 20130325: created by neil.
 * @return Function
*/
function callEvalFun ( string ){
  if (typeof string == 'undefined') {
    return false;
  }
  
  var fun_array = string.split(".");
  var fun_obj = window;
  for(var i = 0; i <fun_array.length; i ++ ){
    fun_obj = fun_obj[fun_array[i]];
  }
  
  if (typeof fun_obj != 'function') {
    return false;
  }
  
  return fun_obj;
}

$(document).ready(function(){
  // Fix Window Phone 8 width bug of bootstrap. see http://v3.bootcss.com/getting-started/#disable-responsive
  if (navigator.userAgent.match(/IEMobile\/10\.0/)) {
    var msViewportStyle = document.createElement("style")
    msViewportStyle.appendChild(
      document.createTextNode(
        "@-ms-viewport{width:auto!important}"
      )
    )
    document.getElementsByTagName("head")[0].appendChild(msViewportStyle)
  }
});