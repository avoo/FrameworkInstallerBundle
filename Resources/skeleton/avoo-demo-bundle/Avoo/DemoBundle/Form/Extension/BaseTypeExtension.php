<?php
namespace Avoo\DemoBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class BaseTypeExtension
 */
class BaseTypeExtension extends AbstractTypeExtension
{
    /**
     * Return name of field type extanded
     *
     * @return string
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * Add option
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'form_group_class' => 'form-group',
            'row_class' => '',
            'label_raw' => false,
        ));
    }

    /**
     * Add view variables
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['form_group_class'] = $options['form_group_class'];
        $view->vars['row_class'] = $options['row_class'];
        $view->vars['label_raw'] = $options['label_raw'];
    }
}
