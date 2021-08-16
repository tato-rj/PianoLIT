@component('components.modal', [
	'id' => 'modal-whatsnew',
  'show' => true,
  'header' => ''
])

@slot('header')
@fa(['icon' => 'paint-brush', 'color' => 'primary'])What's new!
@endslot

@slot('body')
<div class="px-3">
  <p>We've been working some updates recently. Here's what you'll find:</p>

  <div id="whatsnew-carousel" class="carousel slide" data-ride="carousel" data-interval="false">
    <div class="carousel-inner">
      @for($i=1; $i<=$tabscount; $i++)
      <div class="carousel-item {{$i == 1 ? 'active' : null}}">
        @include('components.popups.whatsnew.tab-'.$i)
      </div>
      @endfor
    </div>
    <div class="text-center">
      <div class="d-flex flex-center my-3 carousel-dots">
        @for($i=1; $i <= $tabscount; $i++)
        @fa(['icon' => 'circle', 'mr' => 1, 'ml' => 1, 'size' => 'xs', 'color' => $i == 1 ? 'primary' : 'grey'])
        @endfor
      </div>
      <button class="btn btn-primary btn-wide" data-slide="next" href="#whatsnew-carousel">Next @fa(['icon' => 'chevron-right', 'mr' => 0, 'ml' => 1])</button>
      <button class="btn btn-primary btn-wide" data-slide="end" style="display: none;" data-dismiss="modal">Enjoy!</button>
    </div>
  </div>
</div>
@endslot

@endcomponent