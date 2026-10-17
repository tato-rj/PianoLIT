<?php

namespace App\Services\WebApp;

use App\{Piece, User};
use Illuminate\Support\Facades\DB;

class AccessPolicy
{
    const ROLES = ['guest', 'registered', 'member'];
    const LABELS = ['available' => 'Available', 'limited' => 'Limited', 'blocked' => 'Blocked'];
    const TONES = ['available' => 'green', 'limited' => 'warning', 'blocked' => 'red'];
    const MEDIA_SECONDS = 10;
    const SEARCH_RESULTS = 3;
    const FOLDERS = 2;
    const FOLDER_PIECES = 5;

    private $snapshot;

    public static function features()
    {
        $media = ['available', 'limited', 'blocked'];
        $binary = ['available', 'blocked'];
        $piece = [
            'performance' => ['label' => 'Performance video', 'options' => $media],
            'synthesia' => ['label' => 'Synthesia', 'options' => $media],
            'score' => ['label' => 'Read the score', 'options' => $binary],
            'exports' => ['label' => 'Download & print', 'options' => $binary],
            'annotations' => ['label' => 'Read, add and save annotations', 'options' => $binary, 'account' => true],
            'audio' => ['label' => 'Audio', 'options' => $media],
        ];
        return [
            'free_pick' => $piece,
            'regular' => $piece,
            'account' => [
                'search' => ['label' => 'Search results', 'options' => $media],
                'collections' => ['label' => 'Collections', 'options' => $media],
                'folders' => ['label' => 'Favorite folders', 'options' => $media, 'account' => true],
                'folder_pieces' => ['label' => 'Pieces per favorite folder', 'options' => $media, 'account' => true],
                'personalization' => ['label' => 'Personal history and suggestions', 'options' => $binary, 'account' => true],
                'escores' => ['label' => 'Create eScores', 'options' => $binary, 'account' => true],
            ],
        ];
    }

    public static function defaults()
    {
        $rules = [];
        foreach (self::features() as $scope => $features) {
            foreach ($features as $feature => $definition) {
                $guest = 'blocked';
                $registered = 'available';
                if (in_array($feature, ['performance', 'synthesia', 'audio'], true)) {
                    $guest = $scope === 'free_pick' ? 'limited' : 'blocked';
                    $registered = $scope === 'free_pick' ? 'available' : 'limited';
                } elseif ($feature === 'search') $guest = 'limited';
                elseif ($feature === 'collections') $registered = 'limited';
                elseif (in_array($feature, ['exports', 'escores'], true) || ($scope === 'regular' && in_array($feature, ['score', 'annotations'], true))) $registered = 'blocked';
                $rules[$scope][$feature] = ['guest' => $guest, 'registered' => $registered, 'member' => 'available'];
            }
        }
        return $rules;
    }

    public function snapshot()
    {
        if ($this->snapshot === null) {
            $record = DB::table('web_access_policies')->where('id', 1)->first();
            $rules = $record ? json_decode($record->rules, true) : [];
            $this->snapshot = ['revision' => $record ? (int) $record->revision : 0, 'rules' => array_replace_recursive(self::defaults(), $rules ?: [])];
        }
        return $this->snapshot;
    }

