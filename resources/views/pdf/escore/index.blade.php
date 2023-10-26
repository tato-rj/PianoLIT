<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>eScore</title>

	<style>
	.page-break {
	    page-break-after: always;
	}

	#cover-page {
		margin-left: 44px;
		margin-right: 44px;
	}

	#cover-page h1 {
		font-size: 6rem;
		padding-top: 2%;
	}

	#cover-page h4 {
		font-size: 3rem;
		position: absolute;
		top: 310px;
	}

	.line {
		width: 100%;
		height: 1px;
		background: black;
		position: absolute;
		top: 380px;
	}

	#cover-page h5 {
		font-size: 2rem;
		position: absolute;
		top: 340px;
	}

	#credits {
		text-align: center;
		position: absolute;
		bottom: 40px;
		opacity: .8;
	}
 	</style>
</head>
<body>
	<div id="cover-page">
		<h1>{{$title}}</h1>
		<h4>{{$subtitle}}</h4>
		<div class="line"></div>
		<h5>{{$comment}}</h5>

		<div id="credits">
			<p style="padding-top: 10px;">created by Arthur Villar</p>
			<p>PianoLIT eScore</p>
		</div>
	</div>
	<div class="page-break"></div>
	<h1>Page 2</h1>
</body>
</html>