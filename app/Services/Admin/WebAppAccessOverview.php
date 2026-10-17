<?php

namespace App\Services\Admin;

use App\Services\WebApp\AccessPolicy;

class WebAppAccessOverview
{
    public function sections(AccessPolicy $policy)
    {
        $rules = $policy->snapshot()['rules'];
        $sections = [
            'free_pick' => ['id' => 'free-pick', 'title' => 'Weekly free pick', 'description' => 'Access to the current weekly selection.'],
            'regular' => ['id' => 'regular-pieces', 'title' => 'Regular pieces', 'description' => 'Access to every piece outside the current free pick.'],
            'account' => ['id' => 'account-features', 'title' => 'Account & library', 'description' => 'These rules apply across the repertoire. Browsing and piece pages are always open.'],
        ];
        foreach (AccessPolicy::features() as $scope => $features) {
            $rows = [];
            foreach ($features as $feature => $definition) {
                $row = ['feature' => $definition['label']];
                foreach (AccessPolicy::ROLES as $role) {
                    $state = old('rules.'.$scope.'.'.$feature.'.'.$role, $rules[$scope][$feature][$role]);
                    $locked = $role === 'guest' && !empty($definition['account']);
                    if (!in_array($state, $definition['options'], true) || $locked) $state = $rules[$scope][$feature][$role];
                    $limitedNote = in_array($feature, ['performance', 'synthesia', 'audio'], true) ? '10-second preview' : (['search' => 'First 3 results', 'collections' => 'First 3 pieces per collection', 'folders' => 'Up to 2 folders', 'folder_pieces' => 'Up to 5 pieces per folder'][$feature] ?? '');
                    $notes = ['available' => '', 'limited' => $limitedNote, 'blocked' => ''];
                    $note = $locked ? '' : $notes[$state];
                    $row[$role] = ['state' => $state, 'label' => $locked ? 'Account required' : AccessPolicy::LABELS[$state], 'tone' => $locked ? 'muted' : AccessPolicy::TONES[$state], 'note' => $note, 'notes' => $notes, 'name' => 'rules['.$scope.']['.$feature.']['.$role.']', 'options' => $definition['options'], 'locked' => $locked, 'accessibleLabel' => $sections[$scope]['title'].' · '.$definition['label'].' · '.['guest' => 'Guest', 'registered' => 'Registered user', 'member' => 'Paying member'][$role]];
                }
                $rows[] = $row;
            }
            $sections[$scope]['rows'] = collect($rows);
        }
        return collect($sections)->values();
    }
}
