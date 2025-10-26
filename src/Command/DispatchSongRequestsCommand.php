<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\SongRequest;
use App\Entity\TelegramDispatch;
use App\Service\SongRequestFormatter;
use App\Service\TelegramNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:dispatch-song-requests', description: 'Отправить новые заявки в Telegram и отметить их как отправленные')]
class DispatchSongRequestsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SongRequestFormatter $formatter,
        private TelegramNotifier $notifier,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->entityManager->getRepository(SongRequest::class);

        $pendingRequests = $repository->createQueryBuilder('request')
            ->leftJoin('request.dispatches', 'dispatch')
            ->where('dispatch.id IS NULL')
            ->orderBy('request.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        if ($pendingRequests === []) {
            $io->success('Нет заявок для отправки.');

            return Command::SUCCESS;
        }

        $dispatched = 0;

        foreach ($pendingRequests as $request) {
            \assert($request instanceof SongRequest);

            $messageLines = $this->formatter->buildTelegramMessage($request);

            if (!$this->notifier->sendMessage($messageLines)) {
                $io->warning(sprintf('Не удалось отправить заявку #%d: %s', $request->getId(), $this->notifier->getLastError() ?? 'Причина неизвестна.'));

                continue;
            }

            $dispatch = new TelegramDispatch($request);
            $this->entityManager->persist($dispatch);
            $dispatched++;
        }

        if ($dispatched > 0) {
            $this->entityManager->flush();
        }

        if ($dispatched === 0) {
            $io->warning('Ни одна заявка не была отправлена.');

            return Command::FAILURE;
        }

        $io->success(sprintf('Отправлено %d заявок.', $dispatched));

        return Command::SUCCESS;
    }
}
