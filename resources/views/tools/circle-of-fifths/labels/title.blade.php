<label class="text-muted border-bottom border-1x">{{strtoupper($title)}}
	<span class="cursor-pointer text-teal" title="What is this?" data-bs-toggle="modal" data-bs-target="#modal-{{str_slug($title)}}">
		<small class="align-text-bottom">@icon('circle-help', ['mr' => 0])</small>
	</span>
</label>