@if(! empty($label))
@php($formLabelTag = ($labelTag ?? 'label') === 'span' ? 'span' : 'label')
<{{ $formLabelTag }} class="text-muted ml-2" @isset($labelId) id="{{ $labelId }}" @endisset @if($formLabelTag === 'label' && isset($id)) for="{{$id}}" @endif>
	<small>{{$label}}<span class="text-red">{{empty($asterisk) ? null : '*'}}</span></small>
</{{ $formLabelTag }}>
@endif
