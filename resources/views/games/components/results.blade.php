@component('components.modal', [
  'id' => 'game-results',
  'options' => [
    'header' => ['show' => false],
    'body' => ['padding' => 4],
    'footer' => ['border' => true]
]])

@slot('body')
<div class="text-center">
  <div id="game-feedback" class="mb-4"></div>
  <button type="button" class="btn btn-teal btn-sm btn-wide" data-dismiss="modal"><strong>{{$button}}</strong></button>
</div>
@endslot

@slot('footer')
  <div class="w-100 text-center">
    <p class="mb-1"><strong>Did you like this game?</strong></p>
    <p>Subscribe and we'll keep you in the loop about the new ones!</p>
    @include('components.form.subscription')
  </div>
@endslot
@endcomponent