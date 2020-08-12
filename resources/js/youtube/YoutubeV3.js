class YoutubeV3
{
	init() {
		let obj = this;

        $.ajax({
                    url: 'https://www.googleapis.com/youtube/v3/channels', 
                    data: {
                        part: 'contentDetails',
                        id: 'UCOB67MpdySxyTCZHvWgxHeg',
                        key: app.youtube_key
                    },
                    type: "GET",   
                    dataType: 'jsonp',
                    cache: false,
                    success: function(data, status, xhr){                          
                        $.each(data.items, function(key, item) {
                            obj._getVideos(item.contentDetails.relatedPlaylists.uploads);
                        });              
                    }           
                });
            // $.get('https://www.googleapis.com/youtube/v3/channels', {
            //     part: 'contentDetails',
            //     id: 'UCOB67MpdySxyTCZHvWgxHeg',
            //     key: app.youtube_key
            // }, function(data, status, xhr) {
            //     $.each(data.items, function(key, item) {
            //         obj._getVideos(item.contentDetails.relatedPlaylists.uploads);
            //     });
            // }, 
            // ).fail(function(response) {
            //     console.log('We coudldn\'t load youtube videos in this page.');
            //     console.log(response);
            // });
    }

    _getVideos(id) {

        $.get('https://www.googleapis.com/youtube/v3/playlistItems', {
            part: 'snippet',
            maxResults: 20,
            playlistId: id,
            key: app.youtube_key
        }, function(data, status, xhr) {
            let videos = getRandom(data.items, 2);
            $('iframe.youtube-video-1').attr('src', 'https://www.youtube.com/embed/' + videos[0].snippet.resourceId.videoId);
            $('iframe.youtube-video-2').attr('src', 'https://www.youtube.com/embed/' + videos[1].snippet.resourceId.videoId);
            $('#youtube-previews').css('opacity', 1);
        });
    }
}

window.YoutubeV3 = YoutubeV3;