getCookie = function(name) {
    var prefix = name + '=';
    var cookies = document.cookie.split(';');
    for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim();
        if (cookie.indexOf(prefix) === 0) {
            var value = cookie.substring(prefix.length);
            try {
                return decodeURIComponent(value);
            } catch (error) {
                return value;
            }
        }
    }
    return null;
}

setCookie = function(cname, cvalue, exdays) {
  var expires = '';
  
  if (exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    expires = ';expires='+d.toUTCString();
  }
  
  document.cookie = cname + '=' + encodeURIComponent(cvalue) + expires;
}
