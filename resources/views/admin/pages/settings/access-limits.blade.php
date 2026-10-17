@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid admin-access-limits">
    @component('admin.components.page.title', [
        'title' => 'Access Limits',
        'subtitle' => 'Compare access for guests, registered users and paying members.',
    ])
        @foreach($sections as $section)
            <a href="#{{ $section['id'] }}-title" class="btn btn-sm btn-secondary">{{ $section['title'] }}</a>
        @endforeach
    @endcomponent

    <p class="text-muted mb-4">Guests have no account. Registered users have a free account without active membership. Paying members have an active membership.</p>

    <form method="POST" action="{{ route('admin.access-limits.update') }}" data-access-limits-form>
    @csrf
    @method('PUT')
    <input type="hidden" name="revision" value="{{ $revision }}">
    @if($errors->any())<div class="alert alert-red" role="alert">Please check your selections. {{ $errors->first() }}</div>@endif
    <p class="small text-muted">Click a pill to change access, then save. Limited: media 10 seconds · search 3 results · collections 3 pieces · 2 favorite folders · 5 pieces per folder.</p>

    @foreach($sections as $section)
        @component('admin.components.table', [
            'id' => $section['id'], 'title' => $section['title'], 'items' => $section['rows'],
            'description' => $section['description'], 'tableClass' => 'admin-access-limits__matrix',
            'columns' => ['Feature', 'Guest', 'Registered user', 'Paying member'],
        ])
            @foreach($section['rows'] as $row)
                <tr>
                    <td><span class="admin-table__primary">{{ $row['feature'] }}</span></td>
                    @foreach(['guest', 'registered', 'member'] as $account)
                        <td>@include('admin.pages.settings.access-limit-cell', ['cell' => $row[$account]])</td>
                    @endforeach
                </tr>
            @endforeach
        @endcomponent
    @endforeach

    @component('admin.components.table-save-actions')
        <button type="submit" class="btn btn-primary" data-save-limits>@icon('check')Save changes</button>
    @endcomponent
    </form>

    <div class="row">
        <div class="col-lg-6">
            <h2 class="h6">Saved markings stay saved</h2>
            <p class="small text-muted">Changing access keeps existing favorites and markings saved. Folder and piece limits prevent new additions above the cap; existing items remain visible.</p>
        </div>
        <div class="col-lg-6">
            <h2 class="h6">Membership exceptions</h2>
            <p class="small text-muted">Active trials and eligible cancellation grace periods use the paying-member column. Super users always have full access. Expired memberships use the registered-user column. Account-owned features require signup.</p>
        </div>
    </div>
    <p class="small text-muted mb-0">Score features apply to available public-domain PDFs. This overview covers the web app; existing public file URLs and iOS access are unchanged.</p>
</div></div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/admin-access-limits.js') }}"></script>
@endsection
