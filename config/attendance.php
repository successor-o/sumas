<?php

return [

    /*
    | How often the lecture QR token rotates (seconds). The previous token stays
    | valid for one extra rotation window so a student who just scanned the QR
    | still has time to open the page and check in.
    */
    'rotation_seconds' => (int) env('ATTENDANCE_TOKEN_ROTATION_SECONDS', 60),

    /*
    | Maximum distance (metres) from the lecture venue a student may be when
    | GPS check-in is enabled for a lecture.
    */
    'gps_radius_meters' => (float) env('ATTENDANCE_GPS_RADIUS_METERS', 200),

];
