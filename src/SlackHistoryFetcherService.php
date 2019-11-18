<?php
namespace SlackHistoryWordCount;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Symfony\Component\HttpClient\HttpClient;

class SlackHistoryFetcherService
{
    private $slackApiToken;

    public function __construct(string $slackApiToken) {
        $this->slackApiToken = $slackApiToken;
    }
    
    public function getMessages(string $channel, DateTimeInterface $fromTime, DateTimeInterface $toTime) : array
    {
        $chatMessages = [];
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            'https://slack.com/api/channels.history',
            [
                'query' => [
                    'token'   => $this->slackApiToken,
                    'channel' => $channel,
                    'count'   => 1000,
                    'latest'  => $fromTime->getTimestamp(),
                ],
            ],
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200)
        {
            throw new Exception(sprintf("Slack replied with non-200 [%s]", $statusCode));
        }
        $content = $response->toArray();
        if (!$content['ok']) {
            throw new Exception(sprintf("Slack replied with false 'ok' error[%s]", $content['error']));
        }

        $messages = (array) $content['messages'];
        foreach ($messages as $message) {
            $index = date('D M j Y', $message['ts']);
            $chatMessages[$index][] = $message['text'];
        }

        $oldestTs = array_reduce($messages, function ($carry, $message){
            if (!$carry or $carry > $message['ts']) {
                $carry = $message['ts'];
            }
            return $carry;
        });
        $lastFetchedTimeStamp = new DateTimeImmutable('@'.intval($oldestTs));
        $lastFetchedTimeStamp->setTimestamp($oldestTs);
        if ($content['has_more'] && $lastFetchedTimeStamp->getTimestamp() > $toTime->getTimestamp()) {
            $chatMessages = array_merge_recursive(
                $chatMessages,
                $this->getMessages($channel, $lastFetchedTimeStamp, $toTime)
            );
        }
        return $chatMessages;
    }
}
