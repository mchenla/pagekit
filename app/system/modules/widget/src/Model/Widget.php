<?php

namespace Pagekit\Widget\Model;

use Pagekit\Application as App;
use Pagekit\Util\Arr;

class Widget implements WidgetInterface
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $position;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->status = self::STATUS_DISABLED;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the widget id.
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the name.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets the position.
     *
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status.
     *
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Sets the widget settings.
     *
     * @param array $settings
     */
    public function setSettings(array $settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, $default = null)
    {
        return Arr::get($this->settings, $name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        Arr::set($this->settings, $name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        Arr::remove($this->settings, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function render($options = [])
    {
        $type = App::module('system/widget')->getType($this->type);

        return $type ? $type->render($this, $options) : '';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $widget = get_object_vars($this);

        if (!$widget['settings']) {
            $widget['settings'] = new \stdClass;
        }

        return $widget;
    }
}
