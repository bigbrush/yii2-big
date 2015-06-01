<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\filemanager;

use Yii;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\JsExpression;
use bigbrush\big\widgets\filemanager\assets\FileManagerAsset;
use bigbrush\big\widgets\filemanager\assets\FileManagerLangAsset;

require_once(__DIR__.'/elfinder/php/elFinderConnector.class.php');
require_once(__DIR__.'/elfinder/php/elFinder.class.php');
require_once(__DIR__.'/elfinder/php/elFinderVolumeDriver.class.php');
require_once(__DIR__.'/elfinder/php/elFinderVolumeLocalFileSystem.class.php');

/**
 * FileManager
 */
class FileManager extends Widget
{
    const STATE_RENDER = 1;
    const STATE_UPDATE = 2;

    const SESSION_VAR_ICON_URL = '_filemanagaer_icon_url';

    /**
     * @var array client options to provide for elFinder.
     * @see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#contents
     */
    public $clientOptions = [];
    /**
     * @var array options to provide for the elFinder connector.
     * @see https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
     */
    public $connectorConfig = [];
    /**
     * @var string url to the controller handling elFinder UI updates.
     * In a controller action run the widget like this:
     * ~~~php
     * FileManager::widget([
     *     'state' => FileManager::STATE_UPDATE 
     * ]); 
     * ~~~
     */
    public $url = '/big/media/update';
    /**
     * @var string folder for media. Relative to [[baseUrl]] and [[basePath]].
     * Must not start with a slash "/".
     */
    public $folder = 'media/filemanager/';
    /**
     * @var string base url of the file manager. Must end with a slash "/".
     * Defaults to Url::home().
     */
    public $baseUrl;
    /**
     * @var string base path of the file manager. Must end with a slash "/".
     */
    public $basePath = '@app/';
    /**
     * @var boolean set to true to use the Yii csrf token in elFinder.
     * Used when upload is allowed in the file manager. Defaults to true.
     * @see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#customData
     */
    public $enableCsrfToken = true;
    /**
     * @var string|JsExpression a custom javascript callback triggered when an image is clicked.
     */
    public $onClickCallback;
    /**
     * @var int the current state of the widget.
     */
    public $state = self::STATE_RENDER;

    
    /**
     * Updates the elFinder UI
     */
    public function update()
    {
        // used in elFinderVolumeLocalFileSystem::__construct() to render the volume_icon file by elfinder
        define('ELFINDER_IMG_PARENT_URL', Yii::$app->getSession()->get(self::SESSION_VAR_ICON_URL));

        Yii::$app->response->format = Response::FORMAT_JSON;
        $config = ArrayHelper::merge($this->getDefaultConnectorConfig(), $this->connectorConfig);
        $connector = new \elFinderConnector(new \elFinder($config));
        $connector->run();
    }

    /**
     * Runs elFinder.
     *
     * @return string the rendering result
     */
    public function run()
    {
        if ($this->state === static::STATE_UPDATE) {
            $this->update(); // will exit the application
        }

        $view = $this->getView();
        $bundle = FileManagerAsset::register($view);
        // save assets bundle url so the volume driver file icon is displayed in elFinder
        Yii::$app->getSession()->set(self::SESSION_VAR_ICON_URL, Url::to($bundle->baseUrl . '/'));
        
        $options = [];
        $options['url'] = Yii::$app->getUrlManager()->createUrl($this->url);
        // elfinder uses short language codes
        $language = substr(Yii::$app->language, 0, 2);
        if ($language !== 'en') {
            $options['lang'] = $language;
            $bundle->js[] = 'js/i18n/elfinder.'.$language.'.js';
        }
        if ($this->enableCsrfToken) {
            $request = Yii::$app->getRequest();
            $options['customData'] = [$request->csrfParam => $request->getCsrfToken()];
        }
        if ($this->onClickCallback !== null) {
            if (is_string($this->onClickCallback)) {
                $options['getFileCallback'] = new JsExpression($this->onClickCallback);
            } else {
                $options['getFileCallback'] = $this->onClickCallback;
            }
        }
        $options = ArrayHelper::merge($options, $this->clientOptions);
        $options = Json::encode($options);
        $view->registerJs("$('#elfinder').elfinder($options);");
        return $this->render('index');
    }

    /**
     * Returns an configuration array for elFinder
     *
     * @return array
     * @see https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
     */
    public function getDefaultConnectorConfig()
    {
        return [
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'path' => Yii::getAlias($this->basePath . $this->folder),
                    'URL' => ($this->baseUrl !== null ? $this->baseUrl : Url::home()) . $this->folder,
                    'uploadAllow' => array('image'),
                    'attributes' => [
                        [
                            'pattern' => '/.tmb|.quarantine|.gitignore/',
                            'hidden'  => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}