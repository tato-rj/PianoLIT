<section class="container-fluid mb-5">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3"><strong>{{$title}}</strong></h4>
        </div>
    </div>
    <div class="pb-2 w-100 d-flex custom-scroll dragscroll" style="overflow-x: scroll;">
        {{$slot}}
    </div>    
</section>