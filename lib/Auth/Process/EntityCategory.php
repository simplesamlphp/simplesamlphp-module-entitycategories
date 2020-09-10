<?php

namespace SimpleSAML\Module\entitycategories\Auth\Process;

/**
 * An authentication processing filter that modifies the list of attributes sent to a service depending on the entity
 * categories it belongs to. This filter DOES NOT alter the list of attributes sent itself, but modifies the list of
 * attributes requested by the service provider. Therefore, in order to be of any use, it must be used together with the
 * core:AttributeLimit authentication processing filter.
 *
 * @package SimpleSAMLphp
 */
class EntityCategory extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * A list of categories available. An associative array where the identifier of the category is the key, and the
     * associated value is an array with all the attributes allowed for services in that category.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Whether the attributes allowed by this category should be sent by default in case no attributes are explicitly
     * requested or not.
     *
     * @var bool
     */
    protected $default = false;

    /**
     *
     * Whether it is allowed to release attributes to entities having no entity category or
     * having unconfigured entity categories
     * Strict means not to release attributes to that entities. If strict is false, attributeLimit will do the filtering
     *
     * @var bool
     */
    protected $strict = false;

    /**
     *
     * Whether it is allowed to release additional requested attributes than configured in the list of the configuration of
     * the entity category and allow release attributes based on requested attributes to entities having unconfigured
     * entity categories.
     *
     * @var bool
     */
    protected $allowRequestedAttributes = false;


    /**
     * EntityCategory constructor.
     *
     * @param array $config An array with the configuration for this processing filter.
     * @param mixed $reserved For future use.
     * @throws \SimpleSAML\Error\ConfigurationError In case of a misconfiguration of the filter.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        foreach ($config as $index => $value) {
            if ($index === 'default') {
                if (!is_bool($value)) {
                    throw new \SimpleSAML\Error\ConfigurationError(
                        "The 'default' configuration option must have a boolean value."
                    );
                }
                $this->default = $value;
                continue;
            }

            if ($index === 'strict') {
                if (!is_bool($value)) {
                    throw new \SimpleSAML\Error\ConfigurationError(
                        "The 'strict' configuration option must have a boolean value."
                    );
                }
                $this->strict = $value;
                continue;
            }

            if ($index === 'allowRequestedAttributes') {
                if (!is_bool($value)) {
                    throw new \SimpleSAML\Error\ConfigurationError(
                        "The 'allowRequestedAttributes' configuration option must have a boolean value."
                    );
                }
                $this->allowRequestedAttributes = $value;
                continue;
            }

            if (is_numeric($index)) {
                throw new \SimpleSAML\Error\ConfigurationError(
                    "Unspecified allowed attributes for the '$value' category."
                );
            }

            if (!is_array($value)) {
                throw new \SimpleSAML\Error\ConfigurationError(
                    "The list of allowed attributes for category '$index' is not an array."
                );
            }

            $this->categories[$index] = $value;
        }
    }


    /**
     * Apply the filter to modify the list of attributes for the current service provider.
     *
     * @param array $request The current request.
     * @return void
     */
    public function process(array &$request): void
    {
        if (!array_key_exists('EntityAttributes', $request['Destination'])) {
            if ($this->strict) {
                // We do not allow to release any attribute to entity having no entity attribute
                $request['Destination']['attributes'] = array();
            }
            return;
        }

        if (!array_key_exists('http://macedir.org/entity-category', $request['Destination']['EntityAttributes'])) {
            if ($this->strict) {
                // We do not allow to release any attribute to entity having no entity category
                $request['Destination']['attributes'] = array();
            }
            return;
        }
        $categories = $request['Destination']['EntityAttributes']['http://macedir.org/entity-category'];

        if (!array_key_exists('attributes', $request['Destination'])) {
            if ($this->default) {
                // handle the case of service providers requesting no attributes and the filter being the default policy
                $request['Destination']['attributes'] = [];
                foreach ($categories as $category) {
                    if (!array_key_exists($category, $this->categories)) {
                        continue;
                    }

                    $request['Destination']['attributes'] = array_merge(
                        $request['Destination']['attributes'],
                        $this->categories[$category]
                    );
                }
            }
            return;
        }

        // iterate over the requested attributes and see if any of the categories allows them
        foreach ($request['Destination']['attributes'] as $index => $value) {
            $attrname = $value;
            if (!is_numeric($index)) {
                $attrname = $index;
            }

            $found = false;
            foreach ($categories as $category) {
                if (!array_key_exists($category, $this->categories)) {
                    continue;
                }

                if (in_array($attrname, $this->categories[$category], true) || $this->allowRequestedAttributes) {
                    $found = true;
                    break;
                }
            }

            if (!$found && (!$this->allowRequestedAttributes || $this->strict)) {
                // no category (if any) allows the attribute, so remove it
                unset($request['Destination']['attributes'][$index]);
            }
        }
    }
}
