<?php
namespace App\Users;
use RedBeanPHP\R;
use Slim\Slim;
use App\Session;


class User {

    protected $id;
    protected $password;
    protected $pw_confirm;
    protected $level = 0;

    public $first_name;
    public $last_name;
    public $email;
    public $phone;


    // validation vars
    protected $is_valid = false;
    public $errors = [];


    /**
     * @param $name
     * @param $password
     * @throws \RedBeanPHP\RedException
     */

    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id;
    }


    public function isAdmin()
    {
        if ($this->level === 9) {
            return true;
        } else {
            return false;
        }
    }

    public function isStaff()
    {
        if ($this->level === 7 || $this->level === 9) {
            return true;
        } else {
            return false;
        }
    }

    public function isUser()
    {
        if ($this->level === 0) {
            return true;
        } else {
            return false;
        }
    }

    public function isValid()
    {
        return $this->is_valid;
    }


    public static function exists($id)
    {
        $user = R::load('user', $id);
        if ($user->id != 0) {
            return $user;
        } else {
            return false;
        }
    }



    public function validEdit($input)
    {
        $password = trim($input['password']);
        if (empty($password)) {
            return $this->validate($input, false, false); // not sign up, no change to pw
        } elseif ($this->isValidCreds($this->email, $input['old_password'])) {
            return $this->validate($input, false); // not sign up
        } else {
            $this->errors['old_password'] = 'You old password is incorrect.';
            return false;
        }
    }



    public function validate($input, $is_signup = true, $password_exists = true)
    {
        $errors = [];
        extract($input);
        $first_name = trim($first_name);
        $last_name = trim($last_name);

        if (empty($first_name) || empty($last_name)) {
            $errors['name'] = 'First and Last Name cannot be empty';
        }
        if ($is_signup) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter valid email address.';
            } elseif (R::findOne('user', ' email = ? ', [ $email ])) {
                $errors['email'] = 'Email already exists.';
            }
        }
        if ( $password_exists && ( strlen($password) < 6 || $password !== $pw_confirm ) ) {
            $errors['new_password'] = 'Password must have at least 6 characters and match confirmation.';
        }
        if (!empty($phone)) {
            $expr = '/^\+?(\(?[0-9]{3}\)?|[0-9]{3})[-\.\s]?[0-9]{3}[-\.\s]?[0-9]{4}$/';
            if (!preg_match($expr, $phone)) {
                $errors['phone'] = 'Phone number invalid.';
            }
        }

        if (!empty($errors)) {
            $this->errors = $errors;
            return false;
        } else {
            $this->is_valid = true;
            $this->errors = [];
            // store valid info to user object, prepare for signup
            $this->first_name = $first_name;
            $this->last_name = $last_name;
            if (isset($email)) {
                $this->email = $email;
            }
            if (isset($phone)) {
                $this->phone = $phone;
            }
            if ($password_exists) {
                $this->password = password_hash($password, PASSWORD_BCRYPT);
            }

            return $this->is_valid;
        }
    }

    private function isValidCreds($email, $password)
    {
        $user = R::findOne('user', 'email=?', [ $email ]);
        if ($user) {
            if (password_verify($password, $user->password)) {
                $this->is_valid = true;
                $this->errors = [];
                return $user;
            } else {
                $this->errors['password'] = 'Your password is incorrect.';
                return false;
            }
        } else {
            $this->errors['email'] = 'Looks like you don\'t have an account, please sign up.';
            return false;
        }
    }

    public function auth($input)
    {
        extract($input);

        if ($user = $this->isValidCreds($email, $password)) {

            $this->id = $user->id;
            $this->first_name = $user->first_name;
            $this->last_name = $user->last_name;
            $this->email = $user->email;
            $this->phone = $user->phone;
            // set user level
            $this->level = (int)$user->level;

            return $this->is_valid;
        } else {
            return false;
        }
    }


    /**
     * @throws \Exception
     * @throws \RedBeanPHP\RedException
     *
     *  Create, Update, Save
     */
    protected function create()
    {
        $user = R::dispense('user');
        $this->save($user);
    }
    protected function update()
    {
        $user = R::load( 'user', $this->id );
        $this->save($user);
    }
    protected function save($user) {
        if ($this->is_valid) {

            $user->first_name = $this->first_name;
            $user->last_name = $this->last_name;
            $user->email = $this->email;
            // for editing user
            if (isset($this->password)) {
                $user->password = $this->password;
            }
            // for editing user
            if (isset($this->phone)) {
                $user->phone = $this->phone;
            }
            // set user level
            $user->level = $this->level;
            $this->id = R::store($user);

        } else {
            throw new \Exception('User input invalid');
        }
    }


    public static function all()
    {

        return R::find('user', ' level = 0 ');

    }





    public static function signUp(Slim $app, Session $session, $uri = '')
    {
        $input = $app->request->post();
        $user = new static;
        if ($user->validate($input)) {
            $user->create();
            $session->login($user);
            $app->redirect("/pms/$uri");
        } else {
            $app->flash('signup_errs', $user->errors);
            $app->flash('old_su', $input);
            $app->redirect("/pms/$uri");
        }
    }

    public static function logIn(Slim $app, Session $session, $uri = '')
    {
        $input = $app->request->post();
        $user = new static;
        if ($user->auth($input)) {
            $session->login($user);
            if ($user->isStaff() || $user->isAdmin()) {
                $app->redirect('/pms/admin');
            } else {
                $app->redirect("/pms/$uri");
            }
        } else {
            $app->flash('login_errs', $user->errors);
            $app->flash('old_lg', $input);
            $app->redirect("/pms/$uri");
        }
    }

    public static function edit(Slim $app, Session $session, $id = 0)
    {
        $input = $app->request->post();
        $user = $session->getUser();
        if ($user->validEdit($input)) {
            $user->update();
            $session->login($user);
            $app->redirect('/pms/user/profile');
        } else {
            $app->flash('edit_errs', $user->errors);
            $app->flash('old_edit', $input);
            $app->redirect('/pms/user/edit');
        }
    }

    public static function delete(Slim $app, Session $session, $id)
    {
        $current = $session->getUser();

        $user = R::load( 'user', $id );
        $user_level = $user->level;
        if ($current->getID() == $id || $current->isAdmin()) {
            R::trash( $user );
        }

        if ($current->getID() == $id || $current->isUser()) {
            $session->logout();
            $app->redirect('/pms');
        } else {
            if ($user_level == 0) {
                $app->redirect('/pms/admin/user');
            } else {
                $app->redirect('/pms/admin/staff');
            }
        }
    }
}