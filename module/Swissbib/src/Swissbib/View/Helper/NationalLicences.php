<?php
/**
 * NationalLicences
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 1/2/13
 * Time: 4:09 PM
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
 * @package  RecordDriver_Helper
 * @author   Matthias Edel <matthias.edel@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Swissbib\RecordDriver\SolrMarc;

/**
 * Return URL for NationalLicence online access if applicable. Otherwise 'false'.
 * Config URLs in TargetsProxy.ini.ini[SwissAcademicLibraries]
 *
 * @category Swissbib_VuFind2
 * @package  RecordDriver_Helper
 * @author   Matthias Edel <matthias.edel@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class NationalLicences extends AbstractHelper
{
    protected $config;
    protected $record;
    protected $marcFields;

    /**
     * NationalLicences constructor.
     *
     * @param VuFind\Config $config config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Return the url for the record if it's available with NL, otherwise false
     *
     * @param SolrMarc $record the record object
     *
     * @return bool|String
     */
    public function getUrl(SolrMarc $record)
    {
        $this->record = $record;
        $this->marcFields = $record->getNationalLicenceData();

        if ($this->marcFields[0] !== "NATIONALLICENCE") return false;

        $issn = $this->marcFields[3];
        $enumeration = $this->marcFields[2];
        $splitted = explode(":", $enumeration);
        $volume = $splitted[0];
        $issuePage = explode("<", $splitted[1]);
        $issue = $issuePage[0];
        $page = $issuePage[1];
        $doi = $record->getDOIs()[0];
        $doiSuffix = explode("/", $doi, 2)[1];
        $journalCode = $this->marcFields[4];

        $userIsAuthorized = isset($_SERVER['entitlement']) ?
                            $_SERVER['entitlement'] === 'urn:mace:dir:entitlement:common-lib-terms' : false;

        $url = $this->buildUrl($userIsAuthorized, $issn, $volume, $issue, $page, $doiSuffix, $doi, $journalCode);

        return $url;
    }

    /**
     * Build the url.
     *
     * @param String $userAuthorized user authorized?
     * @param String $issn           issn
     * @param String $volume         volume
     * @param String $issue          issue
     * @param String $sPage          start page
     * @param String $doiSuffix      doi suffix
     *
     * @return null
     */
    protected function buildUrl($userAuthorized, $issn, $volume, $issue, $sPage, $doiSuffix, $doi, $journalCode)
    {
        $url = $this->getPublisherBlueprintUrl($userAuthorized);
        $url = str_replace('{ISSN}', $issn, $url);
        $url = str_replace('{VOLUME}', $volume, $url);
        $url = str_replace('{ISSUE}', $issue, $url);
        $url = str_replace('{SPAGE}', $sPage, $url);
        $url = str_replace('{DOI-SUFFIX}', $doiSuffix, $url);
        $url = str_replace('{DOI}', $doi, $url);
        $url = str_replace('{JOURNAL-CODE}', $journalCode, $url);
        return $url;
    }

    /**
     * Return skeleton for url.
     *
     * @param String $userAuthorized user authorized?
     *
     * @return null
     */
    protected function getPublisherBlueprintUrl($userAuthorized)
    {
        /* config.ini:
        [PublisherUrls]
        nl-oxford-unauthorized=
        nl-gruyter-unauthorized= https://www.degruyter.com/applib/openathens?entityID=https%3A%2F%2Feduid.ch%2Fidp%2Fshibboleth&openAthens2Redirect=https%3A%2F%2Fwww.degruyter.com%2Fopenurl%3Fgenre%3Darticle%26issn%3D{ISSN}%26volume%3D{VOLUME}%26issue%3D{ISSUE}%26spage%3D{SPAGE}
        nl-cambridge-unauthorized=https://shibboleth.cambridge.org/Shibboleth.sso/discovery?entityID=https%3A%2F%2Feduid.ch%2Fidp%2Fshibboleth&target=https://shibboleth.cambridge.org/CJOShibb2/index?app=https://www.cambridge.org/core/shibboleth?ref=%2Fcore%2Fproduct%2Fidentifier%2F{DOI-SUFFIX}%2Ftype%2FJOURNAL_ARTICLE
        nl-oxford-authorized=
        nl-gruyter-authorized=https://www.degruyter.com/openurl?genre=article&issn={ISSN}&volume={VOLUME}&issue={ISSUE}&spage={SPAGE}
        nl-cambridge-authorized=http://www.cambridge.org/core/product/identifier/{DOI-SUFFIX}/type/JOURNAL_ARTICLE
         */

        $urlBlueprintKey = ($userAuthorized ? "" : "un") . "authorized";
        $publisher = $this->marcFields[1];
        switch ($publisher)
            {
        case 'NL-gruyter':
            $urlBlueprintKey = 'nl-gruyter-' . $urlBlueprintKey;
            break;
        case 'NL-cambridge':
            $urlBlueprintKey = 'nl-cambridge-' . $urlBlueprintKey;
            break;
        case 'NL-oxford':
            $urlBlueprintKey = 'nl-oxford-' . $urlBlueprintKey;
            break;
        }

        $blueprintUrl = "";
        if (isset($this->config->PublisherUrls->$urlBlueprintKey)) {
            $blueprintUrl = $this->config->PublisherUrls->$urlBlueprintKey;
        }

        return $blueprintUrl;
    }

}
