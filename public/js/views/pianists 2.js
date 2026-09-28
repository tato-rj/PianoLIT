$(document).ready(function() {
  axios.get('https://itunes.apple.com/lookup', {params: {id: $('#api-results').attr('data-itunes'), entity: 'album', limit: 200}})
    .then(function(response) {
      showAlbums(response.data.results);
    })
    .catch(function(error) {
      $('#api-results h5').text('Sorry, we couldn\'t load the albums...');
    });
});

function showAlbums(albums) {
  albums.shift();
  let sentence = albums.length == 200 ?
    'We found more than <strong><span>'+albums.length+'</span> albums</strong>' : 
    'We found <strong><span>'+albums.length+'</span> albums</strong>';

 let html = '<div class="col-12"><p class="text-center mb-4 text-muted">'+sentence+' on Apple Music</p></div>';

  for (album in albums) {
    html += `
    <div class="col-lg-6 col-md-6 col-12 mb-4 animate fadeInUp">
     <a href="`+albums[album].collectionViewUrl+`" target="_blank" class="link-none">
       <div class="d-flex rounded alert-grey border" style="height: 100px;border-color: #f3f5f7!important;">
         <div class="mr-3"><img src="`+albums[album].artworkUrl100+`" style="width:100px; height: 100%;" class="rounded-left"></div>
         <div class="d-flex flex-grow">
            <div class="flex-grow py-2 pr-2">
             <p class="m-0 album-title"><strong>`+albums[album].collectionName+`</strong></p>
             <p>Price: `+albums[album].collectionPrice+` `+albums[album].currency+`</p>
            </div>
            <div class="d-flex flex-center p-2" style="background: rgba(0,0,0,0.025);">
              <i class="ml-1 fas fa-angle-right fa-lg"></i>
            </div>
         </div>
       </div>
     </a>
    </div>
    `;
  }

  if (albums.length == 200)
    html += '<div class="col-12 mt-4 text-center"><div class="alert alert-warning d-inline-block"><i class="fas fa-exclamation-circle mr-2"></i>We reached Apple Music\'s limit of 200 results</div></div>';

  $('#api-results').html(html);

  $('.album-title').clamp(2);
}