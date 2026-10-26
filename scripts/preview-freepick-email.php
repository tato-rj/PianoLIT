<?php

// Export all four redesigned emails using the welcome exporter's isolated boot.
require __DIR__.'/preview-welcome-emails.php';

$piece = new App\Piece([
    'id' => 0, 'name' => 'Adagio Patetico', 'key' => 'C minor',
    'catalogue_name' => 'Op.', 'catalogue_number' => '61',
    'description' => 'A quiet melody, a warm singing tone, and a world of feeling in every phrase. Discover a lyrical side of Cécile Chaminade and let this week’s piece inspire your next practice session.',
    'cover_path' => 'preview/freepick-cover.jpg', 'score_path' => 'preview/score.pdf',
    'audio_path' => 'preview/recording.mp3', 'audio_path_rh' => 'preview/right-hand.mp3',
]);
$piece->setRelation('composer', new App\Composer([
    'name' => 'Cécile Chaminade', 'cover_path' => 'preview/freepick-composer.jpg',
]));
$piece->setRelation('tags', collect([new App\Tag(['name' => 'advanced', 'type' => 'level'])]));
$mail = new class($piece) extends App\Mail\FreePickEmail {
    public function __construct($piece)
    {
        $this->piece = $piece;
        $this->subscription = null;
        $this->list = new App\EmailList(['name' => App\EmailList::FREE_PICK]);
        $this->list_id = 'preview';
        $this->videoCommentsAvailable = true;
        $this->latestPieces = [
            ['title' => 'Prelude in E minor', 'composer' => 'Frédéric Chopin', 'image' => 'https://pianolit.com'.mix('images/emails/welcome/preview-prelude.jpg'), 'url' => '#email-preview'],
            ['title' => 'Arabesque No. 1', 'composer' => 'Claude Debussy', 'image' => 'https://pianolit.com'.mix('images/emails/welcome/preview-arabesque.jpg'), 'url' => '#email-preview'],
            ['title' => 'Bagatelle in A minor', 'composer' => 'Ludwig van Beethoven', 'image' => 'https://pianolit.com'.mix('images/emails/welcome/preview-bagatelle.jpg'), 'url' => '#email-preview'],
        ];
        $this->tutorials = [
            ['type' => 'Performance', 'video_url' => 'https://example.com/performance.mp4'],
            ['type' => 'Synthesia', 'video_url' => 'https://example.com/synthesia.mp4'],
        ];
    }
};
$mail->build();
$mail->with('pieceUrl', '#email-preview');
$renderer = clone app(Illuminate\Mail\Markdown::class);
$renderer->theme($mail->theme);
$data = $mail->buildViewData();
$html = (string) $renderer->render($mail->markdown, $data);
$text = (string) $renderer->renderText($mail->markdown, $data);
$images = [
    'freepick-cover.jpg' => storage_path('app/public/piece/cover_image/pianolit-arabesque-no1-op61-0619.jpg'),
    'freepick-composer.jpg' => storage_path('app/public/composer/cover_image/pianolit-cecile-chaminade-8811.jpg'),
];
foreach ($images as $name => $source) {
    // Optional local public artwork; a fresh checkout falls back to existing period art.
    if (!is_file($source)) $source = resource_path('images/backgrounds/periods/romantic/01.jpg');
    copy($source, $output.'/assets/'.$name);
}
$html = preg_replace('#https://pianolit\\.com/images/emails/welcome/([^"?]+)\\?id=[a-f0-9]+#', 'assets/$1', $html);
$html = str_replace('https://pianolit.com/images/emails/youtube.png', 'assets/youtube.png', $html);
$html = str_replace('https://pianolit.com/storage/preview/', 'assets/', $html);
file_put_contents($output.'/freepick.html', $html);
file_put_contents($output.'/freepick.txt', $text);
echo $output.'/freepick.html'.PHP_EOL;
