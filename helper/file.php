<?php

namespace Eckinox\Nex;

/**
 * @author       Mikael Laforge <mikael.laforge@gmail.com>
 * @version      1.1.2
 * @package      Nex
 *
 * @update (2010/10/19) [ML] - 1.0.1 - Added create_dirs() and recursive_rmdir() methods.
 * @update (2011/08/02) [ML] - 1.0.2 - Added method bytes2str()
 * @update (2011/09/15) [ML] - 1.0.3 - Added method uniqueName()
 * @update (22/11/12) [ML] - 1.1.0 - Added argument $overwrite copy()
 *                                  Added move() method
 *                                  Directories are now closed before recursivity. Files are kept in a tmp array
 * @update (04/11/12) [ML] - 1.1.1 - recursive_rmdir() will now correctly remove hidden files
 * @update (31/07/14) [ML] - 1.1.2 - added replaceExt() method
 *
 * 20/10/2009
 * This class was made to manage files on a server.
 */
abstract class file {

    /**
     * Get extension of a file
     * @param string $filename - path or filename
     * @return string extension without dot '.'
     */
    public static function ext($filename) {
        return strtolower(substr(strrchr(basename($filename), '.'), 1));
    }

    public static function replaceExt($filename, $new_ext) {
        $info = pathinfo($filename);
        return ($info['dirname'] && $info['dirname'] != '.' ? $info['dirname'] . DIRECTORY_SEPARATOR : '')
                . $info['filename']
                . '.'
                . $new_ext;
    }

    /**
     * Build unique name fromt filename and return it
     * @param string $filename
     */
    public static function uniqueName($filename) {
        return time() . '_' . $filename;
    }

    /**
     * Format bytes
     * @param int $size number of bytes returned by filesize()
     */
    public static function bytes2str($size) {
        $units = array(' b', ' kb', ' mb', ' gb', ' tb');
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $units[$i];
    }

    /**
     * Check the given path and make sure each segment exist
     * @param string $path path to create
     * @param string $chmod file permissions
     *
     * DEPRECIATED function already exist in php spl
     */
    public static function create_dirs($path, $chmod = 0755) {
        if (!is_dir($path)) {
            $directory_path = "";
            $directories = explode("/", $path);
            array_pop($directories);

            foreach ($directories as $directory) {
                $directory_path .= $directory . "/";
                if (!is_dir($directory_path)) {
                    mkdir($directory_path);
                    chmod($directory_path, $chmod);
                }
            }
        }
    }

    /**
     * Delete directory and its tree or complety empty a directory.
     * @param string $directory path to directory
     * @param bool $empty true = empty directory, false = remove directory
     */
    public static function recursive_rmdir($directory, $empty = false) {
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }
        if (!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif (is_readable($directory)) {
            $handle = opendir($directory);
            $files = [];
            while (false !== ($file = readdir($handle))) {
                // Skip special file
                if (in_array($file, array('.', '..')))
                    continue;

                $files[] = $file;
            }
            closedir($handle);

            foreach ($files as $file) {
                $path = $directory . '/' . $file;
                if (is_dir($path)) {
                    self::recursive_rmdir($path);
                } else {
                    unlink($path);
                }
            }

            if ($empty == false) {
                if (!rmdir($directory)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Copy a file or directory to a new path
     * @param string $source
     * @param string $dest
     */
    public static function copy($source, $dest, $overwrite = true) {
        // Simple copy for a file
        if (is_file($source)) {
            if (!$overwrite && file_exists($dest))
                return false;

            copy($source, $dest);
            chmod($dest, fileperms($source));

            return true;
        }
        elseif (is_dir($source)) {
            // Make destination directory if it doesnt exist
            if (!is_dir($dest)) {
                $oldumask = umask(0);
                mkdir($dest, 0755, true);
                umask($oldumask);
            }

            // Loop through the folder
            $handle = opendir($source);
            $files = [];
            while (false !== ($file = readdir($handle))) {
                // Skip hidden files
                if (substr($file, 0, 1) == '.')
                    continue;

                $files[] = $file;
            }
            closedir($handle);

            foreach ($files as $file) {
                // Deep copy directories
                if ($dest !== $source . DIRECTORY_SEPARATOR . $file) {
                    self::copy($source . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file, $overwrite);
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Move a file or directory to a new path
     * @param string $source
     * @param string $dest
     */
    public static function move($source, $dest, $overwrite = true) {
        // Simple copy for a file
        if (is_file($source)) {
            if (!$overwrite && file_exists($dest)) {
                unlink($source);
                return false;
            }

            rename($source, $dest);

            return true;
        } elseif (is_dir($source)) {
            // Make destination directory if it doesnt exist
            if (!is_dir($dest)) {
                $oldumask = umask(0);
                mkdir($dest, 0755, true);
                umask($oldumask);
            }

            // Loop through the folder
            $handle = opendir($source);
            $files = [];
            while (false !== ($file = readdir($handle))) {
                // Skip hidden files
                if (substr($file, 0, 1) == '.') {
                    if (!in_array($file, array('.', '..')))
                        unlink($source . DIRECTORY_SEPARATOR . $file);
                    continue;
                }

                $files[] = $file;
            }
            closedir($handle);

            foreach ($files as $file) {
                // Deep copy directories
                if ($dest !== $source . DIRECTORY_SEPARATOR . $file) {
                    self::move($source . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file, $overwrite);
                }
            }

            rmdir($source);

            return true;
        } else {
            return false;
        }
    }

}
