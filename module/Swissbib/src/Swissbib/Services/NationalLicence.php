<?php
/**
 * Service for manage the National Licence users.
 *
 * PHP version 5
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category Swissbib_VuFind2
 * @package  Services
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Swissbib\Services;


use Swissbib\Libadmin\Exception\Exception;
use Swissbib\VuFind\Db\Row\NationalLicenceUser;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class NationalLicence
 * @package Swissbib\Services
 */
class NationalLicence implements ServiceLocatorAwareInterface
{
    /**
     * ServiceLocator
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;
    /** @var  array  $config */
    protected $config;
    /** @var  SwitchApi $switchApi */
    protected $switchApiService;
    /** @var Email $emailService */
    protected $emailService;

    /**
     * NationalLicence constructor.
     *
     * @param SwitchApi $switchApiService
     * @param Email $emailService
     * @param array $config
     */
    public function __construct($switchApiService, $emailService, $config)
    {
        $this->switchApiService = $switchApiService;
        $this->emailService = $emailService;
        $this->config = $config['swissbib']['national_licence_service'];
    }

    /**
     * Set serviceManager instance
     *
     * @param ServiceLocatorInterface $serviceLocator ServiceLocatorInterface
     *
     * @return void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Retrieve serviceManager instance
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Create a temporary access for the user valid for 14 days. If the user id is not provided
     * the current user in the $_SERVER variable will be used.
     *
     * @param int $persistentId
     * @return bool
     * @throws \Exception
     */
    public function createTemporaryAccessForUser($persistentId = null) {

        /** @var NationalLicenceUser $user */
        $user = $this->getCurrentNationalLicenceUser($persistentId);

        $this->checkIfUserIsBlocked($user);

        if($user->hasAlreadyRequestedTemporaryAccess()) {
            throw new \Exception("snl.youHaveAlreadyRequestedTemporary");
        }

        if(!$user->hasAcceptedTermsAndConditions()){
            throw new \Exception("snl.pleaseAcceptTermsAndConditions");
        }

        /** @var string $mobile */
        $mobile = $_SERVER['mobile'];

        if (!$mobile) {
            throw new \Exception("snl.youDontHaveMobilePhoneNumeber");
        }

        if(!$this->isSwissPhoneNumber($mobile)) {
            throw new \Exception("snl.mobilePhoneNumberIsNotSwissError".$mobile);
        }

        return $user->setTemporaryAccess();
    }

    /**
     * Persist information if the user accepts the terms and conditions
     */
    public function acceptTermsConditions()
    {
        /** @var NationalLicenceUser $user */
        $user = $this->getCurrentNationalLicenceUser();
        $this->checkIfUserIsBlocked($user);
        $user->setConditionsAccepted(true);
        $user->save();
    }

    /**
     * Get the current national licence user if it exists
     *
     * @param null $persistentId
     * @return NationalLicenceUser
     * @throws \Exception
     */
    public function getCurrentNationalLicenceUser($persistentId = null)
    {
        // If no id is passed in the parameter take de current user
        if(empty($persistentId) AND isset($_SERVER["persistent-id"])) {
            $persistentId = $_SERVER["persistent-id"];
        }
        if(empty($persistentId)) {
            throw new \Exception("Error retrieving the current user.");
        }
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->getTable("\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser");
        $user= $userTable->getUserByPersistentId($persistentId);
        if(empty($user)) {
            throw new \Exception("Impossible to retrieve the current user in the database");
        }
        return $user;
    }

    /**
     * Get a NationalLicenceUser or creates a new one if is not existing in the database
     *
     * @param string $persistentId Edu-id persistent id
     * @param array $userFields Array of national licence user fields with their values.
     * @return NationalLicenceUser $user
     */
    public function getOrCreateNationalLicenceUserIfNotExists($persistentId, $userFields = [])
    {
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->getTable('\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser');
        $user = $userTable->getUserByPersistentId($persistentId);

        if(empty($user)) {
            $user = $userTable->createNationalLicenceUserRow($persistentId, $userFields);
        }

        return $user;
    }

