@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid admin-emails">
    @include('admin.components.page.title', ['title' => 'Emails', 'subtitle' => 'Manage automatic emails by purpose, from account signup to order delivery.'])

    <p class="admin-emails__note">Each switch controls automatic delivery for that email. Browser and email previews work even when sending is off.</p>
    @include('admin.pages.emails.send-form')

    <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Email categories">
        @foreach($sections as $section)
        <a class="btn btn-secondary btn-sm" href="#{{ $section['id'] }}-title">{{ $section['title'] }}</a>
        @endforeach
    </nav>

    @foreach($sections as $section)
    @component('admin.components.table', [
        'id' => $section['id'], 'title' => $section['title'], 'items' => $section['emails'],
        'description' => $section['description'],
        'columns' => ['Email', 'Sent when', 'Sending', ['label' => 'Preview actions', 'class' => 'admin-emails__action-heading']],
    ])
        @foreach($section['emails'] as $key => $email)
        <tr>
            <td><span class="admin-table__primary">{{ $email['name'] }}</span></td>
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
    @endforeach

    <p class="admin-emails__note">Free Pick and course emails follow subscription settings. Stripe and Apple receipts follow their provider settings.</p>
</div></div>
@endsection
