class YoutubeV3
{
	init() {
		let obj = this;

        $.ajax({
            url: 'https://www.googleapis.com/youtube/v3/channels', 
            type: "GET",   
            dataType: 'jsonp',
            data: {
                part: 'contentDetails',
                id: 'UCOB67MpdySxyTCZHvWgxHeg',
                key: app.youtube_key
            },
            cache: false,
            success: function(data){
                $.each(data.items, function(key, item) {
                    obj._getVideos(item.contentDetails.relatedPlaylists.uploads);
                });              
            }
        });
    }

    _getVideos(id) {

        $.ajax({
            url: 'https://www.googleapis.com/youtube/v3/playlistItems', 
            type: "GET",   
            dataType: 'jsonp',
            data: {
                part: 'snippet',
                maxResults: 20,
                playlistId: id,
                key: app.youtube_key
            },
            cache: false,
            success: function(data){                          
                let videos = getRandom(data.items, 2);
                $('iframe.youtube-video-1').attr('src', 'https://www.youtube.com/embed/' + videos[0].snippet.resourceId.videoId);
                $('iframe.youtube-video-2').attr('src', 'https://www.youtube.com/embed/' + videos[1].snippet.resourceId.videoId);
                $('#youtube-previews').css('opacity', 1); 
            }
        });
    }
}

window.YoutubeV3 = YoutubeV3;