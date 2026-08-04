<?php

namespace App\Tests;

use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
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
        $suiteKey = static::class;
        if (isset(self::$initializedSuites[$suiteKey])) {
            return;
        }

        self::bootKernel();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $tool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        AccessAuditTestSeeder::seed($em, $passwordHasher);

        /** @var UnitOfMeasureRepositoryInterface $unitRepo */
        $unitRepo = $container->get(UnitOfMeasureRepositoryInterface::class);
        foreach ([['piece', 'Pièce', 0], ['kg', 'Kilogramme', 3], ['liter', 'Litre', 3], ['carton', 'Carton', 0]] as [$code, $label, $decimals]) {
            $unitRepo->save(new UnitOfMeasure($code, $label, $decimals));
        }

        if ($withFinanceSeed) {
            /** @var FinanceSeedService $financeSeed */
            $financeSeed = $container->get(FinanceSeedService::class);
            $financeSeed->seedIfEmpty();
        }

        self::$initializedSuites[$suiteKey] = true;
        self::ensureKernelShutdown();
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
            'HTTP_AUTHORIZATION' => 'Bearer '.$auth['token'],
            'CONTENT_TYPE' => 'application/json',
        ];
    }
}
