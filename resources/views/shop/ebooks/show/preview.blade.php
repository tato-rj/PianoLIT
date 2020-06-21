@component('components.modal', ['id' => 'preview-ebook', 'size' => 'lg', 'headerNoBorder' => true, 'footerNoBorder' => true])
  @slot('title')
    @fa(['icon' => 'book-open'])eBook preview
  @endslot
  @slot('body')
    <div id="ebook-loading">
      <div class="d-flex flex-center" style="min-height: 300px">
        <h5 class="text-grey m-0">loading...</h5>
      </div>
    </div>

    <div id="flipbook-container" class="p-4">
      <div id="flipbook-model" class="mx-auto" style="display: none;">
        <div class="hard"><img src="{{asset('images/ebook-temp/cover.jpg')}}" class="w-100"></div>
        <div class="hard"><img src="{{asset('images/ebook-temp/backcover.jpg')}}" class="w-100"></div>
        <div><img src="{{asset('images/ebook-temp/content.jpg')}}" class="w-100"></div>
        <div><img src="{{asset('images/ebook-temp/quote.jpg')}}" class="w-100"></div>
        <div><img src="{{asset('images/ebook-temp/pag1.jpg')}}" class="w-100"></div>
        <div><img src="{{asset('images/ebook-temp/pag2.jpg')}}" class="w-100"></div>
        <div class="hard"><img src="{{asset('images/ebook-temp/last.jpg')}}" class="w-100"></div>
        <div class="hard"><img src="{{asset('images/ebook-temp/backcover.jpg')}}" class="w-100"></div>
      </div>
    </div>

    <div id="pages-container" class="position-relative" style="display: none;">
      <div id="ebook-pages">
        <div class="mb-2 border"><img src="{{asset('images/ebook-temp/cover.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/backcover.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/content.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/quote.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/pag1.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/pag2.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/last.jpg')}}" class="w-100"></div>
        <div class="mb-2 border" style="display: none;"><img src="{{asset('images/ebook-temp/backcover.jpg')}}" class="w-100"></div>
      </div>
      
      <div class="position-absolute w-100 h-100 d-flex d-apart" style="left: 0; top: 0">
        <div class="h-100 ebook-turn cursor-pointer" style="width: 25%" data-direction="previous"></div>
        <div class="h-100 ebook-turn cursor-pointer" style="width: 25%" data-direction="next"></div>
      </div>
    </div>
  @endslot
  @slot('footer')
    <div class="d-flex flex-center w-100">
      <button class="btn-raw px-3 py-1 ebook-turn" data-direction="previous">@fa(['color' => 'primary', 'size' => 'lg', 'icon' => 'arrow-alt-circle-left', 'mr' => 0])</button>
      <div class="text-grey"><small>click to flip pages</small></div>
      <button class="btn-raw px-3 py-1 ebook-turn" data-direction="next">@fa(['color' => 'primary', 'size' => 'lg', 'icon' => 'arrow-alt-circle-right', 'mr' => 0])</button>
    </div>
  @endslot
@endcomponent