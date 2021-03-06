<?php

namespace Pagekit\User\Controller;

use Pagekit\Application as App;
use Pagekit\Application\Exception;
use Pagekit\User\Entity\User;

class ResetPasswordController
{
    /**
     * @Response("system/user:views/reset/request.php")
     */
    public function indexAction()
    {
        if (App::user()->isAuthenticated()) {
            return App::redirect();
        }

        return [
            '$meta' => [
                'title' => __('Reset')
            ]
        ];
    }

    /**
     * @Request({"email"})
     * @Response("system/user:views/reset/request.php")
     */
    public function resetAction($email)
    {
        try {

            if (App::user()->isAuthenticated()) {
                return App::redirect();
            }

            if (!App::csrf()->validate(App::request()->request->get('_csrf'))) {
                throw new Exception(__('Invalid token. Please try again.'));
            }

            if (empty($email)) {
                throw new Exception(__('Enter a email address.'));
            }

            if (!$user = User::findByEmail($email)) {
                throw new Exception(__('Invalid email address.'));
            }

            if ($user->isBlocked()) {
                throw new Exception(__('Your account has not been activated or is blocked.'));
            }

            $user->setActivation(App::get('auth.random')->generateString(32));

            $url = App::url('@user/resetpassword/confirm', ['user' => $user->getUsername(), 'key' => $user->getActivation()], true);

            try {

                $mail = App::mailer()->create();
                $mail->setTo($user->getEmail())
                     ->setSubject(__('Reset password for %site%.', ['%site%' => App::system()->config('site.title')]))
                     ->setBody(App::view('system/user:views/mails/reset.php', compact('user', 'url', 'mail')), 'text/html')
                     ->send();

            } catch (\Exception $e) {
                throw new Exception(__('Unable to send confirmation link.'));
            }

            $user->save();

            App::message()->success(__('Check your email for the confirmation link.'));

            return App::redirect();

        } catch (Exception $e) {
            App::message()->error($e->getMessage());
        }

        return App::redirect('@user/resetpassword');
    }

    /**
     * @Request({"user", "key"})
     * @Response("system/user:views/reset/confirm.php")
     */
    public function confirmAction($username = "", $activation = "")
    {
        if (empty($username) || empty($activation) || !$user = User::where(compact('username', 'activation'))->first()) {
            App::message()->error(__('Invalid key.'));
            return App::redirect();
        }

        if ($user->isBlocked()) {
            App::message()->error(__('Your account has not been activated or is blocked.'));
            return App::redirect();
        }

        if ('POST' === App::request()->getMethod()) {

            try {

                if (!App::csrf()->validate(App::request()->request->get('_csrf'))) {
                    throw new Exception(__('Invalid token. Please try again.'));
                }

                $password = App::request()->request->get('password');

                if (empty($password)) {
                    throw new Exception(__('Enter password.'));
                }

                if ($password != trim($password)) {
                    throw new Exception(__('Invalid password.'));
                }

                $user->setPassword(App::get('auth.password')->hash($password));
                $user->setActivation(null);
                $user->save();

                App::message()->success(__('Your password has been reset.'));

                return App::redirect();

            } catch (Exception $e) {
                App::message()->error($e->getMessage());
            }
        }

        return [
            '$meta' => [
                'title' => __('Reset Confirm')
            ],
            'username' => $username,
            'activation' => $activation
        ];
    }
}
