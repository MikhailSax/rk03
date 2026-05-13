<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class SiteSectionController extends AbstractController
{
    /** Порядок пунктов в шапке (остальные разделы — в подвале) */
    private const HEADER_SECTION_ORDER = [
        'Markets',
        'Media',
        'Resources',
        'Ad Tech',
        'Creative',
        'About',
        'Careers',
    ];

    /** Полный порядок для карты сайта / подвала */
    private const NAV_SECTION_ORDER = [
        'Markets',
        'Media',
        'Resources',
        'Ad Tech',
        'Creative',
        'About',
        'Careers',
        'Contact',
        'Investors',
        'News',
        'Legal',
    ];

    /** Заголовки разделов в шапке */
    private const SECTION_HEADINGS = [
        'Markets' => 'Рынки',
        'Media' => 'Медиа',
        'Resources' => 'Ресурсы',
        'Ad Tech' => 'Ad Tech',
        'Creative' => 'Креатив',
        'About' => 'О компании',
        'Careers' => 'Карьера',
        'Contact' => 'Контакты',
        'Investors' => 'Инвесторам',
        'News' => 'Пресс-центр',
        'Legal' => 'Правовая информация',
    ];

    /**
     * Структура разделов по аналогии с корпоративным OOH-сайтом (заглушки контента — замените позже).
     *
     * @var array<string, array{title: string, section: string}>
     */
    private const PAGES = [
        'formats' => ['title' => 'Форматы размещения', 'section' => 'Resources'],
        'markets' => ['title' => 'Рынки и география', 'section' => 'Markets'],

        'media/billboards' => ['title' => 'Билборды', 'section' => 'Media'],
        'media/transit' => ['title' => 'Транзитная реклама', 'section' => 'Media'],
        'media/street-furniture' => ['title' => 'Ситиборды и уличная мебель', 'section' => 'Media'],
        'media/mta-network' => ['title' => 'Транспортные сети', 'section' => 'Media'],
        'media/times-square' => ['title' => 'Премьерные локации', 'section' => 'Media'],
        'media/place-based' => ['title' => 'Place-based', 'section' => 'Media'],
        'media/social-ooh' => ['title' => 'Social OOH', 'section' => 'Media'],
        'media/mobile' => ['title' => 'Мобильная связка с OOH', 'section' => 'Media'],

        'resources/blog' => ['title' => 'Блог', 'section' => 'Resources'],
        'resources/video-series' => ['title' => 'Видео-серии', 'section' => 'Resources'],
        'resources/media-preview' => ['title' => 'Предпросмотр медиа', 'section' => 'Resources'],
        'resources/media-kits' => ['title' => 'Медиакиты', 'section' => 'Resources'],
        'resources/case-studies' => ['title' => 'Кейсы', 'section' => 'Resources'],
        'resources/rates' => ['title' => 'Тарифы и ставки', 'section' => 'Resources'],
        'resources/specs' => ['title' => 'Технические спецификации', 'section' => 'Resources'],
        'resources/creative-best-practices' => ['title' => 'Креатив: лучшие практики', 'section' => 'Resources'],

        'ad-tech/automation' => ['title' => 'Автоматизация закупки', 'section' => 'Ad Tech'],
        'ad-tech/programmatic' => ['title' => 'Программатик', 'section' => 'Ad Tech'],
        'ad-tech/ad-server' => ['title' => 'Ad server / прямая цифровая подача', 'section' => 'Ad Tech'],
        'ad-tech/measurement' => ['title' => 'Измерения и отчётность', 'section' => 'Ad Tech'],
        'ad-tech/audience-insights' => ['title' => 'Аудитории и инсайты', 'section' => 'Ad Tech'],
        'ad-tech/outcomes' => ['title' => 'Outcomes measurement', 'section' => 'Ad Tech'],

        'creative/studios' => ['title' => 'Креативные студии', 'section' => 'Creative'],
        'creative/xlabs' => ['title' => 'Инновационная лаборатория', 'section' => 'Creative'],
        'creative/moments' => ['title' => 'Спецпроекты и моменты', 'section' => 'Creative'],
        'creative/advanced' => ['title' => 'Расширенные возможности', 'section' => 'Creative'],

        'about' => ['title' => 'О компании', 'section' => 'About'],
        'about/leadership' => ['title' => 'Команда и руководство', 'section' => 'About'],
        'about/partners' => ['title' => 'Партнёры', 'section' => 'About'],
        'about/esg' => ['title' => 'ESG и устойчивое развитие', 'section' => 'About'],
        'about/investors' => ['title' => 'Инвесторам', 'section' => 'About'],
        'about/real-estate' => ['title' => 'Недвижимость и площадки', 'section' => 'About'],

        'careers' => ['title' => 'Карьера', 'section' => 'Careers'],
        'contact' => ['title' => 'Контакты', 'section' => 'Contact'],

        'investors/stock' => ['title' => 'Акции и котировки', 'section' => 'Investors'],
        'investors/financials' => ['title' => 'Финансовая отчётность', 'section' => 'Investors'],
        'investors/governance' => ['title' => 'Корпоративное управление', 'section' => 'Investors'],

        'newsroom' => ['title' => 'Пресс-центр', 'section' => 'News'],
        'newsletter' => ['title' => 'Подписка на рассылку', 'section' => 'News'],

        'legal/privacy' => ['title' => 'Политика конфиденциальности', 'section' => 'Legal'],
        'legal/cookies' => ['title' => 'Политика cookie', 'section' => 'Legal'],
        'legal/terms' => ['title' => 'Условия использования', 'section' => 'Legal'],
        'legal/posting' => ['title' => 'Правила размещения контента', 'section' => 'Legal'],
    ];

    #[Route('/site/{path}', name: 'app_site_page', requirements: ['path' => '.+'], methods: ['GET'])]
    public function page(string $path): Response
    {
        if (!isset(self::PAGES[$path])) {
            throw new NotFoundHttpException(sprintf('Страница «%s» не найдена.', $path));
        }

        $meta = self::PAGES[$path];

        if ($path === 'formats') {
            return $this->render('site/formats.html.twig', [
                'page_title' => $meta['title'],
            ]);
        }

        if ($path === 'contact') {
            return $this->render('site/contact.html.twig', [
                'page_title' => $meta['title'],
            ]);
        }

        if ($path === 'about') {
            return $this->render('site/about.html.twig', [
                'page_title' => $meta['title'],
                'related_pages' => self::relatedPages($path),
            ]);
        }

        return $this->render('site/page.html.twig', [
            'path' => $path,
            'page_title' => $meta['title'],
            'section_label' => $meta['section'],
            'related_pages' => self::relatedPages($path),
        ]);
    }

    /**
     * @return array<string, array{label: string, items: list<array{path: string, label: string}>}>
     */
    public static function headerNavTree(): array
    {
        $full = self::navTree();
        $out = [];
        foreach (self::HEADER_SECTION_ORDER as $key) {
            if (isset($full[$key])) {
                $out[$key] = $full[$key];
            }
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, items: list<array{path: string, label: string}>}>
     */
    public static function navTree(): array
    {
        $bySection = [];
        foreach (self::PAGES as $p => $meta) {
            // Пункт "Форматы" вынесен в верхнюю навигацию отдельно (см. templates/components/header.html.twig),
            // поэтому не дублируем его в выпадающих секциях.
            if ($p === 'formats') {
                continue;
            }
            $section = $meta['section'];
            if (!isset($bySection[$section])) {
                $bySection[$section] = [
                    'label' => self::SECTION_HEADINGS[$section] ?? $section,
                    'items' => [],
                ];
            }
            $bySection[$section]['items'][] = ['path' => $p, 'label' => $meta['title']];
        }

        $ordered = [];
        foreach (self::NAV_SECTION_ORDER as $key) {
            if (isset($bySection[$key])) {
                $ordered[$key] = $bySection[$key];
                unset($bySection[$key]);
            }
        }

        return $ordered + $bySection;
    }

    /**
     * @return list<array{path: string, label: string}>
     */
    private static function relatedPages(string $current): array
    {
        $section = self::PAGES[$current]['section'] ?? '';
        $out = [];
        foreach (self::PAGES as $p => $meta) {
            if ($p !== $current && ($meta['section'] ?? '') === $section) {
                $out[] = ['path' => $p, 'label' => $meta['title']];
            }
        }

        return array_slice($out, 0, 12);
    }
}
