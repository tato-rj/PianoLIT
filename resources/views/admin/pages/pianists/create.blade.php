@component('components.dialogs.modal', ['id' => 'add-modal', 'header' => 'Add a pianist', 'show' => $errors->getBag('pianistCreate')->any() ? true : null])
@slot('body')
    <form method="POST" action="{{ route('admin.pianists.store') }}" enctype="multipart/form-data">
        @csrf
        @if($errors->getBag('pianistCreate')->any())<div class="alert alert-red" role="alert">Please check the highlighted fields and choose the portrait again.</div>@endif
        @include('admin.pages.pianists.fields', ['pianist' => null, 'bag' => 'pianistCreate', 'prefix' => 'pianist-create'])
        @include('components.forms.upload.file', ['bag' => 'pianistCreate', 'id' => 'pianist-create-cover', 'name' => 'cover', 'label' => 'Portrait', 'required' => true, 'accept' => 'image/jpeg,image/png,image/gif,image/webp'])
        <div class="d-flex justify-content-end flex-wrap gap-2 mt-4">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">@icon('plus')Add pianist</button>
        </div>
    </form>
@endslot
@endcomponent
