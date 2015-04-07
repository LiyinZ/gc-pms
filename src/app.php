<?php
use App\Users\User;
use App\Users\Admin;
use App\Users\Staff;
use App\Page;
use App\Photo;
use RedBeanPHP\R;
use \Michelf\Markdown;


$session = new \App\Session();
R::setup( DSN, DB_USER, DB_PW );
// Prepare app
$app = new \Slim\Slim(array(
    'templates.path' => 'templates',
));
// Prepare view
$app->view(new \Slim\Views\Twig());
// Parser options
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
// Adding twig extension
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
// Twig vars we need in every page...
$twig = $app->view()->getEnvironment();
// Setting timezone (choose your fav. one)...
$twig->getExtension('core')->setTimezone('America/Toronto');
$twig->addGlobal('uri', $app->request->getResourceUri());
$twig->addGlobal('session', $session);
$twig->addGlobal('cssPath', 'http://localhost:8888/pms/assets/css/');
$twig->addGlobal('jsPath', 'http://localhost:8888/pms/assets/js/');
$twig->addGlobal('imgPath', 'http://localhost:8888/pms/assets/img/');
// for generating page links
$pages = R::findAll('page');
$twig->addGlobal( 'pages', $pages);
// logo path
$logo = R::load('logo', 1);
$twig->addGlobal( 'logo', $logo);



/* HOME Routes && Controllers...
   ========================================================================== */
/* Home Page */
$app->get('/', function () use ($app) {
//    var_dump($_SESSION);
    $app->render('home.twig');
})->name('home');

/* View All Properties */
$app->get('/all', function () use ($app) {
   $app->render('all.twig');
});

/* property details (individual) */
$app->get('/property', function () use ($app) {
    $app->render('property.twig');
});
/* property photos */
$app->get('/property/photos', function () use ($app) {
    $app->render('photos.twig');
});

$app->get('/admin/property', function () use ($app) {
    $app->render('admin/property.twig');
});

$app->get('/add/property', function () use ($app) {
    $app->render('admin/add-property.twig');
});



/* 404 Not Found
   ========================================================================== */
$app->get('/404', function () use ($app) {
    $app->render('404.twig');
});
$app->notFound(function () use ($app) {
    $app->redirect('/pms/404');
});

/* Users
   ========================================================================== */

/* --- User Sign Up --- */
$app->post('/user/signup/', function() use ($app, $session) {

    User::signUp($app, $session);

});
$app->post('/user/signup/:uri', function($uri) use ($app, $session) {

    User::signUp($app, $session, $uri);

});
/* --- User Log In --- */
$app->post('/user/login/', function() use ($app, $session) {

    User::logIn($app, $session);

});
$app->post('/user/login/:uri', function($uri) use ($app, $session) {

    User::logIn($app, $session, $uri);

});

/* --- User Log Out --- */
$app->get('/user/logout', function() use ($app, $session) {

    $session->logout();
    $app->redirect('/pms');

});

/* --- User Profile --- */
$app->get('/user/profile', function() use ($app, $session) {

    if ($session->isLoggedIn()) {
        $user = $session->getUser();
        $app->render('user/profile.twig', compact('user'));
    } else {
        $app->flash('login_err', 'You haven\'t logged in.');
        $app->redirect('/pms');
    }

});
/* --- User Edit --- */
$app->get('/user/edit', function() use ($app, $session) {

    if ($session->isLoggedIn()) {
        $id = $session->getUser()->getID();
        $user = R::load('user', $id);
        // bean with id 0 if user deleted
        if ($user->id) {
            $app->render('user/edit.twig', compact('user'));
        } else {
            $session->logout();
            $app->flash('login_err', 'Your account does not exist.');
            $app->redirect('/pms');
        }
    } else {
        $app->flash('login_err', 'You haven\'t logged in.');
        $app->redirect('/pms');
    }

});
$app->post('/user/update/', function() use ($app, $session) {

    User::edit($app, $session);

});
/* --- User Delete --- */
$app->delete('/delete/user/:id', function($id) use ($app, $session) {

    User::delete($app, $session, $id);

});
/* Admin / Staff
   ========================================================================== */


