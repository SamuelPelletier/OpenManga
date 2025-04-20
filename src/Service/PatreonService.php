<?php
/**
 * Created by PhpStorm.
 * User: samue
 * Date: 19/05/2020
 * Time: 18:58
 */

namespace App\Service;


use App\Entity\User;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Patreon\API;
use Patreon\OAuth;

class PatreonService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getPatreonMembership(User $user): ?array
    {
        $patreonResponse = $this->getData($user, 'identity?include=memberships&fields' . urlencode('[member]') . '=next_charge_date,will_pay_amount_cents,patron_status');
        if ($patreonResponse) {
            foreach ($patreonResponse->included as $included) {
                if ($included->type === 'member' && $included->attributes->patron_status === 'active_patron') {
                    $carbonDate = Carbon::parse($included->attributes->next_charge_date);
                    return [$carbonDate->toDateTime(), $included->attributes->will_pay_amount_cents];
                }
            }
        }
        return null;
    }

    public function updateUserFromPatreon(User $user)
    {
        if ([$nextChargeDate, $tier] = $this->getPatreonMembership($user)) {
            $user->setPatreonNextCharge($nextChargeDate);
            // todo change when new tier coming
            $user->setPatreonTier(1);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    private function getData(User $user, string $suffix): string|object|null
    {
        $apiClient = new API($user->getPatreonAccessToken());
        $apiClient::$request_cache = [];
        $apiClient->api_return_format = 'object';
        $result = $apiClient->get_data($suffix);
        if (is_string($result) && isset(json_decode($result, true)['errors'])) {
            $oauth_client = new OAuth($_ENV['PATREON_CLIENT_ID'], $_ENV['PATREON_CLIENT_SECRET']);
            $tokens = $oauth_client->refresh_token($user->getPatreonRefreshToken(), null);
            if (isset($tokens['access_token'])) {
                $user->setPatreonAccessToken($tokens['access_token']);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $this->getData($user, $suffix);
            }
            return null;
        }
        return $result;
    }
}
