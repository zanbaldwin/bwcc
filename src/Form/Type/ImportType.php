<?php declare(strict_types=1);

namespace App\Form\Type;

use App\Model\FormResponse;
use App\Outputter\OutputterFactoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ImportType extends AbstractType
{
    /** @var \App\Outputter\OutputterFactoryInterface $factory */
    private $factory;

    public function __construct(OutputterFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('authCode', Type\TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank,
                    new Assert\Regex(['pattern' => '/^\d+$/'])
                ],
            ])
            ->add('format', Type\ChoiceType::class, [
                'required' => true,
                'choices' => $this->factory->getValidFormats(),
                'choice_label' => function ($value): string {
                    return $value;
                },
                'expanded' => true,
                'constraints' => [
                    new Assert\NotBlank,
                    new Assert\Choice(['choices' => $this->factory->getValidFormats()]),
                ],
            ])
            ->add('submit', Type\SubmitType::class, ['label' => 'Import'])
        ;
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FormResponse::class,
        ]);
        parent::configureOptions($resolver);
    }
}
