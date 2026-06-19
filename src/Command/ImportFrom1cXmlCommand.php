<?php

namespace App\Command;

use App\Services\OneCImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Импортирует ТОЛЬКО занятость (<Row>) из XML 1С.
 * Конструкции должны быть уже в БД (запусти сначала app:import:ads-from-1c-xml).
 *
 * Использование:
 *   php bin/console app:import:1c-occupancy
 *   php bin/console app:import:1c-occupancy /path/to/file.xml
 */
#[AsCommand(
    name: 'app:import:1c-occupancy',
    description: 'Импорт занятости (<Row>) из XML 1С в таблицу occupancy. Конструкции должны быть уже импортированы.',
)]
class ImportFrom1cXmlCommand extends Command
{
    public function __construct(private readonly OneCImportService $importer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'file',
            InputArgument::OPTIONAL,
            'Путь к XML-файлу 1С',
            dirname(__DIR__, 2) . '/MessageFor_ST0000000007.xml',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string)$input->getArgument('file');

        $io->title('Импорт занятости из 1С');
        $io->text(sprintf('Файл: <info>%s</info>', $file));
        $io->text('Конструкции должны быть уже в БД (app:import:ads-from-1c-xml).');
        $io->newLine();

        try {
            $stats = $this->importer->importOccupancyFromFile($file);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->table(
            ['Параметр', 'Значение'],
            [
                ['Блоков прочитано', $stats['blocks_read']],
                ['Блоков без строк занятости', $stats['blocks_without_rows']],
                ['Сторон не найдено в БД', $stats['sides_not_found']],
                ['Записей занятости сохранено', $stats['occupancy_upserted']],
                ['Записей занятости пропущено', $stats['occupancy_skipped']],
            ],
        );

        if ($stats['errors'] !== []) {
            $io->warning(sprintf('Ошибок: %d', count($stats['errors'])));
            foreach (array_slice($stats['errors'], 0, 20) as $err) {
                $io->text('  • ' . $err);
            }
        }

        if ($stats['sides_not_found'] > 0) {
            $io->note(sprintf(
                '%d сторон не найдено в БД. Убедись, что сначала запустил: php bin/console app:import:ads-from-1c-xml',
                $stats['sides_not_found'],
            ));
        }

        $io->success('Готово.');
        return Command::SUCCESS;
    }
}
