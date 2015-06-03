<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\core;

use Yii;
use yii\base\Object;
use yii\base\InvalidParamException;

/**
 * Parser
 */
class Parser extends Object
{
    /**
     * @var string the data currently being parsed.
     */
    private $_data;


    /**
     * Registers data to be parsed.
     *
     * @param string $data the data to be parsed.
     * @throws InvalidParamException if the provided data is not a string.
     */
    public function setData($data)
    {
        if (is_string($data) === false) {
            throw new InvalidParamException("Data registered in bigbrush\big\core\Parser must be of type 'string'. Registered: ".gettype($this->_data));
        }
        $this->_data = $data;
    }

    /**
     * Returns the data currently being parsed.
     *
     * @return string|null the data currently being parsed. Null if no data has been registered.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Clears up the registered data.
     */
    public function clear()
    {
        return $this->_data = null;
    }

    /**
     * Runs the entire process of parsing.
     *
     * @param string $data the data to be parsed.
     * @return string the data after it has been processed.
     * @throws InvalidParamException in [[setData()]].
     */
    public function run($data, $blocks = [])
    {
        $this->setData($data);
        $config = $this->extractAttributes($this->parseIncludeStatements());
        $this->parseData($config, $blocks);
        $this->parseUrls();
        $data = $this->getData();
        $this->clear();
        return $data;
    }

    /**
     * Identifies all "<big:include position="POSITION" ... />" and returns an array with all matches.
     * 
     * Include statements MUST include the position attribute, see below where "position" is "sidebar"
     *     <big:include position="sidebar" />
     * 
     * You can add additional attributes to the include statement, like below (order of the attributes
     * is not important, as long as position is the first attribute)
     *     <big:include position="sidebar" type="widget" autostart="false" color="green" />
     * 
     * @return array all matches as an associative array
     * The structure of the $array:
     *    $array[0] => array of all include statements
     *    $array[1] => array of the postion found in the include statement
     *    $array[2] => array of all attributes in the include statement
     */
    public function parseIncludeStatements()
    {
        preg_match_all('#<big:include\ position="([^"]+)" (.*)\/>#iU', $this->_data, $matches);
        return $matches;
    }

    /**
     * Finds all attributes assigned to an include statement and returns an configuration array.
     * See [[parseIncludeStatements()]] for more information about include statements.
     *
     * Returns an array like the following:
     *     [
     *         INCLUDE_STATEMENT => [
     *             'position' => POSITION_ATTRIBUTE (must be set and not empty to be included)
     *             'config' => [ 
     *                  ATTRIBUTE_KEY  => ATTRIBUTE_VALUE,
     *                  ATTRIBUTE_KEY  => ATTRIBUTE_VALUE,
     *                  ...
     *              ]
     *         ],
     *         ...
     *     ]
     *
     * @param array $matches an associative array created from [[parseIncludeStatements()]].
     * @return array list of configuration arrays for include statements.
     */
    public function extractAttributes($matches)
    {
        $config = [];
        foreach($matches[0] as $i => $includeStatement) {
            $item = [
                'position' => $matches[1][$i],
                'config' => [],
            ];
            // extract properties from include statement
            preg_match_all('/([\w:-]+)[\s]?=[\s]?"([^"]*)"/i', $matches[2][$i], $properties);
            // create configuration array from extracted properties
            foreach ($properties[0] as $i => $property) {
                $item['config'][$properties[1][$i]] = $properties[2][$i];
            }
            $config[$includeStatement] = $item;
        }
        return $config;
    }

    /**
     * Parses the provided [[_data]] by replacing all include statements with content from blocks.
     *
     * @param array $config configuration for the render process. Use [[extractAttributes()]]
     * for the correct format.
     */
    public function parseData($config, $blocks = [])
    {
        $replace = [];
        $with = [];
        foreach($config as $includeStatement => $params) {
            $replace[] = $includeStatement;
            if (isset($blocks[$params['position']])) {
                $with[] = implode("\n", $blocks[$params['position']]);
            } else {
                $with[] = '';
            }
        }
        $this->_data = str_replace($replace, $with, $this->_data);
    }

    /**
     * This methods does 2 things. First it converts all internal urls to a SEO friendly
     * version. Second it prepends the application home url to href, src and poster tags.
     *
     * This method is used to facilitate easy porting from one platform to another. This requires
     * dynamic URLs when saving editor content, as the application could be moved to a subdomain.
     */
    public function parseUrls()
    {
        $content = $this->_data;
        // convert internal urls to SEO friendly URLs
        $regex  = '#href="index.php\?([^"]*)#m';
        $content = preg_replace_callback($regex, array($this, 'replaceUrl'), $content);

        // prefix the protocol to src, href and poster tags inserted by the editor.
        // This ensures images and links to media content will work on subdomains.
        $base   = Yii::$app->homeUrl;
        $protocols  = '[a-zA-Z0-9]+:';
        $regex = '#(src|href|poster)="(?!/|'.$protocols.'|\#|\')([^"]*)"#m';
        $content = preg_replace($regex, "$1=\"$base\$2\"", $content);
        $this->_data = $content;
    }

    /**
     * Callback for a preg_replace_callback() - see [[parseUrls()]] for information.
     * Tries to construct a seo friendly url from a dynamic url.
     * 
     * @param array $matches array of matches .
     * @return string the constructed url.
     */
    public function replaceUrl($matches)
    {
        return 'href="'.Yii::$app->big->urlManager->parseInternalUrl($matches[1]);
    }
}
