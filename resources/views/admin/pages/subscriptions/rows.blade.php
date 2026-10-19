@foreach($subscriptions as $item)
<tr>
    <td><input type="checkbox" class="form-check-input" name="ids[]" value="{{ $item->id }}" form="subscriptions-selection-form" aria-label="Select {{ $item->email }}"></td>
    <td class="text-nowrap">@if($item->created_at)<time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->toFormattedDateString() }}</time>@endif</td>
    <td>{{ $item->id }}</td>
    <td class="admin-table__account"><span class="admin-table__primary">{{ $item->email }}</span>
        @if($item->account_id)<small><a href="{{ route('admin.users.show', $item->account_id) }}" target="_blank" rel="noopener noreferrer">Show user @icon('external-link', ['mr' => 0])</a></small>
        @else<small>No user account</small>@endif
    </td>
    <td class="admin-table__account">{{ $item->origin_url ?: '—' }}</td>
    <td>{{ $item->form_id ?? '—' }}</td>
    <td class="text-nowrap">@include('admin.pages.subscriptions.actions')</td>
</tr>
@endforeach
