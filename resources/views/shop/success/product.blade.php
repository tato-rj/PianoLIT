<div class="col-lg-10 col-12 mx-auto text-center">
	<div>
		<img src="{{$ebook->cover_image()}}" style="width: 200px" class="mb-2">
		<h6>{{$ebook->title}}</h6>
	</div>
	<p>Download the eBook below</p>
	<a href="{{storage($ebook->pdf_path)}}" class="btn btn-wide btn-outline-secondary" target="_blank">@fa(['icon' => 'cloud-download-alt'])PDF</a>
	<a href="{{storage($ebook->epub_path)}}" class="btn btn-wide btn-outline-secondary" target="_blank">@fa(['icon' => 'cloud-download-alt'])ePUB</a>
</div>