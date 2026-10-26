@component('mail::editorial.message', ['title' => 'PianoLIT weekly snapshot', 'preheader' => number_format($snapshot['new_accounts']['total']).' new accounts · '.number_format($snapshot['active_accounts']['total']).' active accounts · Your week in numbers', 'websiteUrl' => $websiteUrl, 'footerReason' => 'A weekly snapshot for PianoLIT administrators', 'internal' => true, 'emailClass' => 'snapshot-email'])
@component('mail::snapshot.header', ['websiteUrl' => $websiteUrl, 'title' => 'The week at PianoLIT', 'period' => $snapshot['period']])
@endcomponent

@component('mail::snapshot.metrics', ['title' => 'Growth & momentum', 'metrics' => [
    ['label' => 'New accounts', 'value' => $snapshot['new_accounts']['total'], 'change' => $snapshot['new_accounts']['change']],
    ['label' => 'Active accounts', 'value' => $snapshot['active_accounts']['total'], 'change' => $snapshot['active_accounts']['change']],
    ['label' => 'Came back', 'value' => $snapshot['returning']['total'], 'note' => $snapshot['returning']['percent'] === null ? 'No active accounts last week' : $snapshot['returning']['percent'].'% of last week’s active accounts'],
    ['label' => 'New users exploring', 'value' => $snapshot['activated']['total'], 'note' => $snapshot['activated']['percent'] === null ? 'No new accounts this week' : $snapshot['activated']['percent'].'% of new accounts explored music'],
], 'comparison' => 'Changes vs '.$snapshot['period']['previous_label'], 'origins' => $snapshot['origins']])
@endcomponent

@component('mail::snapshot.chart', ['title' => 'Active accounts by day', 'days' => $snapshot['days']])
@endcomponent

@component('mail::snapshot.platforms', ['title' => 'How people used PianoLIT', 'platforms' => [
    ['title' => 'Web app', 'value' => $snapshot['web']['active'], 'unit' => 'active accounts', 'rows' => [
        ['Opened a piece', $snapshot['web']['explorers']], ['Played audio / video', $snapshot['web']['players']],
        ['Opened a score', $snapshot['web']['readers']], ['Saved a piece', $snapshot['web']['savers']],
    ], 'note' => 'Distinct accounts per action'],
    ['title' => 'iOS', 'value' => $snapshot['ios']['active'], 'unit' => 'reported accounts', 'rows' => [
        ['Piece requests', $snapshot['ios']['piece_requests']], ['Search requests', $snapshot['ios']['search_requests']],
        ['All requests', $snapshot['ios']['requests']],
    ], 'note' => 'Recorded requests, not playback'],
]])
@endcomponent

@component('mail::snapshot.members', ['title' => 'Members', 'members' => $snapshot['members'], 'url' => $membershipsUrl, 'asOf' => $snapshot['period']['as_of'], 'labels' => ['active' => 'Active', 'trial' => 'Trials', 'ending' => 'Ending']])
@endcomponent

@component('mail::snapshot.pieces', ['title' => 'Music of the week', 'pieces' => $snapshot['pieces'], 'websiteUrl' => $websiteUrl])
@endcomponent

@component('mail::snapshot.closing', ['url' => $analyticsUrl, 'button' => 'Open analytics'])
Accounts are counted once across web app + iOS; guests and excluded accounts are omitted. iOS account IDs are reported by the app and unverified; requests do not confirm viewing or playback.
@endcomponent
@endcomponent
