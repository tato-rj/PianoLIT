<?php

// The shared exporter supplies a credential-free in-memory boot. No mail sends.
require __DIR__.'/preview-freepick-email.php';

function exportCustomerEmail($name, $html, $text, $output)
{
    $html = preg_replace_callback('#https://pianolit\.com/(images/[^"?]+)(?:\?[^"\s]*)?#', function ($match) use ($output) {
        $source = public_path($match[1]);
        if (!is_file($source)) return $match[0];
        $asset = 'assets/'.$match[1];
        if (!is_dir(dirname($output.'/'.$asset))) mkdir(dirname($output.'/'.$asset), 0755, true);
        copy($source, $output.'/'.$asset);
        return $asset;
    }, $html);
    file_put_contents($output.'/'.$name.'.html', $html);
    file_put_contents($output.'/'.$name.'.txt', $text);
    echo $output.'/'.$name.'.html'.PHP_EOL;
}

$catalog = app(App\Services\Admin\AutomaticEmails::class);
foreach ($catalog->all()->keys() as $key) {
    $sample = $catalog->render($key);
    exportCustomerEmail($key, $sample['html'], $sample['text'], $output);
}

$list = new App\EmailList(['id' => 0, 'name' => App\EmailList::NEWSLETTER]);
$lesson = new App\CrashCourse\CrashCourseLesson(['subject' => 'Your first lesson', 'body' => '<p>Hi Jamie,</p><p>Welcome to your first lesson. Listen for the melody, then try playing it slowly at your piano.</p><p><a href="https://example.com/lesson">Watch the lesson</a></p>']);
$samples = [
    'course-lesson' => new App\Mail\CrashCourseEmail($lesson),
    'course-feedback' => new App\Mail\CrashCourseFeedbackEmail(new App\CrashCourse\CrashCourse(['title' => 'A sample piano course'])),
    'gift' => new App\Mail\Gift('#email-preview', new App\Subscription(['email' => 'sample@example.test'])),
    'newsletter' => new App\Mail\Newsletter\MonthlyDigestEmail('2026-10', collect([(object) ['title' => 'A new world to explore', 'hub' => 'Learn', 'type' => 'Tool', 'description' => 'Explore music theory at your own pace.', 'url' => '#email-preview']]), $list),
    'email-change' => new App\Mail\ProfileEmailChange('#email-preview'),
];
foreach ($samples as $name => $mail) {
    $mail->build();
    $markdown = clone app(Illuminate\Mail\Markdown::class);
    $markdown->theme($mail->theme ?: 'default');
    $data = $mail->buildViewData();
    exportCustomerEmail($name, (string) $markdown->render($mail->markdown, $data), (string) $markdown->renderText($mail->markdown, $data), $output);
}
