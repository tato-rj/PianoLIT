@extends('layouts.raw')

@push('header')

<style type="text/css">
#progress-bar {
	width: 400px;
	height: 35px;
}

.progress-fill {
	height: 100%;
	width: 0%;
	background-color: lightblue;
	display: flex;
	align-items: center;
	transition: width .25s;
}

.progress-text {
	margin-left: 10px;
}
</style>
@endpush

@section('content')
<div class="p-4">
	<div class="mb-3">
		<p>Test upload files to Google Cloud</p>
		<form id="upload-form" method="POST" action="{{route('test-upload')}}" enctype="multipart/form-data">
			@csrf
			<input type="file" name="video" class="mb-3">
			<button type="submit" id="upload-file" class="btn btn-default">Upload</button>
		</form>
	</div>

	<div id="progress-bar" class="border rounded">
		<div class="progress-fill">
			<span class="progress-text">0%</span>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js" integrity="sha384-FzT3vTVGXqf7wRfy8k4BiyzvbNfeYjK+frTVqZeNDFl8woCbF0CYG6g2fMEFFo/i" crossorigin="anonymous"></script>

<script type="text/javascript">
let $progressFill = $('#progress-bar .progress-fill');
let $progressText = $('#progress-bar .progress-text');
$(document).ready(function() {
	$('form').ajaxForm({
		beforeSubmit: validate,
		uploadProgress: track,
		error: function(response) {
			alert(response.responseJSON.errors.video);
		}
	});	
});

function track(event, position, total, percentComplete) {
	$progressFill.width(percentComplete + '%');
	$progressText.text(percentComplete + '%');

	if (position == total) {
		setTimeout(function(){ alert('Your video is being processed') }, 500);
	}
}

function validate(formData, jqForm, options) {
    var video = jqForm[0].video;

    if (! video.value) {
        alert('Please select the video to upload'); 
        return false; 
    }

    if (! isVideo(video.value)) {
        alert('File format not supported'); 
        return false; 
    }

    if (video.files.item(0).size > {{config('filesystems.disks.gcs.max_sizes.video')}}) {
    	console.log(video.files.item(0).size);
        alert('File too large'); 
        return false; 
    }

	return true;
}

function getExtension(filename) {
  var parts = filename.split('.');
  return parts[parts.length - 1];
}

function isVideo(filename) {
  var ext = getExtension(filename);
  switch (ext.toLowerCase()) {
    case 'm4v':
    case 'avi':
    case 'mpg':
    case 'mp4':
    case 'mov':
    case '3gp':

      return true;
  }
  return false;
}

// $('#upload-form').submit(function(event) {
// 	event.preventDefault();

// 	var formData = new FormData();
// 	formData.append("video", $('input[name="file"]').prop('files')[0]);

// 	let request = new XMLHttpRequest();

// 	request.open('POST', "");

// 	request.upload.addEventListener('progress', event => {
// 		let progress = event.lengthComputable ? (event.loaded / event.total) * 100 : 0;
// 		let percent = progress.toFixed(0) + '%';
// 		$progressFill.width(percent);
// 		$progressText.text(percent);
// 	});

// 	request.addEventListener("load", response => {
// 		alert('File uploaded!');
// 	});

// 	request.setRequestHeader('Content-Type', 'multipart/form-data');
// 	request.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));

// 	request.send(new FormData(this));
// });
</script>
@endpush