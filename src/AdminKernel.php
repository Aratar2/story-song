<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class AdminKernel extends Kernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $configDir = $this->getProjectDir() . '/config';

        $container->import($configDir . '/packages/*.yaml');
        $envPackages = $configDir . '/packages/' . $this->environment . '/*.yaml';
        if (\glob($envPackages)) {
            $container->import($envPackages);
        }

        $servicesFile = $configDir . '/services.yaml';
        if (\is_file($servicesFile)) {
            $container->import($servicesFile);
        }

        $envServicesFile = $configDir . '/services_' . $this->environment . '.yaml';
        if (\is_file($envServicesFile)) {
            $container->import($envServicesFile);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getProjectDir() . '/config';

        $routes->import($configDir . '/routes/*.yaml');
        $envRoutes = $configDir . '/routes/' . $this->environment . '/*.yaml';
        if (\glob($envRoutes)) {
            $routes->import($envRoutes);
        }

        $routesFile = $configDir . '/routes.yaml';
        if (\is_file($routesFile)) {
            $routes->import($routesFile);
        }
    }
}
