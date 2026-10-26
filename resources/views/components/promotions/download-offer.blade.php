@php($offerFormId = $id.'-form')
@component('components.dialogs.modal', [
    'id' => $id, 'cookie' => $cookie ?? null, 'centered' => true,
    'titleId' => $id.'-heading', 'descriptionId' => $id.'-description',
    'options' => ['size' => 'lg', 'header' => ['raw' => true]],
])
    @slot('header')
        <span class="small fw-semibold text-primary d-inline-flex align-items-center gap-2">
            @icon('gift', ['mr' => 0])A gift from PianoLIT
        </span>
    @endslot
    @slot('body')
        <div class="row g-4 align-items-center">
            <div class="col-md-5">
                <div class="download-offer__preview rounded p-3 p-md-4 text-center">
                    <img src="{{ $image }}" alt="{{ $imageAlt }}" class="download-offer__image shadow-light" decoding="async">
                    @if(! empty($imageTitle))
                        <p class="small fw-semibold mt-3 mb-0">{{ $imageTitle }}</p>
                    @endif
                </div>
            </div>
            <div class="col-md-7">
                <p class="small text-primary fw-semibold mb-2">Free download</p>
                <h2 class="h3 mb-3" id="{{ $id }}-heading">{{ $heading }}</h2>
                <p class="text-muted mb-4" id="{{ $id }}-description">{{ $description }}</p>
                <{{ ($preview ?? false) ? 'div' : 'form' }} id="{{ $offerFormId }}" @unless($preview ?? false) method="POST" action="{{ route('subscriptions.store') }}" @endunless>
                    @unless($preview ?? false)
                        @csrf
                        @include('components.subscriptions.hidden-fields', ['formId' => $offerFormId, 'gift_url' => $giftUrl])
                    @endunless
                    @input([
                        'id' => $offerFormId.'-email', 'name' => 'email', 'type' => 'email',
                        'label' => 'Email address', 'placeholder' => 'you@example.com', 'autocomplete' => 'email',
                        'describedBy' => $offerFormId.'-note', 'designSystemPreview' => $preview ?? false,
                    ])
                    <button type="{{ ($preview ?? false) ? 'button' : 'submit' }}" class="btn btn-primary w-100 mt-2" @if($preview ?? false) data-ds-preview @else disable-on-submit @endif>
                        {{ $actionLabel }} @icon('arrow-right', ['mr' => 0, 'ml' => 2])
                    </button>
                    <p class="small text-muted mt-3 mb-0" id="{{ $offerFormId }}-note">Includes PianoLIT email updates. Unsubscribe anytime.</p>
                </{{ ($preview ?? false) ? 'div' : 'form' }}>
                <button type="button" class="btn btn-raw text-muted w-100 mt-3" data-bs-dismiss="modal">Maybe later</button>
            </div>
        </div>
    @endslot
@endcomponent
