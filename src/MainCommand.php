<?php
namespace SlackHistoryWordCount;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MainCommand extends Command
{
    protected static $defaultName = 'main';
    private $slackHistoryFetcherService;

    public function __construct(SlackHistoryFetcherService $slackHistoryFetcherService)
    {
        parent::__construct();
        $this->slackHistoryFetcherService = $slackHistoryFetcherService;
    }

    public function configure()
    {
        $this
            ->addArgument('channels',  InputArgument::REQUIRED, 'Comma separated channels to analyze')
            ->addArgument('words',  InputArgument::REQUIRED, 'Comma separated list of words to get count of')
            ->addOption('from-date', null,InputOption::VALUE_OPTIONAL, 'From date (latest)', 'today midnight')
            ->addOption('to-date', null,InputOption::VALUE_OPTIONAL, 'From date (earliest)', '3 months ago midnight')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $words = (array) explode(',', $input->getArgument('words'));
            $words = array_unique($words);
            $words = array_filter($words);
            $channels = (array) explode(',', $input->getArgument('channels'));
            $channels = array_unique($channels);
            $channels = array_filter($channels);

            if (empty($channels)) {
                throw new InvalidArgumentException("Must provide channel list");
            }
            if (empty($words)) {
                throw new InvalidArgumentException("Must provide words list");
            }

            $fromDate = new DateTimeImmutable($input->getOption('from-date'));
            $toDate   = new DateTimeImmutable($input->getOption('to-date'));

            $chatTextAggregatedByMonth = $this->getAllChatTextAggregatedByMonth($channels, $fromDate, $toDate);

            $table = new Table($output);
            $table->setHeaders(['Word', 'Month', 'Count']);
            foreach ($chatTextAggregatedByMonth as $month=>$fullMonthText) {
                $fullMonthText = strtolower($fullMonthText);
                foreach ($words as $word) {
                    $table->addRow([
                        $word,
                        $month,
                        substr_count($fullMonthText, $word),
                    ]);
                }
            }
            $table->render();
        } catch (\Throwable $throwable) {
            dump($throwable);
        }
    }

    private function getAllChatTextAggregatedByMonth(array $channels, DateTimeInterface $fromDate, DateTimeInterface $toDate): array {
        $cacheKey = md5(implode($channels).$fromDate->getTimestamp().$toDate->getTimestamp());
        $cacheFile = sprintf("/tmp/%s.cache", $cacheKey);

        if (file_exists($cacheFile)) {
            $AllChannelsChatMessages = json_decode(file_get_contents($cacheFile), true);
        } else {
            $AllChannelsChatMessages = [];
            foreach ($channels as $channel) {
                $AllChannelsChatMessages = array_merge_recursive(
                    $AllChannelsChatMessages,
                    $this->slackHistoryFetcherService->getMessages($channel, $fromDate, $toDate)
                );
            }
            file_put_contents($cacheFile, json_encode($AllChannelsChatMessages));
        }

        $AllChatMessageByMonth = [];
        foreach ($AllChannelsChatMessages as $day => $chatMessage) {
            $dayObj = new DateTimeImmutable($day);
            $index = $dayObj->format('M Y');
            if (empty($AllChatMessageByMonth[$index])) {
                $AllChatMessageByMonth[$index] = '';
            }
            $AllChatMessageByMonth[$index] .= "\r\n" . implode("\r\n", $chatMessage);
        }

        return $AllChatMessageByMonth;
    }
}