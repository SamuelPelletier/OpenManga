<?php
/**
 * Created by PhpStorm.
 * User: samue
 * Date: 19/05/2020
 * Time: 18:58
 */

namespace App\Service;


use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class UserService
{
    public function calculationUserPoints(UserInterface $user)
    {
        $timeSpentPoints = $user->getTimeSpent() / 100;
        $lastMangasReadPoints = $user->getCountMangasRead() * 10;
        $mangasDownloaded = $user->getCountMangasDownload() * 50;
        $bonusPoints = $user->getBonusPoints();
        return $timeSpentPoints + $lastMangasReadPoints + $mangasDownloaded + $bonusPoints;
    }
}