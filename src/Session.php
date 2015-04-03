<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-18
 * Time: 8:23 PM
 */

namespace App;
use App\Users\User;
use Slim\Slim;
use RedBeanPHP\R;


class Session {

    private $logged_in = false;
    private $user;

    public function __construct()
    {
        session_cache_limiter(false);
        session_start();
        $this->checkLogin();
    }

    public function getUser()
    {
        if ($this->logged_in) {
            return $this->user;
        } else {
            throw new \Exception('Method is used before checking isLoggedIn');
        }

    }

    public function isLoggedIn()
    {
        return $this->logged_in;
    }

    public function login(User $user)
    {
        if ($user->isValid()) {
            $this->user = $_SESSION['user'] = $user;
            $this->logged_in = true;
        }
    }

    public function logout()
    {
        unset($_SESSION['user']);
        unset($this->user);
        $this->logged_in = false;
//        session_destroy();
    }

    private function checkLogin()
    {
        if (isset($_SESSION['user'])) {
            $this->user = $_SESSION['user'];
            $this->logged_in = true;
        } else {
            unset($this->user);
            $this->logged_in = false;
        }
    }

    public function getAdminPage(Slim $app, $template = 'admin/panel', $data = [])
    {
        if ($this->logged_in) {
            $user = $this->user;
            $id = $user->getID();
            if (R::load('user', $id)->id) {
                if ($user->isAdmin()) {
                    $data['is_admin'] = true;
                    $app->render("$template.twig", $data);
                }
                elseif ($user->isStaff()) {
                    $current_id = $user->getID();
                    $data['current_id'] = $current_id;
                    $data['is_staff'] = true;
                    $app->render("$template.twig", $data);
                } else {
                    $app->redirect('/pms');
                }
            } else {
                $this->logout();
                $app->flash('login_err', 'Your status has been revoked.');
                $app->redirect('/pms');
            }

        } else {
            $app->redirect('/pms');
        }
    }

}