<?php

use Illuminate\Database\Seeder;
use App\Shop\eScore;
use App\Admin;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $escore = eScore::create([
            "slug" => "piano-solos-book-1",
            "title" => "Piano Solos Book 1",
            "author" => null,
            "subtitle" => "The Piano Solos series is here to supplement and reinforce fundamental skills that pianists need to develop at each step of their advancement. These books may be used with any piano course or method.",
            "description" => "<h3>About this edition</h3>\r\n<p>The pieces found in this book have been edited according to their earliest sources: either the composer&rsquo;s own manuscript was used or the earliest surviving edition. The Baroque and early classical works required some attention to suggested fingering and dynamics, but overall the scores are presented as close as possible to their original sources.</p>\r\n<p>As a valuable addition to this list, you will also find a number of recommended pieces that are not in the public domain. Although we cannot include those scores here, we provide video recordings and a direct link to purchase them. These are unique pieces that are invaluable for beginner pianists.</p>\r\n<p>&nbsp;</p>\r\n<h3>Who's it good for?</h3>\r\n<p>The works included here range from late elementary to beginner levels. This is not a method book, but rather a collection of pieces that explore key fundamental skills in an original and inspiring way.</p>\r\n<p>&nbsp;</p>\r\n<h3>Table of contents</h3>\r\n<ol>\r\n<li>Clover Blossoms by Florence Price</li>\r\n<li>Melody in C by Thomas Dunhill</li>\r\n<li>In May Op.575 No.2 by Franz Behr</li>\r\n<li>Roly Poly by Florence Price</li>\r\n<li>The Little Beggar Op.103 No.2 by M&eacute;lanie Bonis</li>\r\n<li>Spinning Top Op.103 No.1 by M&eacute;lanie Bonis</li>\r\n<li>Swaying Branches by Thomas Dunhill</li>\r\n<li>Tarantella Op.14 No.8 by Frank Lynes</li>\r\n<li>Sonatina in C by William Duncombe</li>\r\n<li>Night Journey Op.82 No.65 by Cornelius Gurlitt</li>\r\n<li>Autumn Rain Op.32 No.5 by Alexander Goedicke</li>\r\n<li>Madrigal Op.103 No.16 by M&eacute;lanie Bonis</li>\r\n<li>The Harp Player by Anton Krause</li>\r\n<li>Minuet in A minor by Johann Krieger</li>\r\n<li>Allegretto Op.82 No.47 by Cornelius Gurlitt</li>\r\n<li>The Flea Op.103 No.17 by M&eacute;lanie Bonis</li>\r\n</ol>",
            "pdf_path" => "app/escores/pdf/pianolit-test-2791.pdf",
            "audio_path" => null,
            "cover_path" => "escore/cover_image/pianolit-test-2698.jpg",
            "mockup_path" => "escore/mockup_image/pianolit-test-mockup-2942.jpg",
            "score" => 0,
            "pages_count" => 29,
            "price" => 5,
            "discount" => null,
            "creator_id" => Admin::first()->id,
            "published_at" => now()
        ]);
    }
}
