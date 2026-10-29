<?php

namespace App\Services\Metronome;

class SpokenVoices
{
    // Metadata only. No provider, audio processing, account data or mutable shared model.
    public function available(?string $directory = null): array
    {
        $directory = $directory ?? public_path('audio/metronome/spoken');
        $manifest = $this->read($directory.'/manifest.json');
        $definitions = $this->read(config_path('metronome-voices.json'));
        if (($manifest['version'] ?? null) !== 1 || ($manifest['beats'] ?? null) !== 4) return [];
        $entries = $manifest['voices'] ?? [];
        if (!is_array($entries)) return [];
        $published = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) $published[$entry['id']] = $entry;
        }
        $voices = [];
        foreach ($definitions['voices'] ?? [] as $definition) {
            $id = $definition['id'];
            $files = $published[$id]['files'] ?? [];
            if (!is_array($files) || count($files) !== 4) continue;
            foreach (array_values($files) as $index => $file) {
                if (!is_string($file) || !preg_match('/^'.preg_quote($id, '/').'-'.($index + 1).'-[a-f0-9]{16}\.wav$/D', $file)
                    || !is_file($directory.'/'.$file) || filesize($directory.'/'.$file) < 44
                    || filesize($directory.'/'.$file) > 58000) continue 2;
            }
            $voices[] = ['id' => $id, 'label' => $definition['label'], 'files' => array_map(function ($file) {
                return '/audio/metronome/spoken/'.$file;
            }, $files)];
        }
        return $voices;
    }

    private function read(string $path): array
    {
        if (!is_file($path) || filesize($path) > 65536) return [];
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }
}
