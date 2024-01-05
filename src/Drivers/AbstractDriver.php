<?php

namespace Intervention\Image\Drivers;

use Intervention\Image\Exceptions\NotSupportedException;
use Intervention\Image\Interfaces\AnalyzerInterface;
use Intervention\Image\Interfaces\DecoderInterface;
use Intervention\Image\Interfaces\DriverInterface;
use Intervention\Image\Interfaces\EncoderInterface;
use Intervention\Image\Interfaces\ModifierInterface;
use Intervention\Image\Interfaces\SpecializableInterface;
use ReflectionClass;

abstract class AbstractDriver implements DriverInterface
{
    public function __construct()
    {
        $this->checkHealth();
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::specialize()
     */
    public function specialize(object $object): ModifierInterface|AnalyzerInterface|EncoderInterface|DecoderInterface
    {
        if (!($object instanceof SpecializableInterface)) {
            return $object;
        }

        $driver_namespace = (new ReflectionClass($this))->getNamespaceName();
        $class_path = substr(get_class($object), strlen("Intervention\\Image\\"));
        $classname = $driver_namespace . "\\" . $class_path;

        if (!class_exists($classname)) {
            throw new NotSupportedException(
                "Class '" . $class_path . "' is not supported by " . $this->id() . " driver."
            );
        }

        return forward_static_call([
            $classname,
            'buildSpecialized'
        ], $object, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @see DriverInterface::specializeMultiple()
     */
    public function specializeMultiple(array $objects): array
    {
        return array_map(function ($classname) {
            return $this->specialize(new $classname());
        }, $objects);
    }
}
