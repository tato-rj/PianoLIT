<?php

// Reuse the isolated exporter: no .env, real accounts, provider calls or mail.
require __DIR__.'/preview-welcome-emails.php';

$sample = app(App\Services\Admin\AutomaticEmails::class)->render('admin-weekly-snapshot');
$html = $sample['html'];
$text = $sample['text'];
$html = preg_replace('#https://pianolit\\.com/images/emails/welcome/([^"?]+)\\?id=[a-f0-9]+#', 'assets/$1', $html);
file_put_contents($output.'/weekly-snapshot.html', $html);
file_put_contents($output.'/weekly-snapshot.txt', $text);
echo $output.'/weekly-snapshot.html'.PHP_EOL;
