<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Throwable;

class UploadAttachments
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    /**
     * Store the uploaded files under public storage/{folder} and record them through the given relation.
     *
     * @param  HasMany<Model, Model>  $attachments
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, Model>
     */
    public function upload(HasMany $attachments, string $folder, User $uploader, array $files): Collection
    {
        $rows = collect($files)->map(function (UploadedFile $file) use ($folder, $uploader): array {
            $filename = strtolower(Str::ulid()).'.'.$file->getClientOriginalExtension();
            $filepath = "{$folder}/{$filename}";

            $file->storeAs('public', $filepath);

            $thumbFilepath = $this->generateThumb($file, $folder, $filename);

            return [
                'user_id' => $uploader->id,
                'name' => $file->getClientOriginalName(),
                'path' => "/storage/{$filepath}",
                'thumb' => $thumbFilepath ? "/storage/{$thumbFilepath}" : null,
                'type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        });

        return $attachments->createMany($rows);
    }

    private function generateThumb(UploadedFile $file, string $folder, string $filename): ?string
    {
        if (! in_array(strtolower($file->getClientOriginalExtension()), self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        try {
            $thumbFilepath = "{$folder}/thumbs/{$filename}";

            Storage::put("public/{$thumbFilepath}", Image::make($file->get())->fit(100, 100)->encode(null, 75));

            return $thumbFilepath;
        } catch (Throwable) {
            return null;
        }
    }
}
