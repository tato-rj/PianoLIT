<?php

namespace App\Services\Admin;

use App\EmailList;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailListTemplates
{
    private $files;
    private $viewsPath;

    public function __construct(Filesystem $files, $viewsPath = null)
    {
        $this->files = $files;
        $this->viewsPath = $viewsPath ?? resource_path('views');
    }

    public static function slug($name)
    {
        return $name === EmailList::FREE_PICK ? 'freepick' : Str::slug($name);
    }

    public function path(EmailList $list)
    {
        return $this->viewsPath.'/emails/lists/'.self::slug($list->name).'.blade.php';
    }

    public function exists(EmailList $list)
    {
        return self::slug($list->name) !== '' && $this->files->isFile($this->path($list));
    }

    public function create(array $data)
    {
        $list = new EmailList($data);
        $slug = self::slug($list->name);
        if (! preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) || strlen($slug) > 150) {
            throw ValidationException::withMessages(['name' => 'Choose a name containing letters or numbers.']);
        }
        foreach (EmailList::pluck('name') as $name) {
            if (self::slug($name) === $slug) {
                throw ValidationException::withMessages(['name' => 'A list already uses this name or email template.']);
            }
        }

        $path = $this->path($list);
        $created = false;
        try {
            // The existing Freepick template must survive deletion/recreation of its list.
            if ($list->name !== EmailList::FREE_PICK || ! $this->exists($list)) {
                $contents = $this->files->get(resource_path('stubs/email-list.blade.stub'));
                $this->files->ensureDirectoryExists(dirname($path));
                $stream = @fopen($path, 'x');
                if ($stream === false) {
                    if ($this->files->exists($path)) {
                        throw ValidationException::withMessages(['name' => 'An email template already uses this name. Choose another name.']);
                    }
                    throw new \RuntimeException('The email template could not be created.');
                }
                $created = true;
                try {
                    if (fwrite($stream, $contents) !== strlen($contents)) {
                        throw new \RuntimeException('The email template could not be written completely.');
                    }
                } finally {
                    fclose($stream);
                }
            }

            return DB::transaction(function () use ($list) {
                $list->save();
                return $list;
            });
        } catch (\Throwable $exception) {
            if ($created) $this->files->delete($path);
            throw $exception;
        }
    }
}