    // One atomic document prevents partially applied access matrices. A revision
    // protects another administrator's changes from an older open tab.
    public function save(array $rules, int $revision, int $adminId)
    {
        $saved = DB::transaction(function () use ($rules, $revision, $adminId) {
            DB::table('web_access_policies')->insertOrIgnore([
                'id' => 1, 'revision' => 0, 'rules' => json_encode(self::defaults()), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $row = DB::table('web_access_policies')->where('id', 1)->lockForUpdate()->first();
            if ((int) $row->revision !== $revision) return false;
            DB::table('web_access_policies')->where('id', 1)->update([
                'rules' => json_encode($rules), 'revision' => $revision + 1, 'updated_by' => $adminId, 'updated_at' => now(),
            ]);
            return true;
        });
        $this->snapshot = null;
        return $saved;
    }

    public function role(?User $user)
    {
        return !$user ? 'guest' : ($user->hasActiveSubscription() ? 'member' : 'registered');
    }

    public function state(string $feature, ?User $user, ?Piece $piece = null)
    {
        // The super-user override is unconditional, including expired accounts.
        if ($user && $user->super_user) return 'available';
        $scope = $piece ? ($piece->is_free ? 'free_pick' : 'regular') : 'account';
        if ($feature === 'tutorial') $feature = 'performance';
        $definition = self::features()[$scope][$feature] ?? null;
        if (!$definition) return 'blocked';
        if (!$user && !empty($definition['account'])) return 'blocked';
        $state = $this->snapshot()['rules'][$scope][$feature][$this->role($user)];
        return in_array($state, $definition['options'], true) ? $state : 'blocked';
    }

    public function available(string $feature, ?User $user, ?Piece $piece = null)
    {
        if ($feature === 'annotations' && (!$user || !$this->available('score', $user, $piece))) return false;
        return $this->state($feature, $user, $piece) === 'available';
    }

    public function mediaLimit(string $feature, ?User $user, Piece $piece)
    {
        $state = $this->state($feature, $user, $piece);
        return $state === 'available' ? null : ($state === 'limited' ? self::MEDIA_SECONDS : 0);
    }

    public function cap(string $feature, ?User $user)
    {
        $state = $this->state($feature, $user);
        if ($state === 'available') return null;
        if ($state === 'blocked') return 0;
        return ['search' => self::SEARCH_RESULTS, 'collections' => 3, 'folders' => self::FOLDERS, 'folder_pieces' => self::FOLDER_PIECES][$feature] ?? 0;
    }

    public function promptTarget(?User $user, string $guestAction = 'score', ?Piece $piece = null)
    {
        if (!$user) return '#signup-'.(in_array($guestAction, ['performance', 'synthesia'], true) ? 'video' : $guestAction).'-modal';
        if ($this->role($user) === 'member') return '#access-unavailable-modal';
        $feature = ['video' => 'performance', 'score-export' => 'exports', 'favorites' => 'folder_pieces', 'escore' => 'escores'][$guestAction] ?? $guestAction;
        $scope = $piece ? ($piece->is_free ? 'free_pick' : 'regular') : 'account';
        $member = $this->snapshot()['rules'][$scope][$feature]['member'] ?? null;
        if ($member !== null && $member !== 'available') return '#access-unavailable-modal';
        return $feature === 'collections' ? '#collection-upgrade-modal' : '#piece-upgrade-modal';
    }

    public function signupDescription(string $feature, ?Piece $piece, string $action)
    {
        $scope = $piece ? ($piece->is_free ? 'free_pick' : 'regular') : 'account';
        $rules = $this->snapshot()['rules'][$scope][$feature] ?? [];
        if ($feature === 'annotations' && $piece) {
            foreach (['registered', 'member'] as $role) {
                if ($this->snapshot()['rules'][$scope]['score'][$role] !== 'available') $rules[$role] = 'blocked';
            }
        }
        if (!$rules && !$piece) return 'Create your free PianoLIT account to explore more piano music.';
        if ($feature === 'collections' && ($rules['registered'] ?? 'blocked') === 'limited') {
            return 'Create your free PianoLIT account to preview 3 pieces in each collection.';
        }
        if (($rules['registered'] ?? 'blocked') === 'available') return 'Create your free PianoLIT account to '.$action.'.';
        if (($rules['registered'] ?? 'blocked') === 'limited') {
            if (in_array($feature, ['performance', 'synthesia', 'audio'], true)) return 'Create your free PianoLIT account to enjoy 10-second media previews.';
            return 'Create your free PianoLIT account to '.$action.' within your account limits.';
        }
        if (($rules['member'] ?? 'blocked') === 'available') return 'Create your free PianoLIT account first; Premium lets you '.$action.'.';
        return 'Create your free PianoLIT account to explore more piano music.';
    }
}
