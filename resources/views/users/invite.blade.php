@extends('layouts.app')

@push('header')
<style type="text/css">
/* enable absolute positioning */
.inner-addon { 
    position: relative; 
}

/* style icon */
.inner-addon .app-icon {
  position: absolute;
  padding: 10px;
  pointer-events: none;
}

/* align icon */
.left-addon .app-icon  { left:  0px; top: 1.5px;}
.right-addon .app-icon { right: 0px; top: 1.5px;}

/* add padding  */
.left-addon input  { padding-left:  32px; }
.right-addon input { padding-right: 32px; }
</style>
@endpush

@section('content')
<div class="container mb-6">
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-8 col-12 mx-auto">
            <h3 class="accent-bottom mb-4">Invite a friend</h3>
            <div>
                <p>Share this link to invite your friends</p>
                <div class="inner-addon right-addon">
                    @icon('link', ['mr' => 0, 'classes' => 'text-muted'])
                    <input type="text" class="form-control" readonly="readonly" value="{{auth()->user()->referralUrl()}}" style="background-color: white">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-6">
    @include('components.sections.youtube')
</div>
@endsection
