<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\Routing\ViewHelpers;

/**
 * URI ViewHelper.
 *
 * @package     routing
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   2014-2017 Causal Sàrl
 * @license     https://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UriViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Renders a route URI.
     *
     * @param string $route
     * @return string
     */
    public function render($route)
    {
        // TODO: Take a global configuration to possibly have a nicer prefix, if available
        $routingUri = '/?eID=routing&route=' . $route;
        return $routingUri;
    }
}
