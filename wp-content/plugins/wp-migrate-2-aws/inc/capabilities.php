<?php

add_filter('map_meta_cap', 'wpm2aws_map_meta_cap', 10, 4);

function wpm2aws_map_meta_cap($caps, $cap, $user_id, $args)
{
    $meta_caps = array(
        'wpm2aws_run_migration' => WPM2AWS_MIGRATE_CAPABILITY,
    );

    $meta_caps = apply_filters('wpm2aws_map_meta_cap', $meta_caps);

    $caps = array_diff($caps, array_keys($meta_caps));

    if (isset($meta_caps[$cap])) {
        $caps[] = $meta_caps[$cap];
    }

    return $caps;
}