    /**
     * Check is it's a swiss phone number
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isSwissPhoneNumber($phoneNumber){
        if(!$phoneNumber) return false;
        $prefix = substr( $phoneNumber, 0, 6 );
        foreach ($this->config['allowed_mobile_prefixes'] as $allowedPrefix) {
            if ($prefix === $allowedPrefix) return true;
        }

        return false;
    }

    /**
     * Check if the current user is compliant with the Swiss National Licence
     *
     * @return bool
     */
    public function isNationalLicenceCompliant()
    {
        $user = $this->getCurrentNationalLicenceUser();

        // Has accepted terms and conditions
        /** @var NationalLicenceUser $user */
        if (!$user->hasAcceptedTermsAndConditions()) return false;
        // Is not blocked by the administrators
        if ($user->isBlocked()) return false;
        // Last activity at least in the last 12 months
        if(!$this->hasBeenActiveInLast12Months($user)) return false;
        // Has requested a temporary access || Has a verified home postal address
        $hasTemporaryAccess = $user->hasAlreadyRequestedTemporaryAccess() &&
                              $this->isTemporaryAccessCurrentlyValid($user);
        $hasVerifiedSwissAddress = $this->hasVerifiedSwissAddress();

        return $hasTemporaryAccess || $hasVerifiedSwissAddress ;
    }

