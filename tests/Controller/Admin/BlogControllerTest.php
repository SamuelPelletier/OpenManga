<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Entity\Manga;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the BlogController used
 * for managing the blog in the backend.
 *
 * See https://symfony.com/doc/current/book/testing.html#functional-tests
 *
 * Whenever you test resources protected by a firewall, consider using the
 * technique explained in:
 * https://symfony.com/doc/current/cookbook/testing/http_authentication.html
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends WebTestCase
{
    /**
     * @dataProvider getUrlsForRegularUsers
     */
    public function testAccessDeniedForRegularUsers(string $httpMethod, string $url)
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW' => 'kitten',
        ]);

        $client->request($httpMethod, $url);
        $this->assertSame(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function getUrlsForRegularUsers()
    {
        yield ['GET', '/en/admin/manga/'];
        yield ['GET', '/en/admin/manga/1'];
        yield ['GET', '/en/admin/manga/1/edit'];
        yield ['POST', '/en/admin/manga/1/delete'];
    }

    public function testAdminBackendHomePage()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);

        $crawler = $client->request('GET', '/en/admin/manga/');
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertGreaterThanOrEqual(
            1,
            $crawler->filter('body#admin_manga_index #main tbody tr')->count(),
            'The backend homepage displays all the available mangas.'
        );
    }

    /**
     * This test changes the database contents by creating a new blog manga. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminNewManga()
    {
        $mangaTitle = 'Blog Manga Title '.mt_rand();
        $mangaSummary = $this->generateRandomString(255);
        $mangaContent = $this->generateRandomString(1024);

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $crawler = $client->request('GET', '/en/admin/manga/new');
        $form = $crawler->selectButton('Create manga')->form([
            'manga[title]' => $mangaTitle,
            'manga[summary]' => $mangaSummary,
            'manga[content]' => $mangaContent,
        ]);
        $client->submit($form);

        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());

        $manga = $client->getContainer()->get('doctrine')->getRepository(Manga::class)->findOneBy([
            'title' => $mangaTitle,
        ]);
        $this->assertNotNull($manga);
        $this->assertSame($mangaSummary, $manga->getSummary());
        $this->assertSame($mangaContent, $manga->getContent());
    }

    public function testAdminShowManga()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request('GET', '/en/admin/manga/1');

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    /**
     * This test changes the database contents by editing a blog manga. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminEditManga()
    {
        $newBlogMangaTitle = 'Blog Manga Title '.mt_rand();

        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $crawler = $client->request('GET', '/en/admin/manga/1/edit');
        $form = $crawler->selectButton('Save changes')->form([
            'manga[title]' => $newBlogMangaTitle,
        ]);
        $client->submit($form);

        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());

        /** @var Manga $manga */
        $manga = $client->getContainer()->get('doctrine')->getRepository(Manga::class)->find(1);
        $this->assertSame($newBlogMangaTitle, $manga->getTitle());
    }

    /**
     * This test changes the database contents by deleting a blog manga. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminDeleteManga()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $crawler = $client->request('GET', '/en/admin/manga/1');
        $client->submit($crawler->filter('#delete-form')->form());

        $this->assertSame(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());

        $manga = $client->getContainer()->get('doctrine')->getRepository(Manga::class)->find(1);
        $this->assertNull($manga);
    }

    private function generateRandomString(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return mb_substr(str_shuffle(str_repeat($chars, ceil($length / mb_strlen($chars)))), 1, $length);
    }
}
