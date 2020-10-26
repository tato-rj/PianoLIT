percentage = function(piece, total) {
    return parseInt(piece * 100 / total);
}

randomBetween = function(first, last) {
	return Math.floor(Math.random() * last) + first;
}