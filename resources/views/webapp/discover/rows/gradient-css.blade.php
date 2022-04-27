  /* Fallback (could use .jpg/.png alternatively) */
  background-color: {{gradient($color)[0]}};

  /* Safari 4, Chrome 1-9, iOS 3.2-4.3, Android 2.1-3.0 */
  background-image:
    -webkit-gradient(linear, left top, right top, from({{gradient($color)[0]}}), to({{gradient($color)[1]}}));

  /* Safari 5.1, iOS 5.0-6.1, Chrome 10-25, Android 4.0-4.3 */
  background-image:
    -webkit-linear-gradient(left, {{gradient($color)[0]}}, {{gradient($color)[1]}});

  /* Firefox 3.6 - 15 */
  background-image:
    -moz-linear-gradient(left, {{gradient($color)[0]}}, {{gradient($color)[1]}});

  /* Opera 11.1 - 12 */
  background-image:
    -o-linear-gradient(left, {{gradient($color)[0]}}, {{gradient($color)[1]}});

  /* Opera 15+, Chrome 25+, IE 10+, Firefox 16+, Safari 6.1+, iOS 7+, Android 4.4+ */
  background-image:
    linear-gradient(to right, {{gradient($color)[0]}}, {{gradient($color)[1]}});