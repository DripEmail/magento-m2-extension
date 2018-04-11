<?php
namespace Drip\Connect\Model\Source;

class Behavior
{
    const CALL_API              = 'call_api';
    const FORCE_VALID           = 'force_valid';
    const FORCE_INVALID         = 'force_invalid';
    const FORCE_TIMEOUT         = 'force_timeout';
    const FORCE_ERROR           = 'force_error';
    const FORCE_UNKNOWN_ERROR   = 'force_unknown_error';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::CALL_API,            'label' => __('Call API')),
            array('value' => self::FORCE_VALID,         'label' => __('Force Valid Result')),
            array('value' => self::FORCE_INVALID,       'label' => __('Force Invalid Result')),
            array('value' => self::FORCE_TIMEOUT,       'label' => __('Force Timeout')),
            array('value' => self::FORCE_ERROR,         'label' => __('Force Error')),
            array('value' => self::FORCE_UNKNOWN_ERROR, 'label' => __('Force Unkown Error')),
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
            self::CALL_API              => __('Call API'),
            self::FORCE_VALID           => __('Force Valid Result'),
            self::FORCE_INVALID         => __('Force Invalid Result'),
            self::FORCE_TIMEOUT         => __('Force Timeout'),
            self::FORCE_ERROR           => __('Force Error'),
            self::FORCE_UNKNOWN_ERROR   => __('Force Unknown Error'),
        );
    }
}
