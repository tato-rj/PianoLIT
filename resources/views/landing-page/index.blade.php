<!DOCTYPE html>
<html lang="en">

<head>
    <!--- Basic Page Needs  -->
    <meta charset="utf-8">
    <title>{{config('app.name')}} | Official Page</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Mobile Specific Meta  -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!-- CSS -->
    <link rel="stylesheet" href="{{mix('landing-page/css/theme.css')}}">
    <!-- Favicon -->
    {{-- <link rel="shortcut icon" type="image/png" href="assets/img/icon/favicon.ico"> --}}

    <link href="https://fonts.googleapis.com/css?family=Poppins:400,800" rel="stylesheet">
    <style type="text/css">
        body, p, h1, h2, h3, h4, h5, h6, a, li {
            font-family: 'Poppins', sans-serif !important;
        }
        .main-menu nav ul li a {
            font-size: 16px;
        }
    </style>
</head>

<body>
    <!-- preloader area start -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>
    <!-- preloader area end -->
    <!-- header area start -->
    <header id="header">
        <div class="header-area">
            <div class="container">
                <div class="row">
                    <div class="menu-area">
                        <div class="col-md-3 col-sm-12 col-xs-12">
                            <div class="logo">
                                <a href="index.html"><h3 style="line-height: 2; color: white; font-weight: 800">PianoLIT</h3></a>
                            </div>
                        </div>
                        <div class="col-md-9 hidden-xs hidden-sm">
                            <div class="main-menu">
                                <nav class="nav-menu">
                                    <ul>
                                        <li class="active"><a href="#home">App</a></li>
                                        {{-- <li><a href="#feature">Features</a></li> --}}
                                        {{-- <li><a href="#screenshot">Screenshot</a></li> --}}
                                        {{-- <li><a href="#pricing">Pricing</a></li> --}}
                                        {{-- <li><a href="#team">Team</a></li> --}}
                                        <li><a href="#blog">Blog</a></li>
                                        <li><a href="#download">Download</a></li>
                                        {{-- <li><a href="#contact">Contact</a></li> --}}
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="col-sm-12 col-xs-12 visible-sm visible-xs">
                            <div class="mobile_menu"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- header area end -->
    <!-- slider area start -->
    <section id="home" class="slider-area image-background parallax" data-speed="3" data-bg-image="{{asset('landing-page/img/bg/slider-bg.jpg')}}">
        <div class="container">
            <div class="col-md-6 col-sm-6 hidden-xs" style="max-height: 50vh">
                <div class="row">
                    <div class="slider-img">
                        <img src="{{asset('landing-page/img/mobile/slider-left-img.png')}}" style="width: 60%" alt="slider image">
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-6 col-xs-12">
                <div class="row">
                    <div class="slider-inner text-right">
                        <h2 style="font-weight: 800">PianoLIT</h2>
                        <h5 style="font-size: 26px">Where pianists discover new pieces and find inspiration to play only what they love.</h5>
                        <a style="    border: 0;
    padding: 0;
    margin: 0;
    width: auto;
    height: auto;" href="https://www.youtube.com/watch?v=8qs2dZO6wcc"><img src="{{asset('landing-page/app-store.svg')}}" style="width: 200px"></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- slider area end -->
    <!-- service area start -->
{{--     <div class="service-area">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-4 col-xs-12">
                    <div class="service-single">
                        <img src="{{asset('landing-page/img/service/service-img1.png')}}" alt="service image">
                        <h2>Call service</h2>
                        <p>Take The initative to call</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12 col-6">
                    <div class="service-single">
                        <img src="{{asset('landing-page/img/service/service-img2.png')}}" alt="service image">
                        <h2>Active warning</h2>
                        <p>Timely detection of accidents</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12 col-6">
                    <div class="service-single">
                        <img src="{{asset('landing-page/img/service/service-img3.png')}}" alt="service image">
                        <h2>Care plan</h2>
                        <p>The care content is pushed</p>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <!-- service area end -->
    <!-- about area start -->
    <div class="about-area ptb--100">
        <div class="container">
            <div class="section-title">
                <h2>About App</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit </p>
            </div>
            <div class="row d-flex flex-center">
                <div class="col-md-6 col-sm-6 hidden-xs">
                    <div class="about-left-img">
                        <img src="{{asset('landing-page/img/about/abt-left-img.png')}}" alt="image">
                    </div>
                </div>

                <div class="col-md-6 col-sm-6 col-xs-12 d-flex flex-center">
                    <div class="about-content">
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed  eiuiosmod terttmpor incididunt ut labore et dolore magna aliqua. enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. doliuor in reprehenderit in voluptate velit esse  dolore eu fugiat nulla pariatur. cdatat non proident</p>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tuiempor incididunt ut labore et dolore magna aliqua. enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- about area end -->
    <!-- feature area start -->
    <section class="feature-area bg-gray ptb--100" id="feature">
        <div class="container">
            <div class="section-title">
                <h2>Features</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-6 col-xs-12">
                    <div class="ft-content rtl">
                        <div class="ft-single">
                            <img src="{{asset('landing-page/img/icon/feature/1.png')}}" alt="icon">
                            <div class="meta-content">
                                <h2>Full Optional</h2>
                                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, iumod tempor incididunt</p>
                            </div>
                        </div>
                        <div class="ft-single">
                            <img src="{{asset('landing-page/img/icon/feature/2.png')}}" alt="icon">
                            <div class="meta-content">
                                <h2>Unique Design</h2>
                                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, iumod tempor incididunt</p>
                            </div>
                        </div>
                        <div class="ft-single">
                            <img src="{{asset('landing-page/img/icon/feature/3.png')}}" alt="icon">
                            <div class="meta-content">
                                <h2>Voice Maker</h2>
                                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, iumod tempor incididunt</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 hidden-sm col-xs-12">
                    <div class="ft-screen-img">
                        <img src="{{asset('landing-page/img/mobile/ft-screen-img.png')}}" alt="image">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 col-xs-12">
                    <div class="ft-content">
                        <div class="ft-single">
                            <img src="{{asset('landing-page/img/icon/feature/4.png')}}" alt="icon">
                            <div class="meta-content">
                                <h2>Easy Settings</h2>
                                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, iumod tempor incididunt</p>
                            </div>
                        </div>
                        <div class="ft-single">
                            <img src="{{asset('landing-page/img/icon/feature/5.png')}}" alt="icon">
                            <div class="meta-content">
                                <h2>Flat Design</h2>
                                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, iumod tempor incididunt</p>
                            </div>
                        </div>
                        <div class="ft-single">
                            <img src="{{asset('landing-page/img/icon/feature/6.png')}}" alt="icon">
                            <div class="meta-content">
                                <h2>Easy Download</h2>
                                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, iumod tempor incididunt</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- feature area end -->
    <!-- achivement area start -->
    <div class="achivement-area ptb--100">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-3 col-xs-12">
                    <div class="ach-single">
                        <div class="icon"><i class="fa fa-users"></i></div>
                        <p><span class="counter">10</span>k</p>
                        <h5>Happy Clients</h5>
                    </div>
                </div>
                <div class="col-md-3 col-sm-3 col-xs-12">
                    <div class="ach-single">
                        <div class="icon"><i class="fa fa-book"></i></div>
                        <span class="counter">978</span>
                        <h5>Projects complet</h5>
                    </div>
                </div>
                <div class="col-md-3 col-sm-3 col-xs-12">
                    <div class="ach-single">
                        <div class="icon"><i class="fa fa-coffee"></i></div>
                        <p><span class="counter">150</span>k</p>
                        <h5>Cups of coffee</h5>
                    </div>
                </div>
                <div class="col-md-3 col-sm-3 col-xs-12">
                    <div class="ach-single">
                        <div class="icon"><i class="fa fa-trophy"></i></div>
                        <span class="counter">100</span>
                        <h5>Winning awards</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- achivement area end -->
    <!-- screen slider area start -->
    <section class="screen-area bg-gray ptb--100" id="screenshot">
        <div class="container">
            <div class="section-title">
                <h2>Screenshot</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <img class="screen-img" src="{{asset('landing-page/img/mobile/screen-slider.png')}}" alt="mobile screen">
            <div class="screen-slider owl-carousel">
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen1.jpg')}}" alt="mobile screen">
                </div>
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen2.jpg')}}" alt="mobile screen">
                </div>
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen3.jpg')}}" alt="mobile screen">
                </div>
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen4.jpg')}}" alt="mobile screen">
                </div>
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen5.jpg')}}" alt="mobile screen">
                </div>
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen3.jpg')}}" alt="mobile screen">
                </div>
                <div class="single-screen">
                    <img src="{{asset('landing-page/img/mobile/screen-slider/screen4.jpg')}}" alt="mobile screen">
                </div>
            </div>
        </div>
    </section>
    <!-- screen slider area end -->
    <!-- testimonial carousel area start -->
    <div class="testimonial-area ptb--100">
        <div class="container">
            <div class="section-title">
                <h2>Client Says</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="testimonial-slider owl-carousel">
                <div class="single-tst">
                    <img src="{{asset('landing-page/img/author/tst-author1.jpg')}}" alt="author">
                    <h4>John Doe</h4>
                    <span>Founder</span>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis</p>
                    <ul class="tst-social">
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                        <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                        <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                    </ul>
                </div>
                <div class="single-tst">
                    <img src="{{asset('landing-page/img/author/tst-author2.jpg')}}" alt="author">
                    <h4>John Doe</h4>
                    <span>CEO</span>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis</p>
                    <ul class="tst-social">
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                        <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                        <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                    </ul>
                </div>
                <div class="single-tst">
                    <img src="{{asset('landing-page/img/author/tst-author1.jpg')}}" alt="author">
                    <h4>John Doe</h4>
                    <span>CEO</span>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis</p>
                    <ul class="tst-social">
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                        <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                        <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                    </ul>
                </div>
                <div class="single-tst">
                    <img src="{{asset('landing-page/img/author/tst-author2.jpg')}}" alt="author">
                    <h4>John Doe</h4>
                    <span>CEO</span>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis</p>
                    <ul class="tst-social">
                        <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                        <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                        <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- testimonial carousel area end -->
    <!-- video area start -->
    <div class="video-area ptb--100">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1 col-xs-12">
                    <h2>Watch Video Demo</h2>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco</p>
                    <a class="expand-video" href="https://www.youtube.com/watch?v=8qs2dZO6wcc"><i class="fa fa-play"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- video area end -->
    <!-- pricing area start -->
    <section class="pricing-area ptb--100" id="pricing">
        <div class="container">
            <div class="section-title">
                <h2>Pricing Plan</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-4 col-xs-12 col-6">
                    <div class="single-price">
                        <div class="prc-head">
                            <span>Silver</span>
                            <h5><small>$</small>50/m</h5>
                        </div>
                        <ul>
                            <li>10 User</li>
                            <li>1 Year</li>
                            <li>512 Mb Memory</li>
                            <li>30GB SSD Disk</li>
                            <li>1 TB Transfer</li>
                            <li>6 Months Support</li>
                        </ul>
                        <a href="#">Order Now</a>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12 col-6">
                    <div class="single-price">
                        <div class="prc-head">
                            <span>Silver</span>
                            <h5><small>$</small>150/m</h5>
                        </div>
                        <ul>
                            <li>10 User</li>
                            <li>1 Year</li>
                            <li>512 Mb Memory</li>
                            <li>30GB SSD Disk</li>
                            <li>1 TB Transfer</li>
                            <li>6 Months Support</li>
                        </ul>
                        <a href="#">Order Now</a>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 col-xs-12 col-6">
                    <div class="single-price">
                        <div class="prc-head">
                            <span>Silver</span>
                            <h5><small>$</small>250/m</h5>
                        </div>
                        <ul>
                            <li>10 User</li>
                            <li>1 Year</li>
                            <li>512 Mb Memory</li>
                            <li>30GB SSD Disk</li>
                            <li>1 TB Transfer</li>
                            <li>6 Months Support</li>
                        </ul>
                        <a href="#">Order Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- pricing area end -->
    <!-- team area start -->
    <section class="team-area bg-gray ptb--100" id="team">
        <div class="container">
            <div class="section-title">
                <h2>Our Amazing Team</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6 col-xs-12 col-6">
                    <div class="single-team">
                        <div class="team-thumb">
                            <img src="{{asset('landing-page/img/team/team-img1.jpg')}}" alt="image">
                        </div>
                        <h4>Jhon Deo</h4>
                        <span>Web Developer</span>
                        <ul class="tst-social">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                            <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-12 col-6">
                    <div class="single-team">
                        <div class="team-thumb">
                            <img src="{{asset('landing-page/img/team/team-img2.jpg')}}" alt="image">
                        </div>
                        <h4>Jhon Deo</h4>
                        <span>Web Developer</span>
                        <ul class="tst-social">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                            <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-12 col-6">
                    <div class="single-team">
                        <div class="team-thumb">
                            <img src="{{asset('landing-page/img/team/team-img2.jpg')}}" alt="image">
                        </div>
                        <h4>Jhon Deo</h4>
                        <span>Web Developer</span>
                        <ul class="tst-social">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                            <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-xs-12 col-6">
                    <div class="single-team">
                        <div class="team-thumb">
                            <img src="{{asset('landing-page/img/team/team-img4.jpg')}}" alt="image">
                        </div>
                        <h4>Jhon Deo</h4>
                        <span>Web Developer</span>
                        <ul class="tst-social">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                            <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            <li><a href="#"><i class="fa fa-ponterest"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- team area end -->
    <!-- call-action area start -->
    <section class="call-to-action ptb--100" id="download">
        <div class="container">
            <div class="section-title text-white">
                <h2>Our Amazing Team</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="download-btns btn-area text-center">
                <a href="#"><i class="fa fa-apple"></i>android story</a>
                <a href="#"><i class="fa fa-windows"></i>Windows story</a>
                <a href="#"><i class="fa fa-android"></i>android story</a>
            </div>
        </div>
    </section>
    <!-- call-action area end -->
    <!-- blog area start -->
    <section class="blog-post ptb--100" id="blog">
        <div class="container">
            <div class="section-title">
                <h2>Latest News</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-6 col-xs-12 col-6">
                    <div class="single-post">
                        <a href="blog.html"><img src="{{asset('landing-page/img/blog/blog-post-img.jpg')}}" alt="blog image"></a>
                        <div class="blog-meta">
                            <ul>
                                <li><i class="fa fa-user"></i>John</li>
                                <li><i class="fa fa-comment"></i>Comments</li>
                                <li><i class="fa fa-calendar"></i>21 Feb 2018</li>
                            </ul>
                        </div>
                        <h2><a href="blog.html">There are many variations</a></h2>
                        <p>Lorem ipsum dolor sit amet,ut consectetur adipisicing elit,eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 col-xs-12 col-6">
                    <div class="single-post">
                        <a href="blog.html"><img src="{{asset('landing-page/img/blog/blog-post-img1.jpg')}}" alt="blog image"></a>
                        <div class="blog-meta">
                            <ul>
                                <li><i class="fa fa-user"></i>John</li>
                                <li><i class="fa fa-comment"></i>Comments</li>
                                <li><i class="fa fa-calendar"></i>21 Feb 2018</li>
                            </ul>
                        </div>
                        <h2><a href="blog.html">There are many variations</a></h2>
                        <p>Lorem ipsum dolor sit amet,ut consectetur adipisicing elit,eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 col-xs-12 col-6">
                    <div class="single-post">
                        <a href="blog.html"><img src="{{asset('landing-page/img/blog/blog-post-img2.jpg')}}" alt="blog image"></a>
                        <div class="blog-meta">
                            <ul>
                                <li><i class="fa fa-user"></i>John</li>
                                <li><i class="fa fa-comment"></i>Comments</li>
                                <li><i class="fa fa-calendar"></i>21 Feb 2018</li>
                            </ul>
                        </div>
                        <h2><a href="blog.html">There are many variations</a></h2>
                        <p>Lorem ipsum dolor sit amet,ut consectetur adipisicing elit,eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- blog area end -->
    <!-- client area start -->
    <div class="clinet-area bg-gray ptb--100">
        <div class="container">
            <div class="client-carousel owl-carousel">
                <img src="{{asset('landing-page/img/client/client-img.png')}}" alt="client image">
                <img src="{{asset('landing-page/img/client/client-img1.png')}}" alt="client image">
                <img src="{{asset('landing-page/img/client/client-img2.png')}}" alt="client image">
                <img src="{{asset('landing-page/img/client/client-img3.png')}}" alt="client image">
                <img src="{{asset('landing-page/img/client/client-img1.png')}}" alt="client image">
            </div>
        </div>
    </div>
    <!-- client area end -->
    <!-- contact area start -->
    <section class="contact-area ptb--100" id="contact">
        <div class="container">
            <div class="section-title">
                <h2>Contact Us</h2>
                <p>Nemo enim ipsam voluptatem quia voluptas sit</p>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="contact-form">
                        <form action="#">
                            <input type="text" name="name" placeholder="Enter Your Name">
                            <input type="text" name="email" placeholder="Enter Your Email">
                            <textarea name="msg" id="msg" placeholder="Your Message "></textarea>
                            <input type="submit" value="Send" id="send">
                        </form>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="contact_info">
                        <div class="s-info">
                            <i class="fa fa-map-marker"></i>
                            <div class="meta-content">
                                <span>17 Bath Rd, Heathrow, Longford,Hounslow</span>
                                <span>TW7 1AB, UK</span>
                            </div>
                        </div>
                        <div class="s-info">
                            <i class="fa fa-mobile"></i>
                            <div class="meta-content">
                                <span>+0123 456 789 78</span>
                                <span>+0123 456 789 78</span>
                            </div>
                        </div>
                        <div class="s-info">
                            <i class="fa fa-paper-plane"></i>
                            <div class="meta-content">
                                <span>Support@domain.com</span>
                                <span>Example@Gmail.com</span>
                            </div>
                        </div>
                        <div class="c-social">
                            <ul>
                                <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                                <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                                <li><a href="#"><i class="fa fa-behance"></i></a></li>
                                <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- contact area end -->
    <!-- footer area start -->
    <footer>
        <div class="footer-area">
            <div class="container">
                <p>Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved</p>
            </div>
        </div>
    </footer>
    <!-- footer area end -->

    <!-- Scripts -->
    <script src="{{mix('landing-page/js/theme.js')}}"></script>
</body>

</html>