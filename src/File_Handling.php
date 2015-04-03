<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-31
 * Time: 10:56 AM
 */

namespace App;
use RedBeanPHP\R;

class File_Handling {

    protected static $max_file_size = 1000000;
    protected static $upload_dir = './assets/img/';


    public static function safeUpload($file)
    {

        // Undefined | Multiple Files | $files Corruption Attack
        // If this request falls under any of them, treat it invalid.
        if (
            !isset($file['error']) ||
            is_array($file['error'])
        ) {
            throw new \RuntimeException('Invalid parameters.');
        }

        // Check $file['error'] value.
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \RuntimeException('No file sent.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \RuntimeException('Exceeded filesize limit.');
            default:
                throw new \RuntimeException('Unknown errors.');
        }

        // You should also check filesize here.
        if ($file['size'] > self::$max_file_size) {
            throw new \RuntimeException('Exceeded filesize limit.');
        }

        // DO NOT TRUST $file['mime'] VALUE !!
        // Check MIME Type by yourself.
        /*$finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
                finfo_file($finfo, $file['tmp_name']),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ),
                true
            )) {
            throw new \RuntimeException('Invalid file format.');
        }
        finfo_close($finfo);*/
        // fix for gc dream host
        if (false === $ext = array_search(
                $file['type'],
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ),
                true
            )) {
            throw new \RuntimeException('Invalid file format.');
        }


        // You should name it uniquely.
        // DO NOT USE $file['name'] WITHOUT ANY VALIDATION !!
        // On this example, obtain safe unique name from its binary data.
        $file_name = sprintf('%s.%s', sha1_file($file['tmp_name']), $ext);
        $target_path = self::$upload_dir . $file_name;

        if (!move_uploaded_file(
            $file['tmp_name'],
            $target_path
        )) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        // if no exceptions, run the following
        $upload_date = strftime('%Y-%m-%d %H:%M:%S', time());
        return [
            'file_name' => $file_name,
            'upload_date' => $upload_date
        ];

    }


    public static function remove($old_file_name)
    {

        $old_file = self::$upload_dir . $old_file_name;
        return unlink($old_file);

    }



    protected static function reArrayFiles(&$file_post)
    {
        $file_ary = [];
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i=0; $i<$file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }


    public static function uploadSingle($file, $bean_type = 'photo')
    {

        $file_info = self::safeUpload($file); // throws exceptions, or stores variables if no errors

        $bean = R::dispense($bean_type);
        $bean->file_name = $file_info['file_name'];
        $bean->upload_date = $file_info['upload_date'];

        return $bean;

    }

    public static function uploadMultiple($files, $bean_type)
    {

        $file_list = self::reArrayFiles($files);

        $bean_list = [];
        foreach ($file_list as $file) {

            $bean_list[] = self::uploadSingle($file, $bean_type);

        }

        return $bean_list;

    }

}