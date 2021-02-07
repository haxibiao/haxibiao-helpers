<?php

function get_files($path, $full_path = true, $allowExtension = '*')
{
    $files = [];
    if (is_file($path)) {
        $files[] = $path;
    }

    if (is_dir($path)) {
        $handler = opendir($path);
        while (($filename = readdir($handler)) !== false) {
            //使用!==，防止目录下出现类似文件名“0”等情况
            if ($filename != "." && $filename != "..") {
                if ($allowExtension != '*' && get_file_ext($filename) != $allowExtension) {
                    continue;
                }
                $files[] = $full_path ? $path . '/' . $filename : $filename;
            }
        }
        closedir($handler);
    }

    return $files;
}

function get_allfiles($path, &$files)
{
    if (is_dir($path)) {
        $dp = dir($path);
        while ($file = $dp->read()) {
            if ($file !== "." && $file !== "..") {
                get_allfiles($path . "/" . $file, $files);
            }
        }
        $dp->close();
    }
    if (is_file($path)) {
        $files[] = $path;
    }
}

function get_file_ext($file)
{
    $ext = substr($file, strpos($file, '.') + 1); //获取文件后缀
    return $ext;
}