    /**
     * Check if the user has been active in the last 12 Months
     *
     * @param NationalLicenceUser $user
     * @return bool
     */
    protected function hasBeenActiveInLast12Months($user) {
        //TODO: Change this method. This will be provided as a shibbboleth attribute by SWITCH.
        $pastPoint = (new \DateTime())->modify("-12 months");
        if ($pastPoint > $user->getLastActivityDate()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the current user has a verified swiss address
     *
     * @return bool
     */
    public function hasVerifiedSwissAddress() {
        // Get shibboleth attributes from $_SERVER variable
        $homePostalAddress           = isset($_SERVER['homePostalAddress']) ? $_SERVER['homePostalAddress']: null;
        $swissLibraryPersonResidence = isset($_SERVER['swissLibraryPersonResidence']) ?
            $_SERVER['swissLibraryPersonResidence'] : null;

        if ($swissLibraryPersonResidence){
            if($swissLibraryPersonResidence === 'CH' && $this->isVerifiedHomePostalAddress())
                return true;
        } else {
            if ($homePostalAddress &&
                $this->isAddressInSwitzerland($homePostalAddress) &&
                $this->isVerifiedHomePostalAddress()
            )
                return true;
        }

        return true;//TODO: change this to false after test
    }

    /**
     * Checks if the user have a verified home postal address in their edu-ID account
     * TODO: This is only for test, the string has been taken from the Switch attribute viewer
     * @param string $string Assurance level string.
     * @return bool
     * @throws \Exception
     */
    protected function isVerifiedHomePostalAddress($string = "mobile:https://eduid.ch/def/loa2;mail:https://eduid.ch/def/loa2;homePostalAddress:https://eduid.ch/def/loa1")
    {
        $singleElements = explode(";", $string);
        $qualityLevelString = null;
        foreach ($singleElements as $singleElement) {
            $parts = explode(":", $singleElement);
            if($parts[0] === "homePostalAddress")
                $qualityLevelString = $parts[count($parts)-1];
        }
        if(empty($qualityLevelString)) {
            throw new \Exception("Assurance level for 'homePostalAddress' attribute not found");
        }
        $qualityLevel = substr($qualityLevelString, -4);
        //echo $qualityLevel;
        if("loa1" === $qualityLevel) return false;
        if("loa2" === $qualityLevel) return true;
        if("loa3" === $qualityLevel) return true;
        throw new \Exception("Assurance level format is incorrect");
    }

    /**
     * Check if the temporary access of the user is still valid.
     *
     * @param NationalLicenceUser $user
     * @return bool
     */
    public function isTemporaryAccessCurrentlyValid($user) {
        if(new \DateTime() > $user->getExpirationDate()) {
            return false;
        }

        return true;
    }

    /**
     * Get a database table object.
     *
     * @param string $table Name of table to retrieve
     *
     * @return \VuFind\Db\Table\Gateway
     */
    protected function getTable($table)
    {
        return $this->getServiceLocator()
            ->get('VuFind\DbTablePluginManager')
            ->get($table);
    }

    /**
     * Check if it is a swiss address
     *
     * @param string $homeAddressString Home address from Swiss edu-ID account
     * @return bool                     True if it's a Swiss address False otherwise
     */
    public function isAddressInSwitzerland($homeAddressString)
    {
        $parts = explode("$", $homeAddressString);
        $state = $parts[count($parts)-1];

        return $state === 'Switzerland';
    }

    /**
     * Check if the user account is blocked
     *
     * @param NationalLicenceUser $user
     * @throws Exception
     */
    protected function checkIfUserIsBlocked($user)
    {
        if($user->isBlocked()){
            throw new Exception("snl.impossibleProcessTheActionUserAccountBlockedError");
        }
    }

    /**
     * Set request permanent access to user
     *
     * @param NationalLicenceUser $user
     */
    public function setPermanentAccess($user = null)
    {
        if(empty($user)) {
            $user = $this->getCurrentNationalLicenceUser();
        }
        $user->setRequestPermanentAccess();
        $user->save();
    }

    /**
     * This method sets the national-licence-compliant flag in the SWITCH API
     *
     * @param NationalLicenceUser $user
     * @throws \Exception
     */
    public function setNationalLicenceCompliantFlag($user = null)
    {
        if (empty($user)) {
           $user = $this->getCurrentNationalLicenceUser();
        }

        if(!$user->hasAcceptedTermsAndConditions()){
            throw new \Exception("snl.pleaseAcceptTermsAndConditions");
        }

        if (!$this->isNationalLicenceCompliant()) {
            throw new \Exception("User is not compliant with the Swiss National Licence");
        }
        /** @var SwitchApi $switchApiService */
        $this->switchApiService->setNationalCompliantFlag($user->getEduId());

        $this->setPermanentAccess($user);
    }

    /**
     * Check if user has access to the national licence content.
     *
     * @param NationalLicenceUser $user
     * @return boolean
     */
    public function hasAccessToNationalLicenceContent($user)
    {
        if(!$user->hasAcceptedTermsAndConditions()) return false;
        if($user->hasAlreadyRequestedTemporaryAccess() && $this->isTemporaryAccessCurrentlyValid($user)){
            return true;
        }
        if($user->hasRequestPermanentAccess()) return true;
        return false;
    }

    /**
     * Get list of all national licence users and their related vufind user.
     *
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getListNationalLicenceUserWithVuFindUsers()
    {
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->getTable('\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser');

        return $userTable->getList();
    }

    /**
     * Send e-mail.
     *
     * @param string $to The receiver of the mail.
     */
    public function sendExportEmailTo($to = null)
    {
        $users = $this->getListNationalLicenceUserWithVuFindUsers();
        $path = getcwd() . $this->config['user_export_path'] . "/" . $this->config['user_export_filename'];
        $attachmentFilePath = $this->createCsvFileFromListUsers($path, $users);
        $this->emailService->sendMail($to, "This is an export of the National Licence users.", $attachmentFilePath);
    }

    /**
     * Create a csv file export from the list of users
     *
     * @param string $path Path of the created file
     * @param array(NationalLicenceUser) $users List of National Licence users
     * @return string
     */
    protected function createCsvFileFromListUsers($path, $users)
    {
        $fieldsNationalLicenceUser = $this->config['national_licence_user_fields_to_export'];
        $fieldVuFindUser = $this->config['vufind_user_fields_to_export'];

        $file = fopen($path, 'w+') or die("Unable to open file!");

        //Header
        $str="";
        foreach ($fieldsNationalLicenceUser as $field) {
            $str = $str . $field . ",";
        }
        foreach ($fieldVuFindUser as $field) {
            $str = $str . $field . ",";
        }
        $str = $str . "\r\n";
        fwrite($file, $str);

        //Data
        /** @var NationalLicenceUser $user */
        foreach ($users as $user) {
            $str = "";
            foreach ($fieldsNationalLicenceUser as $field) {
                $str = $str . $user->$field . ",";
            }
            foreach ($fieldVuFindUser as $field) {
                $str = $str . $user->getRelUser()->$field . ",";
            }
            $str = $str . "\r\n";
            fwrite($file, $str);
        }
        fclose($file);
        return $path;
    }

    /**
     * Check if the homePostalAddress of the user is still compliant with the Swiss National Licence.
     * TODO
     * @param NationalLicenceUser $user
     * @return boolean
     */
    protected function isHomePostalAddressNationalLicenceCompliant($user)
    {
        //No address -->remove flag
        //Postal address not verified anymore --> remove user to national compliant group
        //Postal address not in CH -->remove user to national compliant group
        return false;
    }

    /**
     * Check and update the National Licence user with the last attributes in their edu-ID account and update
     * the flag accordingly.
     * TODO
     */
    public function checkAndUpdateNationalLicenceUserInfo()
    {
        //Get list of users
        $users = $this->getListNationalLicenceUserWithVuFindUsers();
        //Foreach users
        foreach ($users as $user) {
            //Update attributes from the edu-Id account
            //If last activity date < last 12 month
            //If last_account_extension_request == null
            //Send and email to the user for exteding their account
            //Set the last_account_extension_request to now
            //Else if last_account_extension_request < 10 days ago
            //Unset the national licence compliant glag

            //if user is not anymore compliant with their homePostalAddress
            if(!$this->isHomePostalAddressNationalLicenceCompliant($user)) {
                //Unset the national licence compliant flag
            }
        }
    }

}