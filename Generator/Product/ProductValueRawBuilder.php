<?php

namespace Pim\Bundle\DataGeneratorBundle\Generator\Product;

use Faker;
use Pim\Bundle\DataGeneratorBundle\AttributeKeyProvider;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\AttributeOptionInterface;
use Pim\Component\Catalog\Model\ChannelInterface;
use Pim\Component\Catalog\Model\CurrencyInterface;
use Pim\Component\Catalog\Model\LocaleInterface;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Pim\Component\Catalog\Repository\CurrencyRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;

/**
 * Build a raw product value (ie: as an array) with random data.
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductValueRawBuilder
{
    const METRIC_UNIT = 'unit';
    const DEFAULT_NUMBER_MIN = '0';
    const DEFAULT_NUMBER_MAX = '1000';
    const DEFAULT_NB_DECIMALS = '4';

    /** @var AttributeKeyProvider */
    private $attributeKeyProvider;
    
    /** @var Faker\Generator */
    private $faker;

    /** @var AttributeOptionInterface[] */
    private $attributeOptions;

    /**
     * ProductValueRawBuilder constructor.
     *
     * @param AttributeKeyProvider $attributeKeyProvider
     */
    public function __construct(AttributeKeyProvider $attributeKeyProvider)
    {
        $this->attributeKeyProvider = $attributeKeyProvider;
    }

    /**
     * @param Faker\Generator $faker
     *
     * @return ProductValueRawBuilder
     */
    public function setFakerGenerator(Faker\Generator $faker)
    {
        $this->faker = $faker;

        return $this;
    }

    /**
     * Generate a value in term of one or several entries in the product array
     *
     * @param AttributeInterface $attribute
     *
     * @return array
     */
    public function build(AttributeInterface $attribute)
    {
        if (null === $this->faker) {
            throw new \LogicException('Please set the faker generator before using this method.');
        }

        $valueData = [];
        $keys      = $this->attributeKeyProvider->getAttributeKeys($attribute);

        foreach ($keys as $key) {
            $valueData[$key] = $this->generateValueData($attribute, $key);
        }

        return $valueData;
    }

    /**
     * Generate value content based on backend type
     *
     * @param AttributeInterface $attribute
     * @param string             $key
     *
     * @return string
     */
    private function generateValueData(AttributeInterface $attribute, $key)
    {
        if (preg_match('/-' . self::METRIC_UNIT . '$/', $key)) {
            return $attribute->getDefaultMetricUnit();
        }

        switch ($attribute->getBackendType()) {
            case "varchar":
                $data = $this->generateVarcharData($attribute);
                break;
            case "text":
                $data = $this->generateTextData();
                break;
            case "date":
                $data = $this->generateDateData($attribute);
                break;
            case "metric":
            case "decimal":
            case "prices":
                $data = $this->generateNumberData($attribute);
                break;
            case "boolean":
                $data = $this->generateBooleanData();
                break;
            case "option":
            case "options":
                $data = $this->generateOptionData($attribute);
                break;
            default:
                $data = '';
                break;
        }

        return (string) $data;
    }

    /**
     * Generate a varchar product value data
     *
     * @param AttributeInterface $attribute
     *
     * @return string
     */
    private function generateVarcharData(AttributeInterface $attribute)
    {
        $validationRule = $attribute->getValidationRule();
        switch ($validationRule) {
            case 'url':
                $varchar = $this->faker->url();
                break;
            default:
                $varchar = $this->faker->sentence();
                break;
        }

        return $varchar;
    }

    /**
     * Generate a text product value data
     *
     * @return string
     */
    private function generateTextData()
    {
        return $this->faker->sentence();
    }

    /**
     * Generate a date product value data
     *
     * @param AttributeInterface $attribute
     *
     * @return string
     */
    private function generateDateData(AttributeInterface $attribute)
    {
        $date = $this->faker->dateTimeBetween($attribute->getDateMin(), $attribute->getDateMax());

        return $date->format('Y-m-d');
    }

    /**
     * Generate number data
     *
     * @param AttributeInterface $attribute
     *
     * @return string
     */
    private function generateNumberData(AttributeInterface $attribute)
    {
        $min = ($attribute->getNumberMin() != null) ? $attribute->getNumberMin() : self::DEFAULT_NUMBER_MIN;
        $max = ($attribute->getNumberMax() != null) ? $attribute->getNumberMax() : self::DEFAULT_NUMBER_MAX;

        $decimals = $attribute->isDecimalsAllowed() ? self::DEFAULT_NB_DECIMALS : 0;

        $number = $this->faker->randomFloat($decimals, $min, $max);

        return (string) $number;
    }

    /**
     * Generate a boolean product value data
     *
     * @return string
     */
    private function generateBooleanData()
    {
        return $this->faker->boolean() ? "1" : "0";
    }

    /**
     * Generate option data
     *
     * @param AttributeInterface $attribute
     *
     * @return string
     */
    private function generateOptionData(AttributeInterface $attribute)
    {
        $optionCode = "";

        $option = $this->getRandomOptionFromAttribute($attribute);

        if (is_object($option)) {
            $optionCode = $option->getCode();
        }

        return $optionCode;
    }

    /**
     * Get a random option from an attribute
     *
     * @param AttributeInterface $attribute
     *
     * @return AttributeOptionInterface
     */
    private function getRandomOptionFromAttribute(AttributeInterface $attribute)
    {
        if (!isset($this->attributeOptions[$attribute->getCode()])) {
            $this->attributeOptions[$attribute->getCode()] = [];

            foreach ($attribute->getOptions() as $option) {
                $this->attributeOptions[$attribute->getCode()][] = $option;
            }
        }

        return $this->faker->randomElement($this->attributeOptions[$attribute->getCode()]);
    }
}
