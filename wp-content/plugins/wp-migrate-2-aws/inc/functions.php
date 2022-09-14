<?php
/**
 * Retrieve the (likely) wp_content directory path,
 * based on where this plugin is located
 *
 * @return string wp_content directory path (likely)
 */
function wpm2aws_content_dir()
{
    $wpContentPath = false;
    
    $fullPath = dirname(__FILE__);
    $pathSeparator = '/';
    if (strpos($fullPath, '\\') !== false) {
        $pathSeparator = '\\';
    }
    $fullPathParts = explode($pathSeparator, $fullPath);

//    echo "<br><br>";
//    print_r($fullPathParts);
//    echo "<br><br>";
    
    if (!empty($fullPathParts)) {
        $partsInPath = count($fullPathParts);
    }
    $expectedContentDir = (!empty($fullPathParts[($partsInPath-(3+1))]) ? $fullPathParts[($partsInPath-(3+1))] : false);



    if (!empty($expectedContentDir) && $expectedContentDir === 'wp-content') {
        unset(
            $fullPathParts[($partsInPath-1)],
            $fullPathParts[($partsInPath-2)],
            $fullPathParts[($partsInPath-3)],
            $fullPathParts[($partsInPath-4)]
        );
        // ,
        // $fullPathParts[($partsInPath-5)]
        // print_r($fullPathParts);

        if (empty($fullPathParts)) {
            $wpContentPath = $pathSeparator;
        } else {
            $wpContentPath = implode($pathSeparator, $fullPathParts);
        }
    } else {
        $wpContentPath = 'test';
    }
    return $wpContentPath;
}


function wpm2awsFormatHtmlAtts($atts)
{
    $html = '';

    $prioritized_atts = array( 'type', 'name', 'value', 'target' );

    foreach ($prioritized_atts as $att) {
        if (isset($atts[$att])) {
            $value = trim($atts[$att]);
            $html .= sprintf(' %s="%s"', $att, esc_attr($value));
            unset($atts[$att]);
        }
    }

    foreach ($atts as $key => $value) {
        $key = strtolower(trim($key));

        if (! preg_match('/^[a-z_:][a-z_:.0-9-]*$/', $key)) {
            continue;
        }

        $value = trim($value);

        if ('' !== $value) {
            $html .= sprintf(' %s="%s"', $key, esc_attr($value));
        }
    }

    $html = trim($html);

    return $html;
}

function wpm2awsHtmlLink($url, $anchor_text, $new_tab = true, $args = '')
{
    $defaults = array(
        'id' => '',
        'class' => '',
    );

    $args = wp_parse_args($args, $defaults);
    $args = array_intersect_key($args, $defaults);
    $atts = wpm2awsFormatHtmlAtts($args);
    $target = '';
    if ($new_tab === true) {
        $target = 'target = "_blank"';
    }

    $link = sprintf(
        '<a href="%1$s"%3$s ' . $target . '>%2$s</a>',
        esc_url($url),
        esc_html($anchor_text),
        $atts ? (' ' . $atts) : ''
    );

    return $link;
}
