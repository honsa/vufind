<?php
/**
 * Jusbib ExtendedSolrFactoryHelper
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * This program is
 * free software; you can redistribute it and/or modify
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
 * @category Jusbib_VuFind2
 * @package  VuFind_Search_Helper
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Jusbib\VuFind\Search\Helper;

use Swissbib\VuFind\Search\Helper\ExtendedSolrFactoryHelper
    as SwissbibExtendedSolrFactoryHelper;

/**
 * Jusbib ExtendedSolrFactoryHelper
 *
 * @category Jusbib_VuFind2
 * @package  VuFind_Search_Helper
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class ExtendedSolrFactoryHelper extends SwissbibExtendedSolrFactoryHelper
{
    /**
     * Get namespace
     * swissbib namespace for extensible targets, else default namespace
     *
     * @param String $name Name
     *
     * @return String
     */
    public function getNamespace($name)
    {
        if ($this->isExtendedTarget($name)) {
            return 'Jusbib\VuFind\Search';
        } else {
            return 'VuFind\Search';
        }
    }
}
