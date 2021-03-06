<?php

namespace Pagekit\Widget\Entity;

use Pagekit\Database\ORM\ModelTrait;
use Pagekit\User\Entity\AccessTrait;
use Pagekit\Widget\Model\Widget as BaseWidget;

/**
 * @Entity(tableClass="@system_widget", eventPrefix="system.widget")
 */
class Widget extends BaseWidget
{
    use AccessTrait, ModelTrait;

    /** @Column(type="integer") @Id */
    protected $id;

    /** @Column(type="string") */
    protected $type;

    /** @Column */
    protected $title = '';

    /** @Column */
    protected $position = '';

    /** @Column(type="integer") */
    protected $priority = 0;

    /** @Column(type="boolean") */
    protected $status;

    /** @Column(type="text") */
    protected $pages = '';

    /** @Column(name="nodes", type="simple_array") */
    protected $nodes = [];

    /** @Column(type="json_array", name="data") */
    protected $settings = [];

    public function getShowTitle()
    {
        return (bool) $this->get('show_title', true);
    }

    public function setShowTitle($showTitle)
    {
        $this->set('show_title', (bool) $showTitle);
    }

    public function getPriority()
    {
        return (int) $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = (int) $priority;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    public function getNodes()
    {
        return (array) $this->nodes;
    }

    public function setNodes($nodes)
    {
        $this->nodes = $nodes;
    }

    public function hasNode($id)
    {
        return in_array($id, $this->getNodes());
    }

    public function getStatusText()
    {
        $statuses = self::getStatuses();

        return isset($statuses[$this->status]) ? $statuses[$this->status] : __('Unknown');
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_DISABLED => __('Disabled'),
            self::STATUS_ENABLED  => __('Enabled')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $widget = $this->toJson();

        $widget['statusText'] = $this->getStatusText();

        return $widget;
    }
}
