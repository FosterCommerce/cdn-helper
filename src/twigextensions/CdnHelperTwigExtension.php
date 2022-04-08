<?php
/**
 * CDN Helper plugin for Craft CMS 3.x
 *
 * A custom plugin that provides helper functions for working with CDN assets.
 *
 * @link      https://clickrain.com
 * @copyright Copyright (c) 2018 Click Rain
 */

namespace clickrain\cdnhelper\twigextensions;

use clickrain\cdnhelper\CdnHelper;

use Craft;
use ErrorException;
use craft\web\View;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    Click Rain
 * @package   CdnHelper
 * @since     1
 */
class CdnHelperTwigExtension extends \Twig_Extension
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'CdnHelper';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('cdnUrl', [$this, 'cdnUrl']),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
    * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('cdnUrl', [$this, 'cdnUrl']),
            new \Twig_SimpleFunction('cdnCss', [$this, 'cdnCss']),
        ];
    }

    /**
     * Prepends the configured CDN URL to the given URL
     *
     * @access    public
     * @param     $url
     * @return    string
     */
    public function cdnUrl($url = null)
    {
        if (!$cdnUrl = $this->getCdnUrl()) {
            return $url;
        }

        return $this->join($cdnUrl, $url);
    }

    /**
     * Returns the contents of the given template file with relative
     * asset URLs replaced with CDN URLs
     *
     * @access    public
     * @param     $filename
     * @return    string
     */
    public function cdnCss($filename)
    {
        $path = \Craft::$app->view->resolveTemplate($filename);

        // if the path doesn't exist, throw an exception
        if (!$path) {
            if (Craft::$app->config->general->devMode) {
                throw new ErrorException('Could not find the specified template file: ' . $filename . ' (path returned: ' . $path . ')');
            } else {
                return '';
            }
        }

        $contents = file_get_contents($path);

        if (!$cdnUrl = $this->getCdnUrl()) {
            return $contents;
        }

        // lookup all instances of url() in the css
        $pattern = '/url(?:\([\'"]?)(.*?)(?:[\'"]?\))/';
        preg_match_all($pattern, $contents, $matches);

        // replace all relative URLs with absolute CDN urls
        foreach ($matches[1] as $index => $match) {
            // if this is a data url or already has a domain prefix... move on
            if (strpos($match, 'data:') === 0) {
                continue;
            }

            if (strpos($match, 'http://') === 0) {
                continue;
            }

            if (strpos($match, 'https://') === 0) {
                continue;
            }

            $new = 'url(\'' . $this->join($cdnUrl, $match) . '\')';
            $contents = str_replace($matches[0][$index], $new, $contents);
        }

        return $contents;
    }

    /**
     * Get the CDN URL from the site configuration
     */
    protected function getCdnUrl()
    {
        $vars = Craft::$app->config->general->aliases;
        return array_key_exists('cdnUrl', $vars) ? $vars['cdnUrl'] : null;
    }

    /**
     * Join all strings passed into this function
     *
     * Look Ma! No args!
     *
     * Takes an indefinite number of string arguments
     */
    protected function join()
    {
        $paths = [];

        foreach (func_get_args() as $arg) {
            if ($arg !== '') { $paths[] = $arg; }
        }

        return preg_replace('#(?<!:)/+#', '/', join('/', $paths));
    }
}
