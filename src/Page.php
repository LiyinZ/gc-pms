<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-29
 * Time: 9:00 AM
 */

namespace App;
use RedBeanPHP\R;
use Slim\Slim;
use \Michelf\Markdown;

class Page {

    private $id;
    private $is_valid_page = false;
    public $errors = [];

    public $title;
    public $url;
    public $subtitle;

    public $content;
    public $html;

    public $cover_photo;
    public $cover_file_name;
    public $user_id;

    /* page url is generated by page :title */
    protected function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text))
        {
            return 'n-a';
        }

        return $text;
    }



    public function validate($input, $image, $id = null)
    {
        $errors = [];
        // 0. if validating edit (with id)
        if ($id) {

        }
        // 1. title not empty
        $title = trim($input['title']);
        if (empty($title) || strlen($title) > 30) {
            $errors['title'] = 'Title cannot be empty or exceed 30 characters';
        }
        // 2. check for existing page
        elseif (!$id && R::findOne( 'page', ' title = ? ', [ $title ] )) {
            $errors['duplicate'] = 'Page already exist, try editing it instead!';
        }
        // 3. subtitle optional but not empty
        $subtitle = trim($input['subtitle']);
        if (strlen($subtitle) > 140) {
            $errors['subtitle'] = 'Subtitle may not exceed 140 characters';
        }
        // 4. cover photo optional but not empty
        try {
            $cover_photo = Photo::uploadSingle($image);
        } catch (\RuntimeException $e) {
            // no file sent is fine
            if ($e->getMessage() != 'No file sent.') {
                $errors['cover'] = $e->getMessage();
            }

        }
        // 5. content not empty
        $content = trim($input['content']);
        if (empty($content)) {
            $errors['content'] = 'Content cannot be empty. Please write some stuff!';
        }

        if (empty($errors))
        {

            $this->is_valid_page = true;
            $this->errors = [];

            // set properties if valid
            $this->title = $title;
            $this->url = $this->slugify($title);
            $this->content = $content;
            $this->html = Markdown::defaultTransform(htmlspecialchars($content));
            $this->user_id = $input['user_id'];

            if (!empty($subtitle)) {
                $this->subtitle = $subtitle;
            }
            if (isset($cover_photo)) {

                $this->cover_photo = $cover_photo;
                $this->cover_file_name = $cover_photo->file_name;

            }
            $this->id = $id;

            return $this->is_valid_page;
        }
        else
        {
            $this->errors = $errors;
            return false;
        }

    }


    public function create()
    {

        if ($this->is_valid_page) {
            $page = R::dispense('page');
            $page->title = $this->title;
            $page->url = $this->url;
            $page->content = $this->content;
            $page->html = $this->html;
            $page->user_id = $this->user_id;
            $page->subtitle = $this->subtitle;

            if (isset($this->cover_photo)) {
                $page->cover_file_name = $this->cover_file_name;
                // create bean relation
                $page->ownPhotoList[] = $this->cover_photo;
            }

            $this->id = R::store($page);

            return $this->id;

        } else {
            throw new \Exception('Invalid page input not validate properly');
        }

    }

    public function update()
    {

        if ($this->is_valid_page) {
            $page = R::load('page', $this->id);

            if ($page->id) { // if page actually exists

                // prepare update
                $page->title = $this->title;
                $page->url = $this->url;
                $page->content = $this->content;
                $page->html = $this->html;
                $page->user_id = $this->user_id;
                $page->subtitle = $this->subtitle;

                if (isset($this->cover_photo)) {

                    $old_file_name = $page->cover_file_name;

                    if ($old_file_name != null) {
                        $old_photo = R::findOne( 'photo', ' page_id = ? ', [ $this->id ] );
                        R::trash($old_photo);

                        // see if old and new file the same
                        if ($old_file_name != $this->cover_file_name) {
                            // if not the same, see if there are others using it
                            $others  = R::find( 'photo', " file_name = '$old_file_name' " );
                            if (empty($others)) {
                                // if no other using, delete old file
                                Photo::remove($old_file_name);
                            }
                        }
                    }

                    $page->cover_file_name = $this->cover_file_name;
                    // replace whole bean (photo)
                    $page->ownPhotoList = [ $this->cover_photo ];
                }

                R::store($page);
            }
            else
            {
                throw new \Exception('Page does not exist');
            }

        } else {
            throw new \Exception('Invalid page input not validate properly');
        }

    }


    public static function add(Slim $app, Session $session)
    {

        $input = $app->request->post();
        $input['user_id'] = $session->getUser()->getID();
        $image = $_FILES['cover'];

        $page = new self;
        if ($page->validate($input, $image)) {
            $page->create();
            $page_url = $page->url;
            $app->redirect("/pms/page/$page_url");
        } else {
            $app->flash('page_errs', $page->errors);
            $app->flash('old_page', $input);
            $app->redirect("/pms/add/page");
        }

    }

    public static function edit(Slim $app, Session $session, $id)
    {

        $input = $app->request->post();
        $input['user_id'] = $session->getUser()->getID();
        $image = $_FILES['cover'];

        $page = new self;
        if ($page->validate($input, $image, $id)) {
            $page->update();
            $page_url = $page->url;
            $app->redirect("/pms/page/$page_url");
        } else {
            $app->flash('page_errs', $page->errors);
            $app->flash('old_page', $input);
            $app->redirect("/pms/edit/page/$id");
        }

    }


    public static function delete(Slim $app, Session $session, $id)
    {

        if ($session->getUser()->isStaff()) {
            $page = R::load('page', $id);
            if ($page->id) {

                $old_file_name = $page->cover_file_name;

                if ($old_file_name) { // if has cover photo
                    $cover_photo = R::findOne( 'photo', ' page_id = ? ', [$id]);
                    R::trash($cover_photo);

                    $others  = R::find( 'photo', " file_name = '$old_file_name' " );
                    if (empty($others)) {
                        // if no other using, delete old file
                        Photo::remove($old_file_name);
                    }
                }
                R::trash($page);
                $app->redirect('/pms/admin/page');
            }
            else
            {
                throw new \Exception('Page does not exist');
            }

        } else {
            $session->logout();
            $app->redirect('/pms');
        }

    }


}