@php
	$shapeTypes = ['circle', 'ring', 'blob', 'bar', 'triangle', 'arch'];
	shuffle($shapeTypes);
@endphp
@foreach(array_slice($shapeTypes, 0, mt_rand(3, 4)) as $shapeIndex => $shapeType)
<span class="discover-level-card__shape discover-level-card__shape--{{ $shapeType }}" style="--shape-size: {{ mt_rand(96, 160) }}px; --shape-x: {{ [10, 55, 100, 40][$shapeIndex] + mt_rand(-18, 18) }}%; --shape-y: {{ mt_rand(10, 90) }}%; --shape-angle: {{ mt_rand(-75, 75) }}deg; --shape-opacity: {{ mt_rand(7, 15) / 100 }};"></span>
@endforeach
