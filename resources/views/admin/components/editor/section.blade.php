<section class="admin-editor-section {{ $class ?? '' }}" @isset($sectionId) id="{{ $sectionId }}" @endisset aria-labelledby="{{ $id }}-title" @if($preview ?? false) data-ds-preview @endif>
    <header class="admin-editor-section__heading">
        <div>
            <h2 id="{{ $id }}-title">{{ $title }}</h2>
            @if(!empty($description))<p>{{ $description }}</p>@endif
        </div>
        {{ $actions ?? '' }}
    </header>
    {{ $slot }}
</section>
