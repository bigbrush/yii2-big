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
     * @var string javascript function acting as an event handler when a link gets clicked
     */
    public $linkClickCallback;
    /**
     * @var array configuration array used to configure bigbrush\widgets\filemanager\FileManager. If not set the file manager
     * is not included in the search results.
     */
    public $fileManager;


    /**
     * Triggers a search in Big and returns an array where the keys are section names
     * and values are arrays of search results.
     *
     * @return array sorted list of search results. The keys are section names and
     * the values are arrays with search items for each section.
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
        if ($this->linkClickCallback !== null) {
            if (is_string($this->linkClickCallback)) {
                $this->linkClickCallback = new JsExpression($this->linkClickCallback);
            }
            $view->registerJs('$("#all-sections-wrap").on("click", ".insert-on-click", '.Json::encode($this->linkClickCallback).');');
        }

        $sections = $this->triggerSearch();
        $buttons = array_keys($sections);
        if ($this->fileManager !== null) {
            $buttons[] = Yii::t('big', 'Media');
            $this->fileManager = FileManager::widget($this->fileManager);
        }
        return $this->render('index', [
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

    /**
     * Triggers a search event in Big.
     *
     * @return array list of search results. The array keys are section names
     * and the values are zero indexed arrays of search results.
     */
    public function triggerSearch()
    {
        $event = Yii::createObject([
            'class' => SearchEvent::className(),
            'value' => $this->value,
        ]);
        $results = Yii::$app->big->search($event, SearchEvent::EVENT_SEARCH, $this->dynamicUrls);
        $sections = [];
        foreach ($results as $item) {
            $sections[$item['section']][] = $item;
        }
        return $sections;
    }
}