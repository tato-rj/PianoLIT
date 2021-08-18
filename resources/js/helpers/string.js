jQuery.fn.match = function(element) {
  let pos = element.parent().position().left;
  let width = element.outerWidth();

  return this.css({
    width: width,
    left: pos+'px'
  }).empty();
};

objFirst = function(obj) {
  return obj[Object.keys(obj)[0]];
}

searchable = function(string) {
  return string.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
};

fullDatePT = function($element)
{
	$element.text(
		moment(
			$element.attr('data-date')
			).locale('pt').format("D [de] MMMM [de] YYYY")
		);
}

formatBytes = function(bytes,decimals) {
   if(bytes == 0) return '0 Bytes';
   var k = 1024,
       dm = decimals || 2,
       sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
       i = Math.floor(Math.log(bytes) / Math.log(k));
   return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

ucfirst = function(str) {
  if (typeof str !== 'string') return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}