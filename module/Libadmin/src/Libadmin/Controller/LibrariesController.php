<?php
/**
 * LibrariesController
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
 * @category Libadmin_VuFind2
 * @package  Controller
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Libadmin\Controller;

use Libadmin\Institution\InstitutionLoader;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * LibrariesController
 *
 * @category Libadmin_VuFind2
 * @package  Controller
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class LibrariesController extends AbstractActionController
{
    /**
     * Shows grouped Institutions
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $institutionLoader  = new InstitutionLoader();
        $viewModel          = new ViewModel();

        $viewModel->setTemplate('libraries/content');
        $viewModel->groupedInstitutions = $institutionLoader
            ->getGroupedInstitutions();
        $requestVars = $this->getRequest()->getQuery()->toArray();
        $this->layout()->setVariable('pageClass', 'template_page');
        $viewModel->isPreview = array_key_exists('preview', $requestVars) ?
            $requestVars['preview'] == 1 : false;

        return $viewModel;
    }
}
