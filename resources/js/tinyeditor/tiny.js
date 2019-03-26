  tinymce.init({
    selector: 'textarea#editor',
    plugins: 'advcode formatpainter linkchecker lists link image media mediaembed pageembed permanentpen powerpaste tinydrive tinymcespellchecker',
    toolbar: 'formatselect | bold italic strikethrough forecolor backcolor formatpainter | link image media pageembed | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent',
    image_advtab: true,
    images_upload_url: '/admin/blog/images/upload',
    images_upload_handler: function(blobInfo, success, failure) {
        var xhr, formData;
      
        xhr = new XMLHttpRequest();
        xhr.withCredentials = false;
        xhr.open('POST', '/admin/blog/images/upload');
      
        xhr.onload = function() {
            var json;
        
            if (xhr.status != 200) {
                failure('HTTP Error: ' + xhr.status);
                return;
            }
        
            json = JSON.parse(xhr.responseText);
        
            if (!json || typeof json.location != 'string') {
                failure('Invalid JSON: ' + xhr.responseText);
                return;
            }
        
            success(json.location);
        };
      
        formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
      
        xhr.send(formData);
    }
  });