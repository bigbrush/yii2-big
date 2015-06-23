<?php
/**
 * @link http://www.bigbrush-agency.com/
 * @copyright Copyright (c) 2015 Big Brush Agency ApS
 * @license http://www.bigbrush-agency.com/license/
 */

namespace bigbrush\big\widgets\bigsearch;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\JsExpression;
use bigbrush\big\core\SearchEvent;
use bigbrush\big\widgets\filemanager\FileManager;

/**
 * BigSearch
 */
class BigSearch extends Widget
{
    /**
     * @var string|null optional value being searched for.
     */
    public $value;
    /**
     * @var boolean defines whether to use dynamic urls or normal yii urls. If used within the
     * Big editor this property should be true.
     * See [[Big::search()]] for more information about this property.
     */
    public $dynamicUrls = false;
    /**
     * @var string|JsExpression a custom javascript callback triggered when a link gets clicked
     */
    public $onClickCallback;
    /**
     * @var array|FileManager configuration array used to configure the FileManager or a FileManager object. If not set the file manager
     * is not included in the search results.
     */
    public $fileManager;
    /**
     * @var string defines the view file or alias used when rendering.
     */
    public $viewFile = 'index';


    /**
     * Triggers a search event in Big.
     *
     * @param string|null $value a value to search for.
     * @param boolean $dynamicUrls whether to enable dynamic url in the search.
     * @return array list of search results. The array keys are section names
     * and the values are zero indexed arrays of search results.
     */
    public static function search($value = null, $dynamicUrls = false)
    {
        $event = Yii::createObject([
            'class' => SearchEvent::className(),
            'value' => $value,
        ]);
        $results = Yii::$app->big->search($event, SearchEvent::EVENT_SEARCH, $dynamicUrls);
        $sections = [];
        foreach ($results as $item) {
            $sections[$item['section']][] = $item;
        }
        return $sections;
    }

    /**
     * Triggers a search in Big and renders multiple [[yii\grid\GridView]] on the same page. Only one grid is visible at a time
     * and a dropdown menu can be used to switch between the grids.
     *
     * @return string the rendering result.
     */
    public function run()
    {
        $view = $this->getView();
        // Register js script that handles the drop down menu.
        // When a sub menu is clicked the selected section is shown.
        $view->registerJs('
            $(".section-wrapper").first().addClass("selected-section").show();
            $("#sections-dropdown .section-selector").click(function(e){
                e.preventDefault();
                $(".selected-section").removeClass("selected-section").hide();
                var section = $(this).data("section");
                $("#"+section).addClass("selected-section").show();
            });
        ');
        // register script that handles when a user clicks on a search result
        if ($this->onClickCallback !== null) {
            if (is_string($this->onClickCallback)) {
                $this->onClickCallback = new JsExpression($this->onClickCallback);
            }
            $view->registerJs('$("#all-sections-wrap").on("click", ".insert-on-click", '.Json::encode($this->onClickCallback).');');
        }

        $sections = static::search($this->value, $this->dynamicUrls);
        $buttons = array_keys($sections);
        if (is_array($this->fileManager)) {
            $this->fileManager = FileManager::widget($this->fileManager);
        }
        if ($this->fileManager) {
            $buttons[] = Yii::t('big', 'Media');
        }
        return $this->render($this->viewFile, [
            'sections' => $sections,
            'buttons' => $this->createDropDownButtons($buttons),
            'fileManager' => $this->fileManager,
        ]);
    }

    /**
     * Returns an array of buttons compatible with [[yii\bootstrap\ButtonDropDown]].
     *
     * @return array list of buttons for a drop down menu.
     */
    public function createDropDownButtons(array $sections)
    {
        $buttons = [];
        // counter used to generate id for each section wrapper
        $counter = 0;
        foreach ($sections as $section) {
            $buttons[] = [
                'label' => $section,
                'url' => '#',
                'linkOptions' => [
                    'class' => 'section-selector',
                    'data-section' => 'section-' . $counter++,
                ]
            ];
        }
        return $buttons;
    }
}