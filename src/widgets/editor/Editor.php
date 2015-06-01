<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\editor;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;
use bigbrush\big\widgets\editor\assets\EditorAsset;
use bigbrush\big\widgets\editor\assets\EditorLangAsset;

/**
 * Editor
 * 
 * Provides a TinyMCE widget used in form fields
 */
class Editor extends InputWidget
{
    /**
     * @var string the language to use in the editor. Defaults to "en".
     * You can install additional languages by going to the TinyMCE website
     * @see http://www.tinymce.com/i18n/
     * 
     * Downloaded language files should be dropped in @bigbrush/big/widgets/editor/langs/
     */
    public $language;
    /**
     * @var string the document base url for TinyMCE. Must end with a slash "/".
     * Defaults to Url::home().
     * @see http://www.tinymce.com/wiki.php/Configuration:document_base_url
     */
    public $baseUrl;
    /**
     * @var array options to provide for TinyMCE
     * @see http://www.tinymce.com/wiki.php/Configuration
     */
    public $clientOptions = [];
    /**
     * @var boolean whether ajax validation is used in the form.
     * @see https://github.com/2amigos/yii2-tinymce-widget/blob/master/src/TinyMce.php
     */
    public $useAjaxValidation = true;


    /**
     * Runs the widget 
     */
    public function run()
    {
        if ($this->hasModel()) {
            echo Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            echo Html::textarea($this->name, $this->value, $this->options);
        }
        $this->registerClientScript();
    }

    /**
     * Adds TinyMCE clientscript 
     */
    protected function registerClientScript()
    {
        $js = [];
        $view = $this->getView();

        EditorAsset::register($view);

        if (is_array($this->clientOptions) && !empty($this->clientOptions)) {
            $this->clientOptions = array_merge($this->getDefaultClientOptions(), $this->clientOptions);
        } else {
            $this->clientOptions = $this->getDefaultClientOptions();
        }

        $id = $this->options['id'];
        $this->clientOptions['selector'] = "#$id";
        
        if (!isset($this->clientOptions['language_url'])) {
            // tinymce (mostly) uses short language codes
            $language = substr(Yii::$app->language, 0, 2);
            $langFile = "langs/{$language}.js";
            $langAssetBundle = EditorLangAsset::register($view);
            $langAssetBundle->js[] = $langFile;
            $this->clientOptions['language_url'] = $langAssetBundle->baseUrl . "/{$langFile}";
        }

        $options = Json::encode($this->clientOptions);
        $js[] = "tinymce.init($options);";
        
        if ($this->useAjaxValidation) {
            $js[] = "$('#{$id}').parents('form').on('beforeValidate', function() { tinymce.triggerSave(); });";
        }

        $view->registerJs(implode("\n", $js));
    }

    /**
     * Returns the default configuration for the editor
     *
     * @return array
     */
    public function getDefaultClientOptions()
    {
        return [
            'height' => 300,
            'plugins' => ['link', 'image'],
            'relative_urls' => true,
            'document_base_url' => $this->baseUrl !== null ? $this->baseUrl : Url::home(),
            'file_browser_callback' => $this->getFileBrowserCallback(),
        ];
    }

    /**
     * Returns a [[JsExpression]] containing a callback for the TinyMCE file/media browser
     *
     * @return JsExpression
     */
    public function getFileBrowserCallback()
    {
        /**
         * Javascript function that opens a TinyMCE dialog.
         * 
         * @param string {field_name} id of the form element to insert the url into
         * @param string {url} the current value of the form element
         * @param string {type} the type of file browser to present to the user. Can be "file", "image" or "flash"
         * @param object {win} reference to the dialog/window that executes the function
         */
        return new JsExpression('function(field_name, url, type, win){
            if (type === "file") {
                var url = "'.Yii::$app->getUrlManager()->createUrl('/big/editor/get-links').'";
                var title = "' . Yii::t('big', 'Select a link') . '";
            } else {
                var url = "'.Yii::$app->getUrlManager()->createUrl('/big/editor/get-media').'";
                var title = "' . Yii::t('big', 'Select media') . '";
            }
            tinymce.activeEditor.windowManager.open({
                file: url,
                title: title,
                height: $(window).height()*.8,
                width: $(window).width()*.8,
                buttons: [{
                    text: "Close",
                    onClick: "close"
                }]
            }, {
                setUrl: function(url){
                    win.document.getElementById(field_name).value = url;
                    top.tinymce.activeEditor.windowManager.close();
                }
            });
            return false;
        }');
    }
}