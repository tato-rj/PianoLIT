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
                    <input type="hidden" name="enabled" value="0">
                    <div class="d-flex align-items-center">
                        @toggle(['toggle' => $sending[$key], 'autoToggle' => false, 'name' => 'enabled', 'inputValue' => '1', 'ariaLabel' => 'Automatic sending: '.$email['name'], 'submitOnChange' => true])
                    </div>
                    <noscript>@include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Save']])</noscript>
                </form>
            </td>
            <td><div class="admin-emails__actions">
                @include('admin.components.table-action', ['tableAction' => ['url' => route('admin.emails.preview', $key), 'label' => 'Browser', 'icon' => 'eye', 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer', 'aria-label' => 'Preview '.$email['name'].' in browser (opens a new tab)']]])
                @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Email', 'icon' => 'mail', 'attributes' => ['form' => 'email-preview-send', 'name' => 'email_key', 'value' => $key, 'aria-label' => 'Send preview of '.$email['name']]]])
            </div></td>
        </tr>
        @endforeach
    @endcomponent

    <p class="admin-emails__note">Free-pick, birthday and course emails belong to subscriptions and are outside this automatic-email catalog. This page covers user-facing messages; internal admin reports are separate. Stripe and Apple receipts follow their provider settings.</p>
</div></div>
@endsection
