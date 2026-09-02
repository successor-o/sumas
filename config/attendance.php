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

    /*
    | Minimum match confidence between the student's enrolled FaceNet embedding
    | and their live check-in scan for the identity to be accepted (0..1).
    | Confidence is derived from Euclidean distance (1 - distance) — the metric
    | face-api.js's FaceRecognitionNet is actually trained for. Genuine
    | same-person distance is usually below 0.6 (confidence above 0.4);
    | different people are usually well above that distance. 0.45 keeps a
    | little headroom for webcam/lighting differences while still rejecting
    | impostors.
    */
    'face_similarity_threshold' => (float) env('ATTENDANCE_FACE_SIMILARITY_THRESHOLD', 0.45),

];
