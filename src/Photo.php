<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-31
 * Time: 8:08 AM
 */

namespace App;

use RedBeanPHP\R;


class Photo extends File_Handling {


    /**
     * @param $file
     */

    public static function uploadLogo($file)
    {
        $max_logo_size = 10000;

        if (
            !isset($file['error']) ||
            is_array($file['error'])
        ) {
            throw new \RuntimeException('Invalid parameters.');
        }

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

        if ($file['size'] > $max_logo_size) {
            throw new \RuntimeException('Exceeded filesize limit.');
        }

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

        $file_name = "company_logo.$ext";
        $target_path = self::$upload_dir . $file_name;
        if (!move_uploaded_file(
            $file['tmp_name'],
            $target_path
        )) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        // store logo extension (.jpg, .png, .gif)
        $logo = R::load('logo', 1);
        if ($logo->id) {
            $logo->file_name = $file_name;
            R::store($logo);
        } else {
            $logo = R::dispense('logo');
            $logo->file_name = $file_name;
            R::store($logo);
        }

        return 'Logo Successfully Uploaded';
    }


}