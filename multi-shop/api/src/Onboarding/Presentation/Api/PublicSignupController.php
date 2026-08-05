<?php

namespace App\Onboarding\Presentation\Api;

use App\Onboarding\Application\Service\ControlPlaneException;
use App\Onboarding\Application\Service\PublicSignupService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
final class PublicSignupController extends AbstractController
{
    public function __construct(
        private readonly PublicSignupService $publicSignupService,
    ) {
    }

    #[Route('/signup', name: 'api_public_signup', methods: ['POST'])]
    public function signup(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        foreach (['accountName', 'accountSlug'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => sprintf('Field "%s" is required.', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        if (empty($data['billingEmail']) && empty($data['adminEmail'])) {
            return $this->json(['error' => 'Field "adminEmail" is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['billingEmail'])) {
            $data['billingEmail'] = $data['adminEmail'];
        }

        if (empty($data['adminEmail'])) {
            $data['adminEmail'] = $data['billingEmail'];
        }

        if (!empty($data['adminPassword']) && strlen((string) $data['adminPassword']) < 8) {
            return $this->json(['error' => 'Admin password must be at least 8 characters.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->publicSignupService->signup($data);

            return $this->json($result, Response::HTTP_CREATED);
        } catch (ControlPlaneException $exception) {
            return $this->json(['error' => $exception->getMessage()], $exception->getStatusCode());
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}
