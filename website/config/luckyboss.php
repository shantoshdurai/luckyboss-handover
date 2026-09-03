<?php

return [


    /*
    |---------------------------------------------------------------------------
    | Profile photo uploads
    |---------------------------------------------------------------------------
    |
    | Candidates attach a photo from the camera or the device gallery. Limits
    | are enforced server-side; the client's own compression is a courtesy to
    | the user's data plan, never a security control.
    |
    | 'disk' is deliberately configurable: local disk today, swap to 's3' with
    | no code change once a bucket exists.
    |
    */

    'profile_photo' => [
        'disk' => env('LUCKYBOSS_PHOTO_DISK', 'public'),
        'path' => 'profile-photos',

        // Kilobytes. A phone camera JPEG is comfortably under this once the
        // client downscales; the ceiling stops someone posting a 40MB raw file.
        'max_kb' => (int) env('LUCKYBOSS_PHOTO_MAX_KB', 5120),

        // Longest edge, in pixels, after server-side downscaling.
        'max_dimension' => (int) env('LUCKYBOSS_PHOTO_MAX_DIMENSION', 1024),

        'mimes' => ['jpeg', 'jpg', 'png', 'webp'],
    ],

];
