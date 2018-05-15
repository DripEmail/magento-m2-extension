<?php
namespace Drip\Connect\Model\Source;

class SyncState
{
    const READY = 0; // job not running and not going to run
    const QUEUED = 1; // job will start shortly (when cron starts)
    const PROGRESS = 2; // job in progress
    const READYERRORS = 3; // job not running and not going to run, previous run was finished with errors

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::READY, 'label' => __('Ready')),
            array('value' => self::QUEUED, 'label' => __('Queued')),
            array('value' => self::PROGRESS, 'label' => __('In Progress')),
            array('value' => self::READYERRORS, 'label' => __('Ready (finished with errors)')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::READY => __('Ready'),
            self::QUEUED => __('Queued'),
            self::PROGRESS => __('In Progress'),
            self::READYERRORS => __('Ready (finished with errors)'),
        );
    }

    /**
     * @return string
     */
    static function getLabel($key)
    {
        switch ($key) {
            case self::READY :
                return __('Ready');
                break;
            case self::QUEUED :
                return __('Queued');
                break;
            case self::PROGRESS :
                return __('In Progress');
                break;
            case self::READYERRORS :
                return __('Ready (finished with errors)');
                break;
        }
        return '';
    }
}

