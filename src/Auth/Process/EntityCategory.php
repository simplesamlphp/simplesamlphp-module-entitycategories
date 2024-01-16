<?php

declare(strict_types=1);

namespace SimpleSAML\Module\entitycategories\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;

use function array_key_exists;
use function array_merge;
use function in_array;
use function is_numeric;

/**
 * An authentication processing filter that modifies the list of attributes sent to a service depending on the entity
 * categories it belongs to. This filter DOES NOT alter the list of attributes sent itself, but modifies the list of
 * attributes requested by the service provider. Therefore, in order to be of any use, it must be used together with the
 * core:AttributeLimit authentication processing filter.
 *
 * @package SimpleSAMLphp
 */
class EntityCategory extends Auth\ProcessingFilter
{
    /**
     * A list of categories available. An associative array where the identifier of the category is the key, and the
     * associated value is an array with all the attributes allowed for services in that category.
     *
     * @var array
     */
    protected array $categories = [];

    /**
     * Whether the attributes allowed by this category should be sent by default in case no attributes are explicitly
     * requested or not.
     *
     * @var bool
     */
    protected bool $default = false;

    /**
     *
     * Whether it is allowed to release attributes to entities having no entity category or
     * having unconfigured entity categories
     * Strict means not to release attributes to that entities. If strict is false, attributeLimit will do the filtering
     *
     * @var bool
     */
    protected bool $strict = false;

    /**
     *
     * Whether it is allowed to release additional requested attributes than configured in the list of the
     * configuration of the entity category and allow release attributes based on requested attributes to entities
     * having unconfigured entity categories.
     *
     * @var bool
     */
    protected bool $allowRequestedAttributes = false;


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
                Assert::boolean(
                    $value,
                    "The 'default' configuration option must have a boolean value.",
                    Error\ConfigurationError::class,
                );
                $this->default = $value;
                continue;
            }

            if ($index === 'strict') {
                Assert::boolean(
                    $value,
                    "The 'strict' configuration option must have a boolean value.",
                    Error\ConfigurationError::class,
                );
                $this->strict = $value;
                continue;
            }

            if ($index === 'allowRequestedAttributes') {
                Assert::boolean(
                    $value,
                    "The 'allowRequestedAttributes' configuration option must have a boolean value.",
                    Error\ConfigurationError::class,
                );
                $this->allowRequestedAttributes = $value;
                continue;
            }

            Assert::numeric(
                $index,
                "Unspecified allowed attributes for the '$value' category.",
                Error\ConfigurationError::class,
            );

            Assert::isArray(
                $value,
                "The list of allowed attributes for category '$index' is not an array.",
                Error\ConfigurationError::class,
            );

            $this->categories[$index] = $value;
        }
    }


    /**
     * Apply the filter to modify the list of attributes for the current service provider.
     *
     * @param array &$state The current request.
     */
    public function process(array &$state): void
    {
        if (!array_key_exists('EntityAttributes', $state['Destination'])) {
            if ($this->strict === true) {
                // We do not allow to release any attribute to entity having no entity attribute
                $state['Destination']['attributes'] = [];
            }
            return;
        }

        if (!array_key_exists('http://macedir.org/entity-category', $state['Destination']['EntityAttributes'])) {
            if ($this->strict === true) {
                // We do not allow to release any attribute to entity having no entity category
                $state['Destination']['attributes'] = [];
            }
            return;
        }
        $categories = $state['Destination']['EntityAttributes']['http://macedir.org/entity-category'];

        if (!array_key_exists('attributes', $state['Destination'])) {
            if ($this->default === true) {
                // handle the case of service providers requesting no attributes and the filter being the default policy
                $state['Destination']['attributes'] = [];
                foreach ($categories as $category) {
                    if (!array_key_exists($category, $this->categories)) {
                        continue;
                    }

                    $state['Destination']['attributes'] = array_merge(
                        $state['Destination']['attributes'],
                        $this->categories[$category]
                    );
                }
            }
            return;
        }

        // iterate over the requested attributes and see if any of the categories allows them
        foreach ($state['Destination']['attributes'] as $index => $value) {
            $attrname = $value;
            if (!is_numeric($index)) {
                $attrname = $index;
            }

            $found = false;
            foreach ($categories as $category) {
                if (!array_key_exists($category, $this->categories)) {
                    continue;
                }

                if (
                    in_array($attrname, $this->categories[$category], true)
                    || $this->allowRequestedAttributes === true
                ) {
                    $found = true;
                    break;
                }
            }

            if ($found === false && ($this->allowRequestedAttributes === false || $this->strict === true)) {
                // no category (if any) allows the attribute, so remove it
                unset($state['Destination']['attributes'][$index]);
            }
        }
    }
}
