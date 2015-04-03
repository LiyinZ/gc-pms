<?php
/**
 * Created by PhpStorm.
 * User: LZ-Mac-Pro
 * Date: 15-03-29
 * Time: 9:00 AM
 */

namespace App\Users;
use Slim\Slim;
use App\Session;
use RedBeanPHP\R;


class Admin extends User {

    protected $level = 9;


    public static function access($code)
    {

        if ($code == R::load('access', 1)->code) {
            return true;
        } else {
            return false;
        }

    }


    public static function all()
    {

        return R::find('user', ' level = 9 ');

    }


    public static function signUp(Slim $app, Session $session, $uri = '')
    {
        $input = $app->request->post();

        $user = new static;
        if ($user->validate($input)) {
            $user->create();
            if (!$session->isLoggedIn()) {
                $session->login($user);
                $app->redirect("/pms/admin");
            } else {
                $app->redirect("/pms/admin/staff");
            }
        } else {
            $app->flash('reg_errs', $user->errors);
            $app->flash('old_reg', $input);
            if ($session->isLoggedIn() && $session->getUser()->isStaff()) {
                $app->redirect('/pms/add/staff');
            } else {
                $app->redirect("/pms/register");
            }
        }


    }


    public static function register(Slim $app, Session $session)
    {

        $input = $app->request->post();
        $is_admin = $session->isLoggedIn() ? $session->getUser()->isAdmin() : false;

        if ($is_admin || (isset($input['code']) && Admin::access($input['code']))) {
            if (isset($input['level'])) {
                if ($input['level'] == 9) {
                    Admin::signUp($app, $session);
                } elseif ($input['level'] == 7) {
                    Staff::signUp($app, $session);
                }
            } else {
                $app->flash('old_reg', $input);
                $app->flash('level', 'Please select one of the options!');
                if ($is_admin) {
                    $app->redirect('/pms/add/staff');
                } else {
                    $app->redirect('/pms/register');
                }
            }
        }
        elseif ($session->isLoggedIn() && $session->getUser()->isStaff())
        {
            Staff::signUp($app, $session);
        }
        else
        {
            $app->flash('code_err', 'Invalid Access Code');
            $app->flash('old_reg', $input);
            $app->redirect("/pms/register");
        }


    }


    public static function edit(Slim $app, Session $session, $id = 0)
    {

        $input = $app->request->post();

        $user = R::load('user', $id);
        global $staff;

        if ($user->level == 9) {
            $staff = new Admin;
        } elseif ($user->level == 7) {
            $staff = new Staff;
        }
        // mapping data
        $staff->id = $user->id;
        $staff->first_name = $user->first_name;
        $staff->last_name = $user->last_name;
        $staff->email = $user->email;
        $staff->phone = $user->phone;

        if ($staff->validEdit($input)) {
            $staff->save($user);
            $app->redirect("/pms/admin/staff");
        } else {
            $app->flash('edit_errs', $staff->errors);
            $app->flash('old_edit', $input);
            $app->redirect("/pms/admin/edit/$id");
        }

    }
}