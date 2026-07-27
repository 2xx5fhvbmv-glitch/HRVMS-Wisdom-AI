<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

class MoveLeaveAttachmentsToPublicPath extends Migration
{
    /**
     * Leave attachments uploaded through the mobile API were stored under
     * storage/app/uploads/leave_attachments (not web-accessible), while the
     * URLs sent to the app point at public/uploads/leave_attachments. Move
     * every stranded file into public/ so those URLs resolve.
     *
     * @return void
     */
    public function up()
    {
        $source      = storage_path('app/uploads/leave_attachments');
        $destination = public_path('uploads/leave_attachments');

        if (!File::isDirectory($source)) {
            return;
        }

        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $target   = $destination . DIRECTORY_SEPARATOR . $relative;

            if (File::exists($target)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
        }
    }

    /**
     * Files are copied (originals left in storage/app), so nothing to undo.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
