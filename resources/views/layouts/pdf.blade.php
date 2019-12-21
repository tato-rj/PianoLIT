<!DOCTYPE html>
<html>
<head>
	<title>{{$title}}</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	@stack('header')
</head>
<body>

	@yield('content')

    <script type="text/php">
        if ( isset($pdf) ) {
            $x = 528;
            $y = 742;
            $text = "{PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->get_font("serif", "light");
            $size = 10;
            $color = #3b4044;
            $word_space = 0.0;  //  default
            $char_space = 0.0;  //  default
            $angle = 0.0;   //  default
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
</body>
</html>