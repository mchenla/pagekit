<?php

use Pagekit\User\Dashboard\UserWidget;
use Pagekit\User\Entity\Role;
use Pagekit\User\Entity\User;
use Pagekit\User\Event\AccessListener;
use Pagekit\User\Event\AuthorizationListener;
use Pagekit\User\Event\LoginAttemptListener;
use Pagekit\User\Event\PermissionEvent;
use Pagekit\User\Event\UserListener;
use Pagekit\User\Widget\LoginWidget;

return [

    'name' => 'system/user',

    'main' => function ($app) {

        $app->subscribe(
            new AccessListener,
            new AuthorizationListener,
            new LoginAttemptListener,
            new UserListener
        );

        $app['user'] = function ($app) {

            if (!$user = $app['auth']->getUser()) {
                $user  = new User;
                $roles = Role::where(['id' => Role::ROLE_ANONYMOUS])->get();
                $user->setRoles($roles);
            }

            return $user;
        };

        $app['permissions'] = function ($app) {
            return $app->trigger(new PermissionEvent('user.permission'))->getPermissions();
        };

        $app->on('user.permission', function ($event) use ($app) {
            foreach ($app['module'] as $module) {
                if (isset($module->permissions)) {
                    $event->setPermissions($module->name, $module->permissions);
                }
            }
        });

        $app->on('widget.types', function ($event, $widgets) {
            $widgets->registerType(new LoginWidget('widget.user.login', __('Login'), __('Displays a user login form.')));
        });

        $app->on('dashboard.types', function ($event, $dashboard) {
            $dashboard->registerType(new UserWidget('widget.user', __('Users')));
        });

        $app->on('system.settings.edit', function ($event) use ($app) {
            $event->options($this->name, $this->config, ['registration', 'require_verification']);
            $event->section($this->name, 'User', 'app/system/modules/user/views/admin/settings.php');
        });

        if (!$app['config']->get('system/user')) {
            $app['config']->set('system/user', [], true);
        }

    },

    'autoload' => [

        'Pagekit\\User\\' => 'src'

    ],

    'resources' => [

        'system/user:' => ''

    ],

    'controllers' => [

        '@user: /' => [
            'Pagekit\\User\\Controller\\UserController'
        ],

        '@user: /user' => [
            'Pagekit\\User\\Controller\\AuthController',
            'Pagekit\\User\\Controller\\PermissionController',
            'Pagekit\\User\\Controller\\ProfileController',
            'Pagekit\\User\\Controller\\RegistrationController',
            'Pagekit\\User\\Controller\\ResetPasswordController',
            'Pagekit\\User\\Controller\\RoleController'
        ],

        '@user/api: /api/user' => [
            'Pagekit\\User\\Controller\\RoleApiController',
            'Pagekit\\User\\Controller\\UserApiController'
        ]

    ],

    'menu' => [

        'user' => [
            'label'    => 'Users',
            'icon'     => 'system/user:assets/images/icon-users.svg',
            'url'      => '@user',
            'active'   => '@user*',
            'access'   => 'user: manage users || user: manage user permissions',
            'priority' => 15
        ],
        'user: users' => [
            'label'    => 'List',
            'parent'   => 'user',
            'url'      => '@user',
            'active'   => '@user(?!permission|role)',
            'access'   => 'user: manage users',
            'priority' => 15
        ],
        'user: permissions' => [
            'label'    => 'Permissions',
            'parent'   => 'user',
            'url'      => '@user/permission',
            'active'   => '@user/permission*',
            'access'   => 'user: manage user permissions'
        ],
        'user: roles' => [
            'label'    => 'Roles',
            'parent'   => 'user',
            'url'      => '@user/role',
            'active'   => '@user/role*',
            'access'   => 'user: manage user permissions'
        ]

    ],

    'permissions' => [

        'user: manage users' => [
            'title' => 'Manage users',
            'description' => 'Warning: Give to trusted roles only; this permission has security implications.'
        ],
        'user: manage user permissions' => [
            'title' => 'Manage user permissions',
            'description' => 'Warning: Give to trusted roles only; this permission has security implications.'
        ],
        'system: access admin area' => [
            'title' => 'Access admin area',
            'description' => 'Warning: Give to trusted roles only; this permission has security implications.'
        ]

    ],

    'config' => [

        'registration' => 'admin',
        'require_verification' => true,
        'users_per_page' => 20,
        'auth' => [
            'refresh_token' => false
        ]
    ]

];
