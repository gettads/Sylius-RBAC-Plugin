<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Unit;

use PHPUnit\Framework\TestCase;
use Gtt\SyliusRbacPlugin\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class DependencyInjectionConfigurationTest extends TestCase
{
    /**
     * @test
     */
    public function I_check_get_config_tree_builder_positive(): void
    {
        $builder = (new Configuration())->getConfigTreeBuilder();
        $rootNode = $builder->getRootNode()->getNode(true);

        $this->assertEquals(Configuration::NODE_ROOT, $rootNode->getName());

        $data = [
            Configuration::NODE_CUSTOM_ROUTES => [
                [
                    Configuration::NODE_CUSTOM_ROUTE => 'test_route',
                    Configuration::NODE_CUSTOM_MATCH => 'test_match',
                    Configuration::NODE_CUSTOM_LABEL => 'test_label',
                ],
            ],
        ];
        $this->assertSame($data, $builder->buildTree()->normalize($data));
    }

    /**
     * @test
     *
     * @dataProvider getInvalidData
     */
    public function I_check_get_config_tree_builder_negative(string $rootKey, mixed $data, string $errorMessage): void
    {
        $exceptionMessage = '';

        if (is_array($data)) {
            $data = [...$data];
        }

        try {
            (new Configuration())->getConfigTreeBuilder()->buildTree()->normalize([$rootKey => [$data]]);
        } catch (InvalidConfigurationException $throwable) {
            $exceptionMessage = $throwable->getMessage();
        }

        $this->assertTrue(str_contains($exceptionMessage, $errorMessage));
    }

    private function getInvalidData(): array
    {
        return [
            'Bad key root' => [
                Configuration::NODE_CUSTOM_ROUTES . uniqid(),
                [
                    Configuration::NODE_CUSTOM_ROUTE => 'test_route',
                    Configuration::NODE_CUSTOM_MATCH => 'test_match',
                    Configuration::NODE_CUSTOM_LABEL => 'test_label',
                ],
                'Available option is "' . Configuration::NODE_CUSTOM_ROUTES . '"',
            ],
            'Bad keys inside of root' => [
                Configuration::NODE_CUSTOM_ROUTES,
                [Configuration::NODE_CUSTOM_ROUTE . uniqid() => 'test_route',],
                sprintf(
                    'Available options are "%s", "%s", "%s"',
                    Configuration::NODE_CUSTOM_LABEL,
                    Configuration::NODE_CUSTOM_MATCH,
                    Configuration::NODE_CUSTOM_ROUTE,
                ),
            ],
            'String type instead of array' => [
                Configuration::NODE_CUSTOM_ROUTES,
                'string',
                'Expected "array", but got',
            ],
            'Object type instead of array' => [
                Configuration::NODE_CUSTOM_ROUTES,
                new class {
                },
                'Expected "array", but got',
            ],
            'Non scalar value for label' => [
                Configuration::NODE_CUSTOM_ROUTES,
                [Configuration::NODE_CUSTOM_LABEL => []],
                'Expected "scalar", but got',
            ],
            'Non scalar value for match' => [
                Configuration::NODE_CUSTOM_ROUTES,
                [Configuration::NODE_CUSTOM_MATCH => new class {
                }],
                'Expected "scalar", but got',
            ],
            'Non scalar value for route' => [
                Configuration::NODE_CUSTOM_ROUTES,
                [Configuration::NODE_CUSTOM_ROUTE => fn () => 'closure'],
                'Expected "scalar", but got',
            ],
        ];
    }
}
