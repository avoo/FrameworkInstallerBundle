<?php

namespace Avoo\DemoBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class NewsFormType
 */
class NewsFormType extends AbstractType
{
    /**
     * @var string $class
     */
    private $class;

    /**
     * @var string $validationGroup
     */
    private $validationGroup;

    /**
     * Construct
     *
     * @param string $class
     * @param string $validationGroup
     */
    public function __construct($class, $validationGroup)
    {
        $this->class = $class;
        $this->validationGroup = $validationGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text', array(
                'label' => 'avoo_demo.label.name'
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'validation_groups' =>$this->validationGroup
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'avoo_demo_news';
    }
}
