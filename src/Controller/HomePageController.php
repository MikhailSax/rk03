<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class HomePageController extends AbstractController
{
    private const ENABLE_HOME_ENHANCEMENTS = true;

    /**
     * Пропорции и пути к ассетам в public/. При смене макета обновите числа и имена файлов.
     *
     * @var list<array{
     *     id: string,
     *     aspect_ratio: string,
     *     image: string,
     *     webp: string|null,
     *     eager: bool,
     *     heading_id: string|null,
     *     heading_level: int|null,
     *     heading_text: string|null
     * }>
     */
    private const MOSAICS = [
        [
            'id' => 'about',
            'aspect_ratio' => '3005 / 2943',
            'image' => 'images/ooh-hero-collage.png',
            'webp' => 'images/ooh-hero-collage.webp',
            'eager' => true,
            'heading_id' => null,
            'heading_level' => null,
            'heading_text' => null,
        ],
        [
            'id' => 'ooh-stats',
            'aspect_ratio' => '3005 / 2465',
            'image' => 'images/home-stats-block.png',
            'webp' => 'images/home-stats-block.webp',
            'eager' => false,
            'heading_id' => 'ooh-stats-sr-title',
            'heading_level' => 2,
            'heading_text' => 'Подбор локаций: подбираем конструкции по ЦА, трафику и бюджету. Прозрачная отчётность: график размещений, фотофиксация и статусы бронирования. Скорость запуска: старт от двух рабочих дней. 1000 довольных клиентов. Сеть супермаркетов: плюс 28 процентов узнаваемости за 6 недель. Жилой комплекс: плюс 34 процента обращений. Дилерский центр: digital-показы в выходные.',
        ],
        [
            'id' => 'home-formats',
            'aspect_ratio' => '3000 / 2283',
            'image' => 'images/home-formats.jpg',
            'webp' => 'images/home-formats.webp',
            'eager' => false,
            'heading_id' => 'home-formats-sr-title',
            'heading_level' => 2,
            'heading_text' => 'Форматы размещения наружной рекламы',
        ],
    ];

    #[Route('/', name: 'app_homepage')]
    public function index(Request $request): Response
    {
        $publicDir = $this->getParameter('kernel.project_dir') . '/public';
        $mosaics = [];
        $preloadPath = null;
        $homeEnhancementsEnabled = self::ENABLE_HOME_ENHANCEMENTS && '1' !== (string) $request->query->get('home_plain', '0');

        foreach (self::MOSAICS as $row) {
            $hasWebp = null !== $row['webp'] && is_file($publicDir . '/' . $row['webp']);
            $entry = $row;
            $entry['has_webp'] = $hasWebp;
            unset($entry['webp']);
            $entry['webp_path'] = $hasWebp ? $row['webp'] : null;
            $mosaics[] = $entry;

            if ($row['eager'] && null === $preloadPath) {
                $preloadPath = $hasWebp ? $row['webp'] : $row['image'];
            }
        }

        $pageUrl = $this->generateUrl('app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $homeJsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $pageUrl.'#organization',
                    'name' => 'БМС',
                    'alternateName' => 'Бурятия Медиа Сервис',
                    'url' => $pageUrl,
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $pageUrl.'#webpage',
                    'url' => $pageUrl,
                    'name' => 'БМС — наружная реклама',
                    'description' => 'Размещение наружной рекламы: билборды, digital-экраны и конструкции. Планирование кампаний и отчётность.',
                    'publisher' => ['@id' => $pageUrl.'#organization'],
                    'about' => [
                        '@type' => 'Service',
                        'name' => 'Наружная реклама (OOH)',
                        'serviceType' => 'Размещение рекламы на билбордах и digital-экранах',
                        'areaServed' => ['@type' => 'Country', 'name' => 'Россия'],
                    ],
                ],
            ],
        ];

        return $this->render('home_page/index.html.twig', [
            'home_mosaics' => $mosaics,
            'home_preload_image' => $preloadPath,
            'home_json_ld' => $homeJsonLd,
            'home_enhancements_enabled' => $homeEnhancementsEnabled,
        ]);
    }
}
