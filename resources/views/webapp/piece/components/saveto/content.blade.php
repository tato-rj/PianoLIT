@include('webapp.piece.components.saveto.header')
@include('webapp.piece.components.saveto.new')
<div class="save-to-panel__list custom-scroll dragscroll dragscroll-horizontal" role="group" aria-label="Favorite folders">
    @forelse($folders as $folder)
        @include('webapp.piece.components.saveto.folder')
    @empty
        <p class="save-to-panel__empty">Create a folder to start saving this piece.</p>
    @endforelse
</div>
