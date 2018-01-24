<?php
/**
 * Class Model
 * @package GlasOperator\Rack
 */
class Model {

    /**
     * __construct function.
     *
     * @access public
     * @param array $fields
     * @return void
     */
    public function __construct(array $fields)
    {
        foreach ($fields as $field => $value)
        {
            $setter = 'set' . ucfirst($field);
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            }
        }
    }
}