@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid admin-emails">
    @include('admin.components.page.title', ['title' => 'Emails', 'subtitle' => 'Automatic messages for accounts, purchases and requests.'])

    @component('admin.components.table', [
        'id' => 'automatic-emails', 'label' => 'Automatic emails', 'items' => $emails,
        'description' => 'Control automatic delivery for everyone. Preview sends work even when sending is off. Turning off account emails also stops their verification and password-reset messages.',
        'columns' => ['Email', 'Sent when', 'Sending', ['label' => 'Preview actions', 'class' => 'admin-emails__action-heading']],
    ])
        @slot('toolbar')
            @include('admin.pages.emails.send-form')
        @endslot
        @foreach($emails as $key => $email)
        <tr>
            <td><span class="admin-table__primary">{{ $email['name'] }}</span><small>{{ $email['category'] }}</small></td>
            <td class="admin-emails__trigger">{{ $email['trigger'] }}</td>
            <td>
                <form method="POST" action="{{ route('admin.emails.sending', $key) }}">
                    @csrf
                    @method('PATCH')
                    <button class="admin-emails__switch" type="submit" role="switch" aria-checked="{{ $sending[$key] ? 'true' : 'false' }}" aria-label="Automatic sending: {{ $email['name'] }}" name="enabled" value="{{ $sending[$key] ? '0' : '1' }}">
                        <span class="admin-emails__switch-track" aria-hidden="true"></span>
                        <span>{{ $sending[$key] ? 'On' : 'Off' }}</span>
                    </button>
                </form>
            </td>
            <td><div class="admin-emails__actions">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.emails.preview', $key) }}" target="_blank" rel="noopener noreferrer" aria-label="Preview {{ $email['name'] }} in browser (opens a new tab)">Preview in browser</a>
                <button class="btn btn-sm btn-primary" type="submit" form="email-preview-send" name="email_key" value="{{ $key }}" aria-label="Send preview of {{ $email['name'] }}">Send preview</button>
            </div></td>
        </tr>
        @endforeach
    @endcomponent

    <p class="admin-emails__note">Newsletter, free-pick, birthday and course emails belong to subscriptions and are outside this automatic-email catalog. This page covers user-facing messages; internal admin reports are separate. Stripe and Apple receipts follow their provider settings.</p>
</div></div>
@endsection
