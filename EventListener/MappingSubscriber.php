<?php

namespace DualHand\ReusableBundle\EventListener;

use DualHand\ReusableBundle\Model\AbstractPurchasable;
use DualHand\ReusableBundle\Traits\DeliverableTrait;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events as DoctrineEvents;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class MappingSubscriber.
 */
class MappingSubscriber
{
    /**
     * @var string
     */
    protected $cartClass;

    /**
     * @var string
     */
    protected $cartLineClass;
    /**
     * @var array
     */
    protected $purchasableMap;

    /**
     * @param string $cartClass
     * @param string $cartLineClass
     * @param array  $purchasableMap
     */
    public function __construct($cartClass, $cartLineClass, array $purchasableMap)
    {
        $this->cartClass = $cartClass;
        $this->cartLineClass = $cartLineClass;
        $this->purchasableMap = $purchasableMap;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [DoctrineEvents::loadClassMetadata];
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $eventArgs->getClassMetadata();

        if ($classMetadata->reflClass === $this->cartClass) {
            $classMetadata->mapOneToMany(array(

                'fieldName' => 'cartLines',
                'targetEntity' => $this->cartLineClass,
                'cascade' => array(
                    1 => 'all',
                ),
                'mappedBy' => 'cart',
                'orphanRemoval' => true,

            ));
        }

        if ($classMetadata->reflClass === AbstractPurchasable::class) {
            $classMetadata->setDiscriminatorMap($this->purchasableMap);
        }

        if ($this->hasTrait($classMetadata->reflClass, DeliverableTrait::class)) {
            $this->mapDeliverable($classMetadata);
        }
    }

    /**
     * @param \ReflectionClass $class
     * @param string           $traitName
     *
     * @return bool
     */
    private function hasTrait(\ReflectionClass $class, $traitName)
    {
        return in_array($traitName, $class->getTraitNames());
    }

    /**
     * @param ClassMetadataInfo $classMetadata
     */
    private function mapDeliverable(ClassMetadataInfo $classMetadata)
    {
        foreach (array('weight', 'length', 'width', 'height') as $field) {
            $classMetadata->mapField(array(
                'fieldName' => $field,
                'type' => 'float',
            ));
        }
    }
}