/* --- Registration  --- */
// register get route
$app->get('/register', function () use ($app) {

    $app->render('register.twig');

});
// register post route
$app->post('/register', function() use ($app, $session) {

    Admin::register($app, $session);

});
/* Add Staff / Admin */
$app->get('/add/staff', function () use ($app, $session) {

    $session->getAdminPage($app, 'register');

});




/* --- Upload Logo  --- */
$app->post('/upload/logo', function () use ($app, $session) {

    if ($session->getUser()->isStaff()) {
        try {
            $status = Photo::uploadLogo($_FILES['logo']);
            $app->flash('logo_status', $status);
            $app->redirect('/pms/admin');
        } catch (\RuntimeException $e) {
            $app->flash('logo_status', $e->getMessage());
            $app->redirect('/pms/admin');
        }
    }

});

/* --- Admin Panel  --- */
$app->get('/admin', function () use ($app, $session) {

    $session->getAdminPage($app);

});
/* --- Admin -> List Users  --- */
;$app->get('/admin/user', function () use ($app, $session) {

    $users = User::all();
    $session->getAdminPage($app, 'admin/list-users', compact('users'));

});
/* --- Admin -> View a User  --- */
;$app->get('/admin/user/:id', function ($id) use ($app, $session) {

    $user = R::load('user', $id);
    if ($user->id) { // user exists
        $session->getAdminPage($app, 'admin/view-user', compact('user'));
    } else {
        $app->redirect('/pms/404');
    }

});
/* --- Admin -> List Staffs  --- */
$app->get('/admin/staff', function () use ($app, $session) {

    $admins = Admin::all();
    $staffs = Staff::all();
    $session->getAdminPage($app, 'admin/list-staffs', compact('admins', 'staffs'));

});
/* --- Admin -> View a Staff  --- */
;$app->get('/admin/user/:id', function ($id) use ($app, $session) {

    $user = R::load('user', $id);
    if ($user->id) {
        $session->getAdminPage($app, 'admin/view-user', compact('user'));
    } else {
        $app->redirect('/pms/404');
    }

});
/* --- Admin Edit (admin, staff)  --- */
;$app->get('/admin/edit/:id', function ($id) use ($app, $session) {

    $user = R::load('user', $id);
    if ($user->id && $user->level != 0) { // if user exist, and user cannot be edited
        $session->getAdminPage($app, 'user/edit', compact('user', 'id'));
        // id useful for update route in user/edit template
    } else {
        $app->redirect('/pms/404');
    }

});
$app->post('/user/update/:id', function($id) use ($app, $session) {

    Admin::edit($app, $session, $id);

});

/* Page CRUD
   ========================================================================== */
/* --- Read Page  --- */
$app->get('/page/:url', function ($url) use ($app) {

    $page = R::findOne('page', ' url = ? ', [ $url ]);
    if ($page) {
        $app->render('page.twig', compact('page'));
    } else {
        $app->redirect('/pms/404');
    }

});
/* --- List Pages  --- */
$app->get('/admin/page', function () use ($app, $session) {

    $session->getAdminPage($app, 'admin/list-pages');

});
/* --- Add Page  --- */
$app->get('/add/page', function () use ($app, $session) {

    $session->getAdminPage($app, 'admin/edit-page');

});
$app->post('/create/page', function() use ($app, $session) {

    Page::add($app, $session);

});
/* --- Edit Page  --- */
$app->get('/edit/page/:id', function ($id) use ($app, $session) {

    $page = R::load('page', $id);
    if ($page->id) { // page exists
        $session->getAdminPage($app, 'admin/edit-page', compact('page'));
    } else {
        $app->redirect('/pms/404');
    }

});
$app->post('/update/page/:id', function($id) use ($app, $session) {

    Page::edit($app, $session, $id);

});
/* --- Delete Page  --- */
$app->delete('/delete/page/:id', function($id) use ($app, $session) {

    Page::delete($app, $session, $id);

});





/* Tests
   ========================================================================== */

// test
$app->get('/test', function () use ($app, $session) {

//    $app->render('test.twig');


});
$app->post('/test/', function () use ($app) {

    $input = $app->request->post();
    $md = Markdown::defaultTransform($input['texts']);
    $app->flash('markdown', $md);
    $app->redirect('/pms/test');

});

R::close();