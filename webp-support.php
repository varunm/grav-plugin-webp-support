<?php
namespace Grav\Plugin;

use DiDom\Document;
use DiDom\Element;
use Grav\Common\Plugin;
use Grav\Common\Browser;
use RocketTheme\Toolbox\Event\Event;

/**
 * Class WebpSupportPlugin
 * @package Grav\Plugin
 */
class WebpSupportPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        include __DIR__.'/vendor/autoload.php';

        // Enable the main event we are interested in
        $this->enable([
            'onPageContentProcessed' => ['onPageContentProcessed', 0],
        ]);
    }

    /**
     * Process on page content
     *
     * @param Event $event
     */
    public function onPageContentProcessed(Event $event)
    {
        $page = $event['page'];

        if ($this->config->get('plugins.webp-support.enabled') === false) {
            return;
        }

        $content = $page->content();
        $content = $this->processImages($content);
        $page->setRawContent($content);
    }

    /**
     * Process content and replace any images with picture elements
     *
     * @param $content
     * @return string
     */
    protected function processImages($content)
    {
        // Check for empty content
        if (strlen($content) === 0) {
            return '';
        }

        $document = new Document($content);

        $scope = trim($this->grav['config']->get('plugins.webp-support.scope'));
        $preference = trim($this->grav['config']->get('plugins.webp-support.preference'));
        $default = trim($this->grav['config']->get('plugins.webp-support.default'));

        if (count($images = $document->find($scope)) > 0) {
            foreach ($images as $image) {
                $imgsrc = $image->getAttribute('src');
                $imgsrcWebp = str_replace($default, $preference, $imgsrc);
                $picture = new Element('picture');
                $source = new Element('source', null, ['srcset' => $imgsrcWebp, 'type' => "image/webp"]);
                $sources = [$source, $image];
                $picture->appendChild($sources);
                $image->replace($picture);
            }
            return $document->html();
        }
        return $content;
    }
}
