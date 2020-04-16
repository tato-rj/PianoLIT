<meta name="keywords" content="{{$shareable['keywords']}}">
<meta name="twitter:card" value="{{$shareable['description']}}">
<meta property="og:site_name" content="PianoLIT" />
<meta property="og:title" content="{{$shareable['title']}}" />
<meta property="og:type" content="article" />
<meta property="og:url" content="{{url()->current()}}" />
<meta property="og:image" content="{{$shareable['thumbnail']}}" />
<meta property="og:image:width" content="400" />
<meta property="og:image:height" content="245" />
<meta property="og:description" content="{{$shareable['description']}}" />
<meta property="article:published_time" content="{{$shareable['created_at']}}">
<meta property="article:modified_time" content="{{$shareable['updated_at']}}">
<meta property="og:updated_time" content="{{$shareable['updated_at']}}">

<meta name="twitter:site" content="@litpiano">
<meta name="twitter:card" content="summary">
<meta name="twitter:image" content="{{$shareable['thumbnail']}}">
<meta name="twitter:title" content="{{$shareable['title']}}">
<meta name="twitter:description" content="{{$shareable['description']}}">
<meta name="twitter:app:country" content="US">
<meta name="twitter:app:name:iphone" content="PianoLIT">
<meta name="twitter:app:id:iphone" content="00000000">

<meta itemprop="name" content="{{$shareable['title']}}"/>
<meta itemprop="headline" content="{{$shareable['description']}}"/>
<meta itemprop="description" content="{{$shareable['description']}}"/>
<meta itemprop="image" content="{{$shareable['thumbnail']}}"/>
<meta itemprop="datePublished" content="{{$shareable['created_at']}}"/>
<meta itemprop="dateModified" content="{{$shareable['updated_at']}}" />
<meta itemprop="author" content="PianoLIT"/>

<link rel="canonical" href="{{url()->current()}}" />