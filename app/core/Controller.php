<?php

namespace Fir\Controllers;

use Fir\Models;
use Fir\Views;
use Fir\Languages\Language as Language;

/**
 * The base Controller upon which all the other controllers are extended on
 */
class Controller
{
    /**
     * The database connection
     * @var    object
     */
    public $db;

    /**
     * The site settings from the DB
     * @var    array
     */
    protected $settings;

    /**
     * The view object to be passed to the controllers
     * @var    object
     */
    protected $view;

    /**
     * The language array to be passed to the controllers and views
     * @var array
     */
    protected $lang;

    /**
     * The list of available languages
     * @var array
     */
    protected $languages;

    /**
     * User selected language
     * @var string
     */
    protected $language;

    /**
     * The current URL path (route) array to be passed to the controllers
     * @var array
     */
    protected $url;

    /**
     * Controller constructor.
     * @param $db
     * @param $url
     */
    public function __construct($db, $url)
    {
        $this->db = $db;
        $this->url = $url;

        // Set the site settings
        $settings = $this->model('Settings');
        $this->settings = $settings->get();

        // Set the timezone
        if (!empty($this->settings['timezone'])) {
            date_default_timezone_set($this->settings['timezone']);
        }

        // Instantiate the Language system and set the default language
        $language = new Language();

        $this->lang = $language->set($this->settings['language']);
        $this->languages = $language->languages;
        $this->language = $language->get();

        // Instantiate the View
        $this->view = new Views\View($this->settings, $this->lang, $this->url);
    }

    /**
     * Get and instantiate the requested model
     * @param   $model  string  The model to instantiate
     * @return  object
     */
    public function model($model)
    {
        require_once(__DIR__ . '/../models/' . $model . '.php');

        // The namespace\class must be defined in a string as it can't be called shorted using new namespace\$var
        $class = 'Fir\Models\\' . $model;

        return new $class($this->db);
    }

    /**
     * Output the final view to the user based on the request type
     *
     * @param   $data   array   The output generated by the controllers
     */
    public function run($data = null)
    {
        $data['header_view'] = $this->getHeader();
        $data['content_view'] = $data['content'];
        $data['footer_view'] = $this->getFooter();
        if (isAjax()) {
            echo json_encode(['title' => $this->view->docTitle(), 'header' => $data['header_view'], 'content' => $data['content_view'], 'footer' => $data['footer_view']]);
        } else {
            echo $this->view->render($data, 'wrapper');
        }
    }

    /**
     * This is the method from where you can pass data to the Header view
     *
     * @return string
     */
    private function getHeader()
    {
        $data = [
            'languages_list'    => $this->languages,
            'language'          => $this->language
        ];

        return $this->view->render($data, 'shared/header');
    }

    /**
     * This is the method from where you can pass data to the Footer view
     *
     * @return string
     */
    private function getFooter()
    {
        $data = [];
        return $this->view->render($data, 'shared/footer');
    }
}