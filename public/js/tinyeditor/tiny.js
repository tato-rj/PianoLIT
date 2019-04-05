  tinymce.init({
    selector: 'textarea#editor',
    plugins: 'advcode formatpainter linkchecker lists link image media mediaembed pageembed permanentpen powerpaste tinydrive tinymcespellchecker',
    toolbar: 'formatselect | bold italic strikethrough forecolor backcolor formatpainter | link image media pageembed subscribeButton | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent',
    content_css: "/css/admin.css",
    menubar: "edit view insert format tools extra",
    menu: {
      extra: { title: "Extra", items: "subscribe dotseparator quote" }
    },

    setup: function(editor) {
      editor.ui.registry.addMenuItem('subscribe', {
        text: 'Subscription form',
        context: 'extra',
        onAction: function () {
          editor.insertContent('<div id="inner-subscribe"><i style="color:#1876f6">[ subscription form will show here ]</i></div>');
        }
      });

      editor.ui.registry.addMenuItem('dotseparator', {
        text: 'Dots separator',
        context: 'extra',
        onAction: function () {
          editor.insertContent('<h6 class="mce-dots-separator"><span>•</span><span class="middle-dot">•</span><span>•</span></h6>');
        }
      });

      editor.ui.registry.addMenuItem('quote', {
        text: 'Large quote',
        context: 'extra',
        onAction: function () {
          editor.insertContent('<div class="mce-large-quote"><p class="mce-quote-text">Quote</p><p class="mce-quote-author">Author</p></div>');
        }
      });
    },
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