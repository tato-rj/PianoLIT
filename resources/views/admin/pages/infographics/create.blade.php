@component('components.dialogs.modal', ['id' => 'infographic-create-modal', 'header' => 'Create infographic'])
@slot('body')
<form method="POST" action="{{ route('admin.infographs.store', $returnFilters) }}" enctype="multipart/form-data">
    @csrf
    @include('admin.pages.infographics.fields')
    <div class="text-end"><button type="submit" class="btn btn-primary btn-sm">Create infographic</button></div>
</form>
@endslot
@endcomponent
