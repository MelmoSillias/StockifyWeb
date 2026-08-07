<?php

namespace App\Tests;

use App\Finance\Application\Service\FinanceSeedService;
use App\Tests\Support\AccessAuditTestSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    /** @var array<string, true> */
    private static array $initializedSuites = [];

    protected function initializeTestSchema(bool $withFinanceSeed = false): void
    {
        $suiteKey = static::class.($withFinanceSeed ? ':finance' : '');
        if (isset(self::$initializedSuites[$suiteKey])) {
            return;
        }

        self::bootKernel();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $connection = $em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $schemaManager = $connection->createSchemaManager();
        foreach ($schemaManager->listTableNames() as $tableName) {
            $connection->executeStatement(sprintf('DROP TABLE IF EXISTS `%s`', $tableName));
        }
        $tool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool->createSchema($metadata);
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        AccessAuditTestSeeder::seed($em, $passwordHasher);

        if ($withFinanceSeed) {
            /** @var FinanceSeedService $financeSeed */
            $financeSeed = $container->get(FinanceSeedService::class);
            $financeSeed->seedIfEmpty();
        }

        self::$initializedSuites[$suiteKey] = true;
        self::ensureKernelShutdown();
    }

    /** @param array<string, mixed> $auth */
    protected static function extractAccessToken(array $auth): string
    {
        return (string) ($auth['access_token'] ?? $auth['token'] ?? '');
    }

    /** @return array<string, string> */
    protected function authenticateAdmin(KernelBrowser $client): array
    {
        $client->request('POST', '/api/login_check', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'admin-test@stockify.local',
            'password' => 'password123',
        ]));
        self::assertResponseIsSuccessful();
        $auth = json_decode($client->getResponse()->getContent(), true);

        return [
            'HTTP_AUTHORIZATION' => 'Bearer '.self::extractAccessToken($auth),
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
