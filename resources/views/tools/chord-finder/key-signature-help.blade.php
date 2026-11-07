@component('components.dialogs.modal', ['id' => 'chord-key-signature-help', 'header' => 'Why the key matters', 'centered' => true])
    @slot('body')
        <p>When a chord is missing a note, we use the key signature to help work out what that note would most likely be.</p>

        <p>For example, <strong>D–A–C</strong> is missing its 3rd. In <strong>C major</strong>, we assume F (<strong>Dm7</strong>); in <strong>G major</strong>, F♯ (<strong>D7</strong>).</p>

        <p class="mb-0">Accidentals and the surrounding music can still suggest another reading.</p>
    @endslot
@endcomponent
