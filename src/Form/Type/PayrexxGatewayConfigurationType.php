<?php declare(strict_types=1);

namespace Comvation\SyliusPayrexxCheckoutPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Payrexx payment gateway configuration form
 *
 * Properties should be in sync with the respective GatewayFactory and API.
 */
final class PayrexxGatewayConfigurationType extends AbstractType
{
    /**
     * Configuration form
     *
     * Includes a key, and additional instance name and domain.
     * @author Reto Kohli <reto.kohli@comvation.com>
     */
    public function buildForm(
        FormBuilderInterface $builder, array $options
    ): void
    {
        $builder->add('instance', TextType::class);
        $builder->add('domain', TextType::class);
        $builder->add('api_key', TextType::class);
    }
}
