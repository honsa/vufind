<?php
/**
 * RecordLink
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
 * @package  View_Helper
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Swissbib\View\Helper;

use VuFind\View\Helper\Root\RecordLink as VfRecordLink;

/**
 * Build record links
 * Override related method to support ctrlnum type
 *
 * @category Swissbib_VuFind
 * @package  View_Helper
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class RecordLink extends VfRecordLink
{
    /**
     * Related
     *
     * @param string $link   Link
     * @param bool   $escape Escape
     * @param string $source Source
     *
     * @return string
     * @throws \Exception
     */
    public function related($link, $escape = true, $source = DEFAULT_SEARCH_BACKEND)
    {
        if ($link['type'] === 'ctrlnum') {
            return $this->buildCtrlNumRelatedLink($link, $escape);
        } else {
            return parent::related($link, $escape);
        }
    }

    /**
     * Build link for ctrlnum
     *
     * @param String  $link   Link
     * @param Boolean $escape Escape
     *
     * @return string
     */
    protected function buildCtrlNumRelatedLink($link, $escape = true)
    {
        $urlHelper    = $this->getView()->plugin('url');
        $escapeHelper = $this->getView()->plugin('escapeHtml');

        $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=ctrlnum&jumpto=1';

        return $escape ? $escapeHelper($url) : $url;
    }

    /**
     * GetCopyUrl
     *
     * @param array  $item     Item
     * @param string $recordId RecordId
     *
     * @return string
     */
    public function getCopyUrl(array $item, $recordId)
    {
        if (!isset($item['adm_code']) || !isset($item['localid'])
            || !isset($item['sequencenumber'])
        ) {
            return $item['userActions']['photoRequestLink'];
        }

        $urlHelper    = $this->getView()->plugin('url');
        $escapeHelper = $this->getView()->plugin('escapeHtml');
        $bibRecordId = $item['bib_library'] . '-' . $item['bibsysnumber'];
        $itemId = $item['adm_code'] . $item['localid'] . $item['sequencenumber'];

        $url = $urlHelper('record-copy', ['id' => $recordId]) . '?recordId=' .
            $bibRecordId . '&itemId=' . $itemId;

        return $escapeHelper($url);
    }

    /**
     * Alias for getRequestUrl(), to maintain backward compatibility with
     * VuFind 2.2 and earlier versions.
     *
     * @param string|array $item          URL to process
     * @param bool         $includeAnchor Should we include an anchor?
     *
     * @return string
     */
    public function getHoldUrl($item, $includeAnchor = true)
    {
        return $this->getRequestUrl($item, $includeAnchor);
    }

    /**
     * Given a string or array of parts, build a request (e.g. hold) URL.
     *
     * @param string|array $item          URL to process
     * @param bool         $includeAnchor Should we include an anchor?
     *
     * @return string
     */
    public function getRequestUrl($item, $includeAnchor = true)
    {
        if (is_array($item['holdLink'])) {
            // Assemble URL string from array parts:
            $source = $item['holdLink']['source'] ?? DEFAULT_SEARCH_BACKEND;
            $finalUrl
                = $this->getActionUrl(
                    "{$source}|" . $item['holdLink']['record'],
                    $item['holdLink']['action']
                );
            if (isset($item['holdLink']['query'])) {
                $finalUrl .= '?' . $item['holdLink']['query'];
                $finalUrl .= '&institution=' . $item['institution'];
            }
            if (isset($item['holdLink']['anchor']) && $includeAnchor) {
                $finalUrl .= $item['holdLink']['anchor'];
            }
        } else {
            // If URL is already a string but we don't want anchors, strip
            // the anchor now:
            if (!$includeAnchor) {
                list($finalUrl) = explode('#', $item['holdLink']);
            } else {
                $finalUrl = $item['holdLink'];
            }
        }
        // Make sure everything is properly HTML encoded:
        $escaper = $this->getView()->plugin('escapehtml');
        return $escaper($finalUrl);
    }
}
