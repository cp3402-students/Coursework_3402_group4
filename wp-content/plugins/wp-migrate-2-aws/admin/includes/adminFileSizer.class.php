<?php

class WPM2AWS_FileSizer
{
    public function __construct()
    {
    }

    public function getDirSize($dirname)
    {
        if (!is_dir($dirname) || !file_exists($dirname)) {
            return false;
        }

        $dirSize = $this->wpse_67876_foldersize($dirname);

        $formattedDirSize = $this->wpse_67876_format_size($dirSize);

        return $formattedDirSize;
    }
    
    private function wpse_67876_wp_add_dashboard_widget()
    {
        $upload_dir     = wp_upload_dir();
        $upload_space   = $this->wpse_67876_foldersize($upload_dir['basedir']);
        $content_space  = $this->wpse_67876_foldersize(WP_CONTENT_DIR);
        $wp_space       = $this->wpse_67876_foldersize(ABSPATH);
    
        /* ABSOLUTE paths not being shown in Widget */
    
        // echo '<b>' . $upload_dir['basedir'] . ' </b><br />';
        echo '<i>Uploads</i>: ' . $this->wpse_67876_format_size($upload_space) . '<br /><br />';
    
        // echo '<b>' . WP_CONTENT_DIR . ' </b><br />';
        echo '<i>wp-content</i>: ' . $this->wpse_67876_format_size($content_space) . '<br /><br />';
    
        if (is_multisite()) {
            echo '<i>wp-content/blogs.dir</i>: ' . $this->wpse_67876_format_size($this->wpse_67876_foldersize(WP_CONTENT_DIR . '/blogs.dir')) . '<br /><br />';
        }
    
        // echo '<b>' . ABSPATH . ' </b><br />';
        echo '<i>WordPress</i>: ' . $this->wpse_67876_format_size($wp_space);
    }
    
    
    
    private function wpse_67876_foldersize($path)
    {
        $total_size = 0;
        $files = scandir($path);
        $cleanPath = rtrim($path, '/') . '/';
    
        foreach ($files as $t) {
            if ('.' != $t && '..' != $t) {
                $currentFile = $cleanPath . $t;
                if (is_dir($currentFile)) {
                    $size = $this->wpse_67876_foldersize($currentFile);
                    $total_size += $size;
                } else {
                    $size = filesize($currentFile);
                    $total_size += $size;
                }
            }
        }
    
        return $total_size;
    }
    
    private function wpse_67876_format_size($size)
    {
        $units = explode(' ', 'B KB MB GB TB PB');
    
        $mod = 1024;
    
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
    
        $endIndex = strpos($size, ".") + 3;
    
        return array(
            'value' => substr($size, 0, $endIndex),
            'unit' => $units[$i]
        );

        // return substr($size, 0, $endIndex) . ' ' . $units[$i];
    }
}
