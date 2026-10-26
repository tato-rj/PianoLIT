<?php

namespace App\Services\Admin;

/** Fictional data shared by the admin catalog and isolated HTML exporter. */
class WeeklySnapshotPreview
{
    public function data(): array
    {
        $snapshot = [
            'period' => ['start' => '2026-10-18', 'end' => '2026-10-24', 'label' => 'Oct 18–24, 2026 · Sample data', 'previous_label' => 'Oct 11–17', 'as_of' => 'Oct 25, 10:30 PM EDT'],
            'new_accounts' => ['total' => 48, 'previous' => 36, 'change' => 12],
            'active_accounts' => ['total' => 326, 'previous' => 289, 'change' => 37],
            'origins' => ['webapp' => 21, 'ios' => 19, 'web' => 8, 'other' => 0],
            'returning' => ['total' => 204, 'base' => 289, 'percent' => 71],
            'activated' => ['total' => 32, 'base' => 48, 'percent' => 67],
            'days' => [
                ['label' => 'Sun', 'date' => '2026-10-18', 'total' => 76], ['label' => 'Mon', 'date' => '2026-10-19', 'total' => 92],
                ['label' => 'Tue', 'date' => '2026-10-20', 'total' => 84], ['label' => 'Wed', 'date' => '2026-10-21', 'total' => 108],
                ['label' => 'Thu', 'date' => '2026-10-22', 'total' => 97], ['label' => 'Fri', 'date' => '2026-10-23', 'total' => 116],
                ['label' => 'Sat', 'date' => '2026-10-24', 'total' => 101],
            ],
            'web' => ['active' => 189, 'explorers' => 154, 'players' => 113, 'readers' => 87, 'savers' => 42],
            'ios' => ['active' => 172, 'requests' => 2436, 'piece_requests' => 684, 'search_requests' => 219],
            'members' => ['total' => 124, 'engaged' => 89, 'percent' => 72, 'active' => 102, 'trial' => 16, 'ending' => 6, 'review' => 2],
            'pieces' => [
                ['id' => 0, 'title' => 'Prelude in E minor, Op. 28 No. 4 · Frédéric Chopin', 'cover' => 'preview/preview-prelude.jpg', 'total' => 64, 'web' => 42, 'ios' => 28],
                ['id' => 0, 'title' => 'Arabesque No. 1 · Claude Debussy', 'cover' => 'preview/preview-arabesque.jpg', 'total' => 51, 'web' => 31, 'ios' => 24],
                ['id' => 0, 'title' => 'Bagatelle in A minor · Ludwig van Beethoven', 'cover' => 'preview/preview-bagatelle.jpg', 'total' => 39, 'web' => 22, 'ios' => 20],
            ],
        ];
        foreach ($snapshot['pieces'] as &$piece) {
            $piece['url'] = '#email-preview';
            $piece['image'] = (string) mix('images/emails/welcome/'.basename($piece['cover']));
        }
        unset($piece);
        return $snapshot;
    }
}
