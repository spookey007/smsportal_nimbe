<?php
/**
 * The contents of this file was generated using the WSDLs as provided by eBay.
 *
 * DO NOT EDIT THIS FILE!
 */

namespace DTS\eBaySDK\MerchantData\Types;

/**
 *
 * @property string $RefundOption
 * @property string $Refund
 * @property string $ReturnsWithinOption
 * @property string $ReturnsWithin
 * @property string $ReturnsAcceptedOption
 * @property string $ReturnsAccepted
 * @property string $Description
 * @property string $WarrantyOfferedOption
 * @property string $WarrantyOffered
 * @property string $WarrantyTypeOption
 * @property string $WarrantyType
 * @property string $WarrantyDurationOption
 * @property string $WarrantyDuration
 * @property string $EAN
 * @property string $ShippingCostPaidByOption
 * @property string $ShippingCostPaidBy
 * @property string $RestockingFeeValue
 * @property string $RestockingFeeValueOption
 * @property boolean $ExtendedHolidayReturns
 */
class ReturnPolicyType extends \DTS\eBaySDK\Types\BaseType
{
    /**
     * @var array Properties belonging to objects of this class.
     */
    private static $propertyTypes = [
        'RefundOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'RefundOption'
        ],
        'Refund' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'Refund'
        ],
        'ReturnsWithinOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ReturnsWithinOption'
        ],
        'ReturnsWithin' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ReturnsWithin'
        ],
        'ReturnsAcceptedOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ReturnsAcceptedOption'
        ],
        'ReturnsAccepted' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ReturnsAccepted'
        ],
        'Description' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'Description'
        ],
        'WarrantyOfferedOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'WarrantyOfferedOption'
        ],
        'WarrantyOffered' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'WarrantyOffered'
        ],
        'WarrantyTypeOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'WarrantyTypeOption'
        ],
        'WarrantyType' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'WarrantyType'
        ],
        'WarrantyDurationOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'WarrantyDurationOption'
        ],
        'WarrantyDuration' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'WarrantyDuration'
        ],
        'EAN' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'EAN'
        ],
        'ShippingCostPaidByOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ShippingCostPaidByOption'
        ],
        'ShippingCostPaidBy' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ShippingCostPaidBy'
        ],
        'RestockingFeeValue' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'RestockingFeeValue'
        ],
        'RestockingFeeValueOption' => [
            'type' => 'string',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'RestockingFeeValueOption'
        ],
        'ExtendedHolidayReturns' => [
            'type' => 'boolean',
            'repeatable' => false,
            'attribute' => false,
            'elementName' => 'ExtendedHolidayReturns'
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
