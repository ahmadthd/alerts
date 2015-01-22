<?php

/**
 * Part of the Alerts package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Alerts
 * @version    1.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011-2015, Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Cartalyst\Alerts;

use Cartalyst\Alerts\Notifiers\NotifierInterface;

class Alerts
{
    /**
     * The registered notifiers.
     *
     * @var array
     */
    protected $notifiers = [];

    /**
     * The default notifier.
     *
     * @var string
     */
    protected $defaultNotifier;

    protected $filters = [];

    protected $filteredAlerts = [];

    /**
     * Returns all the registered notifiers.
     *
     * @return array
     */
    public function getNotifiers()
    {
        return $this->notifiers;
    }

    /**
     * Adds the given notifier.
     *
     * @param  \Cartalyst\Alerts\Notifiers\NotifierInterface  $notifier
     * @return $this
     */
    public function addNotifier(NotifierInterface $notifier)
    {
        $this->notifiers[$notifier->getName()] = $notifier;

        return $this;
    }

    /**
     * Removes the given notifier.
     *
     * @param  string  $name
     * @return $this
     */
    public function removeNotifier($name)
    {
        unset($this->notifiers[$name]);

        return $this;
    }

    /**
     * Returns the default notifier name.
     *
     * @return string
     */
    public function getDefaultNotifier()
    {
        return $this->defaultNotifier;
    }

    /**
     * Sets the default notifier.
     *
     * @param  string  $notifier
     * @return $this
     */
    public function setDefaultNotifier($notifier)
    {
        $this->defaultNotifier = $notifier;

        return $this;
    }

    /**
     * Returns the given notifier.
     *
     * @param  string  $name
     * @param  string  $default
     * @return \Cartalyst\Alerts\Notifiers\NotifierInterface|null
     */
    public function notifier($name, $default = null)
    {
        return array_get($this->notifiers, $name, $default);
    }

    public function get()
    {
        // Retrieve all alerts if no filters are assigned
        if ( ! $this->filters) {
            $this->registerFilter(null, null, null);
        }

        $filteredAlerts = $this->filteredAlerts;

        // Clear filters and filtered alerts
        $this->filters = [];
        $this->filteredAlerts = [];

        return $filteredAlerts;
    }

    /**
     * Dynamically forward alerts.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, array $parameters = [])
    {
        return call_user_func_array(
            [ $this->notifiers[$this->defaultNotifier], '__call' ],
            [ $method, $parameters ]
        );
    }

    /**
     * Filter alerts based on the given areas.
     *
     * @param  string|array  $areas
     * @return self
     */
    public function whereArea($areas)
    {
        $this->registerFilter('area', $areas);

        return $this;
    }

    /**
     * Filter alerts excluding the given areas.
     *
     * @param  string|array  $areas
     * @return self
     */
    public function whereNotArea($areas)
    {
        $this->registerFilter('area', $areas, true);

        return $this;
    }

    /**
     * Filter alerts based on the given types.
     *
     * @param  string|array  $types
     * @return self
     */
    public function whereType($types)
    {
        $this->registerFilter('type', $types);

        return $this;
    }

    /**
     * Filter alerts excluding the given types.
     *
     * @param  string|array  $types
     * @return self
     */
    public function whereNotType($types)
    {
        $this->registerFilter('type', $types, true);

        return $this;
    }

    protected function registerFilter($zone, $filters, $exclude = false)
    {
        if ( ! is_array($filters)) {
            $filters = (array) $filters;
        }

        $messages = $this->filteredAlerts;

        if ( ! $this->filters) {
            foreach ($this->notifiers as $notifier) {
                $messages = array_merge_recursive($messages, $notifier->all());
            }
        }

        if ($filters) {
            $type = $exclude ? 'exclude' : 'include';

            array_set($this->filters, "{$type}.{$zone}", array_merge(array_get($this->filters, "{$type}.{$zone}", []), $filters));

            $messages = array_filter($messages, function ($message) use ($zone, $filters, $exclude) {
                if ($exclude) {
                    return ! in_array($message->{$zone}, $filters);
                } else {
                    return in_array($message->{$zone}, $filters);
                }
            });
        }

        $this->filteredAlerts = $messages;
    }

}
