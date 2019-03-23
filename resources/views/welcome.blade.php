@extends('layouts.app')

@section('header')
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-118457950-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-118457950-1');
</script>

<style type="text/css">
        .alert {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
        }

        .btn {
            padding: 
        }
        
        .text-blue {
            color: #2d75b5!important;
        }
        
        .btn-outline-blue {
            border: 2px solid #2d75b5;
            border-radius: 4px;
            background: transparent;
            color: #2d75b5;
            transition: 0.2s;
        }

        .btn-outline-blue:hover {
            border: 2px solid #2d75b5;
            background: #2d75b5;
            color: white;
        }

        .slideshow {
            position: relative;
        }

        .slideshow, .screen {
            width: 300px;
            height: 533px;
        }

        .screen {
            position: absolute;
            top: 0;
            left: 0;
        }

        .shadow {
            -webkit-box-shadow: 0 5px 30px rgba(0,0,0,0.15);
            -moz-box-shadow: 0 5px 30px rgba(0,0,0,0.15);
            box-shadow: 0 5px 30px rgba(0,0,0,0.15);
        }
</style>
@endsection

@section('content')
    <header class="bg-gradient" id="home">
        <div class="container">
            <p class="tagline">Play only what you love with </p>
            <h1 style="font-size: 5em"><b>PIANO<span style="color: rgba(255, 255, 255, 0.5)">LIT</span></b></h1>
            <p class="tagline text-white">PianoLIT is where pianists discover new pieces and find inspiration to play only what they love.</p>
        </div>
        <div class="img-holder mt-3"><img src="pianolit/images/iphonex-cover.png" alt="phone" class="img-fluid"></div>
    </header>

    <div class="client-logos my-5">
        <div class="container text-center">
            <div class="d-flex align-items-center justify-content-center">
                <h4 class="m-0">Do you want to find a piano piece that suits you?</h4>
                <a class="btn btn-primary btn-lg ml-4" href="#pricing">SIGN ME UP</a>
            </div>
        </div>
    </div>
    <!-- // end .section -->

    <div class="section light-bg">

        <div class="container">
            <div class="row">
                <div class="col-md-8 d-flex align-items-center">
                    <ul class="list-unstyled ui-steps">
                        <li class="media">
                            <div class="circle-icon mr-4">1</div>
                            <div class="media-body">
                                <h5>Search & Discover</h5>
                                <p>Use our guided tour to discover new pieces based on your <u>level</u> and <u>mood</u>. Or search in our <u>library</u> by technique, period, composer and much more.</p>
                            </div>
                        </li>
                        <li class="media my-4">
                            <div class="circle-icon mr-4">2</div>
                            <div class="media-body">
                                <h5>Find your Top Matches</h5>
                                <p>The search results display your <u>best matches</u>. Our library is created by professionals and teachers, and it's <u>continuously growing</u>.</p>
                            </div>
                        </li>
                        <li class="media">
                            <div class="circle-icon mr-4">3</div>
                            <div class="media-body">
                                <h5>Lear Quickly</h5>
                                <p>The result pieces include <u>audio recordings</u> of separate hands and hands together, recommended video, <u>speed control</u> and option to <u>download the score</u>.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <div class="slideshow mx-auto shadow">
                        <img src="pianolit/images/screen04.png" class="screen">
                        <img src="pianolit/images/screen05.png" style="display: none;" class="screen">
                        <img src="pianolit/images/screen06.png" style="display: none;" class="screen">
                        <img src="pianolit/images/screen07.png" style="display: none;" class="screen">                    
                    </div>

                </div>

            </div>

        </div>

    </div>
    <!-- // end .section -->

    <div class="section" id="pricing">
        <div class="container">
            <div class="section-title">
                <small>PRICING</small>
                <h3>Less than a cup of coffee!</h3>
            </div>

            <div class="card-deck row">
                <div class="card pricing popular col-lg-4 col-md-5 col-sm-6 col-10 mx-auto">
                    <div class="card-head">
                        <small class="text-primary">subscription</small>
                        <span class="price">$0.99<sub>/month</sub></span>
                    </div>
                    <ul class="list-group list-group-flush">
                        <div class="list-group-item"><span class="ti-check text-success mr-2"></span>Unlimited Access</div>
                        <div class="list-group-item"><span class="ti-check text-success mr-2"></span>Over 500 pieces (and growing)</div>
                        <div class="list-group-item"><span class="ti-check text-success mr-2"></span>Audio and Video</div>
                        <div class="list-group-item"><span class="ti-check text-success mr-2"></span>Speed Control</div>
                        <div class="list-group-item"><span class="ti-check text-success mr-2"></span>Download the Score</div>
                    </ul>
                    <div class="card-body">
                        <a  data-toggle="modal" data-target="#signup-modal"  href="#" class="btn btn-primary btn-lg btn-block">Sign me Up</a>
                    </div>
                </div>
            </div>
            <!-- // end .pricing -->


        </div>

    </div>
    <!-- // end .section -->
    <footer class="my-5 text-center">
        <!-- Copyright removal is not prohibited! -->
        <p class="mb-2"><small>MADE WITH <span class="ti-heart text-danger mx-2"></span> BY LEFTLANE</small></p>

    </footer>

<div class="modal fade" id="signup-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header border-0 mb-0 pb-0">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body mt-0 pt-0">
        <form method="POST" action="/piano-lit/subscribe" class="pl-4 pb-4 pr-4">
            {{ csrf_field() }}
            <div class="form-group text-dark">
                <h3>Hello! You caught us before we're ready.</h3>
                <p>We're working hard to put the finishing touches onto the app. If you'd like us to send you a reminder when we're ready, just enter your email.</p>
                <input required type="email" style="border-color: rgba(0,0,0,0.05)!important" class="form-control py-3 px-4 bg-light" name="email" placeholder="Enter your email here">
            </div>
            <button type="submit" style="cursor: pointer;" class="btn btn-primary btn-block btn-lg">Let me know when it's out!</button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- @include('landing-pages/feedback') --}}

@endsection

@section('scripts')
<script type="text/javascript">
$(function(){
    $('.slideshow img:nth-of-type(1)').prependTo('.slideshow');
    
    $('.slideshow img:gt(0)').hide();
    
    setInterval(function() {
    
        $('.slideshow :first-child').fadeOut('slow').next('img').fadeIn('slow').end().appendTo('.slideshow');
    
    }, 6000);
});
</script>
@endsection