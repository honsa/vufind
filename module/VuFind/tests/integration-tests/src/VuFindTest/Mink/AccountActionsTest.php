<?php
/**
 * Mink account actions test class.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace VuFindTest\Mink;

/**
 * Mink account actions test class.
 *
 * @category VuFind
 * @package  Tests
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 * @retry    4
 */
class AccountActionsTest extends \VuFindTest\Unit\MinkTestCase
{
    use \VuFindTest\Unit\AutoRetryTrait;
    use \VuFindTest\Unit\UserCreationTrait;

    /**
     * Standard setup method.
     *
     * @return mixed
     */
    public static function setUpBeforeClass()
    {
        return static::failIfUsersExist();
    }

    /**
     * Standard setup method.
     *
     * @return void
     */
    public function setUp()
    {
        // Give up if we're not running in CI:
        if (!$this->continuousIntegrationRunning()) {
            return $this->markTestSkipped('Continuous integration not running.');
        }
    }

    /**
     * Test changing a password.
     *
     * @retryCallback tearDownAfterClass
     *
     * @return void
     */
    public function testChangePassword()
    {
        $session = $this->getMinkSession();
        $session->visit($this->getVuFindUrl());
        $page = $session->getPage();

        // Create account
        $this->clickCss($page, '#loginOptions a');
        $this->snooze();
        $this->clickCss($page, '.modal-body .createAccountLink');
        $this->snooze();
        $this->fillInAccountForm($page);
        $this->clickCss($page, '.modal-body .btn.btn-primary');
        $this->snooze();

        // Log out
        $this->clickCss($page, '.logoutOptions a.logout');
        $this->snooze();

        // Go to profile page:
        $session->visit($this->getVuFindUrl('/MyResearch/Profile'));

        // Log back in
        $this->clickCss($page, '#loginOptions a');
        $this->fillInLoginForm($page, 'username1', 'test');
        $this->clickCss($page, '.modal-body .btn.btn-primary');
        $this->snooze();

        // Now click change password button:
        $this->findAndAssertLink($page, 'Change Password')->click();
        $this->snooze();

        // Change the password (but get the old password wrong)
        $this->fillInChangePasswordForm($page, 'bad', 'good');
        $this->clickCss($page, '#newpassword .btn.btn-primary');
        $this->snooze();
        $this->assertEquals(
            'Invalid login -- please try again.',
            $this->findCss($page, '.alert-danger')->getText()
        );

        // Change the password successfully:
        $this->fillInChangePasswordForm($page, 'test', 'good');
        $this->clickCss($page, '#newpassword .btn.btn-primary');
        $this->snooze();
        $this->assertEquals(
            'Your password has successfully been changed',
            $this->findCss($page, '.alert-success')->getText()
        );

        // Log out
        $this->clickCss($page, '.logoutOptions a.logout');
        $this->snooze();

        // Log back in (using old credentials, which should now fail):
        $this->clickCss($page, '#loginOptions a');
        $this->fillInLoginForm($page, 'username1', 'test');
        $this->clickCss($page, '.modal-body .btn.btn-primary');
        $this->snooze();
        $this->assertLightboxWarning($page, 'Invalid login -- please try again.');

        // Now log in successfully:
        $this->fillInLoginForm($page, 'username1', 'good');
        $this->clickCss($page, '.modal-body .btn.btn-primary');
        $this->snooze();

        // One final log out (to confirm that log in really worked).
        $this->clickCss($page, '.logoutOptions a.logout');
        $this->snooze();
    }

    /**
     * Standard teardown method.
     *
     * @return void
     */
    public static function tearDownAfterClass()
    {
        static::removeUsers(['username1']);
    }
}
