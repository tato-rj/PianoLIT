{{-- Keep the original device artwork; replace its screen areas with real web captures. --}}
<svg class="d-block w-100" width="1200" height="713" style="height: auto" viewBox="0 0 1200 713" role="img" aria-label="{{ $description }}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    @if($slide === 1)
      <clipPath id="onboarding-desktop-screen">
        <path d="M347 60H1040V451H487V389H405V225H347Z" />
      </clipPath>
      <clipPath id="onboarding-tablet-screen">
        <path d="M112 260H385V390H353V624H112Z" />
      </clipPath>
    @elseif($slide === 2)
      <clipPath id="onboarding-laptop-screen">
        <path d="M154 79H1044V156H906V579H154Z" />
      </clipPath>
      <clipPath id="onboarding-collection-screen">
        <rect x="917" y="190" width="214" height="455" rx="20" />
      </clipPath>
    @else
      <clipPath id="onboarding-performance-screen">
        <rect x="286" y="387" width="385" height="269" />
      </clipPath>
      <clipPath id="onboarding-score-screen">
        <rect x="680" y="62" width="274" height="577" rx="22" />
      </clipPath>
    @endif
  </defs>

  <image href="{{ asset('images/webapp/carousel/slide-'.$slide.'.png') }}" width="1200" height="713" />

  @if($slide === 1)
    <image href="{{ asset('images/webapp/carousel/screens/discover-desktop.jpg') }}" x="347" y="60" width="693" height="391" preserveAspectRatio="none" clip-path="url(#onboarding-desktop-screen)" />
    <image href="{{ asset('images/webapp/carousel/screens/piece-tablet.jpg') }}" x="112" y="260" width="273" height="364" preserveAspectRatio="none" clip-path="url(#onboarding-tablet-screen)" />
    <image href="{{ asset('images/webapp/carousel/screens/collections-phone.jpg') }}" x="361" y="421" width="118" height="209" preserveAspectRatio="none" />
  @elseif($slide === 2)
    <image href="{{ asset('images/webapp/carousel/screens/collections-desktop.jpg') }}" x="154" y="79" width="890" height="500" preserveAspectRatio="none" clip-path="url(#onboarding-laptop-screen)" />
    <image href="{{ asset('images/webapp/carousel/screens/collection-phone.jpg') }}" x="917" y="190" width="214" height="455" preserveAspectRatio="none" clip-path="url(#onboarding-collection-screen)" />
  @else
    <g clip-path="url(#onboarding-performance-screen)">
      <rect x="286" y="387" width="615" height="269" fill="#000" />
      <image href="{{ asset('images/webapp/carousel/screens/media-landscape.jpg') }}" x="286" y="387" width="615" height="269" preserveAspectRatio="xMidYMid meet" />
    </g>
    <image href="{{ asset('images/webapp/carousel/screens/score-phone.jpg') }}" x="680" y="62" width="274" height="577" preserveAspectRatio="none" clip-path="url(#onboarding-score-screen)" />
  @endif
</svg>
