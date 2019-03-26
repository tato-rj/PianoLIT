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

jQuery.fn.cleanVal = function() {
	return this.val().replace(/\D/g,'');
};