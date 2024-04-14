<?php
/**
 * The contents of this file was generated using the WSDLs as provided by eBay.
 *
 * DO NOT EDIT THIS FILE!
 */

namespace DTS\eBaySDK\Trading\Types;

/**
 *
 * @property string $ApplicationURL
 * @property \DTS\eBaySDK\Trading\Enums\EnableCodeType $ApplicationEnable
 * @property string $AlertEmail
 * @property \DTS\eBaySDK\Trading\Enums\EnableCodeType $AlertEnable
 * @property \DTS\eBaySDK\Trading\Enums\DeviceTypeCodeType $DeviceType
 * @property string $PayloadVersion
 * @property \DTS\eBaySDK\Trading\Types\DeliveryURLDetailType[] $DeliveryURLDetails
 */
class ApplicationDeliveryPreferencesType extends \DTS\eBaySDK\Types\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [
        'ApplicationURL' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ApplicationURL'
        ],
        'ApplicationEnable' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ApplicationEnable'
        ],
        'AlertEmail' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'AlertEmail'
        ],
        'AlertEnable' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'AlertEnable'
        ],
        'DeviceType' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'DeviceType'
        ],
        'PayloadVersion' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'PayloadVersion'
        ],
        'DeliveryURLDetails' => [
            'type' => 'DTS\eBaySDK\Trading\Types\DeliveryURLDetailType',
            'repeatable' => true,
            'attribute' => false,
            'elementName' => 'DeliveryURLDetails'
        ]
    ];

    /**
     * @param array $values Optional properties and values to assign to the object.
     */
    public function __construct(array $values = [])
    {
        list($parentValues, $childValues) = self::getParentValues(self::$propertyTypes, $values);

        parent::__construct($parentValues);

        if (!array_key_exists(__CLASS__, self::$properties)) {
            self::$properties[__CLASS__] = array_merge(self::$properties[get_parent_class()], self::$propertyTypes);
        }

        if (!array_key_exists(__CLASS__, self::$xmlNamespaces)) {
            self::$xmlNamespaces[__CLASS__] = 'xmlns="urn:ebay:apis:eBLBaseComponents"';
        }

        $this->setValues(__CLASS__, $childValues);
    }
}
