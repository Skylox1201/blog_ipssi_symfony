<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('content', HiddenType::class)
            ->add('category',  EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title',
            ])
            ->add('state', ChoiceType::class, [
                'choices' => [
                    'Save as draft' => 'draft',
                    'Publish the article' => 'public',
                ],
                'attr' => [
                    'class' => 'form-select mb-3',
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label' => 'Excerpt',
                'attr' => [
                    'placeholder' => 'Excerpt',
                    'class' => 'form-control',
                ],
            ])
            ->add('tags', TextareaType::class, [
                'label' => 'Tags (separated by comma)',
                'attr' => [
                    'placeholder' => 'Tags',
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
