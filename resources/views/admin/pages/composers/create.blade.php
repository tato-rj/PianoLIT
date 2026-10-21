@component('components.modal', ['id' => 'add-modal', 'header' => 'Add new composer', 'options' => ['size' => 'lg'], 'show' => $errors->getBag('composerCreate')->any() ? true : null])
@slot('body')
    <p class="text-muted mb-4">Add a profile and biography. You can manage the composer’s repertoire from the editor.</p>
    <form method="POST" action="{{ route('admin.composers.store') }}" enctype="multipart/form-data">
        @csrf
        @if($errors->getBag('composerCreate')->any())<div class="alert alert-red" role="alert">Please check the highlighted fields and choose the portrait file again.</div>@endif
        @include('admin.pages.composers.fields.profile', ['composer' => null, 'bag' => 'composerCreate', 'prefix' => 'composer-create'])
        @include('admin.pages.composers.fields.biography', ['composer' => null, 'bag' => 'composerCreate', 'prefix' => 'composer-create'])
        @include('admin.pages.composers.fields.options', ['composer' => null])
        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">@icon('plus')Add composer</button>
        </div>
    </form>
@endslot
@endcomponent
