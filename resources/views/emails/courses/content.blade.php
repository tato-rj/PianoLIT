<table class="course-content" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{{ $design['settings']['background'] }}" @if($design['settings']['background_image']) background="{{ $design['settings']['background_image'] }}" @endif style="width:100%;background-color:{{ $design['settings']['background'] }};@if($design['settings']['background_image']) background-image:url('{{ $design['settings']['background_image'] }}');background-size:cover;background-position:center;@endif font-family:{{ $design['settings']['font'] === 'serif' ? 'Georgia, Times New Roman, serif' : 'Arial, Helvetica, sans-serif' }};">
@foreach($design['blocks'] as $block)
<tr><td @if($builderPreview ?? false) data-email-block="{{ $block['id'] }}" @endif style="padding:0 0 {{ $block['gap'] }}px;">
@include('emails.courses.block', ['block' => $block])
</td></tr>
@endforeach
</table>
