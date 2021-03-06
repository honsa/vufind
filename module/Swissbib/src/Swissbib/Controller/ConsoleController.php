<?php
/**
 * Controller for manage console commands.
 *
 * PHP version 7
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
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
 * @category Swissbib_VuFind
 * @package  Controller
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Swissbib\Controller;

use Swissbib\Services\NationalLicence;
use Swissbib\Services\Pura;

/**
 * Class ConsoleController.
 *
 * @category Swissbib_VuFind
 * @package  Controller
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class ConsoleController extends BaseController
{
    /**
     * Send the National Licence user list export in .csv format via e-mail.
     *
     * @return void
     * @throws \Exception
     */
    public function sendNationalLicenceUsersExportAction()
    {
        /**
         * National Licence service.
         *
         * @var NationalLicence $nationalLicenceService
         */
        $nationalLicenceService = $this->serviceLocator
            ->get('Swissbib\NationalLicenceService');
        $nationalLicenceService->sendExportEmail();
    }

    /**
     * Send the Pura monthly report
     *
     * @return void
     * @throws \Exception
     */
    public function sendPuraReportAction()
    {
        /**
         * Pura service.
         *
         * @var Pura $puraService Pura Service
         */
        $puraService = $this->serviceLocator
            ->get('Swissbib\PuraService');
        $puraService->sendPuraReport('Z01');
        $puraService->sendPuraReport('E65');
    }

    /**
     * Script command for update the national licence users with their
     * new attributes.
     *
     * @return void
     * @throws \Exception
     */
    public function updateNationalLicenceUserInfoAction()
    {
        echo "\r\n\r\n-------------------------------\r\n";
        echo "Update national licence users info cron job started.\r\n";
        $date = date("Y-m-d H:i:s");
        echo $date . "\r\n";
        echo "Process all users, this takes a long time (15-20 minutes)...\r\n";

        //These lines allow to retrieve the route urls from the controller command
        //http://stackoverflow.com/questions/27295895/how-can-i-
        //create-a-url-in-a-console-controller-in-zf2
        $event = $this->getEvent();
        $http = $this->serviceLocator->get('HttpRouter');
        $router = $event->setRouter($http);
        $request = new \Zend\Http\Request();
        $request->setUri('');
        $router = $event->getRouter();
        $routeMatch = $router->match($request);

        /**
         * National licence service.
         *
         * @var NationalLicence $nationalLicenceService
         */
        $nationalLicenceService = $this->serviceLocator
            ->get('Swissbib\NationalLicenceService');
        $nationalLicenceService->checkAndUpdateNationalLicenceUserInfo();
    }

    /**
     * Script command for update the national licence users with their
     * new attributes.
     *
     * @return void
     * @throws \Exception
     */
    public function updatePuraUserAction()
    {
        echo "\r\n\r\n-------------------------------\r\n";
        echo "Update Pura Users cron job started.\r\n";
        $date = date("Y-m-d H:i:s");
        echo $date . "\r\n";
        echo "Process all users...\r\n";

        //These lines allow to retrieve the route urls from the controller command
        //http://stackoverflow.com/questions/27295895/how-can-i-
        //create-a-url-in-a-console-controller-in-zf2
        $event = $this->getEvent();
        $http = $this->serviceLocator->get('HttpRouter');
        $router = $event->setRouter($http);
        $request = new \Zend\Http\Request();
        $request->setUri('');
        $router = $event->getRouter();
        $routeMatch = $router->match($request);

        /**
         * Pura service.
         *
         * @var Pura $puraService
         */
        $puraService = $this->serviceLocator
            ->get('Swissbib\PuraService');
        $puraService->checkValidityPuraUsers();
    }
}
