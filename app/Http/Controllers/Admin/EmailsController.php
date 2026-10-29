<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Admin\EmailPreview;
use App\Mail\AutomaticEmailSettings;
use App\Services\Admin\AutomaticEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class EmailsController extends Controller
{
    public function index(Request $request, AutomaticEmails $emails, AutomaticEmailSettings $settings)
    {
        return response()->view('admin.pages.emails.index', [
            'sections' => $emails->sections(), 'sending' => $settings->states(),
            'recipient' => $request->user('admin')->email,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function preview(AutomaticEmails $emails, $email)
    {
        return $this->content($emails, $email);
    }

    public function sending(Request $request, AutomaticEmails $emails, AutomaticEmailSettings $settings, $email)
    {
        $definition = $emails->find($email);
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $settings->setEnabled($email, (bool) $data['enabled'], $request->user('admin')->id);

        return redirect()->route('admin.emails.index')->with('status', e($definition['name']).' sending turned '.($data['enabled'] ? 'on' : 'off').'.');
    }

    public function content(AutomaticEmails $emails, $email)
    {
        return response($emails->render($email)['html'])
            ->header('Cache-Control', 'no-store, private')
            ->header('Content-Security-Policy', "sandbox; default-src 'none'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src https: http: data:; frame-ancestors 'self'");
    }

    public function send(Request $request, AutomaticEmails $emails)
    {
        $data = $request->validate([
            'email_key' => ['required', 'string', Rule::in($emails->all()->keys()->all())],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'return_to' => ['sometimes', 'string', Rule::in(['index', 'preview'])],
        ]);
        $destination = route('admin.emails.index');

        try {
            $sample = $emails->render($data['email_key']);
            Mail::to($data['email'])->send(new EmailPreview($sample['subject'], $sample['html'], $sample['text']));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect($destination)->withInput($data)->with('error', 'The preview could not be sent. Please try again.');
        }

        return redirect($destination)->withInput($data)->with('status', 'Preview sent to '.e($data['email']).'.');
    }
}
