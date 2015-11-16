<?php

namespace Pim\Bundle\DataGeneratorBundle\Generator;

use Faker;
use Pim\Bundle\CatalogBundle\Entity\AttributeGroup;
use Pim\Bundle\CatalogBundle\Entity\Locale;
use Pim\Bundle\CatalogBundle\Repository\LocaleRepositoryInterface;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Yaml;

/**
 * Class AttributeGroupGenerator
 *
 * @author    Damien Carcel (https://github.com/damien-carcel)
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class AttributeGroupGenerator implements GeneratorInterface
{
    /** @staticvar string */
    const ATTR_GROUP_CODE_PREFIX = 'attr_gr_';

    /** @staticvar string */
    const ATTRIBUTE_GROUP_FILENAME = 'attribute_groups.yml';

    /** @var array */
    protected $attributeGroups;

    /** @var string */
    protected $attributeGroupsFile;

    /** @var Faker\Generator */
    protected $faker;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var Locale[] */
    protected $locales;

    /**
     * @param LocaleRepositoryInterface  $localeRepository
     */
    public function __construct(LocaleRepositoryInterface $localeRepository)
    {
        $this->localeRepository  = $localeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $config, $outputDir, ProgressHelper $progress, array $options = null)
    {
        $this->attributeGroupsFile = $outputDir . '/' . static::ATTRIBUTE_GROUP_FILENAME;

        $count = (int) $config['count'];

        $this->faker = Faker\Factory::create();

        $this->attributeGroups = [];

        for ($i = 0; $i < $count; $i++) {
            $attributeGroup = [];

            $attributeGroup['sortOrder'] = $this->faker->numberBetween(1, 10);
            $attributeGroup['labels'] = $this->getLocalizedRandomLabels();

            $this->attributeGroups[self::ATTR_GROUP_CODE_PREFIX.$i] = $attributeGroup;
            $progress->advance();
        }

        $this->writeYamlFile(['attribute_groups' => $this->attributeGroups], $this->attributeGroupsFile);

        return $this;
    }

    /**
     * Return the generated attribute groups as AttributeGroup object.
     *
     * @return array
     */
    public function getAttributeGroupObjects()
    {
        $attrGroupObjects = [];

        foreach ($this->attributeGroups as $code => $attributeGroup) {
            $attrGroupObject = new AttributeGroup();

            $attrGroupObject->setCode($code);

            $attrGroupObjects[$code] = $attrGroupObject;
        }

        return $attrGroupObjects;
    }

    /**
     * Get localized random labels
     *
     * @return array
     */
    protected function getLocalizedRandomLabels()
    {
        $locales = $this->getLocales();
        $labels = [];

        foreach ($locales as $locale) {
            $labels[$locale->getCode()] = $this->faker->sentence(2);
        }

        return $labels;
    }

    /**
     * Get active locales
     *
     * @return Locale[]
     */
    protected function getLocales()
    {
        if (null === $this->locales) {
            $this->locales = [];
            $locales = $this->localeRepository->findBy(['activated' => 1]);
            foreach ($locales as $locale) {
                $this->locales[$locale->getCode()] = $locale;
            }
        }

        return $this->locales;
    }

    /**
     * Write a YAML file
     *
     * @param array  $data
     * @param string $filename
     */
    protected function writeYamlFile(array $data, $filename)
    {
        $dumper = new Yaml\Dumper();
        $yamlData = $dumper->dump($data, 5, 0, true, true);

        file_put_contents($filename, $yamlData);
    }
}